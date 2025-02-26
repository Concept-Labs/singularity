<?php
namespace Concept\Singularity\Config;

use Concept\Config\Adapter\JsonAdapter;
use Concept\Config\Config;
use Concept\Config\ConfigInterface;

class ConfigManager implements ConfigManagerInterface
{
    /**
     * @var array<int, ConfigInterface>
     */
    private ?ConfigInterface $config = null;

    /**
     * {@inheritDoc}
     */
    public function getConfig(): ConfigInterface
    {
        return $this->config ??= new Config();
    }

    /**
     * {@inheritDoc}
     */
    public function addConfig(string|array|ConfigInterface ...$configs): static
    {
        foreach ($configs as $config) {
            $this->addConfigItem($config);
        }

        return $this;
    }

    /**
     * Add a config item
     * 
     * @param string|array|ConfigInterface $config
     * 
     * @return static
     */
    protected function addConfigItem(string|array|ConfigInterface $config): static
    {
        $this->getConfig()->merge(
            match(true) {
                is_string($config) && file_exists($config) => JsonAdapter::load($config),
                is_string($config) && strpos($config, '{') === 0 => json_decode($config, true),
                is_array($config) || ($config instanceof ConfigInterface) => $config,
                default => throw new \InvalidArgumentException('Invalid config data')
            }
        );

        return $this;
    }
    
}