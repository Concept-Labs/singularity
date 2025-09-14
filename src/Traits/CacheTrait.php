<?php
namespace Concept\Singularity\Traits;

use Psr\SimpleCache\CacheInterface;
use Concept\SimpleCache\NullCache;
use Concept\Singularity\Config\ConfigNodeInterface;

trait CacheTrait
{
    /**
     * Cache instance
     * 
     * @var CacheInterface|null
     */
    protected function getCache(): CacheInterface
    {
        if (null === $this->cache) {
            if ($this->isCacheEnabled()) {
                $class = $this->getCacheServiceClass();
                $args = $this->getCacheServiceArgs();
                $this->cache = new $class(
                    $this->getCacheServiceArgs()
                    //$this->get($serviceId)
                );
            } else {
                $this->cache = new NullCache();
            }
        }

        return $this->cache;
    }

    /**
     * Is cache enabled
     * 
     * @return bool
     */
    protected function isCacheEnabled(): bool
    {
        $enabled = $this->getCacheSettings(ConfigNodeInterface::NODE_ENABLED, false);
        return $enabled;
    }

    /**
     * Get the cache service id
     * 
     * @return string
     */
    protected function getCacheServiceClass()
    {
        return $this->getCacheSettings(ConfigNodeInterface::NODE_CLASS);
    }

    /**
     * Get the cache service args
     * 
     * @return array
     */
    protected function getCacheServiceArgs()
    {
        return $this->getCacheSettings(ConfigNodeInterface::NODE_ARGUMENTS);
    }

    protected function getCacheSettings(string $path = '', mixed $default = null)
    {
        $path = sprintf(
            '%s.%s',
            ConfigNodeInterface::NODE_CACHE,
            $path
        );

        return $this->getSettings($path, $default);
    }

    
}