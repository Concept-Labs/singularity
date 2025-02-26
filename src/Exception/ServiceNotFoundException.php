<?php
namespace Concept\Singularity\Exception;

class ServiceNotFoundException extends SingularityException
{
    public function __construct(string $serviceId)
    {
        parent::__construct(
            sprintf(
                'Service "%s" not found',
                $serviceId
            ),
            ExceptionCode::SERVICE_NOT_FOUND->code()
        );
    }
}