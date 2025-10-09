# Service Lifecycle Management

## Overview

Singularity DI provides flexible lifecycle management for services, supporting multiple patterns from singleton to prototype. Understanding lifecycle options helps you optimize memory usage and control object creation.

## Lifecycle Patterns

### 1. Transient (Default)

**Behavior:** New instance created every time, not cached.

**When to Use:**
- Stateful objects that shouldn't be shared
- Objects with request-specific data
- Short-lived objects

**Example:**

```php
class RequestContext
{
    private array $data = [];
    
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}

// Each call creates a new instance
$context1 = $container->get(RequestContext::class);
$context2 = $container->get(RequestContext::class);

var_dump($context1 === $context2); // false
```

**Configuration:**

```json
{
  "singularity": {
    "preference": {
      "RequestContext": {
        "class": "RequestContext"
        // No "shared" property means transient by default
      }
    }
  }
}
```

### 2. Shared (Singleton)

**Behavior:** Single instance created and cached, reused for all requests.

**When to Use:**
- Stateless services
- Configuration objects
- Database connections
- Expensive-to-create objects

**Example:**

```php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;

class DatabaseConnection implements SharedInterface
{
    private \PDO $pdo;
    
    public function __construct(string $dsn, string $username, string $password)
    {
        $this->pdo = new \PDO($dsn, $username, $password);
    }
    
    public function query(string $sql): array
    {
        return $this->pdo->query($sql)->fetchAll();
    }
}

// First call creates and caches
$db1 = $container->get(DatabaseConnection::class);

// Second call returns cached instance
$db2 = $container->get(DatabaseConnection::class);

var_dump($db1 === $db2); // true
```

**Configuration:**

```json
{
  "singularity": {
    "preference": {
      "DatabaseConnection": {
        "class": "DatabaseConnection",
        "shared": true
      }
    }
  }
}
```

**Via Interface:**

```php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;

class ConfigService implements SharedInterface
{
    // Automatically treated as shared
}
```

### 3. Weak Shared

**Behavior:** Singleton that can be garbage collected when not referenced.

**When to Use:**
- Long-running processes (workers, daemons)
- Services used infrequently
- Large objects that can be recreated
- Memory-sensitive applications

**Example:**

```php
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\Contract\Lifecycle\Shared\WeakInterface;

class ImageProcessor implements SharedInterface, WeakInterface
{
    private array $imageCache = [];
    
    public function process(string $imagePath): void
    {
        // Process large image
        $this->imageCache[] = imagecreatefromjpeg($imagePath);
    }
}

$processor = $container->get(ImageProcessor::class);
$processor->process('large-image.jpg');

// After this, if no other references exist, PHP can garbage collect
unset($processor);

// Next call creates a fresh instance
$newProcessor = $container->get(ImageProcessor::class);
```

**Configuration:**

```json
{
  "singularity": {
    "preference": {
      "ImageProcessor": {
        "class": "ImageProcessor",
        "shared": true,
        "weak": true
      }
    }
  }
}
```

**How It Works:**

```php
// Internal container storage
$this->registry->set(
    $serviceId,
    new \WeakReference($service)  // Weak reference, can be GC'd
);

// Later retrieval
$weakRef = $this->registry->get($serviceId);
$service = $weakRef->get();  // Returns null if GC'd

if ($service === null) {
    // Create new instance
}
```

### 4. Prototype

**Behavior:** One template instance cached, clones returned on each request.

**When to Use:**
- Objects with complex initialization
- Need multiple instances with same initial state
- Cloneable objects (implement `__clone()`)

**Example:**

```php
use Concept\Singularity\Contract\Lifecycle\PrototypeInterface;

class QueryBuilder implements PrototypeInterface
{
    private array $where = [];
    private array $orderBy = [];
    
    public function prototype(): static
    {
        return clone $this;
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
    
    public function __clone()
    {
        // Reset state for new instance
        $this->where = [];
        $this->orderBy = [];
    }
}

// First call creates template
$query1 = $container->get(QueryBuilder::class);
$query1->where('status', '=', 'active');

// Second call returns clone (fresh state)
$query2 = $container->get(QueryBuilder::class);

var_dump($query1 === $query2); // false
var_dump($query2->where); // [] (empty, not from $query1)
```

**Configuration:**

```json
{
  "singularity": {
    "preference": {
      "QueryBuilder": {
        "class": "QueryBuilder"
        // Implementing PrototypeInterface is enough
      }
    }
  }
}
```

## Lifecycle Comparison

| Pattern | Cache | Memory | Use Case | Example |
|---------|-------|--------|----------|---------|
| Transient | ❌ No | High (if many instances) | Request-specific data | HTTP Request |
| Shared | ✅ Yes | Low (single instance) | Stateless services | Logger, Config |
| Weak Shared | ✅ Yes (temporary) | Medium | Rarely used services | Cache, Image Processor |
| Prototype | ✅ Template | Medium | Cloneable objects | Query Builder, DTO |

