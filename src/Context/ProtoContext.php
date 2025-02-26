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
        return sprintf(
            '%s&%s',
            //$this->getServiceId(),
            $this->getServiceClass(),
            //hash('xxh3', json_encode($this->getDependencyStack())),
            hash('sha256', json_encode($this->getPreferenceData()))
        );
        
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

    protected function aggregatePlugins(): array
    {
        $configPlugins = $this->getPreferenceConfig()
            ->get(ConfigNodeInterface::NODE_PLUGINS);

        $attributePlgins = $this->getAttibutablePlugins($this);

        return array_merge($configPlugins ?? [], $attributePlgins);
    }

    protected function getAttibutablePlugins(ProtoContextInterface $context): array
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