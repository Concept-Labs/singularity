<?php

namespace Concept\Singularity\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Singularity;
use Concept\Singularity\SingularityInterface;
use Concept\Config\ConfigInterface;
use Psr\Container\ContainerInterface;

/**
 * Unit tests for Singularity DI Container
 *
 * Tests the core functionality of the dependency injection container
 * including service creation, registration, and retrieval.
 */
class SingularityTest extends TestCase
{
    private Singularity $container;

    protected function setUp(): void
    {
        $this->container = new Singularity();
    }

    /**
     * Test that Singularity can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(Singularity::class, $this->container);
        $this->assertInstanceOf(SingularityInterface::class, $this->container);
        $this->assertInstanceOf(ContainerInterface::class, $this->container);
    }

    /**
     * Test that container can be instantiated with config
     */
    public function testCanBeInstantiatedWithConfig(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $container = new Singularity($config);
        
        $this->assertInstanceOf(Singularity::class, $container);
    }

    /**
     * Test that container can set config after instantiation
     */
    public function testCanSetConfig(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('has')->willReturn(false);
        
        $result = $this->container->setConfig($config);
        
        $this->assertSame($this->container, $result);
    }

    /**
     * Test service registration
     */
    public function testRegisterService(): void
    {
        $service = new \stdClass();
        $serviceId = \stdClass::class;
        
        $result = $this->container->register($serviceId, $service);
        
        $this->assertSame($this->container, $result);
        $this->assertTrue($this->container->has($serviceId));
    }

    /**
     * Test that registered service can be retrieved
     */
    public function testGetRegisteredService(): void
    {
        $service = new \stdClass();
        $service->testProperty = 'test_value';
        $serviceId = \stdClass::class;
        
        $this->container->register($serviceId, $service);
        $retrieved = $this->container->get($serviceId);
        
        $this->assertSame($service, $retrieved);
        $this->assertEquals('test_value', $retrieved->testProperty);
    }

    /**
     * Test has() returns false for non-existent service
     */
    public function testHasReturnsFalseForNonExistentService(): void
    {
        $this->assertFalse($this->container->has('NonExistentService'));
    }

    /**
     * Test that container returns itself for container interfaces
     */
    public function testContainerReturnsSelfForContainerInterfaces(): void
    {
        $this->container->setConfig($this->createMockConfig());
        
        $this->assertSame($this->container, $this->container->get(SingularityInterface::class));
        $this->assertSame($this->container, $this->container->get(Singularity::class));
        $this->assertSame($this->container, $this->container->get(ContainerInterface::class));
    }

    /**
     * Create a mock config with basic setup
     */
    private function createMockConfig(): ConfigInterface
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
