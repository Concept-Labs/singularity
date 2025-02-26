<?php

namespace Concept\Singularity\Plugin;

use Concept\Config\ConfigInterface;
use Concept\Config\ConfigurableInterface;
use Concept\Singularity\Context\ProtoContextInterface;

interface PluginManagerInterface extends ConfigurableInterface
{
    /**
     * Configure plugin manager
     * 
     * @param ConfigInterface $config The plugin manager configuration
     * 
     * @return static
     */
    public function configure(ConfigInterface $config): static;

    /**
     * Register plugin
     * The plugin class must implement the PluginInterface
     * 
     * @param string|callable   $plugin The plugin class name
     * @param mixed   $args     The plugin arguments
     * @param int               $priority  The plugin priority
     * 
     * @return static
     */
    public function register(string|callable $plugin, mixed $args = null, int $priority = 0): static;

    /**
     * Unregister plugin
     * 
     * @param string $plugin The plugin class name
     * 
     * @return void
     */
    //public function unregister(string $plugin): static;

    /**
     * Execute before plugins
     * 
     * @param ProtoContextInterface $context
     * 
     * @return void
     */
    public function before(ProtoContextInterface $context): void;

    /**
     * Execute after plugins
     * 
     * @param object           $service The service object
     * @param ProtoContextInterface $context The service context
     * 
     * @return void
     */
    public function after($object, ProtoContextInterface $context): void;

    /**
     * Get the plugins
     * 
     * @return iterable
     */
    //public function getPlugins(): iterable;

}