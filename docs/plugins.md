# Plugin System

## Overview

Singularity DI's plugin system allows you to intercept and modify the service creation process. Plugins can execute custom logic before and after service instantiation, enabling cross-cutting concerns like logging, caching, validation, and lazy loading.

## Plugin Interface

All plugins must implement `PluginInterface`:

```php
<?php
namespace Concept\Singularity\Plugin;

use Concept\Singularity\Context\ProtoContextInterface;

interface PluginInterface
{
    const BEFORE = 'before';
    const AFTER = 'after';

    /**
     * Called before service instantiation
     * 
     * @param ProtoContextInterface $context Service metadata and configuration
     * @param mixed $args Plugin-specific arguments
     */
    public static function before(ProtoContextInterface $context, mixed $args = null): void;

    /**
     * Called after service instantiation
     * 
     * @param object $service The created service instance
     * @param ProtoContextInterface $context Service metadata
     * @param mixed $args Plugin-specific arguments
     */
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void;
}
```

## AbstractPlugin

For convenience, extend `AbstractPlugin` to provide default empty implementations:

```php
<?php
namespace Concept\Singularity\Plugin;

use Concept\Singularity\Context\ProtoContextInterface;

abstract class AbstractPlugin implements PluginInterface
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        // Override if needed
    }

    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        // Override if needed
    }
}
```

## Creating a Custom Plugin

### Example 1: Logging Plugin

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class LoggingPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        $serviceClass = $context->getServiceClass();
        
        error_log("[DI] Creating service: $serviceId");
        error_log("[DI] Class: $serviceClass");
        
        // Log dependency stack to detect circular dependencies
        $stack = $context->getDependencyStack();
        if (count($stack) > 1) {
            error_log("[DI] Dependency chain: " . implode(' -> ', $stack));
        }
    }

    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        $class = get_class($service);
        error_log("[DI] Created instance of: $class");
        
        // Log memory usage
        $memory = memory_get_usage(true);
        error_log("[DI] Memory usage: " . round($memory / 1024 / 1024, 2) . " MB");
    }
}
```

### Example 2: Performance Monitoring Plugin

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class PerformancePlugin extends AbstractPlugin
{
    private static array $timers = [];

    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        self::$timers[$serviceId] = microtime(true);
    }

    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        $elapsed = microtime(true) - self::$timers[$serviceId];
        
        if ($elapsed > 0.1) { // Log if creation took more than 100ms
            error_log("[PERF] Slow service creation: $serviceId took " . 
                     round($elapsed * 1000, 2) . "ms");
        }
        
        unset(self::$timers[$serviceId]);
    }
}
```

### Example 3: Validation Plugin

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class ValidationPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        // Validate that service implements expected interface
        $expectedInterface = $context->getServiceId();
        
        if (interface_exists($expectedInterface)) {
            if (!$service instanceof $expectedInterface) {
                throw new \RuntimeException(
                    sprintf(
                        "Service %s does not implement %s",
                        get_class($service),
                        $expectedInterface
                    )
                );
            }
        }
    }
}
```

### Example 4: Auto-Initialization Plugin

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class AutoInitPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        // Call initialize() method if it exists
        if (method_exists($service, 'initialize')) {
            $service->initialize();
        }
        
        // Call setUp() for test doubles
        if (method_exists($service, 'setUp')) {
            $service->setUp();
        }
    }
}
```

## Registering Plugins

### Global Plugin Registration

Register plugins to run for all services:

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "MyApp\\DI\\Plugin\\LoggingPlugin": {},
          "MyApp\\DI\\Plugin\\PerformancePlugin": {},
          "MyApp\\DI\\Plugin\\ValidationPlugin": {}
        }
      }
    }
  }
}
```

### Plugin with Configuration

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "MyApp\\DI\\Plugin\\CachingPlugin": {
            "ttl": 3600,
            "driver": "redis",
            "prefix": "di_cache_"
          }
        }
      }
    }
  }
}
```

Access configuration in your plugin:

```php
class CachingPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        // $args contains the plugin configuration
        $ttl = $args['ttl'] ?? 3600;
        $driver = $args['driver'] ?? 'file';
        $prefix = $args['prefix'] ?? 'cache_';
        
        // Use configuration...
    }
}
```

### Service-Specific Plugins

Enable plugin only for specific services:

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\ExpensiveService": {
        "class": "App\\Service\\ExpensiveService",
        "plugins": {
          "MyApp\\DI\\Plugin\\CachingPlugin": {
            "enabled": true,
            "ttl": 7200
          }
        }
      }
    }
  }
}
```

### Disabling Plugins

Disable a global plugin for specific service:

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\SimpleService": {
        "class": "App\\Service\\SimpleService",
        "plugins": {
          "MyApp\\DI\\Plugin\\ValidationPlugin": false
        }
      }
    }
  }
}
```

