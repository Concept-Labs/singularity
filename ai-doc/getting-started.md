# Getting Started with Singularity DI

## Installation

### Via Composer

```bash
composer require concept-labs/singularity
```

### Requirements

- PHP 8.0 or higher
- Composer
- PSR-11 compatible environment

## Basic Setup

### 1. Create Configuration

Create a `config.json` file:

```json
{
  "singularity": {
    "preference": {
      "App\\Database\\DatabaseInterface": {
        "class": "App\\Database\\MySQLDatabase"
      }
    }
  }
}
```

### 2. Initialize Container

```php
use Concept\Singularity\Singularity;
use Concept\Config\Config;

// Load configuration
$config = new Config();
$config->load('config.json');

// Create container
$container = new Singularity($config);
```

### 3. Retrieve Services

```php
// Get a service (will create or return cached instance)
$database = $container->get('App\\Database\\DatabaseInterface');

// Check if service exists
if ($container->has('App\\Database\\DatabaseInterface')) {
    // Service is available
}

// Create a fresh instance (bypass cache)
$freshDatabase = $container->create('App\\Database\\DatabaseInterface');
```

## Complete Example: Simple Application

### Step 1: Define Interfaces

```php
<?php
namespace App\Service;

interface LoggerInterface {
    public function log(string $message): void;
}

interface DatabaseInterface {
    public function query(string $sql): array;
}

interface UserRepositoryInterface {
    public function findById(int $id): ?array;
}
```

### Step 2: Implement Classes

```php
<?php
namespace App\Service;

class FileLogger implements LoggerInterface {
    public function __construct(private string $logPath = '/tmp/app.log') {}
    
    public function log(string $message): void {
        file_put_contents($this->logPath, date('Y-m-d H:i:s') . " - " . $message . PHP_EOL, FILE_APPEND);
    }
}

class MySQLDatabase implements DatabaseInterface {
    private \PDO $pdo;
    
    public function __construct(
        private LoggerInterface $logger,
        string $dsn = 'mysql:host=localhost;dbname=app',
        string $username = 'root',
        string $password = ''
    ) {
        $this->pdo = new \PDO($dsn, $username, $password);
        $this->logger->log('Database connected');
    }
    
    public function query(string $sql): array {
        $this->logger->log("Executing: $sql");
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

class UserRepository implements UserRepositoryInterface {
    public function __construct(
        private DatabaseInterface $database,
        private LoggerInterface $logger
    ) {}
    
    public function findById(int $id): ?array {
        $this->logger->log("Finding user: $id");
        $results = $this->database->query("SELECT * FROM users WHERE id = $id");
        return $results[0] ?? null;
    }
}
```

### Step 3: Configure Container

Create `config.json`:

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\LoggerInterface": {
        "class": "App\\Service\\FileLogger",
        "shared": true
      },
      "App\\Service\\DatabaseInterface": {
        "class": "App\\Service\\MySQLDatabase",
        "shared": true,
        "arguments": {
          "dsn": "mysql:host=localhost;dbname=myapp",
          "username": "dbuser",
          "password": "dbpass"
        }
      },
      "App\\Service\\UserRepositoryInterface": {
        "class": "App\\Service\\UserRepository",
        "shared": true
      }
    }
  }
}
```

### Step 4: Use the Container

```php
<?php
require 'vendor/autoload.php';

use Concept\Singularity\Singularity;
use Concept\Config\Config;
use App\Service\UserRepositoryInterface;

// Initialize container
$config = new Config();
$config->load('config.json');
$container = new Singularity($config);

// Get user repository (all dependencies are auto-injected)
$userRepo = $container->get(UserRepositoryInterface::class);

// Use the service
$user = $userRepo->findById(1);
if ($user) {
    echo "Found user: " . $user['name'] . "\n";
}
```

## Working Without Configuration (Autowiring)

If your classes have type-hinted dependencies, you can use them without configuration:

```php
<?php
class SimpleLogger {
    public function log(string $message): void {
        echo $message . PHP_EOL;
    }
}

class SimpleService {
    public function __construct(private SimpleLogger $logger) {}
    
    public function doWork(): void {
        $this->logger->log("Working...");
    }
}

// No configuration needed!
$container = new Singularity(new Config());

// Container will automatically resolve dependencies
$service = $container->get(SimpleService::class);
$service->doWork(); // Outputs: "Working..."
```

## Using Dependency Injection Method

Implement `InjectableInterface` to use method injection:

```php
<?php
use Concept\Singularity\Contract\Initialization\InjectableInterface;

class EmailService implements InjectableInterface {
    private LoggerInterface $logger;
    private MailerInterface $mailer;
    
