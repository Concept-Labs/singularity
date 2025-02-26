<?php
namespace Concept\Singularity;

use Psr\Container\ContainerInterface;
use Concept\Config\ConfigInterface;
use Concept\SimpleCache\SimpleCache;
use Concept\Singularity\Config\ConfigManager;
use Concept\Singularity\Config\ConfigManagerInterface;
use Concept\Singularity\Config\ConfigNodeInterface;

use Concept\Singularity\Context\ContextBuilder;
use Concept\Singularity\Context\ContextBuilderInterface;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Registry\ServiceRegistry;
use Concept\Singularity\Registry\ServiceRegistryInterface;
use Concept\Singularity\Exception\CircularDependencyException;
use Concept\Singularity\Exception\NoConfigurationLoadedException;
use Concept\Singularity\Exception\NotInstantiableException;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\Exception\ServiceNotFoundException;
use Concept\Singularity\Plugin\PluginInterface;
use Concept\Singularity\Plugin\PluginManager;
use Concept\Singularity\Plugin\PluginManagerInterface;
use Psr\SimpleCache\CacheInterface;

class Singularity implements SingularityInterface
{

    /**
     * @var ConfigManagerInterface|null
     * Manager for configuration
     */
    private ?ConfigManagerInterface $configManager = null;

    /**
     * @var ServiceRegistryInterface|null
     */
    private ?ServiceRegistryInterface $serviceRegistry = null;
    private ?ContextBuilderInterface $contextBuilder = null;
    private ?PluginManagerInterface $pluginManager = null;
    private ?CacheInterface $cache = null;
    
    /**
     * @var array<string>
     * Dependency stack to prevent circular dependencies
     * And to resolve correct context
     */
    private array $dependencyStack = [];

    static array $tc = [];

    public function __construct()
    {
        
    }

    protected function getCache(): CacheInterface
    {
        return $this->cache ??= new SimpleCache();
    }

    /**
     * @inheritDoc
     */
    public function configure(string|array|ConfigInterface ...$configs): static
    {
        $this->configManager = new ConfigManager();
        $this->serviceRegistry = new ServiceRegistry();
        //$this->contextBuilder = new ContextBuilder($this, new MemCachedCache());
        $this->contextBuilder = (new ContextBuilder($this))->setCache($this->getCache());
        $this->pluginManager = new PluginManager();
        
        $this->getConfigManager()->addConfig(
            ...$configs
            //'var/config.c.json'
        );
//@todo: pass config manager to context builder
        $this->contextBuilder->setConfig(
                $this->getConfig()->from(ConfigNodeInterface::NODE_SINGULARITY)
            );
        
        
        $pluginManagerConfigNode = sprintf(
            '%s.%s.%s',
            ConfigNodeInterface::NODE_SINGULARITY,
            ConfigNodeInterface::NODE_SETTINGS,
            ConfigNodeInterface::NODE_PLUGIN_MANAGER
        );
        $this->getPluginManager()->configure(
            $this->getConfig()
                ->from($pluginManagerConfigNode) 
                    ?? throw new NoConfigurationLoadedException(
                        sprintf(
                            'Plugin manager configuration not found (Node: "%s")',
                            $pluginManagerConfigNode
                        )
                    )
        );

        //echo "<pre>";
        //print_r($this->getConfig()->asArray());
        //file_put_contents('var/config.c.json', json_encode($this->getConfig()->asArray(), JSON_PRETTY_PRINT));
        //die();

        return $this;
    }

    protected function getConfig(): ConfigInterface
    {
        return $this->getConfigManager()->getConfig();
    }

    /**
     * @inheritDoc
     */
    protected function getServiceRegistry(): ServiceRegistryInterface
    {
        return $this->serviceRegistry;
    }
    
    /**
     * @inheritDoc
     */
    public function getConfigManager(): ConfigManagerInterface
    {
        return $this->configManager;
    }