## Built-in Plugins

Singularity DI includes several built-in plugins:

### DependencyInjection Plugin

Handles method injection via `__di()` method.

**Contract:** `InjectableInterface`

```php
use Concept\Singularity\Contract\Initialization\InjectableInterface;

class EmailService implements InjectableInterface
{
    private LoggerInterface $logger;
    private MailerInterface $mailer;
    
    public function __di(
        LoggerInterface $logger,
        MailerInterface $mailer
    ): void {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }
}
```

### AutoConfigure Plugin

Calls `__configure()` method after instantiation.

**Contract:** `AutoConfigureInterface`

```php
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;

class DatabaseService implements AutoConfigureInterface
{
    private \PDO $connection;
    
    public function __configure(): void
    {
        // Post-construction initialization
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->loadSchema();
    }
}
```

### Shared Plugin

Manages singleton behavior and caching.

**Contract:** `SharedInterface`

```php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;

class ConfigService implements SharedInterface
{
    // This service will be created once and reused
}
```

**With Weak Reference:**

```php
use Concept\Singularity\Contract\Lifecycle\Shared\WeakInterface;

class CacheService implements SharedInterface, WeakInterface
{
    // Can be garbage collected when not referenced
}
```

### Prototype Plugin

Handles prototype pattern via `prototype()` method.

**Contract:** `PrototypeInterface`

```php
use Concept\Singularity\Contract\Lifecycle\PrototypeInterface;

class RequestContext implements PrototypeInterface
{
    private array $data = [];
    
    public function prototype(): static
    {
        return clone $this;
    }
    
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
```

### LazyGhost Plugin

Creates lazy-loading proxy objects.

**Contract:** `LazyGhostInterface`

```php
use Concept\Singularity\Contract\Factory\LazyGhostInterface;

class ExpensiveService implements LazyGhostInterface
{
    // Real object is created only when methods are called
}
```

**Configuration:**

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\ExpensiveService": {
        "class": "App\\Service\\ExpensiveService",
        "plugins": {
          "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": true
        }
      }
    }
  }
}
```

## Advanced Plugin Patterns

### Plugin with State Management

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class StatefulPlugin extends AbstractPlugin
{
    private static array $state = [];
    private static int $counter = 0;

    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        self::$counter++;
        $serviceId = $context->getServiceId();
        
        self::$state[$serviceId] = [
            'order' => self::$counter,
            'timestamp' => microtime(true)
        ];
    }

    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        $state = self::$state[$serviceId];
        
        // Process based on state
        error_log("Service #{$state['order']} created at {$state['timestamp']}");
    }
    
    public static function getStats(): array
    {
        return [
            'total_created' => self::$counter,
            'services' => self::$state
        ];
    }
}
```

### Conditional Plugin Execution

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class ConditionalPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        // Only process services implementing specific interface
        if (!$service instanceof \MyApp\Contracts\ObservableInterface) {
            return;
        }
        
        // Register observer
        $observer = new \MyApp\Observer\ServiceObserver();
        $service->attach($observer);
    }
}
```

### Plugin with Dependency Injection

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use Psr\Container\ContainerInterface;

class ContainerAwarePlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        // Get container from context
        $container = $context->getContainer();
        
        // Inject additional dependencies
        if (method_exists($service, 'setEventDispatcher')) {
            $dispatcher = $container->get('EventDispatcherInterface');
            $service->setEventDispatcher($dispatcher);
        }
    }
}
```

### Plugin Chain with Stop Propagation

```php
<?php
namespace MyApp\DI\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class SecurityPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        
        // Check if service creation is allowed
        if (!self::isAuthorized($serviceId)) {
            // Stop plugin propagation
            $context->stopPluginPropagation(PluginInterface::BEFORE);
            throw new \RuntimeException("Unauthorized service creation: $serviceId");
        }
    }
    
    private static function isAuthorized(string $serviceId): bool
    {
        // Authorization logic
        return true;
    }
}
```

## Plugin Execution Order

