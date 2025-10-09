# Factory Pattern

## Overview

Factories in Singularity DI provide custom service instantiation logic. Instead of relying on automatic constructor injection, you can define how services are created, allowing for complex initialization scenarios.

## Factory Interface

```php
<?php
namespace Concept\Singularity\Factory;

interface FactoryInterface
{
    /**
     * Create a service instance
     * 
     * @param string $serviceId The service identifier
     * @param array $args The arguments to pass to the service
     * 
     * @return object The service instance
     */
    public function create(string $serviceId, array $args = []);
}
```

## Creating a Factory

### Basic Factory

```php
<?php
namespace App\Factory;

use Concept\Singularity\Factory\FactoryInterface;
use App\Model\User;

class UserFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        $user = new User();
        
        // Custom initialization
        $user->setCreatedAt(new \DateTime());
        $user->setStatus('active');
        
        // Apply arguments if provided
        if (isset($args['email'])) {
            $user->setEmail($args['email']);
        }
        
        return $user;
    }
}
```

### Factory with Dependencies

```php
<?php
namespace App\Factory;

use Concept\Singularity\Factory\FactoryInterface;
use Psr\Container\ContainerInterface;
use App\Service\DatabaseInterface;
use App\Model\Product;

class ProductFactory implements FactoryInterface
{
    public function __construct(
        private ContainerInterface $container,
        private DatabaseInterface $database
    ) {}
    
    public function create(string $serviceId, array $args = []): object
    {
        // Load data from database
        $productData = $this->database->query(
            "SELECT * FROM products WHERE id = ?",
            [$args['id'] ?? 0]
        );
        
        // Create product with data
        $product = new Product();
        $product->setId($productData['id']);
        $product->setName($productData['name']);
        $product->setPrice($productData['price']);
        
        // Inject additional services
        $logger = $this->container->get('LoggerInterface');
        $product->setLogger($logger);
        
        return $product;
    }
}
```

## Configuring Factories

### Simple Factory Configuration

```json
{
  "singularity": {
    "preference": {
      "App\\Model\\User": {
        "factory": "App\\Factory\\UserFactory"
      }
    }
  }
}
```

### Factory with Arguments

```json
{
  "singularity": {
    "preference": {
      "App\\Model\\Product": {
        "factory": "App\\Factory\\ProductFactory",
        "arguments": {
          "id": 1,
          "includeMetadata": true
        }
      }
    }
  }
}
```

## Using Factories

### From Container

```php
// Container uses configured factory
$user = $container->get('App\\Model\\User');

// Override arguments at runtime
$product = $container->create('App\\Model\\Product', [
    'id' => 42
]);
```

### Direct Factory Usage

```php
$factory = new UserFactory();
$user = $factory->create('App\\Model\\User', [
    'email' => 'user@example.com'
]);
```

## Factory Patterns

### Abstract Factory

```php
<?php
namespace App\Factory;

interface PaymentGatewayFactoryInterface
{
    public function createGateway(string $type): PaymentGatewayInterface;
}

class PaymentGatewayFactory implements PaymentGatewayFactoryInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {}
    
    public function createGateway(string $type): PaymentGatewayInterface
    {
        return match($type) {
            'stripe' => $this->container->get(StripeGateway::class),
            'paypal' => $this->container->get(PayPalGateway::class),
            'braintree' => $this->container->get(BraintreeGateway::class),
            default => throw new \InvalidArgumentException("Unknown gateway: $type")
        };
    }
}
```

Configuration:

```json
{
  "singularity": {
    "preference": {
      "App\\Factory\\PaymentGatewayFactoryInterface": {
        "class": "App\\Factory\\PaymentGatewayFactory",
        "shared": true
      }
    }
  }
}
```

Usage:

```php
$factory = $container->get(PaymentGatewayFactoryInterface::class);
$gateway = $factory->createGateway('stripe');
$gateway->charge(100.00);
```

### Builder Factory

```php
<?php
namespace App\Factory;

use App\Model\Report;
use App\Builder\ReportBuilder;

class ReportFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        $builder = new ReportBuilder();
        
        // Configure builder from arguments
        if (isset($args['title'])) {
            $builder->setTitle($args['title']);
        }
        
        if (isset($args['type'])) {
            $builder->setType($args['type']);
        }
        
        if (isset($args['filters'])) {
            foreach ($args['filters'] as $filter) {
                $builder->addFilter($filter);
            }
        }
        
        // Build and return report
        return $builder->build();
    }
}
```

### Multiton Factory

