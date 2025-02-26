<?php
namespace Concept\Singularity\Config;

use Concept\Config\ConfigInterface;

interface ConfigManagerInterface
{
    public function addConfig(string|array|ConfigInterface ...$configs): static;
    public function getConfig(): ConfigInterface;
    //public function addDecorator(string $decorator): static;
}