# Built-in Contracts and Interfaces

## Overview

Singularity DI provides a set of built-in interfaces (contracts) that services can implement to enable specific behaviors. These contracts are recognized by the plugin system and trigger corresponding functionality.

## Initialization Contracts

### InjectableInterface

Enables method-based dependency injection via the `__di()` method (deprecated) or methods marked with the `#[Injector]` attribute.

**Location:** `Concept\Singularity\Contract\Initialization\InjectableInterface`

**Plugin:** `DependencyInjection`

**Purpose:** Inject dependencies after construction when constructor injection is not suitable.

#### Interface Definition

```php
<?php
namespace Concept\Singularity\Contract\Initialization;

interface InjectableInterface
{
    const INJECT_METHOD = '__di';
    
    /**
     * Method to inject dependencies into the service
     * @deprecated Use #[Injector] attribute instead
     * @return void
     */
    // public function __di(): void;
}
```

#### Usage with #[Injector] Attribute (Recommended)

```php
<?php
use Concept\Singularity\Contract\Initialization\InjectableInterface;
use Concept\Singularity\Plugin\Attribute\Injector;

class EmailService implements InjectableInterface
{
    private LoggerInterface $logger;
    private MailerInterface $mailer;
    private EventDispatcherInterface $dispatcher;
    
    /**
     * Dependencies injected via #[Injector] attribute
     */
    #[Injector]
    public function setDependencies(
        LoggerInterface $logger,
        MailerInterface $mailer,
        EventDispatcherInterface $dispatcher
    ): void {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->dispatcher = $dispatcher;
    }
    
    public function send(string $to, string $subject, string $body): void
    {
        $this->logger->info("Sending email to: $to");
        $this->mailer->send($to, $subject, $body);
        $this->dispatcher->dispatch(new EmailSentEvent($to));
    }
}
```

#### Multiple Injection Methods

You can have multiple methods marked with `#[Injector]`:

```php
class ComplexService implements InjectableInterface
{
    #[Injector]
    public function injectCore(
        DatabaseInterface $db,
        LoggerInterface $logger
    ): void {
        $this->db = $db;
        $this->logger = $logger;
    }
    
    #[Injector]
    public function injectOptional(
        ?CacheInterface $cache = null,
        ?EventDispatcherInterface $events = null
    ): void {
        $this->cache = $cache;
        $this->events = $events;
    }
}
```

#### Legacy __di() Method (Deprecated)

```php
<?php
use Concept\Singularity\Contract\Initialization\InjectableInterface;

class LegacyService implements InjectableInterface
{
    private LoggerInterface $logger;
    
    /**
     * @deprecated Use #[Injector] attribute instead
     */
    public function __di(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
```

**Note:** The `__di()` method is deprecated. Use the `#[Injector]` attribute on public methods instead.

#### When to Use

- Constructor is already used for other purposes
- Circular dependency resolution (with lazy loading)
- Optional dependencies
- Testing (easy to mock)

#### Configuration

No special configuration needed - just implement the interface:

```json
{
  "singularity": {
    "preference": {
      "EmailService": {
        "class": "EmailService"
      }
    }
  }
}
```

### AutoConfigureInterface

Enables automatic post-construction configuration via the `__configure()` method.

**Location:** `Concept\Singularity\Contract\Initialization\AutoConfigureInterface`

**Plugin:** `AutoConfigure`

**Purpose:** Initialize service state after all dependencies are injected.

#### Interface Definition

```php
<?php
namespace Concept\Singularity\Contract\Initialization;

interface AutoConfigureInterface
{
    /**
     * Configure the service after instantiation
     *
     * @return void
     */
    public function __configure(): void;
}
```

#### Usage Example

```php
<?php
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;

class DatabaseService implements AutoConfigureInterface
{
    private \PDO $connection;
    
    public function __construct(
        string $dsn,
        string $username,
        string $password
    ) {
        $this->connection = new \PDO($dsn, $username, $password);
    }
    
    /**
     * Called automatically after construction
     */
    public function __configure(): void
    {
        // Set connection attributes
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        
        // Initialize database schema if needed
        $this->initializeSchema();
        
        // Load custom functions
        $this->loadCustomFunctions();
    }
    
    private function initializeSchema(): void
    {
        // Create tables if they don't exist
    }
    
    private function loadCustomFunctions(): void
    {
        // Register custom SQL functions
    }
}
```

#### When to Use

- Complex initialization logic
- Setup that requires all dependencies
- Resource allocation
- Cache warming

## Lifecycle Contracts

### SharedInterface

Marks a service as singleton (shared instance).