    // Dependencies injected via __di() method
    public function __di(LoggerInterface $logger, MailerInterface $mailer): void {
        $this->logger = $logger;
        $this->mailer = $mailer;
    }
    
    public function sendEmail(string $to, string $subject, string $body): void {
        $this->logger->log("Sending email to: $to");
        $this->mailer->send($to, $subject, $body);
    }
}
```

Configuration:

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

The `DependencyInjection` plugin will automatically call `__di()` method after instantiation.

## Using Auto-Configuration

Implement `AutoConfigureInterface` for post-construction initialization:

```php
<?php
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;

class CacheService implements AutoConfigureInterface {
    private array $cache = [];
    
    // Called automatically after instantiation
    public function __configure(): void {
        // Load cache from file, connect to Redis, etc.
        $this->cache = json_decode(file_get_contents('cache.json'), true) ?? [];
    }
    
    public function get(string $key): mixed {
        return $this->cache[$key] ?? null;
    }
}
```

## Service Lifecycle Examples

### Singleton (Shared) Services

```php
// First call creates the instance
$logger1 = $container->get(LoggerInterface::class);

// Second call returns the same instance
$logger2 = $container->get(LoggerInterface::class);

var_dump($logger1 === $logger2); // true
```

### Fresh Instances

```php
// Always create a new instance
$db1 = $container->create(DatabaseInterface::class);
$db2 = $container->create(DatabaseInterface::class);

var_dump($db1 === $db2); // false
```

### Prototype Pattern

```php
<?php
use Concept\Singularity\Contract\Lifecycle\PrototypeInterface;

class RequestContext implements PrototypeInterface {
    private array $data = [];
    
    public function prototype(): static {
        return clone $this;
    }
    
    public function setData(array $data): void {
        $this->data = $data;
    }
}

// First call creates and caches the template
$context1 = $container->get(RequestContext::class);

// Subsequent calls return clones
$context2 = $container->get(RequestContext::class);

var_dump($context1 === $context2); // false (different instances)
```

## Constructor Argument Override

You can override constructor arguments when calling `create()`:

```php
$database = $container->create(
    DatabaseInterface::class,
    [
        'dsn' => 'mysql:host=testserver;dbname=test',
        'username' => 'testuser',
        'password' => 'testpass'
    ]
);
```

## Common Patterns

### Repository Pattern

```php
interface RepositoryInterface {
    public function find(int $id);
    public function save(object $entity): void;
}

class UserRepository implements RepositoryInterface {
    public function __construct(private DatabaseInterface $db) {}
    
    public function find(int $id) {
        return $this->db->query("SELECT * FROM users WHERE id = $id");
    }
    
    public function save(object $entity): void {
        // Save logic
    }
}

// Container handles all dependencies
$repo = $container->get(UserRepository::class);
```

### Service Locator Pattern (Anti-Pattern - Avoid)

```php
// DON'T DO THIS - service locator is an anti-pattern
class BadService {
    public function __construct(private ContainerInterface $container) {}
    
    public function doSomething() {
        $db = $this->container->get(DatabaseInterface::class);
        // ...
    }
}

// DO THIS INSTEAD - inject dependencies directly
class GoodService {
    public function __construct(private DatabaseInterface $db) {}
    
    public function doSomething() {
        // Use $this->db directly
    }
}
```

### Factory Pattern

```php
interface CarFactoryInterface {
    public function create(string $model): CarInterface;
}

class CarFactory implements CarFactoryInterface {
    public function __construct(private ContainerInterface $container) {}
    
    public function create(string $model): CarInterface {
        // Here container usage is acceptable - factory pattern
        return $this->container->create(CarInterface::class, ['model' => $model]);
    }
}
```

## Debugging Tips

### Check Service Registration

```php
if ($container->has(MyService::class)) {
    echo "Service is registered\n";
} else {
    echo "Service not found\n";
}
```

### Inspect Configuration

```php
$config = $container->getConfig();
$preferences = $config->get('singularity.preference');
print_r($preferences);
```

### Enable Logging Plugin

Create a custom logging plugin to track service creation:

```php
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

class DebugPlugin extends AbstractPlugin {
    public static function before(ProtoContextInterface $context, mixed $args = null): void {
        error_log("Creating: " . $context->getServiceId());
    }
    
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void {
        error_log("Created: " . get_class($service));
    }
}
```

Register in config:

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "DebugPlugin": {}
        }
      }
    }
  }
}
```

## Next Steps

- [Configuration Guide](configuration.md) - Learn about advanced configuration
- [Plugin System](plugins.md) - Create custom plugins
- [Context Builder](context-builder.md) - Understand dependency resolution
- [Advanced Usage](advanced-usage.md) - Master advanced features
