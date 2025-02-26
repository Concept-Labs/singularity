<?php
namespace Concept\Singularity\Plugin\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Plugin implements AttributePluginInterface
{
    private mixed $args = null;


    public function __construct(
        private string $pluginClass,
        mixed $args = null
    ) {
        $this->args = $args;
    }

    

    public function getPlugin(): string
    {
        return $this->pluginClass;
    }

    public function getArgs(): mixed
    {
        return $this->args;
    }
}