<?php
namespace Concept\Singularity\Plugin\Exception;


class InvalidConfigurationException extends PluginException
{
    public function __construct(
        mixed $plugin
    )
    {
        parent::__construct(
            sprintf(
                'Invalid plugin configuration: "$s"',
                print_r($plugin, true)
            ),
            PluginExceptionInterface::CODE_INVALID_CONFIGURATION
        );
    }
}