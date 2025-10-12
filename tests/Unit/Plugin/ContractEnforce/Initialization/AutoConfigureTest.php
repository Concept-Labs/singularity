<?php

namespace Concept\Singularity\Tests\Unit\Plugin\ContractEnforce\Initialization;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Plugin\ContractEnforce\Initialization\AutoConfigure;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;
use Concept\Config\ConfigInterface;

/**
 * Unit tests for AutoConfigure plugin
 *
 * Tests the autoconfiguration functionality for services.
 */
class AutoConfigureTest extends TestCase
{
    /**
     * Test that AutoConfigure extends AbstractPlugin
     */
    public function testExtendsAbstractPlugin(): void
    {
        $reflection = new \ReflectionClass(AutoConfigure::class);
        
        $this->assertTrue($reflection->isSubclassOf(AbstractPlugin::class));
    }

    /**
     * Test autoconfigure calls __configure method on service
     */
    public function testAutoConfigureCallsConfigureMethod(): void
    {
        $service = new class implements AutoConfigureInterface {
            public bool $configured = false;
            
            public function __configure(ConfigInterface $config): void
            {
                $this->configured = true;
            }
        };
        
        $context = $this->createMock(ProtoContextInterface::class);
        $context->method('getServiceClass')->willReturn(get_class($service));
        $context->method('getReflectionMethod')->willReturn(
            new \ReflectionMethod($service, '__configure')
        );
        $config = $this->createMock(ConfigInterface::class);
        $context->method('getPreferenceConfig')->willReturn($config);
        
        AutoConfigure::after($service, $context, null);
        
        $this->assertTrue($service->configured);
    }

    /**
     * Test autoconfigure throws exception for non-autoconfigurable service
     */
    public function testThrowsExceptionForNonAutoConfigurableService(): void
    {
        $this->expectException(\Concept\Singularity\Exception\RuntimeException::class);
        
        $service = new \stdClass();
        $context = $this->createMock(ProtoContextInterface::class);
        $context->method('getServiceClass')->willReturn(\stdClass::class);
        
        AutoConfigure::after($service, $context, null);
    }
}
