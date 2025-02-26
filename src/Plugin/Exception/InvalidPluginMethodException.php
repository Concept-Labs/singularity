<?php
namespace Concept\Singularity\Plugin\Exception;


class InvalidPluginMethodException extends PluginException
{
    public function __construct(
        string $plugin,
        string $method
    )
    {
        parent::__construct(
            sprintf('Invalid plugin method "%s::%s()"', $plugin, $method),
            PluginExceptionInterface::CODE_INVALID_PLUGIN_METHOD
        );
    }
}