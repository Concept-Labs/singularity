<?php
namespace Concept\Singularity\Plugin\Exception;

use Concept\Singularity\Plugin\PluginInterface;

class InvalidPluginClassException extends PluginException
{
    public function __construct(
        string $pluginClass
    )
    {
        parent::__construct(
            sprintf(
                'Invalid plugin class: "%s". The plugin class must implement the "%s".',
                $pluginClass,
                PluginInterface::class
            ),
            PluginExceptionInterface::CODE_INVALID_PLUGIN
        );
    }
}