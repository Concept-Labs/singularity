<?php
namespace Concept\Singularity\Exception;

class NoConfigurationLoadedException extends SingularityException
{
    public function __construct(?string $message = null)
    {
        parent::__construct(
            'No configuration loaded. ' . $message ?? '', 
            ExceptionCode::CONFIGURATION_NOT_LOADED->code()
        );
    }
}