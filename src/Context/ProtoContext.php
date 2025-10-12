<?php

namespace Concept\Singularity\Context;

use Concept\Singularity\SingularityInterface;
use Concept\Config\Config;
use Concept\Config\ConfigInterface;
use Concept\Singularity\Config\ConfigNodeInterface;
use Concept\Singularity\Plugin\Attribute\AttributePluginInterface;
use Concept\Singularity\Plugin\PluginInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionMethod;


class ProtoContext implements ProtoContextInterface
{

    /**
     * Meta data
     * 
     * @var array
     */
    private array $metaData = [];

    /**
     * Plugins cache
     * 
     * @var array|null
     */
    private ?array $pluginsCache = null;

    /**
     * The service factory
     * 
     * @var callable|null
     */
    private $serviceFactory = null;
   
    /**
     * The service reflection cache
     * 
     * @var ReflectionClass|null
     */
    private ?ReflectionClass $serviceReflection = null;

    static private array $reflectionCache = [];

    /**
     * The preference config cache
     * 
     * @var ConfigInterface|null
     */
    private ?ConfigInterface $preferenceConfig = null;

    /**
     * The preference arguments cache
     * 
     * @var array|null
     */
    private ?array $preferenceArguments = null;

    /**
     * The reflection methods cache
     * Key as filter. e.g. ReflectionMethod::IS_PUBLIC, '*'
     * 
     * @var array<string, array<int|string, ReflectionMethod>>
     */
    private array $filteredReflectionMethod = [];

    /**
     * The reflection method cache
     * Key as method name
     * 
     * @var array<string, ReflectionMethod|null>
     */
    private array $reflectionMethod = [];

    /**
     * The attributes cache
     * 
     * @var array<string, array>
     */
    private array $attributesCache = [];

    /**
     * Plugin propagation stop flag
     * 
     * @var array<string, bool>
     */
    private array $isPluginPropagationStopped = [
        PluginInterface::BEFORE => false,
        PluginInterface::AFTER => false
    ];

    public function __construct(private readonly SingularityInterface $container)
    {}

    // public function __clone()
    // {
    //     // Deep clone all properties that are objects or arrays of objects
    //     $this->metaData = is_array($this->metaData) ? unserialize(serialize($this->metaData)) : $this->metaData;
    //     $this->pluginsCache = is_array($this->pluginsCache) ? unserialize(serialize($this->pluginsCache)) : $this->pluginsCache;
    //     // $serviceFactory is a callable, do not clone
    //     if ($this->serviceReflection instanceof ReflectionClass) {
    //         //$this->serviceReflection = clone $this->serviceReflection;
    //     }
    //     // $reflectionCache is static, do not clone
    //     if ($this->preferenceConfig instanceof ConfigInterface) {
    //         $this->preferenceConfig = clone $this->preferenceConfig;
    //     }
    //     $this->preferenceArguments = is_array($this->preferenceArguments) ? unserialize(serialize($this->preferenceArguments)) : $this->preferenceArguments;
    //     // Clone ReflectionMethod objects in filteredReflectionMethod
    //     foreach ($this->filteredReflectionMethod as $filter => $methods) {
    //         foreach ($methods as $k => $method) {
    //         if ($method instanceof ReflectionMethod) {
    //             $this->filteredReflectionMethod[$filter][$k] = clone $method;
    //         }
    //         }
    //     }
    //     // Clone ReflectionMethod objects in reflectionMethod
    //     foreach ($this->reflectionMethod as $name => $method) {
    //         if ($method instanceof ReflectionMethod) {
    //         $this->reflectionMethod[$name] = clone $method;
    //         }
    //     }
    //     // Deep clone attributesCache
    //     $this->attributesCache = is_array($this->attributesCache) ? unserialize(serialize($this->attributesCache)) : $this->attributesCache;
    //     // isPluginPropagationStopped is array of bool, no need to clone
    // }

    // public function reset(): static
    // {
    //     $this->metaData = [];
    //     $this->pluginsCache = null;
    //     $this->serviceFactory = null;
    //     $this->serviceReflection = null;
    //     $this->preferenceConfig = null;
    //     $this->filteredReflectionMethod = [];
    //     $this->reflectionMethod = [];
    //     $this->attributesCache = [];
    //     $this->isPluginPropagationStopped = [
    //         PluginInterface::BEFORE => false,
    //         PluginInterface::AFTER => false
    //     ];

    //     return $this;
    // }

    public function asConfig(): ConfigInterface
    {
        return Config::fromArray($this->getMetaData());
    }

