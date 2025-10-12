<?php

namespace Concept\Singularity\Tests\Unit\Plugin\ContractEnforce\Lifecycle;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Plugin\ContractEnforce\Lifecycle\Shared;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Config\ConfigInterface;

/**
 * Unit tests for Shared lifecycle plugin
 *
 * Tests the shared service lifecycle functionality.
 */
class SharedTest extends TestCase
{
    /**
     * Test that Shared extends AbstractPlugin
     */
    public function testExtendsAbstractPlugin(): void
    {
        $reflection = new \ReflectionClass(Shared::class);
        
        $this->assertTrue($reflection->isSubclassOf(AbstractPlugin::class));
    }

    /**
     * Test shared plugin can be called
     */
    public function testSharedPluginCanBeCalled(): void
    {
        $context = $this->createMock(ProtoContextInterface::class);
        $config = $this->createMock(ConfigInterface::class);
        $context->method('getPreferenceConfig')->willReturn($config);
        
        // Should not throw exception
        Shared::before($context, null);
        $this->assertTrue(true);
    }
}
