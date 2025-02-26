<?php

namespace Concept\Singularity\Plugin;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Plugin\PluginInterface;

class AggregatePlugin extends AbstractPlugin
{

    /**
     * {@inheritDoc}
     */
    public static function before(ProtoContextInterface $context, mixed $pluginsData = null): void
    {
        $method = PluginInterface::BEFORE;

        foreach (static::aggregate($context, null, $pluginsData) as $plugin => $pluginArgs) {
            if ($context->isPluginPropagationStopped($method)) {
                break;
            }
            echo sprintf(
                "<br>Aggregator <b>%s</b>::before() for <b>%s</b>",
                $plugin,
                $context->getReflection()->getName()
            );
            if (method_exists($plugin, $method)) {
                $plugin::$method($context, $pluginArgs);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public static function after(object $service, ProtoContextInterface $context, mixed $pluginsData = null): void
    {
        $method = PluginInterface::AFTER;

        foreach (static::aggregate($context, $service, $pluginsData) as $plugin => $pluginArgs) {
            if ($context->isPluginPropagationStopped($method)) {
                break;
            }
            echo sprintf(
                "<br>Aggregator: <b>%s</b>::after() for <b>%s</b>",
                $plugin,
                $context->getReflection()->getName()
            );
            if (method_exists($plugin, $method)) {
                $plugin::$method($service, $context, $pluginArgs);
            }
        }
    }

    /**
     * Aggregate plugins
     * 
     * @param ProtoContextInterface $context     The context
     * @param object|null           $service     The service
     * @param mixed                 $pluginsData The plugins data
     * 
     * 
     * @return iterable
     */
    protected static function aggregate(ProtoContextInterface $context, ?object $service = null, mixed $pluginsData = null): iterable
    {
        return $pluginsData ?? [];
    }

    protected static function assertPlugin(string|callable $plugin): void
    {
        if (!is_string($plugin) && !is_callable($plugin)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Plugin must be a string or a callable, %s given',
                    gettype($plugin)
                )
            );
        }

        if (is_string($plugin) && !class_exists($plugin)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Plugin class %s does not exist',
                    $plugin
                )
            );
        }
    }

}
