<?php
namespace Concept\Singularity;

use Psr\SimpleCache\CacheInterface;
use Psr\Container\ContainerInterface;
use Concept\Config\ConfigInterface;
use Concept\Config\Contract\ConfigurableInterface;
use Concept\Config\Contract\ConfigurableTrait;
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
use Concept\Singularity\Traits\CacheTrait;
use Concept\Singularity\Traits\ConfigTrait;
use Concept\Singularity\Traits\SettingsTrait;

class Singularity implements SingularityInterface, ConfigurableInterface
{

    use ConfigurableTrait;
    use SettingsTrait;
    use CacheTrait;

    //private ?ConfigInterface $config = null;

    /**
     * @var ServiceRegistryInterface|null
     */
    private ?ServiceRegistryInterface $serviceRegistry = null;
    private ?ContextBuilderInterface $contextBuilder = null;
    private ?PluginManagerInterface $pluginManager = null;
    private ?CacheInterface $cache = null;
    private array $serviceProfivders = [];
    
    /**
     * @var array<string>
     * Dependency stack to prevent circular dependencies
     * And to resolve correct context
     */
    private array $dependencyStack = [];

    static array $tc = [];

    public function __construct(private ConfigInterface $config)
    {
        $this->getPluginManager()
            ->configure(
                $this->getConfig()
            );

        return $this;
        //$this->setCache($cache);
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
    public function register(string $serviceId, object $service, bool $weak = false): static
    {
        $this->getServiceRegistry()->register($serviceId, $service, $weak);

        return $this;
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
            /**
             * Parameter is provided (f.e. from factory)
             */
            return $args[$param->getName()];
        }

        if ($param->isDefaultValueAvailable() && !$context->hasPreferenceArgument($param->getName())) {
            /**
             * Parameter has default value and is not provided in preferences config
             */
            return $param->getDefaultValue();
        }

        if ($param->isOptional() && !$context->hasPreferenceArgument($param->getName())) {
            /**
             * Parameter is optional and is not provided in preferences config
             */
            return null;
        }

        $type = $param->getType();

        if ($type === null) {
            throw new RuntimeException(
                sprintf(
                    'Unable to resolve dependency for parameter "%s". Service "%s"("%s") has no type hint',
                    $param->getName(),
                    $context->getServiceId(),
                    $context->getServiceClass()
                )
            );
        }

        if ($type->isBuiltin()) {
            /**
             * When service requests scalar type dependency 
             * the preference argument is returned if it is provided
             */
            if ($context->hasPreferenceArgument($param->getName())) {
                return $context->getPreferenceArgument($param->getName());
            }

            throw new RuntimeException(
                sprintf(
                    'Unable to resolve dependency for parameter "%s" for service "%s" ("%s").',
                    $param->getName(),
                    $context->getServiceId(),
                    $context->getServiceClass()
                )
            );
        }


        if ($type->getName() == ProtoContextInterface::class) {
            /**
             * NOTE: When service requests context as dependency the current context is returned
             */
            return $context;
        }

        /**
         * Request dependency from container
         */
        return $this->get($type->getName());
    }

    /**
     * Get context builder
     * 
     * @return ContextBuilderInterface
     */
    protected function getContextBuilder(): ContextBuilderInterface
    {
        return $this->contextBuilder ??= 
            new ContextBuilder(
                $this,
                /**
                 * @todo: pass data reference to do not do config duplicates
                 */
                $this->getConfig(),
                $this->getCache()
            );
    }

    /**
     * Get plugin manager
     * 
     * @return PluginManagerInterface
     */
    protected function getPluginManager(): PluginManagerInterface
    {
        return $this->pluginManager ??= new PluginManager();
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
     * @inheritDoc
     */
    protected function getServiceRegistry(): ServiceRegistryInterface
    {
        return $this->serviceRegistry ??= new ServiceRegistry();
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
        if ($this->getConfig() === null || !$this->getConfig()->has(ConfigNodeInterface::NODE_SINGULARITY)) {
            throw new NoConfigurationLoadedException();
        }

        if (in_array($serviceId, $dependencyStack)) {
            throw new CircularDependencyException($serviceId);
        }

        return $this;
    }

}