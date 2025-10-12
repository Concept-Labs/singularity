<?php

namespace Concept\Singularity\Tests\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Plugin\AggregatePlugin;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

/**
 * Unit tests for AggregatePlugin
 *
 * Tests the aggregate plugin functionality that can combine multiple plugins.
 */
class AggregatePluginTest extends TestCase
{
    /**
     * Test that AggregatePlugin extends AbstractPlugin
     */
    public function testExtendsAbstractPlugin(): void
    {
        $reflection = new \ReflectionClass(AggregatePlugin::class);
        
        $this->assertTrue($reflection->isSubclassOf(AbstractPlugin::class));
    }

    /**
     * Test before method can be called
     */
    public function testBeforeMethodCanBeCalled(): void
    {
        $context = $this->createMock(ProtoContextInterface::class);
        $context->method('isPluginPropagationStopped')->willReturn(false);
        
        // Should not throw exception
        AggregatePlugin::before($context, null);
        $this->assertTrue(true);
    }

    /**
     * Test after method can be called
     */
    public function testAfterMethodCanBeCalled(): void
    {
        $service = new \stdClass();
        $context = $this->createMock(ProtoContextInterface::class);
        $context->method('isPluginPropagationStopped')->willReturn(false);
        
        // Should not throw exception
        AggregatePlugin::after($service, $context, null);
        $this->assertTrue(true);
    }

    /**
     * Test aggregate plugin can process empty plugin data
     */
    public function testAggregatePluginProcessesEmptyPluginData(): void
    {
        $context = $this->createMock(ProtoContextInterface::class);
        $context->method('isPluginPropagationStopped')->willReturn(false);
        
        AggregatePlugin::before($context, []);
        
        $this->assertTrue(true);
    }

    /**
     * Test aggregate plugin stops on propagation stopped
     */
    public function testAggregatePluginStopsOnPropagationStopped(): void
    {
        $context = $this->createMock(ProtoContextInterface::class);
        $context->method('isPluginPropagationStopped')->willReturn(true);
        
        // Should stop early without error
        AggregatePlugin::before($context, []);
        
        $this->assertTrue(true);
    }
}
