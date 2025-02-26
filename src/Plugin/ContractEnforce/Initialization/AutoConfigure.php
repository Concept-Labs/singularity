<?php
namespace Concept\Singularity\Plugin\ContractEnforce\Initialization;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\Plugin\AbstractPlugin;

class AutoConfigure extends AbstractPlugin
{
    /**
     * Register the service in the service manager if it is shared and not already registered.
     * 
     * {@inheritDoc}
     */
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        if (!$service instanceof AutoConfigureInterface) {
            throw new RuntimeException(
                sprintf(
                    'Cannot autoconfigure service %s. It must implement %s',
                    $context->getServiceClass(),
                    AutoConfigureInterface::class
                )
            );
        }
        
        $autoConfigureMethod = 
            $context->getReflectionMethod(AutoConfigureInterface::AUTOCONFIGURE_METHOD);


        if ($autoConfigureMethod !== null) {
            $autoConfigureMethod->setAccessible(true);
            $autoConfigureMethod->invoke(
                $service, 
                /**
                 * @todo: pass whole preference config or specific node (f.e. "etc")?
                 */
                $context->getPreferenceConfig()
            );
        }
        
    }
}