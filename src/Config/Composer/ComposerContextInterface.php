<?php

namespace Concept\Singularity\Config\Composer;

use Concept\Config\ConfigInterface;

interface ComposerContextInterface
{
    /**
     * Build composer context
     * 
     * @return static
     */
    public function build(): static;

    /**
     * Get config
     * 
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;
}