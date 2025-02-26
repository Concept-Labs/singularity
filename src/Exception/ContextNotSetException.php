<?php
namespace Concept\Singularity\Exception;

class ContextNotSetException extends SingularityException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            sprintf(
                'Context not set. %s',
                $message ?? ''
            ), 
            ExceptionCode::CONTEXT_NOT_SET->code());
    }
}