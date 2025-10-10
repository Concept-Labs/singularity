# Context Builder and Dependency Resolution

## Overview

The `ContextBuilder` is responsible for analyzing service requests and building a `ProtoContext` that contains all the metadata needed to create a service instance. It merges configuration from multiple sources and resolves dependencies.

## How It Works

### Service Resolution Flow

```
User requests: $container->get(UserServiceInterface::class)
                    ↓
        ContextBuilder::build()
                    ↓
    ┌───────────────────────────────────┐
    │ 1. Identify namespace & package   │
    └───────────────────────────────────┘
                    ↓
    ┌───────────────────────────────────┐
    │ 2. Load package configuration     │
    └───────────────────────────────────┘
                    ↓
    ┌───────────────────────────────────┐
    │ 3. Apply namespace overrides      │
    └───────────────────────────────────┘
                    ↓
    ┌───────────────────────────────────┐
    │ 4. Apply global preferences       │
    └───────────────────────────────────┘
                    ↓
    ┌───────────────────────────────────┐
    │ 5. Determine concrete class       │
    └───────────────────────────────────┘
                    ↓
    ┌───────────────────────────────────┐
    │ 6. Resolve constructor arguments  │
    └───────────────────────────────────┘
                    ↓
    ┌───────────────────────────────────┐
    │ 7. Create ProtoContext object     │
    └───────────────────────────────────┘
                    ↓
            Service Creation
```

## Configuration Hierarchy

### Priority Levels (Highest to Lowest)

1. **Global Preferences** - Defined in `singularity.preference`
2. **Namespace Overrides** - Defined in `singularity.namespace.<Namespace>.override`
3. **Package Configuration** - Defined in package's `concept.json`
4. **Autowiring** - Automatic resolution via reflection

### Example: Multi-Level Configuration

#### Package Config (`vendor/my-package/concept.json`)

```json
{
  "singularity": {
    "preference": {
      "MyPackage\\Logger\\LoggerInterface": {
        "class": "MyPackage\\Logger\\FileLogger",
        "arguments": {
          "path": "/var/log/package.log"
        }
      }
    }
  }
}
```

#### Application Config (`config.json`)

```json
{
  "singularity": {
    "namespace": {
      "App\\Admin\\": {
        "preference": {
          "MyPackage\\Logger\\LoggerInterface": {
            "class": "MyPackage\\Logger\\DatabaseLogger"
          }
        }
      }
    },
    "preference": {
      "MyPackage\\Logger\\LoggerInterface": {
        "class": "MyPackage\\Logger\\SyslogLogger",
        "arguments": {
          "facility": "LOG_USER"
        }
      }
    }
  }
}
```

#### Resolution Results

```php
// Services in App\Admin namespace
$adminService = $container->get('App\\Admin\\SomeService');
// Logger dependency resolves to: DatabaseLogger

// Other services
$otherService = $container->get('App\\Public\\OtherService');
// Logger dependency resolves to: SyslogLogger (global preference)

// If no global preference was set
$packageService = $container->get('ThirdParty\\Service');
// Logger dependency resolves to: FileLogger (package default)
```

## ProtoContext Structure

### What is ProtoContext?

`ProtoContext` is a data structure containing all information needed to create a service:

```php
interface ProtoContextInterface
{
    // Service identifiers
    public function getServiceId(): string;
    public function getServiceClass(): string;
    public function getSharedId(): string;
    
    // Reflection
    public function getReflection(): ReflectionClass;
    public function getReflectionMethods(?int $filter = null): array;
    public function getReflectionMethod(string $name): ?ReflectionMethod;
    
    // Configuration
    public function getPreferenceConfig(): ConfigInterface;
    public function getPreferenceData(): array;
    public function getPreferenceArguments(): array;
    public function hasPreferenceArgument(string $name): bool;
    public function getPreferenceArgument(string $name, mixed $default = null): mixed;
    
    // Dependencies
    public function getDependencyStack(): array;
    public function getContainer(): SingularityInterface;
    
    // Plugins
    public function getPlugins(): iterable;
    public function hasPlugins(): bool;
    public function isPluginDisabled(string $plugin): bool;
    
    // Factory
    public function getServiceFactory(): ?callable;
    public function setServiceFactory(callable $factory): static;
    
    // Metadata
    public function inflate(array $metaData): static;
    public function getMetaData(?string $key = null): mixed;
    
    // Plugin control
    public function stopPluginPropagation(string $type): static;
    public function isPluginPropagationStopped(string $type): bool;
}
```

### ProtoContext Creation Example

```php
// Internal container process
$protoContext = $contextBuilder->build(
    serviceId: 'App\\Service\\UserService',
    dependencyStack: ['App\\Controller\\UserController']
);

// ProtoContext now contains:
// - serviceId: 'App\\Service\\UserService'
// - serviceClass: 'App\\Service\\UserService' (or configured class)
// - reflection: ReflectionClass of serviceClass
// - preferenceConfig: Merged configuration
// - plugins: List of applicable plugins
// - dependencyStack: ['App\\Controller\\UserController', 'App\\Service\\UserService']
```

