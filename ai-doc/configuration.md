# Configuration Reference

## Configuration Structure

Singularity DI uses a hierarchical configuration system that allows defining service bindings and behaviors at multiple levels.

## Basic Configuration Schema

```json
{
  "singularity": {
    "preference": {
      "<ServiceID>": {
        "class": "<ConcreteClass>",
        "shared": true|false,
        "weak": true|false,
        "arguments": {},
        "plugins": {},
        "factory": "<FactoryClass>",
        "reference": "<config-path>"
      }
    },
    "namespace": {
      "<Namespace>": {
        "require": {},
        "override": {}
      }
    },
    "settings": {
      "plugin-manager": {
        "plugins": {}
      }
    }
  }
}
```

## Global Preferences

Global preferences have the highest priority and override all other configurations.

### Simple Class Binding

```json
{
  "singularity": {
    "preference": {
      "App\\Logger\\LoggerInterface": {
        "class": "App\\Logger\\FileLogger"
      }
    }
  }
}
```

### Shared (Singleton) Services

```json
{
  "singularity": {
    "preference": {
      "App\\Database\\DatabaseInterface": {
        "class": "App\\Database\\MySQLDatabase",
        "shared": true
      }
    }
  }
}
```

**Behavior:**
- First `get()` call creates the instance
- Subsequent calls return the same instance
- Service is cached in ServiceRegistry

### Weak Shared Services

```json
{
  "singularity": {
    "preference": {
      "App\\Cache\\CacheInterface": {
        "class": "App\\Cache\\RedisCache",
        "shared": true,
        "weak": true
      }
    }
  }
}
```

**Behavior:**
- Instance is cached using `WeakReference`
- If no strong references exist, PHP garbage collector can reclaim memory
- Next `get()` call will create a new instance if old one was collected
- Useful for long-running processes to prevent memory leaks

### Constructor Arguments

#### Primitive Values

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\EmailService": {
        "class": "App\\Service\\SmtpEmailService",
        "arguments": {
          "host": "smtp.example.com",
          "port": 587,
          "encryption": "tls",
          "timeout": 30
        }
      }
    }
  }
}
```

Corresponding class:

```php
class SmtpEmailService {
    public function __construct(
        string $host,
        int $port,
        string $encryption,
        int $timeout
    ) {
        // ...
    }
}
```

#### Service Dependencies

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\UserService": {
        "class": "App\\Service\\UserService",
        "arguments": {
          "repository": {
            "type": "service",
            "preference": "App\\Repository\\UserRepositoryInterface"
          },
          "logger": {
            "type": "service",
            "preference": "Psr\\Log\\LoggerInterface"
          }
        }
      }
    }
  }
}
```

Corresponding class:

```php
class UserService {
    public function __construct(
        UserRepositoryInterface $repository,
        LoggerInterface $logger
    ) {
        // ...
    }
}
```

#### Mixed Arguments

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\PaymentService": {
        "class": "App\\Service\\StripePaymentService",
        "arguments": {
          "apiKey": "sk_test_xxxxx",
          "gateway": {
            "type": "service",
            "preference": "App\\Gateway\\PaymentGatewayInterface"
          },
          "timeout": 60
        }
      }
    }
  }
}
```

## Namespace Configuration

Namespace configuration allows you to define service bindings for all classes within a specific namespace.

### Basic Namespace Override

```json
{
  "singularity": {
    "namespace": {
      "App\\Module\\Admin\\": {
        "override": {
          "App\\Auth\\AuthInterface": {
            "class": "App\\Auth\\AdminAuth"
          }
        }
      },
      "App\\Module\\Public\\": {
        "override": {
          "App\\Auth\\AuthInterface": {
            "class": "App\\Auth\\PublicAuth"
          }
        }
      }
    }
  }
}
```

**Result:**
- Services in `App\Module\Admin\*` will receive `AdminAuth`
- Services in `App\Module\Public\*` will receive `PublicAuth`
- Other services use default binding (if defined)

### Namespace with Package Requirements

```json
{
  "singularity": {
    "namespace": {
      "App\\Payment\\": {
        "require": {
          "vendor/payment-gateway": {
            "version": "^2.0"
          }
        },
        "override": {
          "PaymentProcessorInterface": {
            "class": "App\\Payment\\StripeProcessor"
          }
        }
      }
    }
  }
}
```

## Plugin Configuration

### Global Plugins

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "MyApp\\DI\\Plugin\\LoggingPlugin": {
            "enabled": true,
            "logPath": "/var/log/di.log"
          },
          "MyApp\\DI\\Plugin\\CachingPlugin": {
            "enabled": true,
            "ttl": 3600
          }
        }
      }
    }
  }
}
```

