# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive test suite using PHPUnit and PEST (26 test files)
  - **Unit Tests (PHPUnit + PEST):**
    - Core classes: Singularity, ProtoContext, ServiceRegistry, PluginManager
    - Plugins: AbstractPlugin, AggregatePlugin, AutoConfigure, Lifecycle (Shared, Prototype)
    - Factories: FactoryInterface implementations with validation and default values
    - Exceptions: CircularDependencyException, ServiceNotFoundException, RuntimeException
  - **Integration Tests (PHPUnit + PEST):**
    - Container integration: service creation, registration, lifecycle management
    - Plugin integration: plugin execution in container context
    - Factory integration: custom factories with default values and overrides
- Test infrastructure:
  - PHPUnit configuration (phpunit.xml)
  - PEST configuration (tests/Pest.php)
  - Comprehensive test documentation (tests/README.md)
  - .gitignore for test artifacts
- Development dependencies:
  - phpunit/phpunit ^10.5
  - pestphp/pest ^2.34
  - pestphp/pest-plugin-arch ^2.7
- This CHANGELOG file

### Documentation
- Added comprehensive test documentation in tests/README.md
- Test structure and running instructions
- Examples for both PHPUnit and PEST frameworks
- Best practices for writing tests
