<?php

namespace Concept\Singularity\Plugin\Attribute;

interface AttributePluginInterface
{
    public function getPlugin(): string;

    public function getArgs(): mixed;
}