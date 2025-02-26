<?php

namespace Concept\Singularity\Plugin;

use Concept\Singularity\Context\ProtoContextInterface;

interface PluginInterface
{
    const BEFORE = 'before';
    const AFTER = 'after';

    public static function before(ProtoContextInterface $context, mixed $args = null): void;
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void;
}