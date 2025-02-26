<?php
namespace Concept\Singularity\Plugin\ContractEnforce\Factory;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Plugin\AbstractPlugin;

class LazyGhost extends AbstractPlugin
{
    /**
     * Cretaes callable service factory for lazy ghost objects
     * 
     * {@inheritDoc}
     */
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        $reflection = $context->getReflection();
        $hasAccessableConstructor = 
            $reflection->getConstructor() &&
            $reflection->getConstructor()->isPublic();

        $context->setServiceFactory(
            static fn (...$arguments) =>
                 $reflection->newLazyGhost(
                    static function ($object) use ($arguments, $hasAccessableConstructor) {
                        if ($hasAccessableConstructor) {
                            $object->__construct(...$arguments);
                        }
                    }
                )
        );

        echo sprintf(
            "<br>LAZY CREATED for %s",
            $context->getServiceId()
        );
    }
}