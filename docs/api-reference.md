# API Reference

## Core Classes

### Singularity

The main container class implementing PSR-11 and providing dependency injection.

**Namespace:** `Concept\Singularity`

**Implements:** `SingularityInterface`, `ContainerInterface`, `ConfigurableInterface`

#### Methods

##### `__construct(ConfigInterface $config)`

Create a new container instance.

**Parameters:**
- `$config` - Configuration object containing DI settings

**Example:**
```php
$config = new Config();
$config->load('config.json');
$container = new Singularity($config);
```

##### `get(string $id): object`

Retrieve a service by its identifier. Returns cached instance if available.

**Parameters:**
- `$id` - Service identifier (class name or interface)

**Returns:** Service instance

**Throws:** 
- `ServiceNotFoundException` - If service cannot be created
- `CircularDependencyException` - If circular dependency detected

**Example:**
```php
$logger = $container->get(LoggerInterface::class);
```

##### `has(string $id): bool`

Check if a service exists or can be created.

**Parameters:**
- `$id` - Service identifier

**Returns:** `true` if service is available

**Example:**
```php
if ($container->has('UserService')) {
    $service = $container->get('UserService');
}
```

##### `create(string $id, array $overrideArgs = [], array $depStack = []): object`

Create a fresh service instance, bypassing cache.

**Parameters:**
- `$id` - Service identifier
- `$overrideArgs` - Arguments to override from configuration
- `$depStack` - Internal dependency stack (for circular detection)

**Returns:** New service instance

**Example:**
```php
$db = $container->create(DatabaseInterface::class, [
    'dsn' => 'mysql:host=testserver;dbname=test'
]);
```

---

### SingularityInterface

Interface defining the container contract.

**Namespace:** `Concept\Singularity`

**Extends:** `Psr\Container\ContainerInterface`

#### Methods

##### `create(string $id, array $overrideArgs = []): object`

Create a new service instance, bypassing any cached instances.

---

### ServiceRegistry

Manages created service instances and caching.

**Namespace:** `Concept\Singularity\Registry`

**Implements:** `ServiceRegistryInterface`

#### Methods

##### `set(string $serviceId, object $service, bool $weak = false): void`

Store a service instance in the registry.

**Parameters:**
- `$serviceId` - Service identifier
- `$service` - Service instance to store
- `$weak` - Use weak reference (allows garbage collection)

**Example:**
```php
$registry->set('MyService', $service, true); // Weak reference
```

##### `get(string $serviceId): ?object`

Retrieve a service from the registry.

**Parameters:**
- `$serviceId` - Service identifier

**Returns:** Service instance or `null` if not found

**Example:**
```php
$service = $registry->get('MyService');
if ($service === null) {
    // Service not in registry or was garbage collected
}
```

##### `has(string $serviceId): bool`

Check if a service is registered.

**Parameters:**
- `$serviceId` - Service identifier

**Returns:** `true` if service exists in registry

##### `remove(string $serviceId): void`

Remove a service from the registry.

**Parameters:**
- `$serviceId` - Service identifier to remove

##### `clear(): void`

Remove all services from the registry.

---

### ContextBuilder

Builds service context from configuration.

**Namespace:** `Concept\Singularity\Context`

**Implements:** `ContextBuilderInterface`

#### Methods

##### `build(string $serviceId, array $dependencyStack = []): ProtoContextInterface`

Build a ProtoContext for a service.

**Parameters:**
- `$serviceId` - Service identifier
- `$dependencyStack` - Current dependency chain

**Returns:** ProtoContext with all metadata

**Example:**
```php
$context = $builder->build('UserService', ['Controller']);
$serviceClass = $context->getServiceClass();
```

---

### ProtoContext

Contains all metadata needed to create a service.

**Namespace:** `Concept\Singularity\Context`

**Implements:** `ProtoContextInterface`

#### Methods

##### `getServiceId(): string`

Get the original service identifier.

**Returns:** Service ID as requested

##### `getServiceClass(): string`

Get the concrete class to instantiate.

