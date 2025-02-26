<?php

namespace Concept\Singularity\Context;

use Concept\Config\Traits\ConfigurableTrait;
use Concept\Singularity\Config\ConfigNodeInterface;
use Concept\Singularity\SingularityInterface;
use Psr\SimpleCache\CacheInterface;

class ContextBuilder implements ContextBuilderInterface
{
    use ConfigurableTrait;

    private array $configData;
    private ?CacheInterface $cache = null;

    public function __construct(private readonly SingularityInterface $container)
    {
    }

    public function setCache(CacheInterface $cache): static
    {
        $this->cache = $cache;
        return $this;
    }

    public function getCache(): CacheInterface
    {
        if ($this->cache === null) {
            throw new \RuntimeException('Cache not set. Please provide a PSR-16 CacheInterface implementation.');
        }
        return $this->cache;
    }

    protected function getContextInstance(): ProtoContextInterface
    {
        return new ProtoContext($this->getContainer());
    }

    protected function inflateContext(string $serviceId, array $data, array $dependencyStack): ProtoContextInterface
    {
        return $this->getContextInstance()
            ->inflate([
                ConfigNodeInterface::NODE_SERVICE_ID => $serviceId,
                ConfigNodeInterface::NODE_DEPENDENCY_STACK => $dependencyStack,
                ConfigNodeInterface::NODE_PREFERENCE => $data
            ]);
    }

    public function build(string $serviceId, array $dependencyStack = [], array $overrides = []): ProtoContextInterface
    {
        $this->configData = &$this->getConfig()->asArrayRef();
        $cacheKey = $this->generateCacheKey($serviceId, $dependencyStack);
        $dependencyStack[] = $serviceId;

        if ($this->getCache()->has($cacheKey) && empty($overrides)) {
            $preferences = $this->getCache()->get($cacheKey);
        } else {
            $preferences = $this->buildPreferences($dependencyStack);
            $this->getCache()->set($cacheKey, $preferences);
        }

        if (!empty($overrides)) {
            $this->mergePreferences($preferences, $overrides);
        }

        $servicePreference = $preferences[$serviceId] ?? ['unresolved' => true];
        return $this->inflateContext($serviceId, $servicePreference, $dependencyStack);
    }

    private function buildPreferences(array $dependencyStack): array
    {
        $preferences = [];
        $processedPackages = [];

        foreach ($dependencyStack as $id) {
            $namespaces = $this->getNamespacesForId($id);
            foreach ($namespaces as $namespace) {
                if (!isset($this->configData['namespace'][$namespace])) {
                    continue;
                }

                if (isset($this->configData['namespace'][$namespace]['depends'])) {
                    foreach ($this->configData['namespace'][$namespace]['depends'] as $pack => $packData) {
                        if (!isset($processedPackages[$pack])) {
                            $this->processPackage($pack, $preferences, $processedPackages);
                        }
                    }
                }

                if (isset($this->configData['namespace'][$namespace]['preference'])) {
                    $this->mergePreferences($preferences, $this->configData['namespace'][$namespace]['preference']);
                }
            }

            if (isset($this->configData['preference'][$id])) {
                $this->mergePreferences($preferences, [$id => $this->configData['preference'][$id]]);
            }
        }

        return $preferences;
    }

    private function processPackage(string $pack, array &$preferences, array &$processedPackages): void
    {
        if (!isset($this->configData['package'][$pack])) {
            return;
        }

        $packData = $this->configData['package'][$pack];
        $processedPackages[$pack] = true;

        if (isset($packData['depends'])) {
            foreach ($packData['depends'] as $depPack => $depData) {
                if (!isset($processedPackages[$depPack])) {
                    $this->processPackage($depPack, $preferences, $processedPackages);
                }
            }
        }

        if (isset($packData['preference'])) {
            $this->mergePreferences($preferences, $packData['preference']);
        }
    }

    private function mergePreferences(array &$target, array $source): void
    {
        foreach ($source as $key => $value) {
            if (is_array($value) && isset($target[$key]) && is_array($target[$key])) {
                $this->mergePreferences($target[$key], $value);
            } else {
                $target[$key] = $value;
            }
        }
    }

    private function getNamespacesForId(string $serviceId): array
    {
        // Обчислюємо локально без кешу Memcached
        $parts = explode('\\', $serviceId);
        $namespace = '';
        $namespaces = [];
        foreach ($parts as $part) {
            $namespace .= $part . '\\';
            if (isset($this->configData['namespace'][$namespace])) {
                $namespaces[] = $namespace;
            }
        }
        return $namespaces;
    }

    private function generateCacheKey(string $serviceId, array $dependencyStack): string
    {
        return 'pref:' . $serviceId . ':' . implode(':', $dependencyStack);
    }

    protected function getContainer(): SingularityInterface
    {
        return $this->container;
    }
}