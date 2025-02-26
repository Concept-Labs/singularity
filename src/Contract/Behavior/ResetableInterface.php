<?php
namespace Concept\Singularity\Contract\Behavior;

interface ResetableInterface
{
    /**
     * Reset the object
     * 
     * @return static
     */
    public function reset(): static;
}