### Service-Specific Plugins

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\ExpensiveService": {
        "class": "App\\Service\\ExpensiveService",
        "plugins": {
          "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": {
            "enabled": true
          }
        }
      }
    }
  }
}
```

### Disable Plugin for Specific Service

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\SimpleService": {
        "class": "App\\Service\\SimpleService",
        "plugins": {
          "MyApp\\Plugin\\ValidationPlugin": false
        }
      }
    }
  }
}
```

## Factory Configuration

### Custom Factory Class

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

Factory implementation:

```php
<?php
namespace App\Factory;

use Concept\Singularity\Factory\FactoryInterface;

class UserFactory implements FactoryInterface {
    public function create(string $serviceId, array $args = []): object {
        // Custom instantiation logic
        $user = new \App\Model\User();
        $user->initialize();
        return $user;
    }
}
```

### Factory with Arguments

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\CacheService": {
        "factory": "App\\Factory\\CacheFactory",
        "arguments": {
          "driver": "redis",
          "prefix": "app_"
        }
      }
    }
  }
}
```

## Configuration References

Reuse configuration blocks with references:

```json
{
  "singularity": {
    "references": {
      "database-config": {
        "class": "App\\Database\\PDODatabase",
        "shared": true,
        "arguments": {
          "dsn": "mysql:host=localhost;dbname=app",
          "username": "root",
          "password": "secret"
        }
      }
    },
    "preference": {
      "App\\Database\\ReadDatabase": {
        "reference": "database-config"
      },
      "App\\Database\\WriteDatabase": {
        "reference": "database-config",
        "arguments": {
          "dsn": "mysql:host=writeserver;dbname=app"
        }
      }
    }
  }
}
```

**Note:** Referenced configuration can be partially overridden.

## Environment-Specific Configuration

### Development Environment

```json
{
  "singularity": {
    "preference": {
      "App\\Logger\\LoggerInterface": {
        "class": "App\\Logger\\ConsoleLogger",
        "arguments": {
          "level": "DEBUG"
        }
      },
      "App\\Database\\DatabaseInterface": {
        "class": "App\\Database\\SQLiteDatabase",
        "arguments": {
          "path": "/tmp/dev.db"
        }
      }
    }
  }
}
```

### Production Environment

```json
{
  "singularity": {
    "preference": {
      "App\\Logger\\LoggerInterface": {
        "class": "App\\Logger\\FileLogger",
        "arguments": {
          "level": "ERROR",
          "path": "/var/log/app.log"
        }
      },
      "App\\Database\\DatabaseInterface": {
        "class": "App\\Database\\MySQLDatabase",
        "shared": true,
        "arguments": {
          "dsn": "mysql:host=prodserver;dbname=production",
          "username": "${DB_USER}",
          "password": "${DB_PASS}"
        }
      }
    }
  }
}
```

### Loading Environment-Specific Config

```php
$env = getenv('APP_ENV') ?: 'development';
$configFile = "config.{$env}.json";

$config = new Config();
$config->load($configFile);

$container = new Singularity($config);
```

## Multi-Tenant Configuration

```json
{
  "singularity": {
    "namespace": {
      "App\\Tenant\\Alpha\\": {
        "override": {
          "App\\Database\\DatabaseInterface": {
            "class": "App\\Database\\MySQLDatabase",
            "arguments": {
              "dsn": "mysql:host=localhost;dbname=tenant_alpha"
            }
          },
          "App\\Storage\\StorageInterface": {
            "class": "App\\Storage\\S3Storage",
            "arguments": {
              "bucket": "tenant-alpha-files"
            }
          }
        }
      },
      "App\\Tenant\\Beta\\": {
        "override": {
          "App\\Database\\DatabaseInterface": {
            "class": "App\\Database\\PostgreSQLDatabase",
            "arguments": {
              "dsn": "pgsql:host=localhost;dbname=tenant_beta"
            }
          },
          "App\\Storage\\StorageInterface": {
            "class": "App\\Storage\\LocalStorage",
            "arguments": {
              "path": "/var/www/tenant-beta/storage"
            }
          }
        }
      }
    }
  }
}
```

## Configuration Priority

The container merges configuration from multiple sources in this order (lowest to highest priority):

1. **Package configuration** (`concept.json` in vendor packages)
2. **Namespace overrides** (namespace-specific bindings)
3. **Global preferences** (application-level bindings)
4. **Runtime overrides** (arguments passed to `create()`)

### Example

```json
// Package: vendor/concept-labs/logger/concept.json
{
  "singularity": {
    "preference": {
      "Psr\\Log\\LoggerInterface": {
        "class": "Concept\\Logger\\FileLogger"
      }
    }
  }
}

