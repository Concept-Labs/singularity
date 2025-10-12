<?php

namespace Concept\Singularity\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Concept\Singularity\Exception\CircularDependencyException;
use Concept\Singularity\Exception\ServiceNotFoundException;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\Exception\SingularityExceptionInterface;

/**
 * Unit tests for Exception classes
 *
 * Tests exception handling and error reporting.
 */
class ExceptionTest extends TestCase
{
    /**
     * Test CircularDependencyException can be thrown
     */
    public function testCircularDependencyExceptionCanBeThrown(): void
    {
        $this->expectException(CircularDependencyException::class);
        
        throw new CircularDependencyException('test.service');
    }

    /**
     * Test ServiceNotFoundException can be thrown
     */
    public function testServiceNotFoundExceptionCanBeThrown(): void
    {
        $this->expectException(ServiceNotFoundException::class);
        
        throw new ServiceNotFoundException('NonExistentService');
    }

    /**
     * Test RuntimeException can be thrown
     */
    public function testRuntimeExceptionCanBeThrown(): void
    {
        $this->expectException(RuntimeException::class);
        
        throw new RuntimeException('Runtime error');
    }

    /**
     * Test exceptions implement SingularityExceptionInterface
     */
    public function testExceptionsImplementInterface(): void
    {
        $exception = new RuntimeException('Test');
        
        $this->assertInstanceOf(SingularityExceptionInterface::class, $exception);
    }

    /**
     * Test exception messages are preserved
     */
    public function testExceptionMessagesArePreserved(): void
    {
        $message = 'Test error message';
        $exception = new RuntimeException($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }
}