    /**
     * @inheritDoc
     */
    public function inflate(array $metaData): static
    {
        $this->metaData = $metaData;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getMetaData(?string $metaKey = null): mixed
    {
        return $metaKey === null
            ? $this->metaData
            : $this->metaData[$metaKey] ?? null;
    }

     /**
     * @inheritDoc
     */
    public function getSharedId(): string
    {
        return $this->getServiceId()
            // .'&'.
            // hash('sha256', json_encode($this->getPreferenceData()))
            ;
        
        // if (!$this->getPreferenceData() || empty($this->getPreferenceData())) {
        //     return $this->getServiceId();
        // }

        // return sprintf(
        //     '%s&%s',
        //     //$this->getServiceId(),
        //     $this->getServiceClass(),
        //     //hash('xxh3', json_encode($this->getDependencyStack())),
        //     hash('sha256', json_encode($this->getPreferenceData()))
        // );
        
    }

    /**
     * @inheritDoc
     */
    public function getServiceFactory(): ?callable
    {
        return $this->serviceFactory;
    }

    /**
     * @inheritDoc
     */
    public function setServiceFactory(callable $factory): static
    {
        $this->serviceFactory = $factory;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getContainer(): SingularityInterface
    {
        return $this->container;
    }

    /**
     * @inheritDoc
     */
    public function getServiceId(): string
    {
        return 
            $this->getMetaData(ConfigNodeInterface::NODE_SERVICE_ID);
    }

    /**
     * @inheritDoc
     */
    public function getServiceClass(): string
    {
        return 
            $this->getMetaData(ConfigNodeInterface::NODE_PREFERENCE)
            [ConfigNodeInterface::NODE_CLASS] ?? $this->getServiceId();
    }

    /**
     * @inheritDoc
     */
    public function getDependencyStack(): array
    {
        return 
            $this->getMetaData(ConfigNodeInterface::NODE_DEPENDENCY_STACK);
    }

    /**
     * {@inheritDoc}
     */
    public function getPreferenceData(): array
    {
        return $this->getMetaData(ConfigNodeInterface::NODE_PREFERENCE);
    }

    /**
     * @inheritDoc
     */
    public function getPreferenceConfig(): ConfigInterface
    {
        return $this->preferenceConfig ??= 
            Config::fromArray( $this->getPreferenceData() );
    }

    /**
     * @inheritDoc
     */
    public function getPreferenceArguments(): array
    {
        return $this->preferenceArguments ??= $this->getPreferenceConfig()
            ->get(ConfigNodeInterface::NODE_ARGUMENTS) ?? [];
    }

    /**
     * @inheritDoc
     */
    public function hasPreferenceArgument(string $name): bool
    {
        return isset($this->getPreferenceArguments()[$name]);
    }

    /**
     * @inheritDoc
     */
    public function getPreferenceArgument(string $name): mixed
    {
        $argument = $this->getPreferenceArguments()[$name];


        return $argument;
    }

    /**
     * @inheritDoc
     */
    public function hasPlugins(): bool
    {
        return !empty($this->getPlugins());
    }

    /**
     * @inheritDoc
     */
    public function getPlugins(): array
    {
        if (null === $this->pluginsCache) {
            $this->pluginsCache = $this->aggregatePlugins();
        }

         return $this->pluginsCache;
    }

    /**
     * Aggregate plugins
     * 
     * @return array<string, mixed>
     */
    protected function aggregatePlugins(): array
    {
        $configPlugins = $this->getPreferenceConfig()
            ->get(ConfigNodeInterface::NODE_PLUGINS);

        $attributePlgins = $this->getAttibutablePlugins($this);

        return array_merge($configPlugins ?? [], $attributePlgins);
    }

    /**
     * Get attributable plugins
     * 
     * @return array<string, mixed>
     */
    protected function getAttibutablePlugins(): array
    {
        $plugins = [];

        $attributes = $this->getAttributes(
            AttributePluginInterface::class, 
            ReflectionAttribute::IS_INSTANCEOF
        );

        foreach ($attributes as $attribute) {
            $plugin = $attribute->newInstance();
            $plugins[$plugin->getPlugin()] = $plugin->getArgs();
        }

        return $plugins;
    }

    /**
     * @inheritDoc
     */
    public function isPluginDisabled(string $plugin): bool
    {
        if (null === $this->pluginsCache) {
            /**
             * @todo throw exception
             */
            $this->pluginsCache = $this->aggregatePlugins();
        }

        return false === ($this->pluginsCache[$plugin] ?? true);
    }

    /**
     * @inheritDoc
     */
    public function stopPluginPropagation(string $type): static
    {
        $this->isPluginPropagationStopped[$type] = true;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isPluginPropagationStopped(string $type): bool
    {
        if (!isset($this->isPluginPropagationStopped[$type])) {
            return false;
        }

        return $this->isPluginPropagationStopped[$type];
    }

    /**
     * @inheritDoc
     */
    public function getReflection(): ReflectionClass
    {
        return $this->serviceReflection ??=
            static::$reflectionCache[$this->getServiceClass()] ??=
            new ReflectionClass($this->getServiceClass());
        
        // return static::$reflectionCache[$this->getServiceClass()] ??=
        //     new ReflectionClass($this->getServiceClass());
    }

    /**
     * @inheritDoc
     */
    public function getReflectionMethods(?int $filter = null): array
    {
        return $this->filteredReflectionMethod[$filter ?? '*'] ??=
            $this->getReflection()->getMethods($filter);
    }

    /**
     * @inheritDoc
     */
    public function getReflectionMethod(string $name): ?ReflectionMethod
    {
        return  $this->reflectionMethod[$name] ??=
            $this->getReflection()->hasMethod($name) 
                ? $this->getReflection()->getMethod($name)
                : null;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        $key = $name ?? '*';

        if (!isset($this->attributesCache[$key])) {
            $this->attributesCache[$key] = $this->getReflection()->getAttributes($name, $flags);
        }

        return $this->attributesCache[$key];
    }

}