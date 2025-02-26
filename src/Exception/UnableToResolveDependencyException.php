<?php
namespace Concept\Singularity\Exception;

use Concept\Singularity\Context\ProtoContextInterface;
use Throwable;

class UnableToResolveDependencyException extends SingularityException
{
    public function __construct(string $dependency, ProtoContextInterface $context, array $args = [], ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                        'Unable to resolve dependency for parameter "%s" in service "%s" (Resolved as "%s")',
                        $dependency,
                        $context->getServiceId(),
                        $context->getServiceClass()
                    ),
                    ExceptionCode::UNABLE_TO_RESOLVE_DEPENDENCY->code(),
            $previous
        );
    }
}