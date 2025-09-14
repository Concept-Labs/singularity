<?php
namespace Concept\Singularity\Plugin\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Plugin implements AttributePluginInterface
{

    /**
     * @param string $pluginClass
     * @param mixed $args
     */
    public function __construct(
        private string $pluginClass,
        private mixed $args = null
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getPlugin(): string
    {
        return $this->pluginClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getArgs(): mixed
    {
        return $this->args;
    }
}