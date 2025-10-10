# Singularity DI - Overview

## What is Singularity DI?

Singularity DI is a next-generation PSR-11 compliant Dependency Injection Container that serves as the core of the Concept Labs ecosystem. Unlike simple service containers, Singularity DI provides context-aware dependency resolution, powerful plugin system, and flexible configuration management.

## Key Features

### PSR-11 Compliance with Extensions

Singularity implements the standard `Psr\Container\ContainerInterface` with these methods:

- **`get(string $id)`** - Retrieves a service instance by identifier
- **`has(string $id)`** - Checks if a service exists or can be created
instance, bypassing cache

### Context-Aware Dependency Injection

Singularity allows you to:

- Bind different implementations to the same interface based on context
- Define bindings at multiple levels: global, namespace, or package
- Override dependencies for specific services without affecting others

**Example:**
```php
// In one context, CarInterface might resolve to BMW
$car = $container->get(CarInterface::class); // Returns BMW instance

// In another context (different namespace), it resolves to Audi
// This is controlled by configuration, not code changes
```

### Automatic Dependency Resolution (Autowiring)

If a service is not explicitly configured, Singularity attempts to:

1. Treat the identifier as a class name
2. Use reflection to analyze constructor dependencies
3. Recursively resolve and inject dependencies
4. Create the instance automatically

**Example:**
```php
class UserService {
    public function __construct(
        private DatabaseInterface $db,
        private LoggerInterface $logger
    ) {}
}

// No configuration needed if dependencies are resolvable
$userService = $container->get(UserService::class);
```

### Powerful Plugin System

Plugins can intercept service creation:

- **Before** creation: Modify context, add metadata, prepare resources
- **After** creation: Initialize services, wrap in proxies, register listeners

**Built-in plugins include:**
- `SharedPlugin` - Manages singleton behavior
- `DependencyInjection` - Handles attribute `#[Injector]` method injection
- `AutoConfigure` - Calls `__configure()` after instantiation
- `LazyGhost` - Creates lazy-loading proxies
- `NewInstance` - Controls instance creation

### Flexible Service Lifecycle

Singularity supports multiple lifecycle patterns:

- **Shared (Singleton)**: One instance reused across requests
- **Weak Shared**: Singleton that can be garbage collected when not referenced
- **Prototype**: Template object that creates copies via `prototype()` method
- **Transient**: New instance every time (default when not configured as shared)

### Configuration-Driven Architecture

Configuration is loaded from multiple sources:

- Package-level config (`concept.json` in each package)
- Namespace-level overrides
- Global application config
- Priority: Global > Namespace > Package

## Core Components

### Container (`Singularity`)

The main entry point that:
- Manages service registry
- Coordinates context building
- Executes plugin pipeline
- Handles caching and lifecycle

### Context Builder (`ContextBuilder`)

Responsible for:
- Analyzing service identifiers
- Merging configuration from multiple levels
- Determining which class to instantiate
- Preparing metadata for plugins

### Proto Context (`ProtoContext`)

A data structure containing:
- Service ID and class name
- Reflection information
- Configuration preferences
- Plugin metadata
- Dependency stack

### Service Registry (`ServiceRegistry`)

Manages:
- Caching of created instances
- Weak reference support
- Prototype pattern handling
- Service lookup

### Plugin Manager (`PluginManager`)

Coordinates:
- Plugin registration and configuration
- Before/after hook execution
- Plugin-specific arguments
- Propagation control

## How It Works

### Service Resolution Flow

```
1. Request service via get(ServiceInterface::class)
   ↓
2. ContextBuilder analyzes the identifier
   ↓
3. Load and merge configuration (package → namespace → global)
   ↓
4. Determine concrete class and dependencies
   ↓
5. Create ProtoContext with all metadata
   ↓
6. Execute 'before' plugins
   ↓
7. Instantiate the service (via factory or reflection)
   ↓
8. Execute 'after' plugins
   ↓
9. Store in ServiceRegistry (if shared)
   ↓
10. Return service instance
```

### Configuration Hierarchy

```
Global Preferences (highest priority)
    ↓
Namespace Preferences
    ↓
Package Preferences
    ↓
Autowiring (lowest priority)
```

## Design Principles

### Separation of Concerns

- **Container**: Orchestration and caching
- **ContextBuilder**: Configuration resolution
- **Plugins**: Cross-cutting concerns
- **Registry**: Instance management

### Open/Closed Principle

- Core container is closed for modification
- Behavior is extended through plugins
- Configuration changes behavior without code changes

### Dependency Inversion

- Depend on interfaces, not concrete classes
- Container manages concrete implementations
- Runtime binding based on configuration

## Use Cases

### Multi-Tenant Applications

Different database connections per tenant:

```json
{
  "singularity": {
    "namespace": {
      "App\\Tenant\\Alpha\\": {
        "preference": {
          "DatabaseInterface": {
            "class": "AlphaDatabase"
          }
        }
      },
      "App\\Tenant\\Beta\\": {
        "preference": {
          "DatabaseInterface": {
            "class": "BetaDatabase"
          }
        }
      }
    }
  }
}
```

### Plugin-Based Architecture

Add logging to all services:

```php
class LoggingPlugin extends AbstractPlugin {
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void {
        error_log("Created: " . get_class($service));
    }
}
```

### Testing with Mock Objects

Override production services in test config:

```json
{
  "singularity": {
    "preference": {
      "PaymentGatewayInterface": {
        "class": "MockPaymentGateway"
      }
    }
  }
}
```

## Comparison with Other DI Containers

| Feature | Singularity DI | Simple PSR-11 | Symfony DI | Laravel Container |
|---------|----------------|---------------|------------|-------------------|
| PSR-11 Compliant | ✅ | ✅ | ✅ | ✅ |
| Context-Aware Resolution | ✅ | ❌ | Limited | Limited |
| Plugin System | ✅ | ❌ | Limited | ❌ |
| Weak References | ✅ | ❌ | ❌ | ❌ |
| Prototype Pattern | ✅ | ❌ | ✅ | ❌ |
| Multi-Level Config | ✅ | ❌ | Limited | ❌ |
| Autowiring | ✅ | Varies | ✅ | ✅ |

## Performance Considerations

### Caching

- ProtoContext can be cached to avoid repeated configuration resolution
- Reflection information is cached per service
- Service instances are cached by default (singleton behavior)

### Lazy Loading

- LazyGhost plugin creates proxy objects
- Real service is instantiated only when methods are called
- Reduces memory footprint for rarely-used services

### Weak References

- PHP 7.4+ feature for automatic memory cleanup
- Shared services can be garbage collected when not in use
- Useful for long-running processes (workers, daemons)

## Next Steps

- [Getting Started Guide](getting-started.md) - Build your first application
- [Configuration Reference](configuration.md) - Detailed configuration options
- [Plugin Development](plugins.md) - Create custom plugins
- [API Reference](api-reference.md) - Complete API documentation