**Returns:** Class name to create

##### `getSharedId(): string`

Get the cache key for shared services.

**Returns:** Unique identifier for caching

##### `getReflection(): ReflectionClass`

Get reflection for the service class.

**Returns:** `ReflectionClass` instance

##### `getReflectionMethods(?int $filter = null): array`

Get all methods from reflection.

**Parameters:**
- `$filter` - Optional ReflectionMethod filter flags

**Returns:** Array of `ReflectionMethod` objects

##### `getReflectionMethod(string $name): ?ReflectionMethod`

Get a specific method from reflection.

**Parameters:**
- `$name` - Method name

**Returns:** `ReflectionMethod` or `null`

##### `getPreferenceConfig(): ConfigInterface`

Get the merged configuration for this service.

**Returns:** Configuration object

##### `getPreferenceData(): array`

Get raw preference data.

**Returns:** Array of configuration data

##### `getPreferenceArguments(): array`

Get constructor arguments from configuration.

**Returns:** Associative array of arguments

##### `hasPreferenceArgument(string $name): bool`

Check if a specific argument is configured.

**Parameters:**
- `$name` - Argument name

**Returns:** `true` if argument exists

##### `getPreferenceArgument(string $name, mixed $default = null): mixed`

Get a specific argument value.

**Parameters:**
- `$name` - Argument name
- `$default` - Default value if not found

**Returns:** Argument value

##### `getDependencyStack(): array`

Get the chain of parent dependencies.

**Returns:** Array of parent service IDs

##### `getContainer(): SingularityInterface`

Get the container instance.

**Returns:** Container

##### `getPlugins(): iterable`

Get all applicable plugins.

**Returns:** Iterable of plugin classes

##### `hasPlugins(): bool`

Check if any plugins are registered.

**Returns:** `true` if plugins exist

##### `isPluginDisabled(string $plugin): bool`

Check if a plugin is disabled for this service.

**Parameters:**
- `$plugin` - Plugin class name

**Returns:** `true` if disabled

##### `getServiceFactory(): ?callable`

Get custom factory callable if set.

**Returns:** Factory callable or `null`

##### `setServiceFactory(callable $factory): static`

Set a custom factory.

**Parameters:**
- `$factory` - Callable factory

**Returns:** `$this` for chaining

##### `inflate(array $metaData): static`

Add metadata to context.

**Parameters:**
- `$metaData` - Key-value pairs of metadata

**Returns:** `$this` for chaining

##### `getMetaData(?string $key = null): mixed`

Get metadata.

**Parameters:**
- `$key` - Specific key or `null` for all

**Returns:** Metadata value or array

##### `stopPluginPropagation(string $type): static`

Stop executing plugins of a type.

**Parameters:**
- `$type` - Plugin type (`PluginInterface::BEFORE` or `AFTER`)

**Returns:** `$this` for chaining

##### `isPluginPropagationStopped(string $type): bool`

Check if plugin propagation is stopped.

**Parameters:**
- `$type` - Plugin type

**Returns:** `true` if stopped

---

## Plugin System

### PluginInterface

Interface for all plugins.

**Namespace:** `Concept\Singularity\Plugin`

#### Constants

- `BEFORE` - Before hook type
- `AFTER` - After hook type

#### Methods

##### `static before(ProtoContextInterface $context, mixed $args = null): void`

Called before service creation.

**Parameters:**
- `$context` - Service metadata
- `$args` - Plugin-specific arguments

##### `static after(object $service, ProtoContextInterface $context, mixed $args = null): void`

Called after service creation.

**Parameters:**
- `$service` - Created service instance
- `$context` - Service metadata
- `$args` - Plugin-specific arguments

---

### AbstractPlugin

Base class for plugins with default implementations.

**Namespace:** `Concept\Singularity\Plugin`

**Implements:** `PluginInterface`

Provides empty default implementations of `before()` and `after()` methods.

---

### PluginManager

Manages plugin registration and execution.

**Namespace:** `Concept\Singularity\Plugin`

**Implements:** `PluginManagerInterface`

