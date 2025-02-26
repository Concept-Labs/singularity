<?php

namespace Concept\Singularity\Config\Composer;

use Concept\Config\Config;
use Concept\Singularity\Config\ConfigNodeInterface;
use Traversable;

class PackageConfig extends Config implements PackageConfigInterface
{

    const DEFAULT_PACKAGE_PRIORITY = 0;

    protected string $filename = '';
    protected array $composerData = [];
    protected array $conceptData = [];
    protected string $name = '';
    protected array $requires = [];
    protected array $namespaces = [];

    protected $compabilityValidator = null;

    /**
     * {@inheritDoc}
     */
    public function loadPackage(string $filename): static
    {
        $this->filename = $filename;

        $this->initPackageData(
            $this->readJson($filename)
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    protected function initPackageData(array $data): static
    {
        $this->composerData = $data;

        $this->name = $data['name'];
        $this->namespaces = array_keys($data['autoload']['psr-4'] ?? []);
        $this->requires = array_keys($data['require'] ?? []);
        $this->conceptData = $data['extra']['concept'] ?? [];

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getComposerData(): array
    {
        return $this->composerData;
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageName(): string
    {
        return $this->name;
    }

    /**
     * Get the namespaces
     * 
     * @return Traversable
     */
    protected function getNamespaces(): Traversable
    {
        foreach ($this->namespaces as $namespace) {
            yield $namespace;
        }
    }

    /**
     * Get the requires
     * 
     * @return Traversable
     */
    protected function getRequires(): Traversable
    {
        foreach ($this->requires as $require) {
            yield $require;
        }
    }

    /**
     * Set the external compability validator
     * 
     * @param callable $validator
     * 
     * @return static
     */
    public function setCompabilityValidator(callable $validator): static
    {
        $this->compabilityValidator = $validator;

        return $this;
    }

    /**
     * Get the external compability validator
     * 
     * @return callable
     */
    protected function getCompabilityValidator(): callable
    {
        return $this->compabilityValidator;
    }

    /**
     * Check if the package is compatible
     * 
     * @return bool
     */
    public function isCompatible(): bool
    {
        return isset($this->composerData['extra']['concept']) ? true : false;
    }

    /**
     * Build the package configuration
     * 
     * @return static
     */
    public function build(): static
    {
        if (!$this->isCompatible()) {
            return $this;
        }

        $this->buildNamespaceDependency();
        $this->buildPakageDependency();
        $this->includeExternalConfig();
        $this->buildConceptData();

        return $this;
    }

    /**
     * Build the namespace dependency
     */
    protected function buildNamespaceDependency()
    {

        foreach ($this->getNamespaces() as $namespace) {
            $this->mergeTo(
                join(
                    '.',
                    [
                        ConfigNodeInterface::NODE_SINGULARITY,
                        ConfigNodeInterface::NODE_NAMESPACE,
                        $namespace,
                        ConfigNodeInterface::NODE_DEPENDS
                    ]
                ), 
                [$this->getPackageName() => ["priority" => static::DEFAULT_PACKAGE_PRIORITY]]
            );
        }
    }

    /**
     * build the package dependency
     */
    protected function buildPakageDependency()
    {
        $packageNodePath = join(
            '.',
            [
                ConfigNodeInterface::NODE_SINGULARITY,
                ConfigNodeInterface::NODE_PACKAGE,
                $this->getPackageName()
            ]
        );

        $this->mergeTo(
            $packageNodePath,
            []
        );

        foreach ($this->getRequires() as $require) {

            if (!$this->getCompabilityValidator()($require)) {
               continue;
            }

            $this->mergeTo(
                join(
                    '.',
                    [
                        $packageNodePath,
                        ConfigNodeInterface::NODE_DEPENDS,
                        $require
                    ]
                ),
                ["priority" => static::DEFAULT_PACKAGE_PRIORITY]
            );
        }
    }

    

    /**
     * Merge collected concept data to the configuration
     * 
     * @return void
     */
    protected function buildConceptData(): void
    {
        $this->mergeTo(
            join(
                '.',
                [
                    ConfigNodeInterface::NODE_SINGULARITY,
                    ConfigNodeInterface::NODE_PACKAGE,
                    $this->getPackageName()
                ]
            ),
            $this->conceptData
        );
    }

    /**
     * Includes an external configuration file into the current configuration.
     *
     * This method is responsible for loading and merging an external configuration
     * file into the existing configuration of the application. It ensures that any
     * additional settings or overrides specified in the external file are applied.
     *
     * @return static Returns the current instance of the class for method chaining.
     */
    protected function includeExternalConfig(): static
    {
        $includes = $this->getComposerData()['extra']['concept']['include'] ?? null;
        if (null === $includes) {
            return $this;
        }
        if (!is_array($includes)) {
            $includes = [$includes];
        }

        foreach ($includes as $filename) {
         
            $filename = dirname($this->filename) . '/' . $filename;
            $this->conceptData['included'] = $this->conceptData['included'] ?? [];

            if (isset($this->conceptData['included'][$filename])) {
                //continue;
            }
             $this->conceptData['included'][$filename] = $this->conceptData['included'][$filename] ?? 0;
             $this->conceptData['included'][$filename]++;
            
            if (null === $filename || !is_file($filename) || !is_readable($filename)) {
                return $this;
            }
            
            $config = $this->readJson($filename);
            
            $this->merge($config);
        }
            
        return $this;
    }
}