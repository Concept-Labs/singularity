# Configuration Reference

## Configuration Structure

Singularity DI uses a hierarchical configuration system that allows defining service bindings and behaviors at multiple levels.

## Basic Configuration Schema

```json
{
  "singularity": {
    "package": {
      "<package-name>": {
        "preference": {
          "<ServiceID>": {
            "class": "<ConcreteClass>",
            "shared": true|false,
            "weak": true|false,
            "arguments": {},
            "plugins": {}
          }
        }
      }
    },
    "namespace": {
      "<Namespace>": {
        "require": {
          "vendor/package-name": {}
        }
      }
    },
    "preference": {
      "<ServiceID>": {
        "class": "<ConcreteClass>",
        "arguments": {},
        "plugins": {}
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

**Note:** 
- Configuration should primarily be defined at the **package level** in `concept.json` files
- Use **namespace** level mainly for declaring package dependencies via `require`
- Use **global preferences** sparingly for application-specific overrides
- The `@include()` and `@path.to.node` syntax is handled by the concept/config package for referencing other configuration values

## Package Configuration (Primary Strategy)

Package-level configuration is the **recommended approach** for defining service bindings. Each package should define its own services in its `concept.json` file.

### Why Package-Level Configuration?

- **Encapsulation**: Each package manages its own dependencies
- **Reusability**: Packages can be used across multiple projects with consistent behavior
- **Auto-Discovery**: Composer autoload PSR-4 mappings automatically generate namespace dependencies
- **Maintainability**: Changes to a package's services are contained within that package

### Basic Package Configuration

Create a `concept.json` in your package root:

```json
{
  "singularity": {
    "package": {
      "vendor/my-logger": {
        "preference": {
          "Vendor\\Logger\\LoggerInterface": {
            "class": "Vendor\\Logger\\FileLogger",
            "shared": true
          },
          "Vendor\\Logger\\FormatterInterface": {
            "class": "Vendor\\Logger\\JsonFormatter"
          }
        }
      }
    }
  }
}
```

### Real-World Package Example: Database Package

**Package:** `acme/database`  
**File:** `vendor/acme/database/concept.json`

```json
{
  "singularity": {
    "package": {
      "acme/database": {
        "preference": {
          "Acme\\Database\\ConnectionInterface": {
            "class": "Acme\\Database\\PDOConnection",
            "shared": true
          },
          "Acme\\Database\\QueryBuilderInterface": {
            "class": "Acme\\Database\\QueryBuilder"
          },
          "Acme\\Database\\MigrationRunnerInterface": {
            "class": "Acme\\Database\\MigrationRunner",
            "shared": true
          }
        }
      }
    }
  }
}
```

### Real-World Package Example: HTTP Client

**Package:** `acme/http-client`  
**File:** `vendor/acme/http-client/concept.json`

```json
{
  "singularity": {
    "package": {
      "acme/http-client": {
        "preference": {
          "Acme\\Http\\ClientInterface": {
            "class": "Acme\\Http\\GuzzleClient",
            "shared": true,
            "arguments": {
              "timeout": 30,
              "verify_ssl": true
            }
          },
          "Acme\\Http\\RequestFactoryInterface": {
            "class": "Acme\\Http\\RequestFactory"
          },
          "Acme\\Http\\ResponseParserInterface": {
            "class": "Acme\\Http\\JsonResponseParser"
          }
        }
      }
    }
  }
}
```

### Package with Settings

**Package:** `acme/cache`  
**File:** `vendor/acme/cache/concept.json`

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "Acme\\Cache\\Plugin\\CacheWarmer": {
            "warmup_on_boot": false
          }
        }
      }
    },
    "package": {
      "acme/cache": {
        "preference": {
          "Acme\\Cache\\CacheInterface": {
            "class": "Acme\\Cache\\RedisCache",
            "shared": true,
            "weak": true,
            "arguments": {
              "host": "localhost",
              "port": 6379,
              "database": 0
            }
          },
          "Acme\\Cache\\SerializerInterface": {
            "class": "Acme\\Cache\\JsonSerializer"
          }
        }
      }
    }
  }
}
```

## Global Preferences (Application-Level Overrides)

Global preferences should be used **sparingly** for application-specific overrides. Use them only when you need to override a package's default behavior for your specific application.

