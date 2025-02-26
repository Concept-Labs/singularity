<?php
namespace Concept\Singularity\Exception;

enum ExceptionCode : int
{
    case CONFIGURATION_NOT_LOADED = 1010;
    case CIRCULAR_DEPENDENCY = 1020;
    case SERVICE_NOT_FOUND = 1030;
    case SERVICE_NOT_INSTANTIABLE = 1040;
    case UNABLE_TO_RESOLVE_DEPENDENCY = 1050;
    case SERVICE_MANAGER_NOT_SET = 1060;
    case SERVICE_FACTORY_NOT_SET = 1070;
    case CONTAINER_NOT_SET = 1080;
    case CONTEXT_NOT_SET = 1090;

    public function code(): int
    {
        return $this->value;
    }

}