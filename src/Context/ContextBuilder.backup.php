<?php

namespace Concept\Singularity\Context;

use Concept\Config\ConfigurableInterface;
use Concept\Config\Traits\ConfigurableTrait;
use Concept\Singularity\Config\ConfigNodeInterface;
use Concept\Singularity\SingularityInterface;

class ContextBuilder implements ContextBuilderInterface, ConfigurableInterface
{
    use ConfigurableTrait;
    //private array 
    private array $runtimeData = [];
    private static ?ProtoContextInterface $contextPrototype = null;

    public function __construct(private readonly SingularityInterface $container)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function build(string $serviceId, array $dependencyStack = [], array $overrides = []): ProtoContextInterface
    {
        $this->runtimeData = $this->getConfig()->asArray();
        
        $preferences = [];
        
        $dependencyStack[] = $serviceId;

        foreach ($dependencyStack as $serviceId) {
            $packs = [];
            $prefs = [];
            
            foreach ($this->aggregateNamespace($serviceId) as $namespace) {
            
                if (is_array($this->runtimeData['namespace'][$namespace]['depends'])) {
                    $packs = array_merge_recursive(
                        $packs,
                        array_splice($this->runtimeData['namespace'][$namespace]['depends'], 0)
                    );
                    unset($this->runtimeData['namespace'][$namespace]['depends']);
                }

                if (is_array($this->runtimeData['namespace'][$namespace])) {
                    $prefs = array_merge_recursive(
                        $prefs, 
                        array_splice($this->runtimeData['namespace'][$namespace], 0)
                    );
                    unset($this->runtimeData['namespace'][$namespace]);
                }

                // foreach($packs as $pack => $packData) {
                //     if (is_array($this->runtimeData['package'][$pack])) {
                //         $packs = array_merge_recursive(
                //             $packs,
                //             [$pack => array_splice($this->runtimeData['package'][$pack], 0)]
                //         );
                //     }
                //     unset($this->runtimeData['package'][$pack]);
                // }

                unset($this->runtimeData['namespace'][$namespace]);
                
            }
            $pckPrefs = $this->unpacks($packs);
            $prefs = array_replace_recursive($pckPrefs, $prefs);
            $preferences = array_replace_recursive($preferences, $this->bubblePreference($prefs['preference']??[]));
        }

        $preferences = array_replace_recursive($preferences, $this->runtimeData['preference'] ?? []);
        $preferences = array_replace_recursive($preferences, $overrides);


        return $this->inflateContext($serviceId, $preferences[$serviceId] ?? [], $dependencyStack);
            
    }

    protected function aggregateNamespace(string $serviceId): array
    {
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

    

    protected function unpacks(array $packs): array
    {
        $unpacked = [];
        foreach ($packs as $pack => $packData) {
            $packData = array_replace_recursive($packData, $this->runtimeData['package'][$pack] ?? []);
            unset($this->runtimeData['package'][$pack]);
            if (!empty($packData['depends']) && is_array($packData['depends'])) {
                $packData = array_replace_recursive($this->unpacks($packData['depends']), $packData);
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

    /**
     * Get context prototype
     * 
     * @return ProtoContextInterface
     */
    protected function getContextPrototype(): ProtoContextInterface
    {
        return clone (static::$contextPrototype ??= new ProtoContext($this->getContainer()));
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
        return $this->getContextPrototype()
            ->inflate(
                [
                    ConfigNodeInterface::NODE_SERVICE_ID => $serviceId,
                    ConfigNodeInterface::NODE_DEPENDENCY_STACK => $dependencyStack,
                    ConfigNodeInterface::NODE_PREFERENCE => $data
                ]
            );
    }
        
        
}