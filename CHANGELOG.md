# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Comprehensive test suite using PHPUnit and PEST
  - Unit tests for core classes (Singularity, ContextBuilder, ProtoContext, ServiceRegistry, PluginManager)
  - Unit tests for plugins (AbstractPlugin, AggregatePlugin, AutoConfigure, DependencyInjection, LazyGhost, Lifecycle)
  - Unit tests for factories (Factory, ServiceFactory)
  - Integration tests for container, plugins, and factories
- PHPUnit configuration (phpunit.xml)
- PEST configuration (tests/Pest.php)
- .gitignore for test artifacts
- This CHANGELOG file
