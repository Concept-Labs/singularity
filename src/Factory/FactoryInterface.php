<?php
namespace Concept\Singularity\Factory;

interface FactoryInterface
{
    /**
     * Create a service instance
     * 
     * @param string $serviceId The service identifier
     * @param array $args The arguments to pass to the service
     * 
     * @return object The service instance
     */
    public function create(string $serviceId, array $args = []);
}