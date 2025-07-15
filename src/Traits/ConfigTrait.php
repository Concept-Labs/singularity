<?php
namespace Concept\Singularity\Traits;

use Concept\Config\ConfigInterface;
use Concept\Singularity\Exception\RuntimeException;

trait ConfigTrait
{
    /**
     * @inheritDoc
     */
    public function getConfig(): ConfigInterface
    {
        if (null === $this->config) {
            throw new RuntimeException(
                'Config not set'
            );
        }
        return $this->config;
    }

}