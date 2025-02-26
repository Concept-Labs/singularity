<?php
namespace Concept\Singularity\Event;

use Concept\EventDispatcher\Event\EventInterface;
use Concept\Singularity\Context\ProtoContextInterface;

interface SingularityEventInterface extends EventInterface
{
    /**
     * Set the service
     * 
     * @param mixed $service The service
     * 
     * @return static
     */
    public function setService(mixed $service): static;
    
    /**
     * Get the service id
     * 
     * @return string The service id
     */
    public function getServiceId(): string;
    
    /**
     * Get the service
     * 
     * @return mixed The service
     */
    public function getService(): mixed;

    /**
     * Set the service context
     * 
     * @param ProtoContextInterface $serviceContext The service context
     * 
     * @return static
     */
    public function setServiceContext(ProtoContextInterface $serviceContext): static;

    /**
     * Get the service context
     * 
     * @return ProtoContextInterface The service context
     */
    public function getServiceContext(): ProtoContextInterface;
}