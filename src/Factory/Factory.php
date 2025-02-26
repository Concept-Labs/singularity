<?php
namespace Concept\Singularity\Factory;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\SingularityInterface;

class Factory implements FactoryInterface
{
    /**
     * Factory constructor.
     * 
     * @param SingularityInterface $container
     */
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
     * @return object
     */
    public function create(string $serviceId, array $args = [], ?array $dependencyStack = null): object
    {
        return $this->getContainer()->create(
            $serviceId,
            $args,
            $dependencyStack ?? $this->getContext()->getDependencyStack()
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