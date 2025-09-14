<?php
namespace Concept\Singularity\Contract\Initialization;

interface InjectableInterface
{
    const INJECT_METHOD = '__di';

    /**
     * Method to inject dependencies into the service
     *
     * @return void
     */
    //public function __di(): void;
}