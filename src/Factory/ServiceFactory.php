<?php
namespace Concept\Singularity\Factory;


use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\SingularityInterface;

abstract class ServiceFactory implements ServiceFactoryInterface, SharedInterface
{

    public function __construct(
        private readonly SingularityInterface $container,
        private readonly ProtoContextInterface $context
    )
    {
    }

    /**
     * Create service
     * 
     * @param string $serviceId
     * @param array $args
     * 
     * @return object
     */
    protected function createService(string $serviceId, array $args = []): object
    {
        $depStack = $this->getContext()->getDependencyStack();
        array_unshift($depStack, static::class);
        
        return $this->getContainer()
            ->create(
                $serviceId,
                $args,
                $depStack
            );
    }

    /**
     * Get service manager
     * 
     * @return SingularityInterface
     */
    protected function getContainer(): SingularityInterface
    {
        return $this->container;
    }

    /**
     * Get context
     * 
     * @return ProtoContextInterface
     */
    protected function getContext(): ProtoContextInterface
    {
        return $this->context;
    }
    
}