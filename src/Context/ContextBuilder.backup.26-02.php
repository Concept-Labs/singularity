<?php

namespace Concept\Singularity\Context;

use Concept\Config\Traits\ConfigurableTrait;
use Concept\Singularity\Config\ConfigNodeInterface;
use Concept\Singularity\SingularityInterface;

class ContextBuilder implements ContextBuilderInterface
{
    use ConfigurableTrait;
    //private array 
    private array $runtimeData = [];
    private array $preferenceCache = [];
    private static ?ProtoContextInterface $contextPrototype = null;

    public function __construct(private readonly SingularityInterface $container)
    {
    }

    /**
     * Get context instance
     * 
     * @return ProtoContextInterface
     */
    protected function getContextInstance(): ProtoContextInterface
    {
        return  new ProtoContext($this->getContainer());
    }


    /**
    * Inflate context with data
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
            ->inflate(
                [
                    ConfigNodeInterface::NODE_SERVICE_ID => $serviceId,
                    ConfigNodeInterface::NODE_DEPENDENCY_STACK => $dependencyStack,
                    ConfigNodeInterface::NODE_PREFERENCE => $data
                ]
            );
    }

    /**
     * {@inheritDoc}
     */
    public function build(string $serviceId, array $dependencyStack = [], array $overrides = []): ProtoContextInterface
    {
        $cacheKey = $serviceId . ':' . md5(serialize($dependencyStack));
        if (isset($this->preferenceCache[$cacheKey]) && empty($overrides)) {
            $preferences = $this->preferenceCache[$cacheKey];
        } else {
            $this->runtimeData = $this->getConfig()->asArray();
            $preferences = [];
            $dependencyStack[] = $serviceId;
            foreach ($dependencyStack as $serviceId) {
                $packages = [];
                $namespaceConfig = [];
                
                foreach ($this->getConfigurableNamespaces($serviceId) as $namespace) {
                    if (is_array($this->runtimeData['namespace'][$namespace]['depends'])) {
                        $packages = array_replace_recursive(
                            $packages,
                            $this->runtimeData['namespace'][$namespace]['depends']
                        );
                    }
                    if (is_array($this->runtimeData['namespace'][$namespace])) {
                        $namespaceConfig = array_replace_recursive(
                            $namespaceConfig, 
                            $this->runtimeData['namespace'][$namespace]
                        );
                    }
                    unset($this->runtimeData['namespace'][$namespace]);
                }
                $packagesConfig = $this->bubblePakagesData($packages);
                $aggregateConfig = array_replace_recursive($packagesConfig, $namespaceConfig);
                $preferences = array_replace_recursive(
                    $preferences,
                    $this->bubblePreference($aggregateConfig['preference'] ?? [])
                );
            }
            $preferences = array_replace_recursive($preferences, $this->runtimeData['preference'] ?? []);
            $preferences = array_replace_recursive($preferences, $overrides);
            $this->preferenceCache[$cacheKey] = $preferences; // Зберігаємо в кеш
        }
        $servicePreference = $preferences[$serviceId] ?? ['unresolved' => true];
        return $this->inflateContext($serviceId, $servicePreference, $dependencyStack);
    }

    protected function getConfigurableNamespaces(string $serviceId): array
    {
        /**
         * @todo: cache namespaces
         */
        if (!isset($this->runtimeData['namespace'])) {
            /**
             * No namespaces configured
             * throw exception ?
             */
            return [];
        }

        $parts = explode('\\', $serviceId);
        $namespace = '';
        $namespaces = [];
        foreach ($parts as $part) {
            $namespace .= $part . '\\';
            if (!isset($this->runtimeData['namespace'][$namespace])) {
                continue;
            }
            $namespaces[] = $namespace;
        }
        return $namespaces;
    }

    

    protected function bubblePakagesData(array $packages): array
    {
        if (!is_array($this->runtimeData['package'])) {
            /**
             * No packages configured
             * throw exception ?
             */
            return [];
        }

        $unpacked = [];
        foreach ($packages as $pack => $packData) {

            if (empty($this->runtimeData['package'][$pack]) ?? []) {
                /**
                 * No package data found
                 * it could be removed on previous iteration
                 * to avoid double processing and circular reference
                 */
                continue;
            }
            $packData = array_replace_recursive(
                $packData, 
                $this->runtimeData['package'][$pack] ?? []
            );

            /**
             * Avoid double processing
             */
            unset($this->runtimeData['package'][$pack]);

            if (isset($packData['depends']) && is_array($packData['depends'])) {
                $packData = array_replace_recursive(
                    $this->bubblePakagesData($packData['depends']), 
                    $packData
                );
                unset($packData['depends']);
            }
            $unpacked = array_replace_recursive($unpacked, $packData);
        }
         
        return $unpacked;

    }

    protected function bubblePreference(array $prefs): array
    {
        $unpacked = [];
        foreach ($prefs as $pref => $prefData) {
            if (isset($prefData['preference']) && is_array($prefData['preference'])) {
                $unpacked = array_replace_recursive($unpacked, $this->bubblePreference($prefData['preference']));
                unset($prefData['preference']);
            }
            $unpacked = array_replace_recursive([$pref => $prefData], $unpacked);
        }

        return $unpacked;
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