**Location:** `Concept\Singularity\Contract\Lifecycle\SharedInterface`

**Plugin:** `Shared`

**Purpose:** Create once, reuse everywhere.

#### Interface Definition

```php
<?php
namespace Concept\Singularity\Contract\Lifecycle;

interface SharedInterface
{
    // Marker interface - no methods required
}
```

#### Usage Example

```php
<?php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;

class ConfigService implements SharedInterface
{
    private array $config = [];
    
    public function __construct()
    {
        // Load configuration once
        $this->config = json_decode(file_get_contents('config.json'), true);
    }
    
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }
}

// First call creates instance
$config1 = $container->get(ConfigService::class);

// Second call returns same instance
$config2 = $container->get(ConfigService::class);

var_dump($config1 === $config2); // true
```

#### Configuration Alternative

```json
{
  "singularity": {
    "preference": {
      "ConfigService": {
        "class": "ConfigService",
        "shared": true
      }
    }
  }
}
```

### WeakInterface

Marks a shared service to use weak references (can be garbage collected).

**Location:** `Concept\Singularity\Contract\Lifecycle\Shared\WeakInterface`

**Plugin:** `Shared` (with weak reference support)

**Purpose:** Singleton that allows memory cleanup.

#### Interface Definition

```php
<?php
namespace Concept\Singularity\Contract\Lifecycle\Shared;

interface WeakInterface
{
    // Marker interface - no methods required
}
```

#### Usage Example

```php
<?php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\Contract\Lifecycle\Shared\WeakInterface;

class ImageProcessor implements SharedInterface, WeakInterface
{
    private array $imageCache = [];
    
    public function process(string $imagePath): void
    {
        // Process large image
        $image = imagecreatefromjpeg($imagePath);
        $this->imageCache[] = $image;
        
        // ... processing logic
    }
    
    public function __destruct()
    {
        // Cleanup images
        foreach ($this->imageCache as $image) {
            imagedestroy($image);
        }
    }
}
```

#### When to Use

- Long-running processes (workers, daemons)
- Services with large memory footprint
- Rarely used services
- Services that can be safely recreated

**Note:** Requires PHP 7.4+ for `WeakReference` support.

### PrototypeInterface

Enables prototype pattern - one template, multiple clones.

**Location:** `Concept\Singularity\Contract\Lifecycle\PrototypeInterface`

**Plugin:** `Prototype`

**Purpose:** Efficient creation of similar objects.

#### Interface Definition

```php
<?php
namespace Concept\Singularity\Contract\Lifecycle;

interface PrototypeInterface
{
    /**
     * Create a copy of this service
     *
     * @return static A clone of the service
     */
    public function prototype(): static;
}
```

#### Usage Example

```php
<?php
use Concept\Singularity\Contract\Lifecycle\PrototypeInterface;

class QueryBuilder implements PrototypeInterface
{
    private string $table = '';
    private array $where = [];
    private array $orderBy = [];
    private int $limit = 0;
    
    public function prototype(): static
    {
        return clone $this;
    }
    
    public function table(string $table): self
    {
        $this->table = $table;
        return $this;
    }
    
    public function where(string $column, string $operator, mixed $value): self
    {
        $this->where[] = [$column, $operator, $value];
        return $this;
    }
    
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = [$column, $direction];
        return $this;
    }
    
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }
    
    public function __clone()
    {
        // Reset state for new query
        $this->where = [];
        $this->orderBy = [];
        $this->limit = 0;
    }
    
    public function toSql(): string
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($this->where)) {
            $conditions = array_map(
                fn($w) => "{$w[0]} {$w[1]} ?",
                $this->where
            );
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if (!empty($this->orderBy)) {
            $orders = array_map(
                fn($o) => "{$o[0]} {$o[1]}",
                $this->orderBy
            );
            $sql .= " ORDER BY " . implode(', ', $orders);
        }
        
        if ($this->limit > 0) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        return $sql;
    }
}

// Usage
$query1 = $container->get(QueryBuilder::class);
$query1->table('users')->where('status', '=', 'active');

// Get fresh clone
$query2 = $container->get(QueryBuilder::class);
$query2->table('posts')->where('published', '=', true);

var_dump($query1 === $query2); // false
```

## Factory Contracts

### LazyGhostInterface

Enables lazy loading - service created only when first method is called.

**Location:** `Concept\Singularity\Contract\Factory\LazyGhostInterface`

**Plugin:** `LazyGhost`

**Purpose:** Defer expensive object creation.

#### Interface Definition

```php
<?php
namespace Concept\Singularity\Contract\Factory;

interface LazyGhostInterface
{
    // Marker interface - no methods required
}
```

