<?php
namespace Concept\Singularity;

use Psr\Container\ContainerInterface;
use Concept\Config\ConfigInterface;

interface SingularityInterface extends ContainerInterface
{
    /**
     * {@inheritDoc}
     * 
     * Get a service from the service manager.
     *
     * @param string $serviceId           The service identifier.
     * @param array $args                 The arguments to pass to the service constructor.
     * @param array|null $dependencyStack The dependency stack to build the service context.
     * 
     * @return object
     * 
     * @throws ServiceNotFoundException
     */
    public function get(string $serviceId, array $args = [], ?array $dependencyStack = null): object;

    /**
     * Require a service from the service manager.
     *
     * @param string $serviceId           The service identifier.
     * @param array $args                 The arguments to pass to the service constructor.
     * @param array|null $dependencyStack The service stack to build the service context.
     * @param bool $forceCreate           Force create the service.
     * 
     * @return object
     * 
     * @throws ServiceNotFoundException
     */
    //public function require(string $serviceId, array $args = [], ?array $dependencyStack = null, bool $forceCreate = false): object;
    
    /**
     * Create a service from the service manager.
     *
     * @param string $serviceId           The service identifier.
     * @param array $args                 The arguments to pass to the service constructor.
     * @param array|null $dependencyStack The dependency stack to build the service context.
     * 
     * @return object
     */
    public function create(string $serviceId, array $args = [], ?array $dependencyStack = null): object;

    /**
     * Register a service in the service manager.
     *
     * @param string $serviceId The service identifier.
     * @param object $service   The service object.
     * 
     * @return static
     */
    public function register(string $serviceId, object $service, bool $weak = false): static;

    /**
     * Check if a service is registered in the service manager.
     *
     * @param string $serviceId The service identifier.
     * 
     * @return bool
     */
    public function has(string $serviceId): bool;

    /**
     * Get the container.
     *
     * @return ContainerInterface
     */
    //public function getContainer(): ContainerInterface;


    /**
     * Get the config.
     *
     * @return ConfigInterface
     */
    public function getConfig(): ConfigInterface;

}