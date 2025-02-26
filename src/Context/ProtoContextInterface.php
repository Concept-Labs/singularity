<?php
namespace Concept\Singularity\Context;

use Concept\Config\ConfigInterface;
use Concept\Singularity\SingularityInterface;
use ReflectionClass;
use ReflectionMethod;

interface ProtoContextInterface 
{

    /**
     * Set the meta data
     * 
     * @param array<string|int, mixed> $data The meta data
     * 
     * @return static
     */
    public function inflate(array $metaData): static;

    /**
     * Get the meta data
     * 
     * @param string|null $key The meta data key
     * 
     * @return mixed The meta data
     */
    public function getMetaData(?string $key = null ): mixed;
    
    /**
     * Get the container
     * 
     * @return SingularityInterface The container
     */
    public function getContainer(): SingularityInterface;

    /**
     * Get the service factory
     * 
     * @return callable The service factory
     */
    public function getServiceFactory(): ?callable;

    /**
     * Set the service factory
     * 
     * @param callable $factory The service factory
     * 
     * @return static
     */
    public function setServiceFactory(callable $factory): static;

    /**
     * Get the service id
     * 
     * @return string The service id
     */
    public function getServiceId(): string;    

    /**
     * Get the service shared id
     * 
     * @return string The service shared id
     */
    public function getSharedId(): string;

    /**
     * Get the service class
     * 
     * @return string The service class
     */
    public function getServiceClass(): string;
    
    /**
    * Get the service stack
    *
    * @return array<int, string> The service stack
    */
    public function getDependencyStack(): array;

    /**
     * Get the service reflection
     * Reflection will be created if not cached
     * 
     * @return ReflectionClass The service reflection
     */
    public function getReflection(): ReflectionClass;
    
    /**
     * Get the service reflection methods
     * Methods will be retrieved from reflection if not cached
     * 
     * @param int|null $filter Filter the methods
     * 
     * @return ReflectionMethod[] The service reflection methods
     */
    public function getReflectionMethods(?int $filter = null): array;

    /**
     * Get the service reflection method
     * Method will be retrieved from reflection if not cached
     * 
     * @param string $name The method name
     * 
     * @return ReflectionMethod The service reflection method
     */
    public function getReflectionMethod(string $name): ?ReflectionMethod;

    /**
     * Get the preference config
     * Config will be created from config node (ConfigNodeInterface::NODE_PREFERENCE) 
     * if not cached
     * 
     * @return ConfigInterface The preference config
     */
    public function getPreferenceConfig(): ConfigInterface;

    /**
     * Get the preference data. 
     * Config node (ConfigNodeInterface::NODE_PREFERENCE)
     * 
     * @return array The preference data
     */
    public function getPreferenceData(): array;


    /**
     * Get the plugins
     * 
     * @return iterable The plugins
     */
    public function getPlugins(): iterable;


    /**
     * Get the service plugins
     * 
     * @return array The service plugins
     */
    public function hasPlugins(): bool;

    /**
     * Check if the plugin is disabled
     * 
     * @param string $plugin The plugin class
     * 
     * @return bool
     */
    public function isPluginDisabled(string $plugin): bool;

    public function stopPluginPropagation(string $type): static;

    public function isPluginPropagationStopped(string $type): bool;
}