#### Methods

##### `register(string $pluginClass, mixed $args = null): void`

Register a plugin.

**Parameters:**
- `$pluginClass` - Plugin class name
- `$args` - Plugin configuration

##### `executeBefore(ProtoContextInterface $context): void`

Execute all 'before' hooks.

**Parameters:**
- `$context` - Service context

##### `executeAfter(object $service, ProtoContextInterface $context): void`

Execute all 'after' hooks.

**Parameters:**
- `$service` - Created service
- `$context` - Service context

---

## Factory System

### FactoryInterface

Interface for service factories.

**Namespace:** `Concept\Singularity\Factory`

#### Methods

##### `create(string $serviceId, array $args = []): object`

Create a service instance.

**Parameters:**
- `$serviceId` - Service identifier
- `$args` - Constructor arguments

**Returns:** Created service

---

### ServiceFactoryInterface

Interface for the default service factory.

**Namespace:** `Concept\Singularity\Factory`

#### Methods

##### `createService(ProtoContextInterface $context, array $overrideArgs = []): object`

Create a service using reflection and dependency injection.

**Parameters:**
- `$context` - Service context
- `$overrideArgs` - Arguments to override

**Returns:** Created service

---

## Contracts (Interfaces)

### Initialization Contracts

#### InjectableInterface

**Namespace:** `Concept\Singularity\Contract\Initialization`

**Constant:** `INJECT_METHOD = '__di'`

Services implementing this interface will have their `__di()` method called for dependency injection.

#### AutoConfigureInterface

**Namespace:** `Concept\Singularity\Contract\Initialization`

Services implementing this interface will have their `__configure()` method called after construction.

**Method:**
```php
public function __configure(): void;
```

---

### Lifecycle Contracts

#### SharedInterface

**Namespace:** `Concept\Singularity\Contract\Lifecycle`

Marker interface indicating service should be cached (singleton pattern).

#### WeakInterface

**Namespace:** `Concept\Singularity\Contract\Lifecycle\Shared`

Marker interface indicating shared service should use weak references.

#### PrototypeInterface

**Namespace:** `Concept\Singularity\Contract\Lifecycle`

Services implementing this interface use prototype pattern.

**Method:**
```php
public function prototype(): static;
```

---

### Factory Contracts

#### LazyGhostInterface

**Namespace:** `Concept\Singularity\Contract\Factory`

Marker interface indicating service should be lazy-loaded via proxy.

---

## Exceptions

### ServiceNotFoundException

**Namespace:** `Concept\Singularity\Exception`

**Extends:** `Psr\Container\NotFoundExceptionInterface`

Thrown when a service cannot be found or created.

**Example:**
```php
try {
    $service = $container->get('NonExistentService');
} catch (ServiceNotFoundException $e) {
    echo "Service not found: " . $e->getMessage();
}
```

---

### CircularDependencyException

**Namespace:** `Concept\Singularity\Exception`

Thrown when circular dependency is detected.

**Example:**
```php
// ServiceA depends on ServiceB
// ServiceB depends on ServiceA
try {
    $service = $container->get(ServiceA::class);
} catch (CircularDependencyException $e) {
    echo "Circular dependency: " . $e->getMessage();
    // Shows dependency chain
}
```

---

### NotInstantiableException

**Namespace:** `Concept\Singularity\Exception`

Thrown when a class cannot be instantiated (abstract, interface, etc.).

---

### NoConfigurationLoadedException

**Namespace:** `Concept\Singularity\Exception`

Thrown when container is used without loading configuration.

---

### RuntimeException

**Namespace:** `Concept\Singularity\Exception`

Generic runtime exception for container operations.

---

## Configuration Structure

### Preference Configuration

```json
{
  "class": "string",           // Concrete class name
  "shared": "boolean",         // Singleton behavior
  "weak": "boolean",           // Weak reference (requires shared: true)
  "arguments": {               // Constructor arguments
    "argName": "value",        // Primitive value
    "argName": {               // Service dependency
      "type": "service",
      "preference": "ServiceId"
    }
  },
  "plugins": {                 // Service-specific plugins
    "PluginClass": "config"    // Plugin configuration or false to disable
  }
}
```

