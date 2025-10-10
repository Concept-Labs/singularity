# Advanced Usage

## Advanced Patterns and Techniques

This guide covers advanced usage patterns, edge cases, and complex scenarios with Singularity DI.

## Multi-Tenant Applications

### Tenant-Specific Services

```php
<?php
class TenantContext
{
    private static ?string $currentTenant = null;
    
    public static function setTenant(string $tenant): void
    {
        self::$currentTenant = $tenant;
    }
    
    public static function getTenant(): string
    {
        if (self::$currentTenant === null) {
            throw new \RuntimeException('No tenant set');
        }
        return self::$currentTenant;
    }
}

class TenantAwareFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        $tenant = TenantContext::getTenant();
        
        // Create tenant-specific instance
        return match($serviceId) {
            DatabaseInterface::class => new TenantDatabase($tenant),
            CacheInterface::class => new TenantCache($tenant),
            default => new $serviceId(...$args)
        };
    }
}
```

### Configuration Per Tenant

```json
{
  "singularity": {
    "namespace": {
      "App\\Tenant\\Alpha\\": {
        "preference": {
          "DatabaseInterface": {
            "class": "AlphaDatabase",
            "arguments": {
              "dsn": "mysql:host=alpha-db;dbname=alpha"
            }
          }
        }
      },
      "App\\Tenant\\Beta\\": {
        "preference": {
          "DatabaseInterface": {
            "class": "BetaDatabase",
            "arguments": {
              "dsn": "pgsql:host=beta-db;dbname=beta"
            }
          }
        }
      }
    }
  }
}
```

### Tenant Resolution Plugin

```php
<?php
class TenantPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $tenant = TenantContext::getTenant();
        
        // Add tenant to metadata
        $context->inflate([
            'tenant' => $tenant,
            'tenant_prefix' => "tenant_{$tenant}_"
        ]);
    }
}
```

## Middleware Pattern

### Service Middleware

```php
<?php
interface MiddlewareInterface
{
    public function process(object $service, callable $next): object;
}

class LoggingMiddleware implements MiddlewareInterface
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function process(object $service, callable $next): object
    {
        $this->logger->info("Before: " . get_class($service));
        $service = $next($service);
        $this->logger->info("After: " . get_class($service));
        return $service;
    }
}

class ValidationMiddleware implements MiddlewareInterface
{
    public function process(object $service, callable $next): object
    {
        // Validate service
        if (!$this->isValid($service)) {
            throw new \RuntimeException('Invalid service');
        }
        return $next($service);
    }
    
    private function isValid(object $service): bool
    {
        // Validation logic
        return true;
    }
}
```

### Middleware Pipeline Plugin

```php
<?php
class MiddlewarePipeline extends AbstractPlugin
{
    private static array $middlewares = [];
    
    public static function registerMiddleware(MiddlewareInterface $middleware): void
    {
        self::$middlewares[] = $middleware;
    }
    
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        $pipeline = array_reduce(
            array_reverse(self::$middlewares),
            fn($next, $middleware) => fn($service) => $middleware->process($service, $next),
            fn($service) => $service
        );
        
        return $pipeline($service);
    }
}
```

## Decorator Pattern

### Automatic Decorators

```php
<?php
interface LoggableInterface
{
    public function enableLogging(): void;
}

class LoggingDecorator implements LoggableInterface
{
    public function __construct(
        private object $decorated,
        private LoggerInterface $logger
    ) {}
    
    public function __call(string $method, array $arguments)
    {
        $this->logger->debug("Calling: $method");
        $result = $this->decorated->$method(...$arguments);
        $this->logger->debug("Result: " . json_encode($result));
        return $result;
    }
    
    public function enableLogging(): void
    {
        // Already logging
    }
}

class DecoratorPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        if ($service instanceof LoggableInterface) {
            $container = $context->getContainer();
            $logger = $container->get(LoggerInterface::class);
            $service = new LoggingDecorator($service, $logger);
        }
        
        // Note: Plugins can't modify the service object directly
        // This is for demonstration - use factory or other patterns
    }
}
```

### Decorator Factory

