<?php

namespace Concept\Singularity\Tests\Unit\Factory;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Factory\FactoryInterface;
use Concept\Singularity\Factory\ServiceFactory;

/**
 * Unit tests for Factory classes
 *
 * Tests factory pattern implementation.
 */
class FactoryTest extends TestCase
{
    /**
     * Test that FactoryInterface defines create method
     */
    public function testFactoryInterfaceDefinesCreateMethod(): void
    {
        $this->assertTrue(method_exists(FactoryInterface::class, 'create'));
    }

    /**
     * Test custom factory implementation
     */
    public function testCustomFactoryImplementation(): void
    {
        $factory = new class implements FactoryInterface {
            public function create(string $serviceId, array $args = []): object
            {
                return new \stdClass();
            }
        };
        
        $service = $factory->create(\stdClass::class);
        
        $this->assertInstanceOf(\stdClass::class, $service);
    }

    /**
     * Test factory can create service with arguments
     */
    public function testFactoryCanCreateServiceWithArguments(): void
    {
        $factory = new class implements FactoryInterface {
            public function create(string $serviceId, array $args = []): object
            {
                $obj = new \stdClass();
                $obj->args = $args;
                return $obj;
            }
        };
        
        $args = ['key' => 'value'];
        $service = $factory->create(\stdClass::class, $args);
        
        $this->assertEquals($args, $service->args);
    }

    /**
     * Test factory can validate required arguments
     */
    public function testFactoryCanValidateRequiredArguments(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $factory = new class implements FactoryInterface {
            public function create(string $serviceId, array $args = []): object
            {
                if (!isset($args['required'])) {
                    throw new \InvalidArgumentException('Missing required argument: required');
                }
                return new \stdClass();
            }
        };
        
        $factory->create(\stdClass::class, []);
    }
}
