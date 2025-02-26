<?php
namespace Concept\Singularity\Event;

use Concept\EventDispatcher\Event\Event;
use Concept\Singularity\Context\ProtoContextInterface;

class SingularityEvent extends Event implements SingularityEventInterface
{

    /**
     * {@inheritDoc}
     */
    public function setService(mixed $service): static
    {
        $this->attach('service', $service);

        return $this;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getServiceId(): string
    {
        return $this->getServiceContext()->getServiceId();
    }
    
    /**
     * {@inheritDoc}
     */
    public function getService(): mixed
    {
        return $this->getContext()->get('service');
    }

    /**
     * {@inheritDoc}
     */
    public function setServiceContext(ProtoContextInterface $serviceContext): static
    {
        $this->attach('serviceContext', $serviceContext);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getServiceContext(): ProtoContextInterface
    {
        return $this->getContext()->get('serviceContext');
    }
}