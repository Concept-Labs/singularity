<?php
namespace Concept\Singularity\Plugin\ContractEnforce\Lifecycle;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Plugin\AbstractPlugin;

class Shared extends AbstractPlugin
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

        $shared = $args['shared'] ?? true;
        $weak = $args['weak'] ?? false;

        echo sprintf(
            "<br>Sharing... shared: %s; weak: %s",
            $shared ? 'true' : 'false',
            $weak ? 'true' : 'false'
        );

        if ($shared && !$context->getContainer()->has($context->getSharedId())) {
            $context->getContainer()->register($context->getSharedId(), $service, $weak);
        }
    }
}