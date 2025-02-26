<?php
namespace Concept\Singularity\Plugin\Exception;

use Concept\Singularity\Plugin\PluginInterface;

class PluginNotFoundException extends PluginException
{
    public function __construct(
        string $pluginClass
    )
    {
        parent::__construct(
            sprintf(
                'Plugin "%s" not found',
                $pluginClass
            ),
            PluginExceptionInterface::CODE_PLUGIN_NOT_FOUND
        );
    }
}