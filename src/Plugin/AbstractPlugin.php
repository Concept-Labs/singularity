<?php
namespace Concept\Singularity\Plugin;

use Concept\Singularity\Context\ProtoContextInterface;

abstract class AbstractPlugin implements PluginInterface
{
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
    }

    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {   
    }

}