## Dependency Resolution

### Constructor Autowiring

#### Basic Autowiring

```php
class UserService
{
    public function __construct(
        private LoggerInterface $logger,
        private DatabaseInterface $database
    ) {}
}

// Container automatically resolves:
// - LoggerInterface → configured logger implementation
// - DatabaseInterface → configured database implementation
```

#### With Scalar Arguments

```php
class EmailService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $fromAddress,
        private int $timeout = 30
    ) {}
}
```

Configuration:

```json
{
  "singularity": {
    "preference": {
      "EmailService": {
        "class": "EmailService",
        "arguments": {
          "fromAddress": "noreply@example.com",
          "timeout": 60
        }
      }
    }
  }
}
```

### Nested Dependencies

```php
class UserController
{
    public function __construct(
        private UserService $userService
    ) {}
}

class UserService
{
    public function __construct(
        private UserRepository $repository,
        private LoggerInterface $logger
    ) {}
}

class UserRepository
{
    public function __construct(
        private DatabaseInterface $database
    ) {}
}

// Dependency tree:
// UserController
//   └─ UserService
//       ├─ UserRepository
//       │   └─ DatabaseInterface
//       └─ LoggerInterface
```

When you request `UserController`:

```php
$controller = $container->get(UserController::class);

// Container resolves in order:
// 1. DatabaseInterface
// 2. UserRepository (with DatabaseInterface)
// 3. LoggerInterface
// 4. UserService (with UserRepository and LoggerInterface)
// 5. UserController (with UserService)
```

### Circular Dependency Detection

```php
class ServiceA
{
    public function __construct(private ServiceB $serviceB) {}
}

class ServiceB
{
    public function __construct(private ServiceA $serviceA) {}
}

// This will throw CircularDependencyException
try {
    $container->get(ServiceA::class);
} catch (CircularDependencyException $e) {
    echo $e->getMessage();
    // "Circular dependency detected: ServiceA -> ServiceB -> ServiceA"
}
```

**Solution:** Use setter injection or lazy loading:

```php
class ServiceA
{
    private ServiceB $serviceB;
    
    public function setServiceB(ServiceB $serviceB): void
    {
        $this->serviceB = $serviceB;
    }
}

// Or use LazyGhost
use Concept\Singularity\Contract\Factory\LazyGhostInterface;

class ServiceA implements LazyGhostInterface
{
    public function __construct(private ServiceB $serviceB) {}
}
```

## Argument Resolution Strategies

### 1. Configured Arguments (Highest Priority)

```json
{
  "singularity": {
    "preference": {
      "DatabaseService": {
        "arguments": {
          "dsn": "mysql:host=localhost;dbname=app"
        }
      }
    }
  }
}
```

### 2. Type-Hinted Dependencies

```php
public function __construct(
    LoggerInterface $logger  // Resolved automatically
) {}
```

### 3. Default Values

```php
public function __construct(
    string $host = 'localhost',  // Uses default if not configured
    int $port = 3306
) {}
```

### 4. Runtime Override

```php
$service = $container->create(DatabaseService::class, [
    'dsn' => 'mysql:host=testserver;dbname=test'
]);
```

## Context Builder in Action

### Example: Building Context for a Service

```php
// Request
$service = $container->get('App\\Service\\PaymentService');

// Step 1: ContextBuilder analyzes the request
// - serviceId: 'App\\Service\\PaymentService'
// - namespace: 'App\\Service\\'
// - package: determined from composer.json

// Step 2: Load package configuration
$packageConfig = [
    'class' => 'App\\Service\\PaymentService'
];

// Step 3: Apply namespace overrides
$namespaceConfig = [
    'arguments' => [
        'gateway' => [
            'type' => 'service',
            'preference' => 'App\\Gateway\\TestGateway'
        ]
    ]
];

// Step 4: Apply global preferences
$globalConfig = [
    'shared' => true,
    'arguments' => [
        'apiKey' => 'sk_test_123'
    ]
];

// Step 5: Merge configurations (global > namespace > package)
$mergedConfig = [
    'class' => 'App\\Service\\PaymentService',
    'shared' => true,
    'arguments' => [
        'apiKey' => 'sk_test_123',
        'gateway' => [
            'type' => 'service',
            'preference' => 'App\\Gateway\\TestGateway'
        ]
    ]
];

// Step 6: Analyze constructor
class PaymentService {
    public function __construct(
        PaymentGatewayInterface $gateway,
        string $apiKey,
        LoggerInterface $logger  // Not in config, will be auto-resolved
    ) {}
}

// Step 7: Resolve all arguments
// - gateway: Resolve App\\Gateway\\TestGateway
// - apiKey: Use 'sk_test_123' from config
// - logger: Auto-resolve LoggerInterface

// Step 8: Create ProtoContext with all metadata
// Step 9: Return to container for service creation
```

