<?php

namespace Concept\Singularity\Plugin\Attribute;

interface AttributePluginInterface
{
    /**
     * Get the plugin class
     * 
     * @return string
     */
    public function getPlugin(): string;

    /**
     * Get the plugin args
     * 
     * @return mixed
     */
    public function getArgs(): mixed;
}