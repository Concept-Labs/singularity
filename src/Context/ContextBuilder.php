<?php

namespace Concept\Singularity\Context;

use Concept\Config\ConfigInterface;
use Concept\Singularity\Config\ConfigNodeInterface;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\SingularityInterface;
use Psr\SimpleCache\CacheInterface;

class ContextBuilder implements ContextBuilderInterface
{

    private array $configData;

    /**
     * ContextBuilder constructor
     * 
     * @param SingularityInterface $container
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly SingularityInterface $container,
        private CacheInterface $cache
        )
    {
    }

    /**
     * Get the configuration
     * 
     * @return ConfigInterface
     */
    protected function getConfig(): ConfigInterface
    {
        return $this->getContainer()->getConfig();
    }

    /**
     * Get the configuration data
     * 
     * @return array
     */
    protected function &getDataReference(): array
    {
        $data = $this->getConfig()->dataReference()[ConfigNodeInterface::NODE_SINGULARITY];
        if (!is_array($data)) {
            throw new RuntimeException('Invalid configuration data');
        }
        return $data;
    }

    /**
     * Get the cache
     * 
     * @return CacheInterface
     */
    protected function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * Get the context instance
     * 
     * @return ProtoContextInterface
     */
    protected function getContextInstance(): ProtoContextInterface
    {
        /** 
            @todo: use prototype?
        */
        return new ProtoContext($this->getContainer());
    }

    /**
     * Inflate the context
     * 
     * @param string $serviceId
     * @param array $data
     * @param array $dependencyStack
     * 
     * @return ProtoContextInterface
     */
    protected function inflateContext(string $serviceId, array $data, array $dependencyStack): ProtoContextInterface
    {
        return $this->getContextInstance()
            ->inflate([
                ConfigNodeInterface::NODE_SERVICE_ID => $serviceId,
                ConfigNodeInterface::NODE_DEPENDENCY_STACK => $dependencyStack,
                ConfigNodeInterface::NODE_PREFERENCE => $data
            ]);
    }

    /**
     * Build the context
     * 
     * @param string $serviceId
     * @param array $dependencyStack
     * @param array $overrides
     * 
     * @return ProtoContextInterface
     */
    public function build(string $serviceId, array $dependencyStack = [], array $overrides = []): ProtoContextInterface
    {
        /**
           @todo: test cerefully
         */
        $this->configData ??= $this->getDataReference();

        /**
         * Generate a cache key for the current context
         * if cache is enabled
         */
        $cacheKey = $this->getCache() ? $this->generateCacheKey($serviceId, $dependencyStack) : null;

        if ($this->getCache()?->has($cacheKey) && empty($overrides)) {
            return 
                $this->inflateContext(
                    $serviceId,
                    $this->getCache()->get($cacheKey), 
                    $dependencyStack
                );
        } 
        
        /**
         * Add the current service to the dependency stack
         */
        $dependencyStack[] = $serviceId;

        /**
         * Build the preferences based on the dependency stack
         * and the configuration data
         */
        $preferences = $this->buildPreferences($dependencyStack);
        
        /**
         * If there are overrides, we will merge them with the preferences
         * to ensure that the context is built with the correct data.
         */
        if (!empty($overrides)) {
            $this->mergePreferences($preferences, $overrides);
        }
        
        /**
         * If the service is not resolved, we will use the default preference
         * which is 'unresolved' and will be used to create the service later.
         */
        $servicePreference = $preferences[$serviceId] ?? ['unresolved' => true];

        /**
            @todo: is it correct or keep the service ID? 
            We are adding the resolved class to the dependency stack instead of the service ID.
            This is to ensure that the context can resolve the next service correctly.
            If the service class is not resolved, dependency stack will not be changed. 
         */
        if (isset($servicePreference[ConfigNodeInterface::NODE_CLASS])) {
            // Add the resolved class to the dependency stack
            if (!in_array($servicePreference[ConfigNodeInterface::NODE_CLASS], $dependencyStack)) {
                array_pop($dependencyStack);
                $dependencyStack[] = $servicePreference[ConfigNodeInterface::NODE_CLASS];
            }
        }

        /**
         * Cache the service preference if cache is enabled
         */
        $this->getCache()?->set($cacheKey, $servicePreference);

        return $this->inflateContext($serviceId, $servicePreference, $dependencyStack);
    }