## Service Registry

The `ServiceRegistry` manages instance storage and retrieval.

### Registry Operations

```php
interface ServiceRegistryInterface
{
    /**
     * Store a service instance
     */
    public function set(string $serviceId, object $service, bool $weak = false): void;
    
    /**
     * Retrieve a service instance
     */
    public function get(string $serviceId): ?object;
    
    /**
     * Check if service is registered
     */
    public function has(string $serviceId): bool;
    
    /**
     * Remove service from registry
     */
    public function remove(string $serviceId): void;
    
    /**
     * Clear all services
     */
    public function clear(): void;
}
```

### Manual Registry Management

```php
// Get registry from container (if exposed)
$registry = $container->getServiceRegistry();

// Check if service is cached
if ($registry->has('MyService')) {
    echo "Service is cached\n";
}

// Remove from cache (forces recreation)
$registry->remove('MyService');

// Clear all cached services
$registry->clear();
```

## Advanced Lifecycle Patterns

### Scoped Services

Create services scoped to specific contexts:

```php
class ScopedContainer
{
    private Singularity $container;
    private ServiceRegistry $scopeRegistry;
    
    public function __construct(Singularity $container)
    {
        $this->container = $container;
        $this->scopeRegistry = new ServiceRegistry();
    }
    
    public function get(string $serviceId): object
    {
        // Check scope registry first
        if ($this->scopeRegistry->has($serviceId)) {
            return $this->scopeRegistry->get($serviceId);
        }
        
        // Create from parent container
        $service = $this->container->create($serviceId);
        
        // Store in scope registry
        $this->scopeRegistry->set($serviceId, $service);
        
        return $service;
    }
    
    public function clearScope(): void
    {
        $this->scopeRegistry->clear();
    }
}

// Usage
$scope = new ScopedContainer($container);

// Services are scoped to this instance
$service1 = $scope->get('MyService');
$service2 = $scope->get('MyService');
var_dump($service1 === $service2); // true (within scope)

// Clear scope
$scope->clearScope();

// New instance created
$service3 = $scope->get('MyService');
var_dump($service1 === $service3); // false (different scope)
```

### Request-Scoped Services

```php
class RequestScope
{
    private static ?ServiceRegistry $registry = null;
    
    public static function start(): void
    {
        self::$registry = new ServiceRegistry();
    }
    
    public static function end(): void
    {
        if (self::$registry) {
            self::$registry->clear();
            self::$registry = null;
        }
    }
    
    public static function getRegistry(): ?ServiceRegistry
    {
        return self::$registry;
    }
}

// In your request handler
RequestScope::start();

try {
    // Handle request
    $response = $controller->handle($request);
} finally {
    // Cleanup request-scoped services
    RequestScope::end();
}
```

### Pooled Services

Create a pool of reusable instances:

```php
class ServicePool
{
    private Singularity $container;
    private array $pool = [];
    private int $maxSize;
    
    public function __construct(Singularity $container, int $maxSize = 10)
    {
        $this->container = $container;
        $this->maxSize = $maxSize;
    }
    
    public function acquire(string $serviceId): object
    {
        // Try to get from pool
        if (!empty($this->pool[$serviceId])) {
            return array_pop($this->pool[$serviceId]);
        }
        
        // Create new instance
        return $this->container->create($serviceId);
    }
    
    public function release(string $serviceId, object $service): void
    {
        // Return to pool if not full
        if (!isset($this->pool[$serviceId])) {
            $this->pool[$serviceId] = [];
        }
        
        if (count($this->pool[$serviceId]) < $this->maxSize) {
            // Reset service state if needed
            if (method_exists($service, 'reset')) {
                $service->reset();
            }
            
            $this->pool[$serviceId][] = $service;
        }
    }
}

// Usage
$pool = new ServicePool($container, 5);

// Get from pool
$worker = $pool->acquire(Worker::class);
$worker->process($task);

// Return to pool
$pool->release(Worker::class, $worker);
```

### Lazy Initialization

Defer service creation until first use:

```php
class LazyService
{
    private ?ExpensiveService $expensive = null;
    
    public function __construct(private Singularity $container) {}
    
    private function getExpensive(): ExpensiveService
    {
        if ($this->expensive === null) {
            $this->expensive = $this->container->get(ExpensiveService::class);
        }
        return $this->expensive;
    }
    
    public function doWork(): void
    {
        // Service only created when needed
        $this->getExpensive()->process();
    }
}
```

**With LazyGhost Plugin:**

```php
use Concept\Singularity\Contract\Factory\LazyGhostInterface;

class ExpensiveService implements LazyGhostInterface
{
    public function __construct()
    {
        // Expensive initialization
        sleep(5);
    }
    
    public function process(): void
    {
        // Work
    }
}

// Service is NOT created yet (proxy returned)
$service = $container->get(ExpensiveService::class);

// NOW it's created (on first method call)
$service->process();
```

