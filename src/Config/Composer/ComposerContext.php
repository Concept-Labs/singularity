<?php
namespace Concept\Singularity\Config\Composer;

use Concept\Config\Config;
use Concept\Config\ConfigInterface;

class ComposerContext implements ComposerContextInterface
{
    private array $loadedFiles = [];
    private ?ConfigInterface $config = null;

    

    /**
     * @var string|null
     */
    static private ?string $vendorDir = null;
    /**
     * @var array
     */
    protected array $composerPackages = [];

    public function __construct()
    {
        
    }

    public function getConfig(): ConfigInterface
    {
        
        return $this->config ??= new Config();
    }

    /**
     * {@inheritDoc}
    
     * phpdoc
     */
    public function build(): static
    {
        $this->collectPackages();

        foreach ($this->getPackages() as $package) {
            $this->getConfig()->merge(
                $package->build()
            );
        }

        return $this;
    }

    /**
     * Get loaded composerPackages
     * 
     * @return array
     */
    protected function getPackages(): array
    {
        return $this->composerPackages;
    }

    /**
     * Collect composerPackages
     * 
     * @return static
     */
    protected function collectPackages(): static
    {
        $composerFiles = array_merge(
            glob(static::getVendorDir() . '/*/*/composer.json'),
            [dirname(static::getVendorDir()) . '/composer.json']
        );
        
        foreach ($composerFiles as $composerFile) {
            $packageConfig = $this->createPackageConfig($composerFile);
            if (!$packageConfig->isCompatible()) {
                continue;
            }
            $this->composerPackages[$packageConfig->getPackageName()] = $this->createPackageConfig($composerFile);

        }

        array_filter($this->composerPackages);

        return $this;
    }

    /**
     * Create package config instance
     * 
     * @param string $path
     * @return PackageConfigInterface|null
     */
    protected function createPackageConfig(string $path): ?PackageConfigInterface
    {
        $packageConfig = new PackageConfig();
        $packageConfig->loadPackage($path);

        $packageConfig->setCompabilityValidator(
            fn (string $packageName) => $this->isPackageCompatible($packageName)
        );

        return $packageConfig;
    }

    /**
     * Check if the package is compatible
     * 
     * @param string $packageName
     * @return bool
     */
    protected function isPackageCompatible(string $packageName): bool
    {
        if (!preg_match('/^[a-z0-9-]+\/[a-z0-9-]+$/', $packageName)) {
            return false;
        }
        return $this->hasPackage($packageName) && $this->getPackage($packageName)->isCompatible();
    }

    /**
     * Get package instance
     * 
     * @param string $packageName
     * @return PackageConfigInterface
     */
    protected function getPackage(string $packageName): PackageConfigInterface
    {
        return $this->composerPackages[$packageName];
    }

    /**
     * Check if the package is loaded
     * 
     * @param string $packageName
     * @return bool
     */
    protected function hasPackage(string $packageName): bool
    {
        return isset($this->composerPackages[$packageName]);
    }

    /**
     * Get vendor directory
     * 
     * @return string|null
     */
    public static function getVendorDir(): ?string
    {
        if (null !== static::$vendorDir) {
            return static::$vendorDir;
        }
        if (!class_exists('Composer\Autoload\ClassLoader')) {
            throw new \RuntimeException('Composer is not loaded');
            return null;
        }

        
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        static::$vendorDir = dirname(dirname($reflection->getFileName()));

        return static::$vendorDir;
    }
    
}