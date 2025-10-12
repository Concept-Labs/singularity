<?php

namespace Concept\Singularity\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Singularity;
use Concept\Config\ConfigInterface;

/**
 * Integration tests for Singularity Container
 *
 * Tests the full workflow of the dependency injection container
 * including service creation, dependency resolution, and plugin execution.
 */
class ContainerIntegrationTest extends TestCase
{
    private Singularity $container;

    protected function setUp(): void
    {
        $this->container = new Singularity();
    }

    /**
     * Test that container can create simple service
     */
    public function testContainerCanCreateSimpleService(): void
    {
        $config = $this->createBasicConfig();
        $this->container->setConfig($config);
        
        // Register and retrieve a simple service
        $service = new \stdClass();
        $this->container->register(\stdClass::class, $service);
        
        $retrieved = $this->container->get(\stdClass::class);
        
        $this->assertSame($service, $retrieved);
    }

    /**
     * Test service lifecycle with registration
     */
    public function testServiceLifecycleWithRegistration(): void
    {
        $config = $this->createBasicConfig();
        $this->container->setConfig($config);
        
        // Create a service with properties
        $service = new \stdClass();
        $service->id = 'test-id';
        $service->name = 'Test Service';
        
        // Register the service
        $this->container->register('test.service', $service);
        
        // Verify it exists
        $this->assertTrue($this->container->has('test.service'));
        
        // Retrieve and verify
        $retrieved = $this->container->get('test.service');
        $this->assertSame($service, $retrieved);
        $this->assertEquals('test-id', $retrieved->id);
        $this->assertEquals('Test Service', $retrieved->name);
    }

    /**
     * Test multiple services can coexist
     */
    public function testMultipleServicesCanCoexist(): void
    {
        $config = $this->createBasicConfig();
        $this->container->setConfig($config);
        
        // Create and register multiple services
        $service1 = new \stdClass();
        $service1->type = 'service1';
        
        $service2 = new \stdClass();
        $service2->type = 'service2';
        
        $service3 = new \stdClass();
        $service3->type = 'service3';
        
        $this->container->register('service.1', $service1);
        $this->container->register('service.2', $service2);
        $this->container->register('service.3', $service3);
        
        // Verify all exist
        $this->assertTrue($this->container->has('service.1'));
        $this->assertTrue($this->container->has('service.2'));
        $this->assertTrue($this->container->has('service.3'));
        
        // Verify they are distinct
        $this->assertNotSame(
            $this->container->get('service.1'),
            $this->container->get('service.2')
        );
        $this->assertNotSame(
            $this->container->get('service.2'),
            $this->container->get('service.3')
        );
    }

    /**
     * Test weak reference service registration
     */
    public function testWeakReferenceServiceRegistration(): void
    {
        $config = $this->createBasicConfig();
        $this->container->setConfig($config);
        
        $service = new \stdClass();
        $service->value = 'weak-service';
        
        // Register with weak reference
        $this->container->register('weak.service', $service, true);
        
        $this->assertTrue($this->container->has('weak.service'));
        $retrieved = $this->container->get('weak.service');
        $this->assertEquals('weak-service', $retrieved->value);
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
