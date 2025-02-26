<?php
namespace Concept\Singularity\Factory;


use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Lifecycle\SharedInterface;
use Concept\Singularity\SingularityInterface;

abstract class ServiceFactory implements ServiceFactoryInterface, SharedInterface
{

    public function __construct(
        private readonly SingularityInterface $container,
        private ProtoContextInterface $context
    )
    {
    }

    /**
     * Set context
     * 
     * @param ProtoContextInterface $context
     * 
     * @return static
     */
    public function setContext(ProtoContextInterface $context): static
    {
        $this->context = $context;

        return $this;
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
        return $this->getContainer()
            ->create(
                $serviceId,
                $args,
                $this->getContext()->getDependencyStack()
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
    
}