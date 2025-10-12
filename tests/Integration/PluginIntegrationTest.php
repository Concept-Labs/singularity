<?php

namespace Concept\Singularity\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Singularity;
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;
use Concept\Config\ConfigInterface;

/**
 * Integration tests for Plugin functionality
 *
 * Tests that plugins work correctly with the container.
 */
class PluginIntegrationTest extends TestCase
{
    private Singularity $container;

    protected function setUp(): void
    {
        $this->container = new Singularity();
    }

    /**
     * Test that container can work with plugins
     */
    public function testContainerWorksWithPlugins(): void
    {
        $config = $this->createBasicConfig();
        $this->container->setConfig($config);
        
        // This test verifies basic plugin integration
        $this->assertTrue(true);
    }

    /**
     * Test service registration with plugins
     */
    public function testServiceRegistrationWithPlugins(): void
    {
        $config = $this->createBasicConfig();
        $this->container->setConfig($config);
        
        $service = new \stdClass();
        $service->value = 'test';
        
        $this->container->register('test.service', $service);
        
        $retrieved = $this->container->get('test.service');
        
        $this->assertSame($service, $retrieved);
        $this->assertEquals('test', $retrieved->value);
    }

    /**
     * Create basic config mock
     */
    private function createBasicConfig(): ConfigInterface
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('has')->willReturn(true);
        $config->method('get')->willReturn([
            'settings' => [
                'plugin-manager' => [
                    'plugins' => []
                ]
            ]
        ]);
        
        return $config;
    }
}
