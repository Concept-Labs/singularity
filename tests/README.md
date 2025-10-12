# Singularity DI Tests

This directory contains comprehensive test coverage for the Singularity Dependency Injection Container using both PHPUnit and PEST testing frameworks.

## Test Structure

```
tests/
├── Unit/              # Unit tests for individual components
│   ├── Context/       # ProtoContext tests
│   ├── Exception/     # Exception handling tests
│   ├── Factory/       # Factory pattern tests
│   ├── Plugin/        # Plugin tests
│   │   └── ContractEnforce/  # Contract enforcement plugin tests
│   └── Registry/      # Service registry tests
├── Integration/       # Integration tests for workflows
└── Pest.php          # PEST configuration
```

## Running Tests

### PHPUnit Tests

Run all PHPUnit tests:
```bash
vendor/bin/phpunit
```

Run specific test suite:
```bash
vendor/bin/phpunit --testsuite Unit
vendor/bin/phpunit --testsuite Integration
```

Run specific test file:
```bash
vendor/bin/phpunit tests/Unit/SingularityTest.php
```

### PEST Tests

Run all PEST tests:
```bash
vendor/bin/pest
```

Run specific test file:
```bash
vendor/bin/pest tests/Unit/SingularityPestTest.php
```

Run tests with coverage:
```bash
vendor/bin/pest --coverage
```

## Test Coverage

### Unit Tests

#### Core Classes
- **Singularity** - Main container functionality
  - Instantiation with/without config
  - Service registration and retrieval
  - Self-registration for container interfaces
  
- **ServiceRegistry** - Service storage and retrieval
  - Service registration (normal and weak references)
  - Service existence checks
  - Multiple service handling

- **ProtoContext** - Context object for service metadata
  - Service ID and class retrieval
  - Reflection access
  - Configuration access
  - Cloning support

- **PluginManager** - Plugin orchestration
  - Plugin registration
  - Configuration acceptance
  - Before/after hook execution

#### Plugins
- **AbstractPlugin** - Base plugin class
  - Interface implementation
  - Method overriding capabilities
  
- **AggregatePlugin** - Plugin aggregation
  - Multiple plugin execution
  - Propagation stopping
  
- **AutoConfigure** - Automatic configuration
  - __configure() method invocation
  - Contract enforcement

- **Lifecycle Plugins** - Service lifecycle management
  - Shared service handling
  - Prototype service handling

#### Factories
- **FactoryInterface** - Factory pattern
  - Service creation
  - Argument passing
  - Default values
  - Validation

#### Exceptions
- **Exception Hierarchy** - Error handling
  - CircularDependencyException
  - ServiceNotFoundException
  - RuntimeException
  - Interface implementation

### Integration Tests

- **Container Integration** - Full container workflows
  - Service creation and retrieval
  - Multiple service coexistence
  - Service lifecycle management

- **Plugin Integration** - Plugin system integration
  - Plugin execution in container context
  - Service registration with plugins

- **Factory Integration** - Factory pattern integration
  - Custom factory implementations
  - Default values and overrides
  - Service creation with factories

## Test Frameworks

### PHPUnit
Traditional PHP unit testing framework with extensive assertion library.

**Benefits:**
- Mature and well-documented
- Extensive ecosystem
- IDE support
- Industry standard

### PEST
Modern PHP testing framework with expressive syntax.

**Benefits:**
- Cleaner, more readable syntax
- Built on PHPUnit
- Expectation API
- Less boilerplate

## Writing Tests

### PHPUnit Example

```php
<?php

namespace Concept\Singularity\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Singularity;

class MyTest extends TestCase
{
    public function testSomething(): void
    {
        $container = new Singularity();
        
        $this->assertInstanceOf(Singularity::class, $container);
    }
}
```

### PEST Example

```php
<?php

use Concept\Singularity\Singularity;

test('singularity can be instantiated', function () {
    $container = new Singularity();
    
    expect($container)->toBeInstanceOf(Singularity::class);
});
```

## Best Practices

1. **Test Isolation** - Each test should be independent
2. **Clear Names** - Test names should describe what is being tested
3. **Arrange-Act-Assert** - Follow AAA pattern in tests
4. **Mock External Dependencies** - Use mocks for external services
5. **Test Both Success and Failure** - Cover happy path and error cases
6. **Use Data Providers** - For testing multiple scenarios
7. **Keep Tests Simple** - One assertion per test when possible

## Continuous Integration

Tests are automatically run on:
- Pull requests
- Push to main branch
- Release creation

## Code Coverage

Generate code coverage report:
```bash
vendor/bin/phpunit --coverage-html coverage/
```

View coverage in browser:
```bash
open coverage/index.html
```

## Contributing

When adding new functionality:
1. Write tests first (TDD)
2. Ensure all tests pass
3. Add both PHPUnit and PEST tests when applicable
4. Update this README if adding new test categories
5. Maintain minimum 80% code coverage

## Documentation

For more information about the tested functionality, see:
- [Main Documentation](../README.md)
- [Plugin System](../docs/plugins.md)
- [Factory Pattern](../docs/factories.md)
- [Contracts](../docs/contracts.md)
