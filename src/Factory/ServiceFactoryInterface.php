<?php
namespace Concept\Singularity\Factory;

interface ServiceFactoryInterface
{
    /**
     * Create a service
     * 
     * @param array $args
     * 
     * @return object
     */
    public function create(array $args = []);
}