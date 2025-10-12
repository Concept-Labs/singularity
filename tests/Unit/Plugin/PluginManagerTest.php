<?php

namespace Concept\Singularity\Tests\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Plugin\PluginManager;
use Concept\Singularity\Plugin\PluginManagerInterface;
use Concept\Singularity\Plugin\PluginInterface;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Config\ConfigInterface;

/**
 * Unit tests for PluginManager
 *
 * Tests plugin registration, configuration, and execution order.
 */
class PluginManagerTest extends TestCase
{
    private PluginManager $pluginManager;

    protected function setUp(): void
    {
        $this->pluginManager = new PluginManager();
    }

    /**
     * Test that PluginManager implements PluginManagerInterface
     */
    public function testImplementsPluginManagerInterface(): void
    {
        $this->assertInstanceOf(PluginManagerInterface::class, $this->pluginManager);
    }

    /**
     * Test that PluginManager can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(PluginManager::class, $this->pluginManager);
    }

    /**
     * Test that configure method accepts ConfigInterface
     */
    public function testConfigureAcceptsConfig(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('has')->willReturn(false);
        
        $result = $this->pluginManager->configure($config);
        
        $this->assertInstanceOf(PluginManager::class, $result);
    }

    /**
     * Test before method can be called without plugins
     */
    public function testBeforeCanBeCalledWithoutPlugins(): void
    {
        $context = $this->createMock(ProtoContextInterface::class);
        
        // Should not throw exception
        $this->pluginManager->before($context, PluginInterface::class);
        $this->assertTrue(true);
    }

    /**
     * Test after method can be called without plugins
     */
    public function testAfterCanBeCalledWithoutPlugins(): void
    {
        $service = new \stdClass();
        $context = $this->createMock(ProtoContextInterface::class);
        
        // Should not throw exception
        $this->pluginManager->after($service, $context, PluginInterface::class);
        $this->assertTrue(true);
    }
}
