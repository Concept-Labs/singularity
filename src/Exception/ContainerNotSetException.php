<?php
namespace Concept\Singularity\Exception;

class ContainerNotSetException extends SingularityException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            sprintf(
                'Container not set. %s',
                $message ?? ''
            ), 
            ExceptionCode::CONTAINER_NOT_SET->code());
    }
}