<?php

namespace Concept\Singularity\Plugin\ContractEnforce;

use Concept\Singularity\Config\ConfigNodeInterface;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Plugin\AggregatePlugin;

class Enforcement extends AggregatePlugin
{
    /**
     * Get applicable plugins
     * 
     * @param ProtoContextInterface $context
     * 
     * @return array
     */
    protected static function aggregate(ProtoContextInterface $context, ?object $service = null, mixed $args = null): array
    {
        //@todo: cache
        $plugins = [];
        $map = $args ?? [];
        foreach ($map as $contract => $contractPlugins) {
            if (self::isApplicable($context, $contract, $service) ) {
                foreach ($contractPlugins as $plugin => $pluginArgs) {
                    static::assertPlugin($plugin);
                    if ($pluginArgs === false || $context->isPluginDisabled($plugin)) {
                        continue;
                    }
                    $plugins[$plugin] = $pluginArgs;
                }
            }
        }

        return $plugins;
    }

    /**
     * Check if the plugin is applicable to the service class
     * 
     * @param ProtoContextInterface $context
     * @param string $contract
     * 
     * @return bool
     */
    protected static function isApplicable(ProtoContextInterface $context, string $contract ='*', ?object $service = null): bool
    {
        return  (
                    $contract === ConfigNodeInterface::NODE_ASTERISK 
                    || $contract === ConfigNodeInterface::NODE_STRATEGY
                    || is_a($service ?? $context->getServiceClass(), $contract, true)
                )
        ;
    }

}