```php
<?php
class DecoratorFactory implements FactoryInterface
{
    public function __construct(
        private ContainerInterface $container,
        private array $decorators = []
    ) {}
    
    public function create(string $serviceId, array $args = []): object
    {
        // Create base service
        $service = new $serviceId(...$args);
        
        // Apply decorators
        foreach ($this->decorators[$serviceId] ?? [] as $decoratorClass) {
            $service = new $decoratorClass($service, $this->container);
        }
        
        return $service;
    }
    
    public function addDecorator(string $serviceId, string $decoratorClass): void
    {
        $this->decorators[$serviceId][] = $decoratorClass;
    }
}
```

## Event-Driven Architecture

### Service Events

```php
<?php
class ServiceCreatedEvent
{
    public function __construct(
        public readonly object $service,
        public readonly string $serviceId,
        public readonly array $args
    ) {}
}

class EventDispatcherPlugin extends AbstractPlugin
{
    private static ?EventDispatcherInterface $dispatcher = null;
    
    public static function setDispatcher(EventDispatcherInterface $dispatcher): void
    {
        self::$dispatcher = $dispatcher;
    }
    
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        if (self::$dispatcher) {
            $event = new ServiceCreatedEvent(
                $service,
                $context->getServiceId(),
                $context->getPreferenceArguments()
            );
            
            self::$dispatcher->dispatch($event);
        }
    }
}
```

### Event Listeners

```php
<?php
class ServiceCreatedListener
{
    public function __construct(private LoggerInterface $logger) {}
    
    public function onServiceCreated(ServiceCreatedEvent $event): void
    {
        $this->logger->info(
            "Service created: {$event->serviceId}",
            ['class' => get_class($event->service)]
        );
    }
}
```

## Aspect-Oriented Programming (AOP)

### Method Interception

```php
<?php
interface AspectInterface
{
    public function before(string $method, array $args): void;
    public function after(string $method, mixed $result): void;
}

class TimingAspect implements AspectInterface
{
    private array $timers = [];
    
    public function before(string $method, array $args): void
    {
        $this->timers[$method] = microtime(true);
    }
    
    public function after(string $method, mixed $result): void
    {
        $elapsed = microtime(true) - $this->timers[$method];
        error_log("Method $method took " . round($elapsed * 1000, 2) . "ms");
    }
}

class AspectProxy
{
    public function __construct(
        private object $target,
        private array $aspects
    ) {}
    
    public function __call(string $method, array $arguments)
    {
        // Before aspects
        foreach ($this->aspects as $aspect) {
            $aspect->before($method, $arguments);
        }
        
        // Call method
        $result = $this->target->$method(...$arguments);
        
        // After aspects
        foreach ($this->aspects as $aspect) {
            $aspect->after($method, $result);
        }
        
        return $result;
    }
}
```

### AOP Factory

```php
<?php
class AopFactory implements FactoryInterface
{
    public function __construct(
        private ContainerInterface $container,
        private array $aspectConfig = []
    ) {}
    
    public function create(string $serviceId, array $args = []): object
    {
        $service = new $serviceId(...$args);
        
        // Check if service needs aspects
        if (isset($this->aspectConfig[$serviceId])) {
            $aspects = array_map(
                fn($aspectClass) => $this->container->get($aspectClass),
                $this->aspectConfig[$serviceId]
            );
            
            return new AspectProxy($service, $aspects);
        }
        
        return $service;
    }
}
```

## Dynamic Service Registration

### Runtime Service Binding

```php
<?php
class DynamicContainer
{
    private Singularity $container;
    private Config $config;
    
    public function __construct(Singularity $container, Config $config)
    {
        $this->container = $container;
        $this->config = $config;
    }
    
    public function bind(string $abstract, string $concrete, array $options = []): void
    {
        $preference = array_merge([
            'class' => $concrete
        ], $options);
        
        $this->config->set(
            "singularity.preference.{$abstract}",
            $preference
        );
    }
    
    public function singleton(string $abstract, string $concrete): void
    {
        $this->bind($abstract, $concrete, ['shared' => true]);
    }
    
    public function factory(string $abstract, callable $factory): void
    {
        // Store factory callable
        // Implementation depends on how you want to handle closures
    }
}

// Usage
$dynamic = new DynamicContainer($container, $config);
$dynamic->singleton('CacheInterface', 'RedisCache');
$dynamic->bind('LoggerInterface', 'FileLogger', ['arguments' => ['path' => '/var/log/app.log']]);
```

## Conditional Service Creation

