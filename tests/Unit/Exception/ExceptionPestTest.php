<?php

use Concept\Singularity\Exception\CircularDependencyException;
use Concept\Singularity\Exception\ServiceNotFoundException;
use Concept\Singularity\Exception\RuntimeException;
use Concept\Singularity\Exception\SingularityExceptionInterface;

/**
 * PEST tests for Exception classes
 */

test('circular dependency exception can be thrown', function () {
    throw new CircularDependencyException('test.service');
})->throws(CircularDependencyException::class);

test('service not found exception can be thrown', function () {
    throw new ServiceNotFoundException('NonExistentService');
})->throws(ServiceNotFoundException::class);

test('runtime exception can be thrown', function () {
    throw new RuntimeException('Runtime error');
})->throws(RuntimeException::class);

test('exceptions implement singularity exception interface', function () {
    $exception = new RuntimeException('Test');
    
    expect($exception)->toBeInstanceOf(SingularityExceptionInterface::class);
});

test('exception messages are preserved', function () {
    $message = 'Test error message';
    $exception = new RuntimeException($message);
    
    expect($exception->getMessage())->toBe($message);
});
