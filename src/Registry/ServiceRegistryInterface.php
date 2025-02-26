<?php
namespace Concept\Singularity\Registry;

use Psr\Container\ContainerInterface;

interface ServiceRegistryInterface extends ContainerInterface
{
    /**
     * Register a service with the given id.
     *
     * @param string $id       The id of the service.
     * @param object $service  The service to register.
     * @param bool $weak       Whether to store the service as a weak reference.
     *
     * @return static
     */
    public function register(string $id, object $service, bool $weak = false): static;
}