**Best Practice:** Prefer package-level configuration over global preferences.

### When to Use Global Preferences

- Override a third-party package's service binding for your application
- Provide application-specific configuration values
- Temporary overrides during development or testing

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

## Namespace Configuration (Dependency Management)

Namespace configuration is primarily used to declare **package dependencies** for specific namespaces. This is typically auto-generated via composer.json autoloading.

**Best Practice:** Let auto-discovery handle namespace configuration. Only use namespace preferences in specific situations where package-level configuration is insufficient.

### Namespace Dependencies (Primary Use)

Use namespaces to declare which packages should be loaded for classes in a specific namespace:

```json
{
  "singularity": {
    "namespace": {
      "App\\Payment\\": {
        "require": {
          "acme/payment-gateway": {},
          "acme/http-client": {}
        }
      },
      "App\\Reporting\\": {
        "require": {
          "acme/pdf-generator": {},
          "acme/excel-writer": {}
        }
      }
    }
  }
}
```

### Auto-Discovery from Composer

When auto-discovery is enabled, configuration is automatically generated from `composer.json`:

**Example `composer.json`:**
```json
{
  "name": "acme/my-app",
  "require": {
    "acme/database": "^1.0",
    "acme/logger": "^2.0"
  },
  "autoload": {
    "psr-4": {
      "Acme\\MyApp\\": "src/"
    }
  }
}
```

This automatically generates in-memory configuration:
```json
{
  "singularity": {
    "namespace": {
      "Acme\\MyApp\\": {
        "require": {
          "acme/my-app": {}
        }
      }
    },
    "package": {
      "acme/my-app": {
        "require": {
          "acme/database": {},
          "acme/logger": {}
        }
      }
    }
  }
}
```

**How it works:**
- The **namespace** node maps the PSR-4 namespace to its own package
- The **package** node maps the package to its composer dependencies
- This allows proper dependency resolution based on the namespace hierarchy

### Namespace Preferences (Use Sparingly)

In specific situations, you may need to override services for an entire namespace. Use this only when package-level configuration is insufficient.

**Example: Multi-tenant application with tenant-specific namespaces**

```json
{
  "singularity": {
    "namespace": {
      "App\\Tenant\\Premium\\": {
        "require": {
          "acme/premium-features": {}
        },
        "preference": {
          "App\\Storage\\StorageInterface": {
            "class": "App\\Storage\\S3Storage"
          }
        }
      },
      "App\\Tenant\\Basic\\": {
        "preference": {
          "App\\Storage\\StorageInterface": {
            "class": "App\\Storage\\LocalStorage"
          }
        }
      }
    }
  }
}
```

### Namespace Resolution Priority

