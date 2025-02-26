<?php
namespace Concept\Singularity\Exception;

use Throwable;

class CircularDependencyException extends SingularityException
{
    public function __construct(string $serviceId, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                'Circular dependency detected for service "%s"',
                $serviceId
            ),
            ExceptionCode::CIRCULAR_DEPENDENCY->code(),
            $previous
        );
    }
}