#### Usage Example

```php
<?php
use Concept\Singularity\Contract\Factory\LazyGhostInterface;

class ExpensiveService implements LazyGhostInterface
{
    private array $data = [];
    
    public function __construct()
    {
        // Expensive initialization - only runs when method is called
        sleep(2);
        $this->data = $this->loadLargeDataset();
    }
    
    public function process(): void
    {
        // Work with data
        foreach ($this->data as $item) {
            // Process...
        }
    }
    
    private function loadLargeDataset(): array
    {
        // Load from database or file
        return [];
    }
}

// Returns a proxy - ExpensiveService NOT created yet
$service = $container->get(ExpensiveService::class);

// NOW ExpensiveService is created (on first method call)
$service->process();
```

#### Configuration

```json
{
  "singularity": {
    "preference": {
      "ExpensiveService": {
        "class": "ExpensiveService",
        "plugins": {
          "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": true
        }
      }
    }
  }
}
```

#### When to Use

- Services with expensive initialization
- Services that may not be used
- Circular dependency resolution
- Performance optimization

## Combining Contracts

Services can implement multiple contracts:

### Example 1: Shared + AutoConfigure

```php
<?php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;

class CacheService implements SharedInterface, AutoConfigureInterface
{
    private $redis;
    
    public function __construct(string $host, int $port)
    {
        $this->redis = new \Redis();
        $this->redis->connect($host, $port);
    }
    
    public function __configure(): void
    {
        // Configure after construction
        $this->redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_JSON);
        $this->redis->setOption(\Redis::OPT_PREFIX, 'app:');
    }
}
```

### Example 2: Shared + Weak + Injectable

```php
<?php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\Contract\Lifecycle\Shared\WeakInterface;
use Concept\Singularity\Contract\Initialization\InjectableInterface;

class SessionHandler implements 
    SharedInterface, 
    WeakInterface, 
    InjectableInterface
{
    private CacheInterface $cache;
    private LoggerInterface $logger;
    
    public function __di(
        CacheInterface $cache,
        LoggerInterface $logger
    ): void {
        $this->cache = $cache;
        $this->logger = $logger;
    }
}
```

### Example 3: Prototype + AutoConfigure

```php
<?php
use Concept\Singularity\Contract\Lifecycle\PrototypeInterface;
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;

class FormBuilder implements PrototypeInterface, AutoConfigureInterface
{
    private array $fields = [];
    private array $validators = [];
    
    public function __configure(): void
    {
        // Initialize default validators
        $this->validators = [
            'required' => new RequiredValidator(),
            'email' => new EmailValidator(),
        ];
    }
    
    public function prototype(): static
    {
        $clone = clone $this;
        $clone->fields = []; // Reset fields
        return $clone;
    }
}
```

## Contract Enforcement

The `Enforcement` plugin checks if services properly implement their contracts:

### Example Enforcement Plugin

```php
<?php
namespace Concept\Singularity\Plugin\ContractEnforce;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class Enforcement extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        
        // Verify service implements expected interface
        if (interface_exists($serviceId)) {
            if (!$service instanceof $serviceId) {
                throw new \RuntimeException(
                    sprintf(
                        "Service %s does not implement %s",
                        get_class($service),
                        $serviceId
                    )
                );
            }
        }
    }
}
```

## Creating Custom Contracts

You can create your own contracts:

### Example: TransactionalInterface

```php
<?php
namespace App\Contract;

interface TransactionalInterface
{
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
}
```

### Custom Plugin for Contract

```php
<?php
namespace App\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use App\Contract\TransactionalInterface;

class TransactionPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        if ($service instanceof TransactionalInterface) {
            // Auto-start transaction
            $service->beginTransaction();
        }
    }
}
```

### Service Implementation

```php
<?php
use App\Contract\TransactionalInterface;

class UserRepository implements TransactionalInterface
{
    private \PDO $db;
    
    public function beginTransaction(): void
    {
        $this->db->beginTransaction();
    }
    
    public function commit(): void
    {
        $this->db->commit();
    }
    
    public function rollback(): void
    {
        $this->db->rollBack();
    }
    
    public function save(User $user): void
    {
        // ... save logic
        $this->commit(); // Manual commit
    }
}
```

## Contract Summary

| Contract | Purpose | Plugin | Method Required |
|----------|---------|--------|-----------------|
| `InjectableInterface` | Method injection | DependencyInjection | `__di()` |
| `AutoConfigureInterface` | Post-construction init | AutoConfigure | `__configure()` |
| `SharedInterface` | Singleton pattern | Shared | None |
| `WeakInterface` | GC-friendly singleton | Shared | None |
| `PrototypeInterface` | Clone pattern | Prototype | `prototype()` |
| `LazyGhostInterface` | Lazy loading | LazyGhost | None |