Plugins are executed in the order they are registered:

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "PluginA": {},  // Executes first
          "PluginB": {},  // Executes second
          "PluginC": {}   // Executes third
        }
      }
    }
  }
}
```

**Before Hooks:** `PluginA::before` → `PluginB::before` → `PluginC::before`

**Service Creation:** Service is instantiated

**After Hooks:** `PluginA::after` → `PluginB::after` → `PluginC::after`

## Plugin Context Access

The `ProtoContextInterface` provides access to:

### Service Information

```php
$serviceId = $context->getServiceId();        // Original service identifier
$serviceClass = $context->getServiceClass();  // Concrete class to instantiate
$sharedId = $context->getSharedId();          // Cache key for shared services
```

### Reflection

```php
$reflection = $context->getReflection();                    // ReflectionClass
$methods = $context->getReflectionMethods();                // All methods
$constructor = $context->getReflectionMethod('__construct'); // Specific method
```

### Configuration

```php
$config = $context->getPreferenceConfig();    // Service configuration
$data = $context->getPreferenceData();        // Raw preference data
$args = $context->getPreferenceArguments();   // Constructor arguments

// Check specific argument
if ($context->hasPreferenceArgument('timeout')) {
    $timeout = $context->getPreferenceArgument('timeout', 30);
}
```

### Dependencies

```php
$stack = $context->getDependencyStack();  // Array of parent service IDs
$container = $context->getContainer();    // Container instance
```

### Plugins

```php
$plugins = $context->getPlugins();                        // All plugins
$hasPlugins = $context->hasPlugins();                     // Check if any plugins
$disabled = $context->isPluginDisabled('PluginClass');    // Check if disabled
```

### Metadata

```php
// Set metadata for use by other plugins
$context->inflate(['custom_key' => 'custom_value']);

// Get metadata
$value = $context->getMetaData('custom_key');
$allMeta = $context->getMetaData(); // All metadata
```

## Testing Plugins

### Unit Test Example

```php
<?php
use PHPUnit\Framework\TestCase;
use MyApp\DI\Plugin\LoggingPlugin;
use Concept\Singularity\Context\ProtoContext;

class LoggingPluginTest extends TestCase
{
    public function testBeforeLogsServiceCreation(): void
    {
        $context = $this->createMock(ProtoContext::class);
        $context->method('getServiceId')->willReturn('TestService');
        $context->method('getServiceClass')->willReturn('App\\Test\\TestService');
        
        // Capture log output
        $this->expectOutputRegex('/Creating service: TestService/');
        
        LoggingPlugin::before($context, null);
    }
    
    public function testAfterLogsCreatedInstance(): void
    {
        $service = new \stdClass();
        $context = $this->createMock(ProtoContext::class);
        
        $this->expectOutputRegex('/Created instance of: stdClass/');
        
        LoggingPlugin::after($service, $context, null);
    }
}
```

## Best Practices

### 1. Keep Plugins Focused

Each plugin should have a single responsibility:

✅ **Good:**
```php
class LoggingPlugin extends AbstractPlugin { /* ... */ }
class ValidationPlugin extends AbstractPlugin { /* ... */ }
class CachingPlugin extends AbstractPlugin { /* ... */ }
```

❌ **Bad:**
```php
class MegaPlugin extends AbstractPlugin {
    // Logging, validation, caching all in one
}
```

### 2. Use Static Methods

Plugins use static methods for performance - no plugin instance is created:

```php
public static function before(ProtoContextInterface $context, mixed $args = null): void
{
    // Static context only
}
```

### 3. Handle Exceptions Gracefully

```php
public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
{
    try {
        // Plugin logic
    } catch (\Throwable $e) {
        error_log("Plugin error: " . $e->getMessage());
        // Don't prevent service creation
    }
}
```

### 4. Document Plugin Arguments

```php
/**
 * Caching Plugin
 * 
 * Arguments:
 * - ttl: int - Cache TTL in seconds (default: 3600)
 * - driver: string - Cache driver (default: 'file')
 * - prefix: string - Cache key prefix (default: 'cache_')
 */
class CachingPlugin extends AbstractPlugin
{
    // ...
}
```

### 5. Use Type Checks

```php
public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
{
    if (!$service instanceof CacheableInterface) {
        return; // Skip non-cacheable services
    }
    
    // Process cacheable service
}
```

## Common Use Cases

### Logging and Monitoring

Track service creation for debugging and performance analysis.

### Lazy Loading

Defer expensive object creation until actually needed.

### Validation

Ensure services meet contract requirements.

### AOP (Aspect-Oriented Programming)

Implement cross-cutting concerns like transactions, security, caching.

### Testing

Inject mocks, spies, or test doubles.

### Resource Management

Initialize/cleanup resources (database connections, file handles).

## Next Steps

- [Context Builder](context-builder.md) - Understand dependency resolution
- [Advanced Usage](advanced-usage.md) - Complex plugin scenarios
- [API Reference](api-reference.md) - Complete plugin API