### Feature Flags

```php
<?php
class FeatureFlagPlugin extends AbstractPlugin
{
    private static array $flags = [];
    
    public static function setFlags(array $flags): void
    {
        self::$flags = $flags;
    }
    
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        
        // Check if service requires a feature flag
        if (isset(self::$flags[$serviceId])) {
            if (!self::$flags[$serviceId]['enabled']) {
                throw new \RuntimeException(
                    "Service $serviceId is disabled by feature flag"
                );
            }
        }
    }
}

// Usage
FeatureFlagPlugin::setFlags([
    'NewFeatureService' => ['enabled' => true],
    'ExperimentalService' => ['enabled' => false]
]);
```

### Environment-Based Services

```php
<?php
class EnvironmentPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $env = getenv('APP_ENV') ?: 'production';
        $serviceClass = $context->getServiceClass();
        
        // Override class based on environment
        if ($env === 'testing' && str_contains($serviceClass, 'Production')) {
            $testClass = str_replace('Production', 'Test', $serviceClass);
            if (class_exists($testClass)) {
                // Change the service class
                // Note: This would require context to be mutable
            }
        }
    }
}
```

## Circular Dependency Resolution

### Using Lazy Loading

```php
<?php
use Concept\Singularity\Contract\Factory\LazyGhostInterface;

class ServiceA implements LazyGhostInterface
{
    public function __construct(private ServiceB $serviceB) {}
    
    public function doSomething(): void
    {
        // ServiceB is a lazy proxy, created when needed
        $this->serviceB->doWork();
    }
}

class ServiceB
{
    public function __construct(private ServiceA $serviceA) {}
    
    public function doWork(): void
    {
        echo "Working...";
    }
}
```

### Using Setter Injection

```php
<?php
class ServiceA
{
    private ?ServiceB $serviceB = null;
    
    public function setServiceB(ServiceB $serviceB): void
    {
        $this->serviceB = $serviceB;
    }
}

class ServiceB
{
    public function __construct(private ServiceA $serviceA) {}
}

// Setup plugin
class CircularDependencyPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        if ($service instanceof ServiceA) {
            $container = $context->getContainer();
            $serviceB = $container->get(ServiceB::class);
            $service->setServiceB($serviceB);
        }
    }
}
```

## Performance Optimization

### ProtoContext Caching

```php
<?php
use Concept\Singularity\Context\Cache\ProtoContextCacheInterface;

class CachedContextBuilder extends ContextBuilder
{
    public function __construct(
        private ProtoContextCacheInterface $cache,
        Config $config
    ) {
        parent::__construct($config);
    }
    
    public function build(string $serviceId, array $dependencyStack = []): ProtoContextInterface
    {
        $cacheKey = md5($serviceId . serialize($dependencyStack));
        
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        $context = parent::build($serviceId, $dependencyStack);
        $this->cache->set($cacheKey, $context);
        
        return $context;
    }
}
```

### Batch Service Creation

```php
<?php
class BatchContainer
{
    public function __construct(private Singularity $container) {}
    
    public function createBatch(array $serviceIds): array
    {
        $services = [];
        
        foreach ($serviceIds as $serviceId) {
            $services[$serviceId] = $this->container->get($serviceId);
        }
        
        return $services;
    }
    
    public function warmup(array $serviceIds): void
    {
        // Pre-create services to warm cache
        foreach ($serviceIds as $serviceId) {
            $this->container->get($serviceId);
        }
    }
}
```

## Testing Patterns

### Test Double Injection

```php
<?php
class TestContainer
{
    private Singularity $container;
    private array $mocks = [];
    
    public function __construct(Singularity $container)
    {
        $this->container = $container;
    }
    
    public function mock(string $serviceId, object $mock): void
    {
        $this->mocks[$serviceId] = $mock;
        
        // Override in container
        $registry = $this->container->getServiceRegistry();
        $registry->set($serviceId, $mock);
    }
    
    public function get(string $serviceId): object
    {
        if (isset($this->mocks[$serviceId])) {
            return $this->mocks[$serviceId];
        }
        
        return $this->container->get($serviceId);
    }
}

// Usage in tests
class UserServiceTest extends TestCase
{
    private TestContainer $container;
    
    protected function setUp(): void
    {
        $this->container = new TestContainer(new Singularity(new Config()));
        
        // Mock dependencies
        $mockDb = $this->createMock(DatabaseInterface::class);
        $mockDb->method('query')->willReturn([]);
        
        $this->container->mock(DatabaseInterface::class, $mockDb);
    }
    
    public function testUserService(): void
    {
        $service = $this->container->get(UserService::class);
        // Test with mocked database
    }
}
```