When resolving dependencies, namespaces are processed from shortest to longest:
- `Foo\` is processed first (lowest priority)
- `Foo\Bar\` is processed next (medium priority)
- `Foo\Bar\Baz\` is processed last (highest namespace priority)

Longer (more specific) namespaces override shorter (more general) ones.

## Plugin Configuration

### Global Plugins

```json
{
  "singularity": {
    "settings": {
      "plugin-manager": {
        "plugins": {
          "MyApp\\DI\\Plugin\\LoggingPlugin": {
            "logPath": "/var/log/di.log"
          },
          "MyApp\\DI\\Plugin\\CachingPlugin": {
            "ttl": 3600
          }
        }
      }
    }
  }
}
```

**Note:** Plugins are enabled by default when configured. To disable a plugin, set its value to `false`.

### Service-Specific Plugins

```json
{
  "singularity": {
    "preference": {
      "App\\Service\\ExpensiveService": {
        "class": "App\\Service\\ExpensiveService",
        "plugins": {
          "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": {}
        }
      }
    }
  }
}
```

**Note:** An empty object `{}` or any configuration value enables the plugin. Set to `false` to disable.

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

## Configuration References

Configuration references are handled by the `concept/config` package using special syntax. You don't need a separate `references` node.

### Using @include for External Files

```json
{
  "singularity": {
    "settings": "@include(etc/settings.json)",
    "package": {
      "acme/my-package": {
        "preference": "@include(config/preferences.json)"
      }
    }
  }
}
```

### Using @path for Value References

Reference other configuration values using the `@` prefix:

```json
{
  "database": {
    "host": "localhost",
    "port": 3306
  },
  "singularity": {
    "package": {
      "acme/database": {
        "preference": {
          "Acme\\Database\\ConnectionInterface": {
            "class": "Acme\\Database\\Connection",
            "arguments": {
              "host": "@database.host",
              "port": "@database.port"
            }
          }
        }
      }
    }
  }
}
```

**Note:** The `@include()`, `@import`, `@require`, `@path.to.value`, and `${VAR}` syntax is provided by the concept/config package's plugin system.

## Advanced Configuration Techniques

### Configuration Directives Overview

The concept/config package provides several powerful directives for organizing and managing configuration:

- **`@include(path)`** - Includes and merges content from another file (works in nested files)
- **`@import`** - Imports multiple files (only works in the first file in the chain)
- **`@require(path)`** - Requires configuration from another file
- **`@path.to.value`** - References a value from another part of the configuration
- **`${VAR}`** - Environment variable substitution

### Using @include for Modular Configuration

The `@include()` directive allows you to split configuration across multiple files. This is particularly useful for organizing package-level configuration.

**Important notes about `@include()`:**
- Works in nested files (you can use `@include` inside an included file)
- Path can be absolute or relative to the current JSON file
- Relative paths are resolved from the location of the file containing the `@include`

#### Package-Level Configuration Example

**Note:** While you can organize configuration this way, it's optional. Developers may use a single `concept.json` file if they prefer.

**Package structure:**
```
vendor/acme/my-package/
├── concept.json                      # Main package entry point
├── etc/
│   ├── sdi.json                     # DI configuration root
│   └── sdi/
│       ├── plugin-manager.json      # Plugin configuration
│       ├── preference.json          # Package preferences
│       └── packages/
│           ├── foo-bar.json         # Dependency package config
│           └── acme-lib.json        # Another dependency config
```

**Main configuration file** (`vendor/acme/my-package/concept.json`):
```json
{
  "singularity": "@include(etc/sdi.json)"
}
```

**Core DI configuration** (`vendor/acme/my-package/etc/sdi.json`):
```json
{
  "settings": {
    "plugin-manager": "@include(sdi/plugin-manager.json)"
  },
  "preference": "@include(sdi/preference.json)",
  "package": {
    "acme/my-package": {
      "preference": "@include(sdi/packages/package-preferences.json)"
    }
  }
}
```
**Note:** Paths are relative to `etc/sdi.json`, so `sdi/plugin-manager.json` resolves to `etc/sdi/plugin-manager.json`.

**Plugin manager configuration** (`vendor/acme/my-package/etc/sdi/plugin-manager.json`):
```json
{
  "plugins": {
    "Concept\\Singularity\\Plugin\\ContractEnforce\\Enforcement": {
      "priority": 200,
      "*": {
        "Concept\\Singularity\\Plugin\\ContractEnforce\\Factory\\LazyGhost": {}
      }
    }
  }
}
```

**Package-level preferences** (`vendor/acme/my-package/etc/sdi/preference.json`):
```json
{
  "Acme\\MyPackage\\ServiceInterface": {
    "class": "Acme\\MyPackage\\ServiceImpl",
    "shared": true
  }
}
```

### Using @import for Multiple Files

The `@import` directive allows you to import multiple configuration files. **Important:** `@import` only works in the first file in the configuration chain (typically your main `concept.json`). It does not work inside files that are themselves imported or included.

However, `@include` works perfectly inside `@import`ed files or nested `@include` files.

**Example:**
```json
{
  "@import": [
    "singularity.json",
    "database/mysql.json",
    "cache/redis.json"
  ]
}
```

**singularity.json:**
```json
{
  "singularity": {
    "preference": {
      "App\\ServiceInterface": {
        "class": "App\\ServiceImpl"
      }
    }
  }
}
```

**database/mysql.json:**
```json
{
  "database": {
    "mysql": {
      "dsn": "mysql:host=localhost;dbname=myapp",
      "username": "${DB_USER}",
      "password": "${DB_PASSWORD}"
    }
  }
}
```

**Note:** Files imported with `@import` are merged into the current configuration without overriding existing nodes.

### Using @require Directive

The `@require` directive is used to require configuration from another file:

```json
{
  "singularity": {
    "package": {
      "acme/my-package": "@require(vendor/acme/my-package/concept.json)"
    }
  }
}
```

### Environment Variables with ${VAR}

Use `${VAR}` syntax to substitute environment variables into your configuration:

```json
{
  "database": {
    "host": "${DB_HOST}",
    "port": "${DB_PORT}",
    "username": "${DB_USER}",
    "password": "${DB_PASSWORD}"
  },
  "singularity": {
    "preference": {
      "App\\Cache\\CacheInterface": {
        "class": "App\\Cache\\RedisCache",
        "arguments": {
          "host": "${REDIS_HOST}",
          "port": "${REDIS_PORT}"
        }
      }
    }
  }
}
```

### Benefits of Modular Configuration

- **Maintainability**: Each file has a clear, focused purpose
- **Reusability**: Share common configurations across projects
- **Team collaboration**: Different team members can work on different config files
- **Environment management**: Easily swap configuration for different environments
- **Version control**: Cleaner diffs and easier to review changes
- **Package isolation**: Keep package configuration self-contained

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
        "preference": {
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
        "preference": {
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
2. **Namespace preferences** (namespace-specific bindings, shorter namespaces first, then longer)
3. **Global preferences** (application-level bindings)
4. **Runtime overrides** (arguments passed to `create()`)

### Namespace Priority Details

Within namespace preferences, **longer (more specific) namespaces have higher priority**:
- `Foo\` - lowest namespace priority
- `Foo\Bar\` - medium namespace priority  
- `Foo\Bar\Baz\` - highest namespace priority

This allows you to set general defaults for broad namespaces and override them for specific sub-namespaces.

### Example

```json
// Package: vendor/concept-labs/logger/concept.json
{
  "singularity": {
    "package": {
      "concept-labs/logger": {
        "preference": {
          "Psr\\Log\\LoggerInterface": {
            "class": "Concept\\Logger\\FileLogger"
          }
        }
      }
    }
  }
}