```php
<?php
namespace App\Factory;

class DatabaseConnectionFactory implements FactoryInterface
{
    private array $connections = [];
    
    public function create(string $serviceId, array $args = []): object
    {
        $connectionName = $args['connection'] ?? 'default';
        
        // Return existing connection if available
        if (isset($this->connections[$connectionName])) {
            return $this->connections[$connectionName];
        }
        
        // Create new connection
        $config = $this->getConnectionConfig($connectionName);
        $connection = new \PDO(
            $config['dsn'],
            $config['username'],
            $config['password']
        );
        
        // Store for reuse
        $this->connections[$connectionName] = $connection;
        
        return $connection;
    }
    
    private function getConnectionConfig(string $name): array
    {
        // Load from configuration
        return [
            'dsn' => "mysql:host=localhost;dbname={$name}",
            'username' => 'root',
            'password' => 'secret'
        ];
    }
}
```

### Lazy Factory

```php
<?php
namespace App\Factory;

class LazyServiceFactory implements FactoryInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {}
    
    public function create(string $serviceId, array $args = []): object
    {
        // Return a proxy that defers instantiation
        return new class($serviceId, $args, $this->container) {
            private ?object $instance = null;
            
            public function __construct(
                private string $serviceId,
                private array $args,
                private ContainerInterface $container
            ) {}
            
            public function __call(string $method, array $arguments)
            {
                return $this->getInstance()->$method(...$arguments);
            }
            
            public function __get(string $name)
            {
                return $this->getInstance()->$name;
            }
            
            private function getInstance(): object
            {
                if ($this->instance === null) {
                    // Create real instance on first access
                    $class = $this->serviceId;
                    $this->instance = new $class(...$this->args);
                }
                return $this->instance;
            }
        };
    }
}
```

## ServiceFactory

Singularity DI includes a built-in `ServiceFactory` for standard instantiation:

```php
<?php
namespace Concept\Singularity\Factory;

interface ServiceFactoryInterface
{
    /**
     * Create a service using reflection and dependency injection
     */
    public function createService(
        ProtoContextInterface $context,
        array $overrideArgs = []
    ): object;
}
```

### How ServiceFactory Works

```php
// 1. Get service class from context
$serviceClass = $context->getServiceClass();

// 2. Get reflection
$reflection = $context->getReflection();

// 3. Resolve constructor arguments
$constructor = $reflection->getConstructor();
$args = $this->resolveArguments($constructor, $context, $overrideArgs);

// 4. Create instance
$instance = $reflection->newInstanceArgs($args);

// 5. Return service
return $instance;
```

## Advanced Factory Techniques

### Factory with Validation

```php
<?php
namespace App\Factory;

class ValidatedUserFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        // Validate arguments
        if (empty($args['email'])) {
            throw new \InvalidArgumentException('Email is required');
        }
        
        if (!filter_var($args['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email format');
        }
        
        // Create user
        $user = new User();
        $user->setEmail($args['email']);
        
        if (isset($args['name'])) {
            $user->setName($args['name']);
        }
        
        return $user;
    }
}
```

### Factory with Event Dispatching

```php
<?php
namespace App\Factory;

use App\Event\ServiceCreatedEvent;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventAwareFactory implements FactoryInterface
{
    public function __construct(
        private EventDispatcherInterface $dispatcher
    ) {}
    
    public function create(string $serviceId, array $args = []): object
    {
        // Create service
        $service = new $serviceId(...$args);
        
        // Dispatch event
        $event = new ServiceCreatedEvent($service, $serviceId, $args);
        $this->dispatcher->dispatch($event);
        
        return $service;
    }
}
```

### Factory with Caching

```php
<?php
namespace App\Factory;

use Psr\SimpleCache\CacheInterface;

class CachedFactory implements FactoryInterface
{
    public function __construct(
        private CacheInterface $cache,
        private FactoryInterface $innerFactory,
        private int $ttl = 3600
    ) {}
    
    public function create(string $serviceId, array $args = []): object
    {
        $cacheKey = $this->getCacheKey($serviceId, $args);
        
        // Try cache first
        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        
        // Create service
        $service = $this->innerFactory->create($serviceId, $args);
        
        // Cache result
        $this->cache->set($cacheKey, $service, $this->ttl);
        
        return $service;
    }
    
    private function getCacheKey(string $serviceId, array $args): string
    {
        return md5($serviceId . serialize($args));
    }
}
```

### Factory with Decorators

```php
<?php
namespace App\Factory;

class DecoratorFactory implements FactoryInterface
{
    public function __construct(
        private ContainerInterface $container
    ) {}
    
    public function create(string $serviceId, array $args = []): object
    {
        // Create base service
        $service = new $serviceId(...$args);
        
        // Apply decorators based on configuration
        if ($args['enableLogging'] ?? false) {
            $logger = $this->container->get('LoggerInterface');
            $service = new LoggingDecorator($service, $logger);
        }
        
        if ($args['enableCaching'] ?? false) {
            $cache = $this->container->get('CacheInterface');
            $service = new CachingDecorator($service, $cache);
        }
        
        return $service;
    }
}
```