## Advanced Context Features

### Metadata Injection

Plugins can add metadata to context for use by other plugins:

```php
class MetadataPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $context->inflate([
            'created_at' => time(),
            'created_by' => 'MetadataPlugin',
            'custom_flag' => true
        ]);
    }
}

class ConsumerPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        $createdAt = $context->getMetaData('created_at');
        $customFlag = $context->getMetaData('custom_flag');
        
        if ($customFlag) {
            // Do something
        }
    }
}
```

### Custom Service Factory

Override default instantiation:

```php
class CustomFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        // Custom instantiation logic
        return new CustomService(...$args);
    }
}
```

### Custom Service Factory

Factories can be set at runtime via plugins or by implementing custom factory classes:

```php
class FactoryPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        // Set custom factory callable
        $context->setServiceFactory(function($serviceClass, $args) {
            return new $serviceClass(...$args);
        });
    }
}
```

**Note:** The `setServiceFactory()` method on ProtoContext allows runtime factory customization through plugins. There is no `factory` configuration node.

### Dependency Stack

Track the chain of dependencies:

```php
class StackAwareService
{
    public function __construct(ContainerInterface $container)
    {
        // Access dependency stack via plugin
    }
}

class StackPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $stack = $context->getDependencyStack();
        
        // Log dependency chain
        error_log("Creating: " . $context->getServiceId());
        error_log("Called by: " . implode(' -> ', $stack));
    }
}
```

## Performance Optimization

### Context Caching

```php
// ProtoContext can be cached to avoid repeated configuration resolution
use Concept\Singularity\Context\Cache\ProtoContextCache;

$cache = new ProtoContextCache();

// First call - builds context
$context1 = $contextBuilder->build('UserService');
$cache->set('UserService', $context1);

// Second call - retrieve from cache
if ($cache->has('UserService')) {
    $context2 = $cache->get('UserService');
    // Much faster than building again
}
```

### Lazy Resolution

```php
// Don't resolve dependencies until actually needed
class LazyService
{
    private ?ExpensiveService $expensive = null;
    
    private function getExpensive(): ExpensiveService
    {
        if ($this->expensive === null) {
            $this->expensive = $this->container->get(ExpensiveService::class);
        }
        return $this->expensive;
    }
}
```

## Debugging Context Resolution

### Enable Debug Mode

```php
class DebugContextBuilder extends ContextBuilder
{
    public function build(string $serviceId, array $dependencyStack = []): ProtoContextInterface
    {
        error_log("Building context for: $serviceId");
        
        $context = parent::build($serviceId, $dependencyStack);
        
        error_log("Resolved to: " . $context->getServiceClass());
        error_log("Arguments: " . json_encode($context->getPreferenceArguments()));
        
        return $context;
    }
}
```

### Inspect Context

```php
$context = $contextBuilder->build('UserService');

// Service info
var_dump($context->getServiceId());        // 'UserService'
var_dump($context->getServiceClass());     // 'App\\Service\\UserService'

// Configuration
var_dump($context->getPreferenceData());   // All config data
var_dump($context->getPreferenceArguments()); // Constructor arguments

// Dependencies
var_dump($context->getDependencyStack());  // Parent services

// Reflection
$reflection = $context->getReflection();
var_dump($reflection->getConstructor()->getParameters());
```

## Best Practices

### 1. Use Interfaces for Service IDs

```php
// Good: Request by interface
$logger = $container->get(LoggerInterface::class);

// Avoid: Request by concrete class (less flexible)
$logger = $container->get(FileLogger::class);
```

### 2. Keep Dependency Trees Shallow

```php
// Good: 2-3 levels deep
Controller → Service → Repository

// Avoid: Deep nesting (5+ levels)
A → B → C → D → E → F
```

### 3. Use Constructor Injection

```php
// Good: Dependencies in constructor
class UserService
{
    public function __construct(
        private LoggerInterface $logger
    ) {}
}

// Avoid: Service locator pattern
class UserService
{
    public function __construct(
        private ContainerInterface $container
    ) {
        $this->logger = $container->get(LoggerInterface::class);
    }
}
```

### 4. Avoid Circular Dependencies

Design your classes to avoid circular references. Use events, observers, or lazy loading if needed.

### 5. Document Required Arguments

```php
/**
 * Payment Service
 * 
 * Required arguments:
 * - apiKey: string - Payment gateway API key
 * - gateway: PaymentGatewayInterface - Payment processor
 * - timeout: int - Request timeout in seconds (default: 30)
 */
class PaymentService
{
    public function __construct(
        string $apiKey,
        PaymentGatewayInterface $gateway,
        int $timeout = 30
    ) {}
}
```

## Next Steps

- [Lifecycle Management](lifecycle.md) - Service lifecycle patterns
- [Factories](factories.md) - Custom factory implementations
- [Advanced Usage](advanced-usage.md) - Complex scenarios