// Application: config.json
{
  "singularity": {
    "namespace": {
      "App\\Admin\\": {
        "preference": {
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
- Services in `App\Admin\*`: Use `AdminLogger` (namespace preference)
- Other services: Use `ApplicationLogger` (global preference)
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

### 1. Use Package-Level Configuration First

**Primary Strategy:** Define services in package `concept.json` files.

```json
// vendor/acme/user-service/concept.json
{
  "singularity": {
    "package": {
      "acme/user-service": {
        "preference": {
          "Acme\\User\\UserServiceInterface": {
            "class": "Acme\\User\\UserService",
            "shared": true
          }
        }
      }
    }
  }
}
```

### 2. Use Namespace for Dependencies, Not Preferences

**Good:** Declare package dependencies
```json
{
  "singularity": {
    "namespace": {
      "App\\Payment\\": {
        "require": {
          "acme/payment-gateway": {},
          "acme/logger": {}
        }
      }
    }
  }
}
```

**Avoid:** Namespace-level preferences (use only in specific cases)
```json
// Only use when truly necessary for tenant-specific behavior
{
  "singularity": {
    "namespace": {
      "App\\Tenant\\Premium\\": {
        "preference": {
          "StorageInterface": {
            "class": "S3Storage"
          }
        }
      }
    }
  }
}
```

### 3. Use Interfaces for Service IDs

```json
{
  "singularity": {
    "package": {
      "acme/my-package": {
        "preference": {
          "Acme\\Service\\UserServiceInterface": {
            "class": "Acme\\Service\\UserService"
          }
        }
      }
    }
  }
}
```

### 4. Mark Stateless Services as Shared

```json
{
  "singularity": {
    "package": {
      "acme/validator": {
        "preference": {
          "Acme\\Validator\\ValidationService": {
            "class": "Acme\\Validator\\ValidationService",
            "shared": true
          }
        }
      }
    }
  }
}
```

### 5. Use Weak References for Large Objects

```json
{
  "singularity": {
    "package": {
      "acme/image": {
        "preference": {
          "Acme\\Image\\ImageProcessor": {
            "class": "Acme\\Image\\ImageProcessor",
            "shared": true,
            "weak": true
          }
        }
      }
    }
  }
}
```

### 6. Organize Package Configuration Files

```
vendor/acme/my-package/
  ├── concept.json (main DI configuration)
  ├── src/
  └── composer.json
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
