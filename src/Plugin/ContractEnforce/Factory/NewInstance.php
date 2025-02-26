<?php
namespace Concept\Singularity\Plugin\ContractEnforce\Factory;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Plugin\AbstractPlugin;

class NewInstance extends AbstractPlugin
{
    /**
     * Creates callable service factory for new instance objects
     * 
     * {@inheritDoc}
     */
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $context->setServiceFactory(
            static::factory($context, $args)
        );
    }

    /**
     * Creates callable service factory for new instance objects
     * 
     * @param ProtoContextInterface $context
     * @param array|null $args
     * 
     * @return callable
     */
    public static function factory(ProtoContextInterface $context, mixed $args = null): callable
    {
        return 
            fn(...$arguments) => new ($context->getServiceClass())(...$arguments);
    }

}