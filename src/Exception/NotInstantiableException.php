<?php
namespace Concept\Singularity\Exception;

use Concept\Singularity\Context\ProtoContextInterface;
use Throwable;

class NotInstantiableException extends SingularityException
{
    public function __construct(ProtoContextInterface $context, ?Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                    'Service "%s" is not instantiable. Preference resolved as: "%s"',
                    $context->getServiceId(),
                    $context->getServiceClass()
            ),
            ExceptionCode::SERVICE_NOT_INSTANTIABLE->code(),
            $previous
        );
    }
}