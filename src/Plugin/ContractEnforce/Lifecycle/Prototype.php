<?php
namespace Concept\Singularity\Plugin\ContractEnforce\Lifecycle;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Lifecycle\PrototypeInterface;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\Plugin\AbstractPlugin;

class Prototype extends AbstractPlugin
{

    /**
     * Register the service in the service manager if it is shared and not already registered.
     * 
     * {@inheritDoc}
     */
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        if ($context->isPluginDisabled(static::class)) {
            return;
        }

        if (!$service instanceof PrototypeInterface) {
            throw new RuntimeException(
                sprintf(
                    'The service %s must implement %s',
                    $context->getServiceClass(),
                    PrototypeInterface::class
                )
            );
        }

        $shared = $args['shared'] ?? true;
        $weak = $args['weak'] ?? false;

        if ($shared && !$context->getContainer()->has($context->getSharedId())) {
            $context->getContainer()->register($context->getSharedId(), $service, $weak);
        }
    }
}