    /**
     * @inheritDoc
     */
    public function register(string $serviceId, object $service, bool $weak = false): static
    {
        $this->getServiceRegistry()->register($serviceId, $service, $weak);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function has(string $serviceId): bool
    {
        return $this->getServiceRegistry()->has($serviceId);
    }

    /**
     * @inheritDoc
     */
    public function get(string $serviceId, array $args = [], ?array $dependencyStack = null): object
    {
        

        return $this->require($serviceId, $args, $dependencyStack, false);
    }

    /**
     * @inheritDoc
     */
    public function create(string $serviceId, array $args = [], ?array $dependencyStack = null): object
    {
        return $this->require($serviceId, $args, $dependencyStack, true);
    }

    /**
     * @inheritDoc
     * 
     */
    public function require(string $serviceId, array $args = [], ?array $dependencyStack = null, bool $forceCreate = false): object
    {
        if (
            /**
             * @todo: self registration
             */
            $serviceId === SingularityInterface::class 
            || $serviceId === Singularity::class
            || $serviceId === ContainerInterface::class
        ) {
            return $this;
        }

        /**
         * @todo implement PSR cache
         */

        // if ($this->getServiceRegistry()->has($serviceId)) {
        //     return $this->getServiceRegistry()->get($serviceId);
        // }
         
        
        // $contextCacheKey = $this->getProtoContextCache()->key($serviceId, $dependencyStack ?? $this->getDependencyStack());
        // if ($this->getProtoContextCache()->has($contextCacheKey)) {

        //     $context = $this->getProtoContextCache()->get($contextCacheKey);
        // } else {

        //     $context = $this->buildServiceContext($serviceId, $dependencyStack ?? $this->getDependencyStack());
        //     $this->getProtoContextCache()->set($contextCacheKey, $context);
        // }

        $context = $this->buildServiceContext($serviceId, $dependencyStack ?? $this->getDependencyStack());

        
        $this->assertState($serviceId, $dependencyStack ?? $this->getDependencyStack());

        if (!$forceCreate && $this->getServiceRegistry()->has($context->getSharedId())) {
//static::$tc[$serviceId]['get'][] = $dependencyStack ?? $this->getDependencyStack();            
            $service = $this->getServiceRegistry()->get($context->getSharedId());
        } else {
//static::$tc[$serviceId]['create'][] = $dependencyStack ?? $this->getDependencyStack();            
            $service = $this->createService($context, $args);
        }

        // $service = 
        //     $this->getServiceRegistry()->has($context->getSharedId()) && !$forceCreate
        //     ? $this->getServiceRegistry()->get($context->getSharedId())
        //     : $this->createService($context, $args);

        return $service;
    }

    /**
     * @inheritDoc
     */
    protected function buildServiceContext(string $serviceId, array $dependencyStack): ProtoContextInterface
    {
        $context = $this
            ->getContextBuilder()
            ->build($serviceId, $dependencyStack);
        ;

        if (!class_exists($serviceClass = $context->getServiceClass())) {
            throw new ServiceNotFoundException($serviceClass);
        }


        return $context;
    }

    /**
     * Create service instance
     * Push service id to service stack to prevent circular dependencies
     * Resolve constructor dependencies if they are not provided
     * Decorate service if it is decoratable
     * Pop service id from service stack
     * 
     * @param ProtoContextInterface $context
     * @param array $args
     * 
     * @return object
     * 
     * @throws NotInstantiableException
     */
    protected function createService(ProtoContextInterface $context, array $args = []): object
    {
        $this->pushDependencyStack($context->getServiceId());

        $reflection = $context->getReflection();

        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($context);
        }

        $args = empty($args) 
            ? $this->resolveDependencies($context, $args)
            : $args;
        
        $this->getPluginManager()->before($context, PluginInterface::class);

        $factory = $context->getServiceFactory()
            //Fallback to new instance factory
            ?? static fn (...$args) => new ($context->getServiceClass())(...$args);

        $service = $factory(...$args);
        
        $this->getPluginManager()->after($service, $context, PluginInterface::class);
        
        $this->popDependencyStack();

        return $service;
    }

    /**
     * Resolve dependencies
     * 
     * @param ProtoContextInterface $context
     * @param array $args
     * 
     * @return array
     */
    protected function resolveDependencies(ProtoContextInterface $context, array $args = []): array
    {
        $deps = [];
        $params = $context->getReflection()->getConstructor()?->getParameters() ?? [];
        foreach ($params as $param) {
            $deps[] = $this->resolveDependency($param, $context, $args);
        }

        return $deps;
    }

    /**
     * Resolve dependency
     * 
     * @param \ReflectionParameter $param
     * @param array $args
     * 
     * @return mixed
     */
    protected function resolveDependency(\ReflectionParameter $param, ProtoContextInterface $context, array $args = []): mixed
    {
        if (isset($args[$param->getName()])) {
            return $args[$param->getName()];
        }

        if ($param->isDefaultValueAvailable()) {
            return $param->getDefaultValue();
        }

        if ($param->isOptional()) {
            return null;
        }

        $type = $param->getType();

        if ($type === null) {
            throw new RuntimeException(
                sprintf(
                    'Unable to resolve dependency for parameter "%s"',
                    $param->getName()
                )
            );
        }

        $type = $type->getName();

        if ($type == ProtoContextInterface::class) {
            /**
             * @todo: ???
             */
            return $context;
        }

        return $this->get($type);
    }

    /**
     * Get context builder
     * 
     * @return ContextBuilderInterface
     */
    protected function getContextBuilder(): ContextBuilderInterface
    {
        return $this->contextBuilder;
    }

    /**
     * Get plugin manager
     * 
     * @return PluginManagerInterface
     */
    protected function getPluginManager(): PluginManagerInterface
    {
        return $this->pluginManager;
    }

    /**
     * Get dependency stack
     * 
     * @return array
     */
    protected function getDependencyStack(): array
    {
        return $this->dependencyStack;
    }

    /**
     * Push service id to dependency stack
     * 
     * @param string $serviceId
     * 
     * @return void
     */
    protected function pushDependencyStack(string $serviceId): void
    {
        $this->dependencyStack[] = $serviceId;
    }

    /**
     * Pop service id from dependency stack
     * 
     * @return void
     */
    protected function popDependencyStack(): void
    {
        array_pop($this->dependencyStack);
    }

    /**
     * Assert state
     * 
     * @param string $serviceId
     * @param array $dependencyStack
     * 
     * @return static
     */
    protected function assertState(string $serviceId, array $dependencyStack): static
    {
        if ($this->getConfigManager()->getConfig() === null || !$this->getConfigManager()->getConfig()->has("singularity")) {
            throw new NoConfigurationLoadedException();
        }

        if (in_array($serviceId, $dependencyStack)) {
            throw new CircularDependencyException($serviceId);
        }

        return $this;
    }

}