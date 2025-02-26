<?php
namespace Concept\Singularity\Context\Cache;

use Concept\Singularity\Cache\SimpleCache;
use Concept\Singularity\Context\ProtoContextInterface;

class ProtoContextCache extends SimpleCache implements ProtoContextCacheInterface
{
    /**
     * {@inheritDoc}
     */
    public function key(string $serviceId, array $stack): string
    {
        return sprintf('%s@%s', $serviceId, hash('xxh3', json_encode($stack)).uniqid());
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, $default = null): ProtoContextInterface
    {
        return parent::get($key, $default);
    }

}