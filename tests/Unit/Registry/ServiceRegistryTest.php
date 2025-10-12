<?php

namespace Concept\Singularity\Tests\Unit\Registry;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Registry\ServiceRegistry;
use Concept\Singularity\Registry\ServiceRegistryInterface;

/**
 * Unit tests for ServiceRegistry
 *
 * Tests service registration and retrieval functionality.
 */
class ServiceRegistryTest extends TestCase
{
    private ServiceRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ServiceRegistry();
    }

    /**
     * Test that ServiceRegistry implements ServiceRegistryInterface
     */
    public function testImplementsServiceRegistryInterface(): void
    {
        $this->assertInstanceOf(ServiceRegistryInterface::class, $this->registry);
    }

    /**
     * Test that ServiceRegistry can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ServiceRegistry::class, $this->registry);
    }

    /**
     * Test service registration
     */
    public function testRegisterService(): void
    {
        $service = new \stdClass();
        $serviceId = 'test.service';
        
        $this->registry->register($serviceId, $service);
        
        $this->assertTrue($this->registry->has($serviceId));
    }

    /**
     * Test service retrieval
     */
    public function testGetRegisteredService(): void
    {
        $service = new \stdClass();
        $service->value = 'test';
        $serviceId = 'test.service';
        
        $this->registry->register($serviceId, $service);
        $retrieved = $this->registry->get($serviceId);
        
        $this->assertSame($service, $retrieved);
        $this->assertEquals('test', $retrieved->value);
    }

    /**
     * Test has returns false for non-existent service
     */
    public function testHasReturnsFalseForNonExistentService(): void
    {
        $this->assertFalse($this->registry->has('non.existent.service'));
    }

    /**
     * Test weak reference registration
     */
    public function testRegisterServiceWithWeakReference(): void
    {
        $service = new \stdClass();
        $serviceId = 'test.weak.service';
        
        $this->registry->register($serviceId, $service, true);
        
        $this->assertTrue($this->registry->has($serviceId));
        $this->assertSame($service, $this->registry->get($serviceId));
    }

    /**
     * Test multiple service registration
     */
    public function testRegisterMultipleServices(): void
    {
        $service1 = new \stdClass();
        $service2 = new \stdClass();
        
        $this->registry->register('service1', $service1);
        $this->registry->register('service2', $service2);
        
        $this->assertTrue($this->registry->has('service1'));
        $this->assertTrue($this->registry->has('service2'));
        $this->assertSame($service1, $this->registry->get('service1'));
        $this->assertSame($service2, $this->registry->get('service2'));
    }
}
