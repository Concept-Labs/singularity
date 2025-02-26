<?php
namespace Concept\Singularity\Contract\Initialization;

use Concept\Config\ConfigInterface;

interface AutoConfigureInterface
{
    const AUTOCONFIGURE_METHOD = 'autoConfigure';
    const CONFIGURATION_NODE = 'auto-configure';
    /**
     * Configure the service
     * 
     * @param ConfigInterface $config
     * 
     * @return void
     */
    public function autoConfigure(ConfigInterface $config): void;
}