<?php
namespace Concept\Singularity\Plugin;

use Concept\Config\ConfigInterface;
use Concept\Config\Traits\ConfigurableTrait;
use Concept\Singularity\Config\ConfigNodeInterface;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\Plugin\Attribute\AttributePluginInterface;
use Concept\Singularity\Plugin\Exception\InvalidPluginClassException;
use Concept\Singularity\Plugin\Exception\InvalidPluginMethodException;
use Concept\Singularity\Plugin\Exception\PluginNotFoundException;

/**
 * Plugin manager
 * @template TPluginPriority of int
 * @template TPluginClass of class-string
 * @template TPluginArgs of array|bool|null
 
 * 
 * @package Concept\Singularity\Plugin
 */
class PluginManager implements PluginManagerInterface
{

    use ConfigurableTrait;

    /**
     * @var array<TPluginPriority, array<class-string<TPluginClass>, TPluginArgs>>
     */
    private array $plugins = [];

    /**
     * {@inheritDoc}
     */
    // public function getPlugins(): array
    // {
    //     return $this->plugins;
    // }

    /**
     * {@inheritDoc}
     */
    public function configure(ConfigInterface $config): static
    {
        return $this
            ->setConfig($config)
            ->configurePlugins();
    }

    /**
     * Initialize plugins
     * Get plugins from the configuration and register them
     * 
     * @return static
     */
    protected function configurePlugins(): static
    {
        $this->plugins = $this->getConfig()
            ->get(ConfigNodeInterface::NODE_PLUGIN_MANAGER_PLUGINS) ?? [];

       
        return $this;
    }

    

    /**
     * {@inheritDoc}
     */
    public function register(string|callable $plugin, mixed $args = null, ?int $priority = null): static
    {
        //$this->assertPlugin($plugin);
        // $priority ??= $args[ConfigNodeInterface::NODE_PRIORITY] ?? 0;
        // unset($args[ConfigNodeInterface::NODE_PRIORITY]);
        // $this->plugins[$priority][$plugin] = $args;

        $args[ConfigNodeInterface::NODE_PRIORITY] = $priority ?? $args[ConfigNodeInterface::NODE_PRIORITY] ?? 0;
        $this->plugins[$plugin] = $args;
        
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    // public function unregister(string $plugin): static
    // {
    //     foreach ($this->plugins as $order => $plugins) {
    //         if (isset($plugins[$plugin])) {
    //             unset($this->plugins[$order][$plugin]);
    //         }
    //     }

    //     return $this;
    // }

    /**
     * {@inheritDoc}
     */
    public function before(ProtoContextInterface $context): void
    {
        $this->invokePlugins(PluginInterface::BEFORE, $context);
    }

    /**
     * {@inheritDoc}
     */
    public function after($object, ProtoContextInterface $context): void
    {
        $this->invokePlugins(PluginInterface::AFTER, $context, $object);
    }

    /**
     * Invoke plugins
     * 
     * @param string                $method  The method or when  to invoke (before|after)
     * @param ProtoContextInterface $context The context
     * 
     * @return void
     */
    protected function invokePlugins(string $method, ProtoContextInterface $context, ?object $object = null): void
    {
        echo "<hr>".strtoupper($method)."() PLUGINS FOR " . $context->getServiceId() ;

        // foreach ($this->queue as $item) {
        //     [$plugin, $args] = [key($item), current($item)];
        // }

        foreach ($this->createContextQueue($context) as $plugin => $args) {
            //foreach ($plugins as $plugin => $args) {
                
                
                /**
                 * If the plugin is stopped return
                 */
                if ($context->isPluginPropagationStopped($method)) {
                    return;
                }
                /**
                 * $args === false means the plugin is disabled as well
                 */
                if ($args === false || $context->isPluginDisabled($plugin)) {
                    continue;
                }
                /**
                 * Method may be overridden in the configuration as an argument
                 */
                $method = $args[$method] ?? $method;

                /**
                 * If the plugin is a callable, call it
                 */
                if (is_callable($plugin)) {
                    match ($method) {
                        PluginInterface::BEFORE => $plugin($context, $args),
                        PluginInterface::AFTER => $plugin($object, $context, $args),
                        default => throw new RuntimeException('Invalid plugin method "' . $method . '"'),
                    };
                    continue;
                }

                /**
                 * If the plugin is a class, call its static method
                 */
                if (!is_a($plugin, PluginInterface::class, true)) {
                    throw new InvalidPluginClassException($plugin);
                }
                echo "<br>Plugin <b>$plugin</b>::$method()";
                match ($method) {
                    PluginInterface::BEFORE => $plugin::$method($context, $args),
                    PluginInterface::AFTER => $plugin::$method($object, $context, $args),
                    default => throw new InvalidPluginMethodException($plugin, $method)
                };
            //}
        }
    }

    /**
     * 
     * @param ProtoContextInterface $context The context
     * 
     * @return iterable array<class-string<TPluginClass>, TPluginArgs>
     */
    protected function createContextQueue(ProtoContextInterface $context): array
    {
        /**
         * 
         */
        $queue = array_replace(
            $this->plugins,
            $context->getPlugins()
            );

        uasort(
            $queue, 
            fn($a, $b) => 
                (isset($b[ConfigNodeInterface::NODE_PRIORITY]) ? $b[ConfigNodeInterface::NODE_PRIORITY] : 0)
                <=> 
                (isset($a[ConfigNodeInterface::NODE_PRIORITY]) ? $a[ConfigNodeInterface::NODE_PRIORITY] : 0)
        );

        return $queue;
    }

    

    

    /**
     * Assert that the plugin is a valid plugin class
     * 
     * @param string $plugin
     * 
     * @return void
     */
    private function assertPlugin(string $plugin): void
    {
        //@todo remove this (commented out)
        if (strpos($plugin, '--') === 0) {
            return;
        }

        if (!class_exists($plugin)) {
            throw new PluginNotFoundException($plugin);
        }

        if (!is_subclass_of($plugin, PluginInterface::class)) {
            throw new InvalidPluginClassException($plugin);
        }
    }


    // protected function getAttributePlugins(ProtoContextInterface $context): array
    // {
    //     $plugins = [];
    //     $reflection = $context->getReflection();
    //     $attributes = $reflection->getAttributes(AttributePluginInterface::class);

    //     foreach ($attributes as $attribute) {
    //         $plugin = $attribute->newInstance();
    //         $plugin = 
    //             array_replace($plugins, $plugin->getPluginMeta());
    //     }

    //     return $plugins;
    // }

}