## Factory Plugin Integration

Factories can be set via plugins:

```php
<?php
namespace App\Plugin;

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use App\Factory\CustomFactory;

class FactoryPlugin extends AbstractPlugin
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $serviceId = $context->getServiceId();
        
        // Set custom factory for specific services
        if (str_starts_with($serviceId, 'App\\Model\\')) {
            $factory = new CustomFactory();
            $context->setServiceFactory(
                fn($class, $args) => $factory->create($class, $args)
            );
        }
    }
}
```

## Testing Factories

### Unit Test

```php
<?php
use PHPUnit\Framework\TestCase;
use App\Factory\UserFactory;

class UserFactoryTest extends TestCase
{
    private UserFactory $factory;
    
    protected function setUp(): void
    {
        $this->factory = new UserFactory();
    }
    
    public function testCreatesUser(): void
    {
        $user = $this->factory->create('App\\Model\\User', [
            'email' => 'test@example.com'
        ]);
        
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('active', $user->getStatus());
    }
    
    public function testSetsCreatedAt(): void
    {
        $user = $this->factory->create('App\\Model\\User', []);
        
        $this->assertNotNull($user->getCreatedAt());
        $this->assertInstanceOf(\DateTime::class, $user->getCreatedAt());
    }
}
```

### Integration Test

```php
<?php
use PHPUnit\Framework\TestCase;

class FactoryIntegrationTest extends TestCase
{
    private Singularity $container;
    
    protected function setUp(): void
    {
        $config = new Config();
        $config->load('test-config.json');
        $this->container = new Singularity($config);
    }
    
    public function testFactoryIsUsed(): void
    {
        // Factory should be called by container
        $user = $this->container->get('App\\Model\\User');
        
        $this->assertInstanceOf(User::class, $user);
        // Verify factory initialization
        $this->assertEquals('active', $user->getStatus());
    }
}
```

## Best Practices

### 1. Single Responsibility

Each factory should create one type of service:

```php
// ✅ Good
class UserFactory implements FactoryInterface { }
class ProductFactory implements FactoryInterface { }

// ❌ Bad
class GenericFactory implements FactoryInterface {
    public function create(string $serviceId, array $args = []): object {
        switch($serviceId) {
            case 'User': // ...
            case 'Product': // ...
            // Too many responsibilities
        }
    }
}
```

### 2. Use Type Hints

```php
// ✅ Good
public function create(string $serviceId, array $args = []): User
{
    return new User();
}

// ❌ Less specific
public function create(string $serviceId, array $args = []): object
{
    return new User();
}
```

### 3. Validate Arguments

```php
public function create(string $serviceId, array $args = []): object
{
    $requiredArgs = ['id', 'name'];
    foreach ($requiredArgs as $arg) {
        if (!isset($args[$arg])) {
            throw new \InvalidArgumentException("Missing required argument: $arg");
        }
    }
    
    return new Product($args['id'], $args['name']);
}
```

### 4. Document Factory Behavior

```php
/**
 * User Factory
 * 
 * Creates User instances with default values:
 * - status: 'active'
 * - createdAt: current timestamp
 * - role: 'user' (unless specified in arguments)
 * 
 * Required arguments: none
 * Optional arguments:
 * - email: string
 * - name: string
 * - role: string
 */
class UserFactory implements FactoryInterface
{
    // ...
}
```

### 5. Keep Factories Stateless

```php
// ✅ Good - stateless
class UserFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        return new User();
    }
}

// ❌ Bad - stateful (unless intentional like Multiton)
class StatefulFactory implements FactoryInterface
{
    private int $counter = 0;
    
    public function create(string $serviceId, array $args = []): object
    {
        $this->counter++; // State changes
        return new User($this->counter);
    }
}
```

## Common Use Cases

### Database Models

```php
class ModelFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        // Load from database
        $data = $this->db->find($args['id']);
        
        // Hydrate model
        return $serviceId::fromArray($data);
    }
}
```

### API Clients

```php
class ApiClientFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        $client = new ApiClient();
        $client->setBaseUrl($args['baseUrl']);
        $client->setApiKey($args['apiKey']);
        $client->setTimeout($args['timeout'] ?? 30);
        
        return $client;
    }
}
```

### Complex Objects

```php
class ReportFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        $report = new Report();
        $report->loadTemplate($args['template']);
        $report->setDataSource($args['dataSource']);
        $report->applyFilters($args['filters'] ?? []);
        $report->generate();
        
        return $report;
    }
}
```

## Next Steps

- [Contracts](contracts.md) - Built-in interfaces
- [Advanced Usage](advanced-usage.md) - Complex patterns
- [API Reference](api-reference.md) - Complete API documentation
