<?php

namespace Concept\Singularity\Tests\Unit\Context;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Context\ProtoContext;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\SingularityInterface;
use Concept\Config\ConfigInterface;

/**
 * Unit tests for ProtoContext
 *
 * Tests the context object that holds service metadata and configuration.
 */
class ProtoContextTest extends TestCase
{
    private ProtoContext $context;
    private SingularityInterface $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(SingularityInterface::class);
        $config = $this->createMock(ConfigInterface::class);
        $config->method('has')->willReturn(false);
        $config->method('get')->willReturn([]);
        
        $this->context = new ProtoContext(
            $this->container,
            \stdClass::class,
            \stdClass::class,
            [],
            $config
        );
    }

    /**
     * Test that ProtoContext implements ProtoContextInterface
     */
    public function testImplementsProtoContextInterface(): void
    {
        $this->assertInstanceOf(ProtoContextInterface::class, $this->context);
    }

    /**
     * Test that ProtoContext can be instantiated
     */
    public function testCanBeInstantiated(): void
    {
        $this->assertInstanceOf(ProtoContext::class, $this->context);
    }

    /**
     * Test getServiceId returns correct service ID
     */
    public function testGetServiceIdReturnsCorrectId(): void
    {
        $this->assertEquals(\stdClass::class, $this->context->getServiceId());
    }

    /**
     * Test getServiceClass returns correct service class
     */
    public function testGetServiceClassReturnsCorrectClass(): void
    {
        $this->assertEquals(\stdClass::class, $this->context->getServiceClass());
    }

    /**
     * Test getReflection returns ReflectionClass
     */
    public function testGetReflectionReturnsReflectionClass(): void
    {
        $reflection = $this->context->getReflection();
        
        $this->assertInstanceOf(\ReflectionClass::class, $reflection);
        $this->assertEquals(\stdClass::class, $reflection->getName());
    }

    /**
     * Test getPreferenceConfig returns ConfigInterface
     */
    public function testGetPreferenceConfigReturnsConfigInterface(): void
    {
        $preferenceConfig = $this->context->getPreferenceConfig();
        
        $this->assertInstanceOf(ConfigInterface::class, $preferenceConfig);
    }

    /**
     * Test context can be cloned
     */
    public function testContextCanBeCloned(): void
    {
        $cloned = clone $this->context;
        
        $this->assertInstanceOf(ProtoContext::class, $cloned);
        $this->assertNotSame($this->context, $cloned);
    }
}
