<?php

namespace Concept\Singularity\Plugin\Exception;

use Concept\Singularity\Exception\SingularityExceptionInterface;

interface PluginExceptionInterface extends SingularityExceptionInterface
{
    const CODE_PLUGIN_NOT_FOUND = 2001;
    const CODE_INVALID_PLUGIN = 2002;
    const CODE_INVALID_PLUGIN_METHOD = 2003;
    const CODE_INVALID_CONFIGURATION = 20004;
}