### Spy Plugin

```php
<?php
class SpyPlugin extends AbstractPlugin
{
    private static array $createdServices = [];
    
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        self::$createdServices[] = [
            'class' => get_class($service),
            'service_id' => $context->getServiceId(),
            'timestamp' => microtime(true)
        ];
    }
    
    public static function getCreatedServices(): array
    {
        return self::$createdServices;
    }
    
    public static function reset(): void
    {
        self::$createdServices = [];
    }
}

// In tests
SpyPlugin::reset();
$service = $container->get(UserService::class);
$created = SpyPlugin::getCreatedServices();
$this->assertCount(3, $created); // UserService + 2 dependencies
```

## Best Practices Summary

### 1. Prefer Composition Over Inheritance

```php
// ✅ Good
class UserService
{
    public function __construct(
        private DatabaseInterface $db,
        private LoggerInterface $logger
    ) {}
}

// ❌ Avoid
class UserService extends BaseService
{
    // Deep inheritance hierarchies are hard to maintain
}
```

### 2. Use Interfaces for Dependencies

```php
// ✅ Good
public function __construct(private LoggerInterface $logger) {}

// ❌ Avoid
public function __construct(private FileLogger $logger) {}
```

### 3. Keep Services Focused

```php
// ✅ Good - Single responsibility
class UserRepository {}
class UserValidator {}
class UserNotifier {}

// ❌ Avoid - Too many responsibilities
class UserManager {
    // Handles persistence, validation, notifications, etc.
}
```

### 4. Document Service Requirements

```php
/**
 * Payment Service
 * 
 * Dependencies:
 * - PaymentGatewayInterface: Payment processor
 * - LoggerInterface: Activity logging
 * 
 * Lifecycle: Shared (singleton)
 * 
 * Configuration:
 * - apiKey: string - Gateway API key
 * - timeout: int - Request timeout (default: 30s)
 */
class PaymentService {}
```

### 5. Test Service Integration

Always test that services work correctly when resolved from the container.

## Configuration Organization

For packages and applications, you can organize configuration across multiple files using concept/config directives.

### Configuration Directives

- **`@include(path)`** - Include content from another file (works in nested files, paths relative to current file)
- **`@import`** - Import multiple files with optional glob patterns (only works in the first/main file)
- **`${VAR}`** - Environment variable substitution

### Package-Level Organization (Optional)

Packages can optionally split their configuration for better maintainability:

```
vendor/acme/my-package/
├── concept.json                      # Package entry point
├── etc/
│   ├── sdi.json                     # DI root config
│   └── sdi/
│       ├── plugin-manager.json      # Plugins
│       ├── preference.json          # Preferences
│       └── packages/
│           ├── dep1.json            # Dependency 1 config
│           └── dep2.json            # Dependency 2 config
```

**Package concept.json:**
```json
{
  "singularity": "@include(etc/sdi.json)"
}
```

**etc/sdi.json:**
```json
{
  "settings": {
    "plugin-manager": "@include(sdi/plugin-manager.json)"
  },
  "package": {
    "acme/my-package": {
      "preference": "@include(sdi/preference.json)"
    }
  }
}
```

### Application-Level Organization with @import

Applications can use `@import` to merge multiple configuration sources:

```json
{
  "@import": [
    "singularity.json",
    "database.json",
    "cache.json"
  ],
  "app": {
    "name": "${APP_NAME}",
    "env": "${APP_ENV}"
  }
}
```

**Note:** `@import` only works in the first file. Use `@include` for nested file inclusion.

This approach provides:
- Clear separation of concerns
- Easier team collaboration
- Better version control
- Environment-specific configuration with `${VAR}`
- Package isolation and reusability

See [Configuration Guide](configuration.md#advanced-configuration-techniques) for complete examples.

## Next Steps

- [API Reference](api-reference.md) - Complete API documentation
- [Configuration](configuration.md) - Advanced configuration patterns
- [Plugins](plugins.md) - Custom plugin development
