<?php
namespace Concept\Singularity;

use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface;
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
use Concept\Singularity\Traits\SettingsTrait;

class Singularity implements SingularityInterface
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

    public function __construct(?ConfigInterface $config = null)
    {
        if ($config) {
            $this->setConfig($config);
        }

        return $this;
    }

    public function setConfig(?ConfigInterface $config):static
    {
        $this->___config = $config;

        $this->getPluginManager()
            ->configure($config);

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
    protected function require(string $serviceId, array $args = [], ?array $dependencyStack = null, bool $forceCreate = false): object
    {
        /**
         @todo
         */

        if ($serviceId == "Concept\\SimpleHttpExample\\Classes\\SomeService") {
            $debug = 1;
        
        }
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

        

        $context = $this->buildServiceContext($serviceId, $dependencyStack ?? $this->getDependencyStack());

        
        $this->assertState($serviceId, $dependencyStack ?? $this->getDependencyStack());

        if (!$forceCreate && $this->getServiceRegistry()->has($context->getSharedId())) {
//static::$tc[$serviceId]['get'][] = $dependencyStack ?? $this->getDependencyStack();            
            $service = $this->getServiceRegistry()->get($context->getSharedId());
        } elseif (!$forceCreate && $this->getServiceRegistry()->has($serviceId)) {
            $service = $this->getServiceRegistry()->get($serviceId);
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

        if (!empty($args)) {
            $debug = 1;
        }

        $args = empty($args) 
            ? $this->resolveParameters($context, $args)
            : $args;
        
        $this->getPluginManager()->before($context, PluginInterface::class);

        /**
         * Use factory if it is provided in context (f.e. by Factory plugin)
         */
        $factory = $context->getServiceFactory()
            //Fallback to new instance factory
            ?? static fn (...$args) => new ($context->getServiceClass())(...$args);

        $service = $factory(...$args);
        
        $this->getPluginManager()->after($service, $context, PluginInterface::class);
        
        $this->popDependencyStack();

        return $service;
    }

    /**
     * Resolve arguments
     *
     * @param ProtoContextInterface $context
     * @param array $args
     * 
     * @return array
     */
    protected function resolveParameters(ProtoContextInterface $context, array $args = []): array
    {
        $deps = [];

        $constructorParameters = $context->getReflection()->getConstructor()?->getParameters() ?? [];
        
        foreach ($constructorParameters as $parameter) {
            $deps[] = $this->resolveParameter($parameter, $context, $args);
        }

        return $deps;
    }

    

    /**
     * Resolve parameter
     * 
     * - Check if parameter is provided directly in arguments - use it
     * - Check if parameter is provided in preference arguments - use it
     * - Check if parameter has default value - use it
     * - Check if parameter is optional - use null
     * - If parameter has no type hint throw exception
     * - If parameter is type hinted with ProtoContextInterface return current context (special case)
     * - If parameter is type hinted with built-in type and was not provided in arguments or preference arguments throw exception
     * - If parameter is type hinted with service id (class/interface/custom id) request it from container
     * 
     * @param \ReflectionParameter $param
     * @param array $args
     * 
     * @return mixed
     */
    protected function resolveParameter(\ReflectionParameter $parameter, ProtoContextInterface $context, array $args = []): mixed
    {
        $namedArgument = $parameter->getName();
        /**
         * Resolve parameter by type hint
         * 
         * @var \ReflectionNamedType|null $type
         */
        $type = $parameter->getType();

        if (isset($args[$namedArgument])) {
            /**
             * If parameter is provided directly in arguments then prefer it
             */
            return $args[$namedArgument];
        }

        if ($context->hasPreferenceArgument($namedArgument)) {
            return $this->resolvePreferenceArgument(
                $context->getPreferenceArgument($namedArgument),
                $parameter
            );
        }

        if ($parameter->isDefaultValueAvailable()) {
            /**
             * Parameter has default value and is not provided in preferences config
             */
            return $parameter->getDefaultValue();
        }

        if ($parameter->isOptional()) {
            /**
             * Parameter is optional and is not provided in preferences config
             */
            return null;
        }

        if ($type === null) {
            throw new RuntimeException(
                sprintf(
                    'Unable to resolve dependency for parameter "%s". Service "%s"("%s") has no type hint',
                    $parameter->getName(),
                    $context->getServiceId(),
                    $context->getServiceClass()
                )
            );
        }

        if ($type->isBuiltin()) {
            throw new RuntimeException(
                sprintf(
                    'Unable to resolve dependency for parameter "(%s)%s" for service "%s" ("%s").',
                    $type->getName(),
                    $parameter->getName(),
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
     * Resolve configured argument
     * 
     * @param mixed $preferenceArgument
     * @param \ReflectionParameter $parameter
     * 
     * @return mixed
     */
    protected function resolvePreferenceArgument(mixed $preferenceArgument, \ReflectionParameter $parameter): mixed
    {
        if (
            is_array($preferenceArgument) 
                && isset($preferenceArgument['type']) 
                && $preferenceArgument['type'] === 'service'
                && isset($preferenceArgument['id'])
            ) {
            /**
             * If preference argument is service reference then resolve it from container
             */
            $argument = $this->get($preferenceArgument['id']);

            if ($parameter->getType() && !$parameter->getType()->isBuiltin() && !$argument instanceof ($parameter->getType()->getName())) {
                throw new RuntimeException(
                    sprintf(
                        'Unable to resolve dependency for parameter "(%s)%s" for service "%s" ("%s"). Preference argument is not instance of "%s", "%s" given.',
                        $parameter->getType()->getName(),
                        $parameter->getName(),
                        $parameter->getDeclaringClass()->getName(),
                        $parameter->getDeclaringClass()->getFileName(),
                        get_debug_type($argument),
                        get_debug_type($argument)
                    )
                );
            }

            $preferenceArgument = $argument;
        }

        return $preferenceArgument;
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
        //$this->dependencyStack[] = $serviceId;
        
        /**
         @todo: TEST THIS CHANGE CAREFULLY
         */
        array_unshift($this->dependencyStack, $serviceId);
    }

    /**
     * Pop service id from dependency stack
     * 
     * @return void
     */
    protected function popDependencyStack(): void
    {
        //array_pop($this->dependencyStack);

        /**
         @todo: TEST THIS CHANGE CAREFULLY
         */
        array_shift($this->dependencyStack);
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