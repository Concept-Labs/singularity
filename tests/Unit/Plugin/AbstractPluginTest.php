<?php

namespace Concept\Singularity\Tests\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Plugin\PluginInterface;
use Concept\Singularity\Context\ProtoContextInterface;

/**
 * Unit tests for AbstractPlugin
 *
 * Tests the base plugin functionality that all plugins extend.
 */
class AbstractPluginTest extends TestCase
{
    /**
     * Test that AbstractPlugin implements PluginInterface
     */
    public function testImplementsPluginInterface(): void
    {
        $plugin = new class extends AbstractPlugin {};
        
        $this->assertInstanceOf(PluginInterface::class, $plugin);
    }

    /**
     * Test that before() method can be called without error
     */
    public function testBeforeMethodCanBeCalled(): void
    {
        $context = $this->createMock(ProtoContextInterface::class);
        
        $plugin = new class extends AbstractPlugin {
            public static function before(ProtoContextInterface $context, mixed $args = null): void
            {
                parent::before($context, $args);
            }
        };
        
        // Should not throw exception
        $plugin::before($context, null);
        $this->assertTrue(true);
    }

    /**
     * Test that after() method can be called without error
     */
    public function testAfterMethodCanBeCalled(): void
    {
        $service = new \stdClass();
        $context = $this->createMock(ProtoContextInterface::class);
        
        $plugin = new class extends AbstractPlugin {
            public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
            {
                parent::after($service, $context, $args);
            }
        };
        
        // Should not throw exception
        $plugin::after($service, $context, null);
        $this->assertTrue(true);
    }

    /**
     * Test custom plugin can override before method
     */
    public function testCustomPluginCanOverrideBeforeMethod(): void
    {
        $context = $this->createMock(ProtoContextInterface::class);
        $context->method('getServiceId')->willReturn('TestService');
        
        $called = false;
        $plugin = new class($called) extends AbstractPlugin {
            private static bool $wasCalled = false;
            
            public static function before(ProtoContextInterface $context, mixed $args = null): void
            {
                self::$wasCalled = true;
            }
            
            public static function wasCalled(): bool
            {
                return self::$wasCalled;
            }
        };
        
        $plugin::before($context, null);
        $this->assertTrue($plugin::wasCalled());
    }

    /**
     * Test custom plugin can override after method
     */
    public function testCustomPluginCanOverrideAfterMethod(): void
    {
        $service = new \stdClass();
        $context = $this->createMock(ProtoContextInterface::class);
        
        $plugin = new class extends AbstractPlugin {
            private static bool $wasCalled = false;
            
            public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
            {
                self::$wasCalled = true;
            }
            
            public static function wasCalled(): bool
            {
                return self::$wasCalled;
            }
        };
        
        $plugin::after($service, $context, null);
        $this->assertTrue($plugin::wasCalled());
    }
}
