<?php

namespace Concept\Singularity\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Singularity;
use Concept\Singularity\Factory\FactoryInterface;
use Concept\Config\ConfigInterface;

/**
 * Integration tests for Factory functionality
 *
 * Tests that factories work correctly with the container.
 */
class FactoryIntegrationTest extends TestCase
{
    private Singularity $container;

    protected function setUp(): void
    {
        $this->container = new Singularity();
    }

    /**
     * Test custom factory can create services
     */
    public function testCustomFactoryCanCreateServices(): void
    {
        $factory = new class implements FactoryInterface {
            public function create(string $serviceId, array $args = []): object
            {
                $obj = new \stdClass();
                $obj->serviceId = $serviceId;
                $obj->createdAt = new \DateTime();
                return $obj;
            }
        };
        
        $service = $factory->create('test.service');
        
        $this->assertInstanceOf(\stdClass::class, $service);
        $this->assertEquals('test.service', $service->serviceId);
        $this->assertInstanceOf(\DateTime::class, $service->createdAt);
    }

    /**
     * Test factory with default values
     */
    public function testFactoryWithDefaultValues(): void
    {
        $factory = new class implements FactoryInterface {
            public function create(string $serviceId, array $args = []): object
            {
                $obj = new \stdClass();
                $obj->status = $args['status'] ?? 'active';
                $obj->role = $args['role'] ?? 'user';
                return $obj;
            }
        };
        
        $service = $factory->create('user.service');
        
        $this->assertEquals('active', $service->status);
        $this->assertEquals('user', $service->role);
    }

    /**
     * Test factory can override default values with arguments
     */
    public function testFactoryCanOverrideDefaultValues(): void
    {
        $factory = new class implements FactoryInterface {
            public function create(string $serviceId, array $args = []): object
            {
                $obj = new \stdClass();
                $obj->status = $args['status'] ?? 'active';
                $obj->role = $args['role'] ?? 'user';
                return $obj;
            }
        };
        
        $service = $factory->create('admin.service', [
            'status' => 'pending',
            'role' => 'admin'
        ]);
        
        $this->assertEquals('pending', $service->status);
        $this->assertEquals('admin', $service->role);
    }
}
