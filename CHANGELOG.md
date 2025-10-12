# Changelog

All notable changes to the Singularity DI project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- PHP version requirement (>= 8.2) documentation in README.md
- License information in README.md (MIT License)
- Comprehensive test suite using Pest PHP testing framework
  - Unit tests for core Singularity container functionality
  - Unit tests for service creation and dependency injection
  - Unit tests for service lifecycle management (shared vs transient)
  - Unit tests for exception handling
  - Unit tests for plugin system
  - Unit tests for configuration features
  - Feature tests for complete application scenarios

### Features Tested

#### Core Container Features
- Container instantiation with and without configuration
- PSR-11 ContainerInterface implementation
- SingularityInterface implementation
- Service registration and retrieval
- Self-injection (container returns itself when requested)

#### Dependency Injection
- Automatic dependency resolution
- Constructor injection
- Optional dependencies handling
- Default value handling
- Runtime argument passing
- Configuration-based argument injection
- Service reference injection

#### Service Lifecycle Management
- Shared services (singleton pattern)
- Transient services (new instance per request)
- Service registry functionality
- `get()` vs `create()` behavior differences

#### Configuration System
- Interface to class mapping
- Constructor argument configuration
- Service reference configuration as arguments
- Multiple configuration strategies

#### Plugin System
- Plugin registration via configuration
- Before hook execution (pre-instantiation)
- After hook execution (post-instantiation)
- Plugin state management

#### Exception Handling
- NoConfigurationLoadedException for missing configuration
- ServiceNotFoundException for non-existent services
- CircularDependencyException for circular dependencies
- NotInstantiableException for abstract classes/interfaces

### Technical Details

#### Test Coverage
- 35 test cases covering core functionality
- 65 assertions validating behavior
- Feature tests demonstrating real-world usage patterns
- Comprehensive edge case testing

#### Requirements
- **PHP:** >= 8.2
- **PSR-11:** Container Interface ^2.0
- **Dependencies:**
  - `psr/container` ^2
  - `concept-labs/config` ^2
  - `concept-labs/simple-cache` ^1
  - `concept-labs/composer` ^1

#### Testing Framework
- **Pest PHP:** ^2.0
- Test structure:
  - Unit tests in `tests/Unit/`
  - Feature tests in `tests/Feature/`
  - Custom test helpers in `tests/Pest.php`

### Documentation Updates
- Added PHP version requirements to README.md
- Added license information referencing LICENSE file
- Improved documentation structure

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Copyright (c) 2025 Concept-Labs