    /**
     * Build the preferences
     * 
     * @param array $dependencyStack
     * 
     * @return array
     */
    private function buildPreferences(array $dependencyStack): array
    {
        $preferences = [];
        $processedPackages = [];

        foreach ($dependencyStack as $id) {
            /**
             * Retrieve namespaces for the service ID and process their configurations
             */
            $namespaces = $this->getNamespacesForId($id);
            foreach ($namespaces as $namespace) {
                if (!isset($this->configData[ConfigNodeInterface::NODE_NAMESPACE][$namespace])) {
                    continue;
                }

                /**
                 * Get the dependencies (required packages) for the namespace and process them
                 */
                $requires = &$this->configData[ConfigNodeInterface::NODE_NAMESPACE][$namespace][ConfigNodeInterface::NODE_REQUIRE] ?? [];
                foreach ($requires as $pack => $packData) {
                    if (!isset($processedPackages[$pack])) {
                        $this->processPackage($pack, $preferences, $processedPackages);
                    }
                }

                /**
                 * if namespace has preferences, merge them into the top level preferences
                 */
                if (isset($this->configData[ConfigNodeInterface::NODE_NAMESPACE][$namespace][ConfigNodeInterface::NODE_PREFERENCE])) {
                    $this->mergePreferences(
                        $preferences, 
                        $this->configData
                            [ConfigNodeInterface::NODE_NAMESPACE]
                                [$namespace]
                                    [ConfigNodeInterface::NODE_PREFERENCE]
                    );
                }
            }

            /**
             @todo:
              if preference config contains sub-singularity configuration node, merge it into the main configuration data
              to allow nested configuration for services
             */

            /**
             * If the service ID has preferences, merge them into the top level preferences
             */
            if (isset($this->configData[ConfigNodeInterface::NODE_PREFERENCE][$id])) {
                $this->mergePreferences($preferences, [$id => $this->configData[ConfigNodeInterface::NODE_PREFERENCE][$id]]);
            }
        }

        return $preferences;
    }

    /**
     * Process the package
     * 
     * @param string $pack
     * @param array $preferences
     * @param array $processedPackages
     */
    private function processPackage(string $pack, array &$preferences, array &$processedPackages): void
    {
        if (!isset($this->configData[ConfigNodeInterface::NODE_PACKAGE][$pack])) {
            return;
        }

        $packData = $this->configData[ConfigNodeInterface::NODE_PACKAGE][$pack];
        /**
         * Remember that we have processed this package
         */
        $processedPackages[$pack] = true;
        /**
         * Resolve the package dependencies (required packages) recursively
         *  
         */
        if (isset($packData[ConfigNodeInterface::NODE_REQUIRE])) {
            foreach ($packData[ConfigNodeInterface::NODE_REQUIRE] as $depPack => $depData) {
                if (!isset($processedPackages[$depPack])) { // Check if the package is already processed
                    $this->processPackage($depPack, $preferences, $processedPackages);
                }
            }
        }

        /**
         * If the package has preferences, merge them into the top level preferences
         */
        if (isset($packData[ConfigNodeInterface::NODE_PREFERENCE])) {
            $this->mergePreferences($preferences, $packData[ConfigNodeInterface::NODE_PREFERENCE]);
        }
    }

    /**
     * Merge the preferences recursively
     * 
     * @param array $target
     * @param array $source
     */
    private function mergePreferences(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if (is_array($value) && isset($target[$key]) && is_array($target[$key])) {
                $this->mergePreferences($target[$key], $value);
            } else {
                $target[$key] = $value;
            }
        }
    }

    /**
     * Get the namespaces for the service id
     * 
     * @param string $serviceId
     * 
     * @return array
     */
    private function getNamespacesForId(string $serviceId): array
    {
        $parts = explode('\\', $serviceId);
        $parts = array_filter($parts);
        $namespace = '';
        $namespaces = [];
        foreach ($parts as $part) {
            $namespace .= $part . '\\';
            if (isset($this->configData[ConfigNodeInterface::NODE_NAMESPACE][$namespace])) {
                $namespaces[] = $namespace;
            }
        }
        return $namespaces;
    }

    /**
     * Generate the cache key
     * 
     * @param string $serviceId
     * @param array $dependencyStack
     * 
     * @return string
     */
    private function generateCacheKey(string $serviceId, array $dependencyStack): string
    {
        return '' . implode('=>', $dependencyStack) . '=>' . $serviceId;
    }

    /**
     * Get the container
     * 
     * @return SingularityInterface
     */
    protected function getContainer(): SingularityInterface
    {
        return $this->container;
    }
}