**Note:** The `factory` and `reference` nodes are not supported in preference configuration.
- For custom factories, implement the factory pattern in your code
- For configuration organization, use concept/config's directives:
  - `@include(path)` - Include files (works in nested files)
  - `@import` - Import multiple files with optional glob patterns (only works in first file)
  - `@path.to.value` - Reference config values
  - `${VAR}` - Environment variable substitution

### Settings Configuration

```json
{
  "plugin-manager": {
    "plugins": {               // Global plugins
      "PluginClass": {}        // Plugin configuration
    }
  }
}
```

### Namespace Configuration

```json
{
  "require": {                 // Package dependencies
    "vendor/package": {}       // Empty object, reserved for future use
  },
  "preference": {              // Service preferences for namespace
    "ServiceId": {
      "class": "ConcreteClass"
    }
  }
}
```

**Note:** The `require` object structure is reserved for future enhancements. Version constraints may be added in later versions.

---

## Built-in Plugins

### DependencyInjection

**Class:** `Concept\Singularity\Plugin\ContractEnforce\Initialization\DependencyInjection`

Handles `InjectableInterface` - calls `__di()` method.

### AutoConfigure

**Class:** `Concept\Singularity\Plugin\ContractEnforce\Initialization\AutoConfigure`

Handles `AutoConfigureInterface` - calls `__configure()` method.

### Shared

**Class:** `Concept\Singularity\Plugin\ContractEnforce\Lifecycle\Shared`

Handles `SharedInterface` - manages singleton caching.

### Prototype

**Class:** `Concept\Singularity\Plugin\ContractEnforce\Lifecycle\Prototype`

Handles `PrototypeInterface` - manages prototype pattern.

### LazyGhost

**Class:** `Concept\Singularity\Plugin\ContractEnforce\Factory\LazyGhost`

Handles `LazyGhostInterface` - creates lazy-loading proxies.

### NewInstance

**Class:** `Concept\Singularity\Plugin\ContractEnforce\Factory\NewInstance`

Handles service instantiation via reflection.

---

## Type Definitions

### Service Identifier

A string that uniquely identifies a service. Can be:
- Interface name: `App\Service\LoggerInterface`
- Class name: `App\Service\FileLogger`
- Alias: `logger`

### Dependency Stack

An array of service identifiers showing the dependency chain:
```php
[
    'App\Controller\UserController',
    'App\Service\UserService',
    'App\Repository\UserRepository'
]
```

### Plugin Arguments

Mixed type that can be:
- `null` - No configuration
- `array` - Configuration array
- `object` - Configuration object
- `false` - Disable plugin

---

## Common Patterns

### Getting a Service

```php
$service = $container->get(ServiceInterface::class);
```

### Creating Fresh Instance

```php
$service = $container->create(ServiceInterface::class, ['arg' => 'value']);
```

### Checking Service Availability

```php
if ($container->has(ServiceInterface::class)) {
    $service = $container->get(ServiceInterface::class);
}
```

### Custom Factory

```php
class MyFactory implements FactoryInterface
{
    public function create(string $serviceId, array $args = []): object
    {
        return new $serviceId(...$args);
    }
}
```

### Custom Plugin

```php
class MyPlugin extends AbstractPlugin
{
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        // Custom logic
    }
}
```

---

## Version Information

**Current Version:** 1.x (check composer.json for exact version)

**PHP Requirements:** >= 8.0

**Dependencies:**
- `psr/container` - PSR-11 container interface
- `concept-labs/config` - Configuration management

---

## Additional Resources

- [GitHub Repository](https://github.com/Concept-Labs/singularity)
- [Overview](overview.md) - Container overview
- [Getting Started](getting-started.md) - Quick start guide
- [Configuration](configuration.md) - Configuration reference
- [Plugins](plugins.md) - Plugin development
- [Advanced Usage](advanced-usage.md) - Advanced patterns
