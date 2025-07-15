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
     * @param ConfigInterface $config
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly SingularityInterface $container,
        private ConfigInterface $config,
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
        return $this->config;
    }

    /**
     * Get the configuration data
     * 
     * @return array
     */
    protected function &getDataRef(): array
    {
        $data = $this->getConfig()->asArrayRef()[ConfigNodeInterface::NODE_SINGULARITY];
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
        $this->configData ??= $this->getDataRef();

        $cacheKey = $this->generateCacheKey($serviceId, $dependencyStack);
        /**
         * Add the current service to the dependency stack
         */
        
        if ($this->getCache()->has($cacheKey) && empty($overrides)) {
            return 
                $this->inflateContext(
                    $serviceId,
                    $this->getCache()->get($cacheKey), 
                    $dependencyStack
                );
        } 
        
        $dependencyStack[] = $serviceId;
        $preferences = $this->buildPreferences($dependencyStack);
        
        if (!empty($overrides)) {
            $this->mergePreferences($preferences, $overrides);
        }
        
        $servicePreference = $preferences[$serviceId] ?? ['unresolved' => true];
        $this->getCache()->set($cacheKey, $servicePreference);

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
            $namespaces = $this->getNamespacesForId($id);
            foreach ($namespaces as $namespace) {
                if (!isset($this->configData['namespace'][$namespace])) {
                    continue;
                }

                if (isset($this->configData['namespace'][$namespace][ConfigNodeInterface::NODE_REQUIRE])) {
                    foreach ($this->configData['namespace'][$namespace][ConfigNodeInterface::NODE_REQUIRE] as $pack => $packData) {
                        if (!isset($processedPackages[$pack])) {
                            $this->processPackage($pack, $preferences, $processedPackages);
                        }
                    }
                }

                if (isset($this->configData['namespace'][$namespace]['preference'])) {
                    $this->mergePreferences($preferences, $this->configData['namespace'][$namespace]['preference']);
                }
            }

            if (isset($this->configData['preference'][$id])) {
                $this->mergePreferences($preferences, [$id => $this->configData['preference'][$id]]);
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

        $packData = $this->configData['package'][$pack];
        $processedPackages[$pack] = true;

        if (isset($packData[ConfigNodeInterface::NODE_REQUIRE])) {
            foreach ($packData[ConfigNodeInterface::NODE_REQUIRE] as $depPack => $depData) {
                if (!isset($processedPackages[$depPack])) {
                    $this->processPackage($depPack, $preferences, $processedPackages);
                }
            }
        }

        if (isset($packData['preference'])) {
            $this->mergePreferences($preferences, $packData['preference']);
        }
    }

    /**
     * Merge the preferences
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
        // Обчислюємо локально без кешу Memcached
        $parts = explode('\\', $serviceId);
        $namespace = '';
        $namespaces = [];
        foreach ($parts as $part) {
            $namespace .= $part . '\\';
            if (isset($this->configData['namespace'][$namespace])) {
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
        return 'pref_' . implode('_', $dependencyStack) . '_' . $serviceId;
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