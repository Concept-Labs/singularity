<?php
namespace Concept\Singularity\Traits;

use Concept\Singularity\Config\ConfigNodeInterface;

trait SettingsTrait
{
    protected function getSettings(string $path = '', mixed $default = null)
    {
        $path = sprintf(
                    '%s.%s.%s',
                    ConfigNodeInterface::NODE_SINGULARITY,
                    ConfigNodeInterface::NODE_SETTINGS,
                    $path
        );
        
        return $this->getConfig()
            ->get($path) ?? $default;
    }
}