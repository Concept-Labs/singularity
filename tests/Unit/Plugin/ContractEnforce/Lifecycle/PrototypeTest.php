<?php

namespace Concept\Singularity\Tests\Unit\Plugin\ContractEnforce\Lifecycle;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Plugin\ContractEnforce\Lifecycle\Prototype;
use Concept\Singularity\Plugin\AbstractPlugin;

/**
 * Unit tests for Prototype lifecycle plugin
 *
 * Tests the prototype service lifecycle functionality.
 */
class PrototypeTest extends TestCase
{
    /**
     * Test that Prototype extends AbstractPlugin
     */
    public function testExtendsAbstractPlugin(): void
    {
        $reflection = new \ReflectionClass(Prototype::class);
        
        $this->assertTrue($reflection->isSubclassOf(AbstractPlugin::class));
    }
}