## Memory Management

### Memory Leak Prevention

```php
// Problem: Service holds large data
class DataProcessor implements SharedInterface
{
    private array $cache = [];
    
    public function process(array $data): void
    {
        $this->cache[] = $data; // Memory leak!
    }
}

// Solution 1: Use WeakInterface
class DataProcessor implements SharedInterface, WeakInterface
{
    // Can be garbage collected
}

// Solution 2: Manual cleanup
class DataProcessor implements SharedInterface
{
    private array $cache = [];
    
    public function process(array $data): void
    {
        $this->cache[] = $data;
    }
    
    public function cleanup(): void
    {
        $this->cache = [];
    }
}

// Solution 3: Don't use shared
class DataProcessor // Not shared
{
    // New instance each time
}
```

### Monitoring Memory Usage

```php
class MemoryMonitorPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        $memory = memory_get_usage(true);
        $peak = memory_get_peak_usage(true);
        
        error_log(sprintf(
            "[Memory] %s - Current: %s, Peak: %s",
            get_class($service),
            self::formatBytes($memory),
            self::formatBytes($peak)
        ));
    }
    
    private static function formatBytes(int $bytes): string
    {
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }
}
```

## Lifecycle Events

### Initialization

```php
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;

class DatabaseService implements AutoConfigureInterface, SharedInterface
{
    private ?\PDO $connection = null;
    
    public function __configure(): void
    {
        // Called automatically after construction
        $this->connection = new \PDO(/*...*/);
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }
}
```

### Destruction

```php
class ResourceService implements SharedInterface
{
    private $handle;
    
    public function __construct()
    {
        $this->handle = fopen('resource.txt', 'w');
    }
    
    public function __destruct()
    {
        // Cleanup when object is destroyed
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
```

### Cloning

```php
class CloneableService implements PrototypeInterface
{
    private array $data = [];
    
    public function prototype(): static
    {
        return clone $this;
    }
    
    public function __clone()
    {
        // Deep clone if needed
        $this->data = array_map(
            fn($item) => clone $item,
            $this->data
        );
    }
}
```

## Best Practices

### 1. Choose the Right Lifecycle

```php
// Stateless → Shared
class ValidationService implements SharedInterface {}

// Stateful → Transient
class FormRequest {}

// Expensive creation → Shared
class DatabaseConnection implements SharedInterface {}

// Need multiple copies → Prototype
class QueryBuilder implements PrototypeInterface {}

// Rarely used → Weak Shared
class ReportGenerator implements SharedInterface, WeakInterface {}
```

### 2. Avoid Shared Stateful Services

```php
// ❌ BAD: Shared service with state
class UserSession implements SharedInterface
{
    private array $data = []; // Different users share same session!
}

// ✅ GOOD: Transient or scoped
class UserSession
{
    private array $data = []; // Each user gets own session
}
```

### 3. Clean Up Resources

```php
class FileHandler implements SharedInterface
{
    private $handle;
    
    public function __construct(string $filename)
    {
        $this->handle = fopen($filename, 'w');
    }
    
    public function write(string $data): void
    {
        fwrite($this->handle, $data);
    }
    
    public function __destruct()
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }
}
```

### 4. Document Lifecycle

```php
/**
 * Database Connection Pool
 * 
 * Lifecycle: Shared (Singleton)
 * Memory: Weak reference to allow GC in long-running processes
 * Cleanup: Connections closed on __destruct
 */
class ConnectionPool implements SharedInterface, WeakInterface
{
    // ...
}
```

### 5. Test Different Lifecycles

```php
class ServiceTest extends TestCase
{
    public function testSingletonBehavior(): void
    {
        $service1 = $this->container->get(MyService::class);
        $service2 = $this->container->get(MyService::class);
        
        $this->assertSame($service1, $service2);
    }
    
    public function testTransientBehavior(): void
    {
        $service1 = $this->container->get(TransientService::class);
        $service2 = $this->container->get(TransientService::class);
        
        $this->assertNotSame($service1, $service2);
    }
}
```

## Troubleshooting

### Service Not Cached

**Problem:** Service created multiple times despite `shared: true`

**Solutions:**
- Verify configuration is loaded
- Check if service implements `SharedInterface`
- Ensure `SharedPlugin` is enabled

### Memory Leaks

**Problem:** Memory grows continuously

**Solutions:**
- Use `WeakInterface` for large or rarely-used services
- Implement cleanup methods
- Use transient instead of shared for stateful services

### Unexpected Sharing

**Problem:** Service is shared when it shouldn't be

**Solutions:**
- Remove `SharedInterface` implementation
- Set `"shared": false` in configuration
- Use `create()` instead of `get()`

## Next Steps

- [Factories](factories.md) - Custom service factories
- [Contracts](contracts.md) - Built-in interfaces
- [Advanced Usage](advanced-usage.md) - Complex scenarios