## Best Practices

### 1. Use Appropriate Contracts

```php
// ✅ Stateless service → SharedInterface
class ValidationService implements SharedInterface {}

// ✅ Stateful → No SharedInterface
class HttpRequest {}

// ✅ Expensive → LazyGhostInterface
class ReportGenerator implements LazyGhostInterface {}
```

### 2. Document Contract Usage

```php
/**
 * User Service
 * 
 * Contracts:
 * - SharedInterface: Single instance shared across app
 * - InjectableInterface: Uses __di() for dependency injection
 * - AutoConfigureInterface: Initializes cache in __configure()
 */
class UserService implements 
    SharedInterface, 
    InjectableInterface, 
    AutoConfigureInterface
{
    // ...
}
```

### 3. Test Contract Behavior

```php
class UserServiceTest extends TestCase
{
    public function testImplementsSharedInterface(): void
    {
        $service1 = $this->container->get(UserService::class);
        $service2 = $this->container->get(UserService::class);
        
        $this->assertSame($service1, $service2);
    }
    
    public function testCallsConfigureMethod(): void
    {
        $service = $this->container->get(UserService::class);
        
        // Verify __configure() was called
        $this->assertTrue($service->isConfigured());
    }
}
```

### 4. Combine Contracts Wisely

```php
// ✅ Good combination
class CacheService implements SharedInterface, AutoConfigureInterface {}

// ⚠️ Questionable: Prototype + Shared don't make sense together
class BadService implements PrototypeInterface, SharedInterface {}
```

## Contract Enforcement with Aggregate Plugins

Singularity DI uses the `Enforcement` aggregate plugin to apply contract-specific plugins automatically. This pattern allows you to map contracts to their corresponding plugins.

### The Enforcement Plugin

The `Enforcement` plugin extends `AggregatePlugin` and maps contracts to plugins:

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "Concept\\Singularity\\Plugin\\ContractEnforce\\Enforcement": {
            "priority": 200,
            "*": {
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": {}
            },
            "Concept\\Singularity\\Contract\\Factory\\LazyGhostInterface": {
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": true
            },
            "Concept\\Singularity\\Contract\\Initialization\\AutoConfigureInterface": {
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Initialization\\AutoConfigure": true
            },
            "Concept\\Singularity\\Contract\\Initialization\\InjectableInterface": {
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Initialization\\DependencyInjection": true
            },
            "Concept\\Singularity\\Contract\\Lifecycle\\SharedInterface": {
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Lifecycle\\Shared": {
                "shared": true,
                "weak": true
              }
            },
            "Concept\\Singularity\\Contract\\Lifecycle\\PrototypeInterface": {
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Lifecycle\\Prototype": {}
            }
          }
        }
      }
    }
  }
}
```

### Understanding the Configuration

- **`"*"`**: Applies to all services (global strategy)
- **Contract mappings**: Maps each contract interface to its plugin
- **Plugin args**: `true`, `{}`, or configuration object enables the plugin; `false` disables it
- **Priority**: Controls when the plugin runs (higher = earlier)

### Custom Contract Example

You can add your own contracts to the enforcement configuration:

```php
<?php
namespace App\Contract;

interface CacheableInterface
{
    public function getCacheKey(): string;
    public function getCacheTtl(): int;
}
```

Add to configuration:

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "Concept\\Singularity\\Plugin\\ContractEnforce\\Enforcement": {
            "App\\Contract\\CacheableInterface": {
              "App\\Plugin\\CachingPlugin": {
                "ttl": 3600
              }
            }
          }
        }
      }
    }
  }
}
```

### Global Service Lifecycle Strategy

You can set a default lifecycle for ALL services using the `"*"` key:

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "Concept\\Singularity\\Plugin\\ContractEnforce\\Enforcement": {
            "*": {
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": {},
              "Concept\\Singularity\\Plugin\\ContractEnforce\\Lifecycle\\Shared": {
                "weak": true
              }
            }
          }
        }
      }
    }
  }
}
```

This makes ALL services:
- Lazy-loaded (via LazyGhost plugin)
- Shared with weak references (via Shared plugin)

**Note:** Individual service configurations can still override this global strategy.

## Next Steps

- [Advanced Usage](advanced-usage.md) - Complex patterns and scenarios
- [API Reference](api-reference.md) - Complete API documentation
- [Plugins](plugins.md) - Create custom plugins for contracts