// Application: config.json
{
  "singularity": {
    "namespace": {
      "App\\Admin\\": {
        "override": {
          "Psr\\Log\\LoggerInterface": {
            "class": "App\\Logger\\AdminLogger"
          }
        }
      }
    },
    "preference": {
      "Psr\\Log\\LoggerInterface": {
        "class": "App\\Logger\\ApplicationLogger"
      }
    }
  }
}
```

**Resolution:**
- Services in `App\Admin\*`: Use `AdminLogger`
- Other services: Use `ApplicationLogger`
- If global preference not set: Use `FileLogger` from package

## Advanced Configuration Patterns

### Conditional Service Binding

```php
// In your bootstrap code
$config = new Config();
$config->load('config.json');

// Add conditional binding
if (getenv('USE_CACHE') === 'true') {
    $config->set('singularity.preference.CacheInterface', [
        'class' => 'App\\Cache\\RedisCache'
    ]);
} else {
    $config->set('singularity.preference.CacheInterface', [
        'class' => 'App\\Cache\\NullCache'
    ]);
}

$container = new Singularity($config);
```

### Dynamic Configuration

```php
class ConfigBuilder {
    private Config $config;
    
    public function __construct() {
        $this->config = new Config();
    }
    
    public function forDatabase(string $driver): self {
        $class = match($driver) {
            'mysql' => 'App\\Database\\MySQLDatabase',
            'pgsql' => 'App\\Database\\PostgreSQLDatabase',
            'sqlite' => 'App\\Database\\SQLiteDatabase',
            default => throw new \InvalidArgumentException("Unknown driver: $driver")
        };
        
        $this->config->set('singularity.preference.DatabaseInterface', [
            'class' => $class,
            'shared' => true
        ]);
        
        return $this;
    }
    
    public function withLogger(string $type): self {
        $this->config->set('singularity.preference.LoggerInterface', [
            'class' => "App\\Logger\\{$type}Logger"
        ]);
        
        return $this;
    }
    
    public function build(): Config {
        return $this->config;
    }
}

// Usage
$config = (new ConfigBuilder())
    ->forDatabase('mysql')
    ->withLogger('File')
    ->build();
    
$container = new Singularity($config);
```

## Configuration Validation

```php
class ConfigValidator {
    public function validate(Config $config): array {
        $errors = [];
        $preferences = $config->get('singularity.preference') ?? [];
        
        foreach ($preferences as $serviceId => $preference) {
            if (!isset($preference['class'])) {
                $errors[] = "Missing 'class' for service: $serviceId";
                continue;
            }
            
            if (!class_exists($preference['class'])) {
                $errors[] = "Class not found: {$preference['class']} for service: $serviceId";
            }
        }
        
        return $errors;
    }
}

// Usage
$validator = new ConfigValidator();
$errors = $validator->validate($config);

if (!empty($errors)) {
    throw new \RuntimeException("Configuration errors:\n" . implode("\n", $errors));
}
```

## Best Practices

### 1. Use Interfaces for Service IDs

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\UserServiceInterface": {
        "class": "App\\Service\\UserService"
      }
    }
  }
}
```

### 2. Mark Stateless Services as Shared

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\ValidationService": {
        "class": "App\\Service\\ValidationService",
        "shared": true
      }
    }
  }
}
```

### 3. Use Weak References for Large Objects

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\ImageProcessor": {
        "class": "App\\Service\\ImageProcessor",
        "shared": true,
        "weak": true
      }
    }
  }
}
```

### 4. Organize by Environment

```
config/
  ├── config.json (common settings)
  ├── config.development.json
  ├── config.production.json
  └── config.testing.json
```

### 5. Use Configuration References for Reusability

Avoid duplicating configuration blocks by using references.

## Common Pitfalls

### ❌ Incorrect: Circular Dependencies

```json
{
  "singularity": {
    "preference": {
      "ServiceA": {
        "arguments": {
          "serviceB": {"type": "service", "preference": "ServiceB"}
        }
      },
      "ServiceB": {
        "arguments": {
          "serviceA": {"type": "service", "preference": "ServiceA"}
        }
      }
    }
  }
}
```

**Solution:** Refactor to remove circular dependency or use lazy loading.

### ❌ Incorrect: Hardcoded Values

```json
{
  "singularity": {
    "preference": {
      "DatabaseInterface": {
        "arguments": {
          "password": "hardcoded_password_123"
        }
      }
    }
  }
}
```

**Solution:** Use environment variables or secure configuration.

### ❌ Incorrect: Missing Type Hints

```php
// This won't work well with autowiring
class BadService {
    public function __construct($dependency) {} // No type hint
}
```

**Solution:** Always use type hints for autowiring to work properly.

## Next Steps

- [Plugin System](plugins.md) - Extend container behavior
- [Context Builder](context-builder.md) - Understand resolution process
- [Advanced Usage](advanced-usage.md) - Master complex scenarios
