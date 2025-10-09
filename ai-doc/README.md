# Singularity DI Documentation

Comprehensive documentation for the Singularity Dependency Injection Container.

## Table of Contents

### Getting Started

1. **[Overview](overview.md)** - Introduction to Singularity DI
   - What is Singularity DI?
   - Key features
   - Core components
   - How it works
   - Use cases

2. **[Getting Started](getting-started.md)** - Quick start guide
   - Installation
   - Basic setup
   - Complete examples
   - Common patterns
   - Debugging tips

### Core Concepts

3. **[Configuration](configuration.md)** - Configuration reference
   - Configuration structure
   - Global preferences
   - Namespace configuration
   - Plugin configuration
   - Factory configuration
   - Environment-specific config
   - Best practices

4. **[Context Builder](context-builder.md)** - Dependency resolution
   - How it works
   - Configuration hierarchy
   - ProtoContext structure
   - Dependency resolution
   - Argument resolution
   - Performance optimization

5. **[Lifecycle Management](lifecycle.md)** - Service lifecycle
   - Lifecycle patterns (Transient, Shared, Weak, Prototype)
   - Service registry
   - Memory management
   - Lifecycle events
   - Best practices

### Advanced Features

6. **[Plugin System](plugins.md)** - Plugin development
   - Plugin interface
   - Creating custom plugins
   - Registering plugins
   - Built-in plugins
   - Advanced patterns
   - Best practices

7. **[Factories](factories.md)** - Factory pattern
   - Factory interface
   - Creating factories
   - Factory patterns (Abstract, Builder, Multiton, Lazy)
   - ServiceFactory
   - Testing factories

8. **[Contracts](contracts.md)** - Built-in interfaces
   - Initialization contracts (Injectable, AutoConfigure)
   - Lifecycle contracts (Shared, Weak, Prototype)
   - Factory contracts (LazyGhost)
   - Combining contracts
   - Creating custom contracts

9. **[Advanced Usage](advanced-usage.md)** - Complex scenarios
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

10. **[API Reference](api-reference.md)** - Complete API documentation
    - Core classes
    - Plugin system
    - Factory system
    - Contracts
    - Exceptions
    - Configuration structure
    - Built-in plugins

## Quick Links

### Common Tasks

- **Install and Setup:** [Getting Started → Installation](getting-started.md#installation)
- **Configure Services:** [Configuration → Global Preferences](configuration.md#global-preferences)
- **Create a Plugin:** [Plugins → Creating Custom Plugins](plugins.md#creating-a-custom-plugin)
- **Use Factories:** [Factories → Creating a Factory](factories.md#creating-a-factory)
- **Manage Lifecycle:** [Lifecycle → Lifecycle Patterns](lifecycle.md#lifecycle-patterns)
- **Advanced Patterns:** [Advanced Usage](advanced-usage.md)

### By Use Case

- **Multi-Tenant Apps:** [Advanced Usage → Multi-Tenant Applications](advanced-usage.md#multi-tenant-applications)
- **Testing:** [Advanced Usage → Testing Patterns](advanced-usage.md#testing-patterns)
- **Performance:** [Advanced Usage → Performance Optimization](advanced-usage.md#performance-optimization)
- **AOP:** [Advanced Usage → Aspect-Oriented Programming](advanced-usage.md#aspect-oriented-programming-aop)

### By Feature

- **Autowiring:** [Getting Started → Working Without Configuration](getting-started.md#working-without-configuration-autowiring)
- **Lazy Loading:** [Contracts → LazyGhostInterface](contracts.md#lazyghostinterface)
- **Singleton Pattern:** [Contracts → SharedInterface](contracts.md#sharedinterface)
- **Prototype Pattern:** [Contracts → PrototypeInterface](contracts.md#prototypeinterface)
- **Method Injection:** [Contracts → InjectableInterface](contracts.md#injectableinterface)

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

### Cross-References

Links to other documentation sections:
- `[Link Text](file.md#section)` - Link to specific section
- `[Link Text](file.md)` - Link to file

## Contributing

Found an error or want to improve the documentation?

1. Identify the file in `ai-doc/`
2. Make your changes
3. Submit a pull request
4. Include clear description of changes

## Version

This documentation is for Singularity DI version 1.x

For the latest version, check the [GitHub repository](https://github.com/Concept-Labs/singularity).

## License

This documentation is part of the Singularity DI project and is covered by the same license.

## Support

- **Issues:** [GitHub Issues](https://github.com/Concept-Labs/singularity/issues)
- **Discussions:** [GitHub Discussions](https://github.com/Concept-Labs/singularity/discussions)
- **Source Code:** [GitHub Repository](https://github.com/Concept-Labs/singularity)

---

**Start Learning:** Begin with the [Overview](overview.md) or jump to [Getting Started](getting-started.md) for a hands-on introduction.
