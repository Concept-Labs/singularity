<?php

namespace Concept\Singularity\Contract\Lifecycle;

/**
 * Interface PrototypeInterface
 * 
 * The prototype lifecycle interface.
 * Used for services that should be instantiated once 
 * and return prototype instances on each request.
 * 
 * @package Concept\Singularity
 */
interface PrototypeInterface
{
    /**
     * Get the prototype instance of the service.
     * By default, it should return a cloned version of the current instance.
     * However, in some cases, this method may return a new instance of the service 
     * with modified or default values.
     * 
     * @return static A new instance or a deep clone or reset of the current instance, etc.
     */
    public function prototype(): static;
}