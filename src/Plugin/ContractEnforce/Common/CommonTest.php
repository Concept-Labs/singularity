<?php
namespace Concept\Singularity\Plugin\ContractEnforce\Common;

use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Plugin\AbstractPlugin;

class CommonTest extends AbstractPlugin
{


    /**
     * Cretaes callable service factory for lazy ghost objects
     * 
     * {@inheritDoc}
     */
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        echo "<br>COMMON::before() ".$context->getReflection()->getName();
    }

    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {   
        echo "<br>COMMON::after() ".$context->getReflection()->getName();
    }
}