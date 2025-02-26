<?php
namespace Concept\Singularity\Context\Cache;

use Psr\SimpleCache\CacheInterface;

interface ProtoContextCacheInterface extends CacheInterface
{
    public function key(string $serviceId, array $stack ): string;
}