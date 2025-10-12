# Singularity DI Documentation

Comprehensive documentation for the Singularity Dependency Injection Container.

## Requirements

- **PHP Version:** >= 8.2
- **PSR-11:** Container Interface ^2.0
- **Dependencies:**
  - `psr/container` ^2
  - `concept-labs/config` ^2
  - `concept-labs/simple-cache` ^1
  - `concept-labs/composer` ^1

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Concept-Labs

## Table of Contents

### Getting Started

1. **[Overview](docs/overview.md)** - Introduction to Singularity DI
   - What is Singularity DI?
   - Key features
   - Core components
   - How it works
   - Use cases

2. **[Getting Started](docs/getting-started.md)** - Quick start guide
   - Installation
   - Basic setup
   - Complete examples
   - Common patterns
   - Debugging tips

### Core Concepts

3. **[Configuration](docs/configuration.md)** - Configuration reference
   - Configuration structure
   - Package-level configuration (recommended)
   - Namespace configuration
   - Global preferences
   - Plugin configuration
   - Advanced configuration techniques (@include, @import, ${VAR})
   - Environment-specific config
   - Best practices

4. **[Context Builder](docs/context-builder.md)** - Dependency resolution
   - How it works
   - Configuration hierarchy
   - ProtoContext structure
   - Dependency resolution
   - Argument resolution
   - Performance optimization

5. **[Lifecycle Management](docs/lifecycle.md)** - Service lifecycle
   - Lifecycle patterns (Transient, Shared, Weak, Prototype)
   - Service registry
   - Memory management
   - Lifecycle events
   - Best practices

### Advanced Features

6. **[Plugin System](docs/plugins.md)** - Plugin development
   - Plugin interface
   - Creating custom plugins
   - Registering plugins
   - Built-in plugins
   - Advanced patterns
   - Best practices

7. **[Factories](docs/factories.md)** - Factory pattern
   - Factory interface
   - Creating factories
   - Factory patterns (Abstract, Builder, Multiton, Lazy)
   - ServiceFactory
   - Testing factories

8. **[Contracts](docs/contracts.md)** - Built-in interfaces
   - Initialization contracts (Injectable, AutoConfigure)
   - Lifecycle contracts (Shared, Weak, Prototype)
   - Factory contracts (LazyGhost)
   - Combining contracts
   - Creating custom contracts

9. **[Advanced Usage](docs/advanced-usage.md)** - Complex scenarios
   - Multi-tenant applications
   - Middleware pattern
   - Decorator pattern
   - Event-driven architecture
   - Aspect-oriented programming
   - Dynamic service registration
   - Circular dependency resolution
   - Performance optimization
   - Testing patterns

### Reference

10. **[API Reference](docs/api-reference.md)** - Complete API documentation
    - Core classes
    - Plugin system
    - Factory system
    - Contracts
    - Exceptions
    - Configuration structure
    - Built-in plugins

## Quick Links

### Common Tasks

- **Install and Setup:** [Getting Started → Installation](docs/getting-started.md#installation)
- **Configure Services:** [Configuration → Package Configuration](docs/configuration.md#package-configuration-primary-strategy)
- **Advanced Config Techniques:** [Configuration → Advanced Configuration Techniques](docs/configuration.md#advanced-configuration-techniques)
- **Create a Plugin:** [Plugins → Creating Custom Plugins](docs/plugins.md#creating-a-custom-plugin)
- **Use Factories:** [Factories → Creating a Factory](docs/factories.md#creating-a-factory)
- **Manage Lifecycle:** [Lifecycle → Lifecycle Patterns](docs/lifecycle.md#lifecycle-patterns)
- **Advanced Patterns:** [Advanced Usage](docs/advanced-usage.md)

### By Use Case

- **Multi-Tenant Apps:** [Advanced Usage → Multi-Tenant Applications](docs/advanced-usage.md#multi-tenant-applications)
- **Testing:** [Advanced Usage → Testing Patterns](docs/advanced-usage.md#testing-patterns)
- **Performance:** [Advanced Usage → Performance Optimization](docs/advanced-usage.md#performance-optimization)
- **AOP:** [Advanced Usage → Aspect-Oriented Programming](docs/advanced-usage.md#aspect-oriented-programming-aop)

### By Feature

- **Autowiring:** [Getting Started → Working Without Configuration](docs/getting-started.md#working-without-configuration-autowiring)
- **Lazy Loading:** [Contracts → LazyGhostInterface](docs/contracts.md#lazyghostinterface)
- **Singleton Pattern:** [Contracts → SharedInterface](docs/contracts.md#sharedinterface)
- **Prototype Pattern:** [Contracts → PrototypeInterface](docs/contracts.md#prototypeinterface)
- **Method Injection:** [Contracts → InjectableInterface](docs/contracts.md#injectableinterface)

## Documentation Structure

Each documentation file follows a consistent structure:

1. **Overview** - Introduction to the topic
2. **Core Concepts** - Fundamental knowledge
3. **Examples** - Practical code examples
4. **Advanced Topics** - Complex scenarios
5. **Best Practices** - Recommended patterns
6. **Next Steps** - Links to related topics

## Code Examples

All code examples in this documentation are:

- **Complete** - Can be copied and run
- **Tested** - Based on actual working code
- **Commented** - Include explanatory comments
- **Progressive** - Start simple, build complexity

## Conventions

### Code Blocks

```php
// PHP code examples
$container = new Singularity($config);
```

```json
// JSON configuration examples
{
  "singularity": {
    "preference": {}
  }
}
```

### Symbols

- ✅ **Good practice** - Recommended approach
- ❌ **Anti-pattern** - Avoid this
- ⚠️ **Warning** - Use with caution
- 💡 **Tip** - Helpful information

## Contributing

Found an error or want to improve the documentation?

1. Make your changes
2. Submit a pull request
4. Include clear description of changes

## License

This documentation is part of the Singularity DI project and is covered by the same license.

## Support

- **Issues:** [GitHub Issues](https://github.com/Concept-Labs/singularity/issues)
- **Discussions:** [GitHub Discussions](https://github.com/Concept-Labs/singularity/discussions)
- **Source Code:** [GitHub Repository](https://github.com/Concept-Labs/singularity)

---

**Start Learning:** Begin with the [Overview](docs/overview.md) or jump to [Getting Started](docs/getting-started.md) for a hands-on introduction.
