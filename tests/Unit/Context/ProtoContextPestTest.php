<?php

use Concept\Singularity\Context\ProtoContext;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\SingularityInterface;
use Concept\Config\ConfigInterface;

/**
 * PEST tests for ProtoContext
 */

test('proto context implements interface', function () {
    $container = Mockery::mock(SingularityInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(false);
    $config->shouldReceive('get')->andReturn([]);
    
    $context = new ProtoContext(
        $container,
        stdClass::class,
        stdClass::class,
        [],
        $config
    );
    
    expect($context)->toBeInstanceOf(ProtoContextInterface::class);
});

test('proto context returns correct service id', function () {
    $container = Mockery::mock(SingularityInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(false);
    $config->shouldReceive('get')->andReturn([]);
    
    $context = new ProtoContext(
        $container,
        stdClass::class,
        stdClass::class,
        [],
        $config
    );
    
    expect($context->getServiceId())->toBe(stdClass::class);
});

test('proto context returns correct service class', function () {
    $container = Mockery::mock(SingularityInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(false);
    $config->shouldReceive('get')->andReturn([]);
    
    $context = new ProtoContext(
        $container,
        stdClass::class,
        stdClass::class,
        [],
        $config
    );
    
    expect($context->getServiceClass())->toBe(stdClass::class);
});

test('proto context returns reflection class', function () {
    $container = Mockery::mock(SingularityInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(false);
    $config->shouldReceive('get')->andReturn([]);
    
    $context = new ProtoContext(
        $container,
        stdClass::class,
        stdClass::class,
        [],
        $config
    );
    
    $reflection = $context->getReflection();
    
    expect($reflection)
        ->toBeInstanceOf(ReflectionClass::class)
        ->getName()->toBe(stdClass::class);
});

test('proto context can be cloned', function () {
    $container = Mockery::mock(SingularityInterface::class);
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(false);
    $config->shouldReceive('get')->andReturn([]);
    
    $context = new ProtoContext(
        $container,
        stdClass::class,
        stdClass::class,
        [],
        $config
    );
    
    $cloned = clone $context;
    
    expect($cloned)
        ->toBeInstanceOf(ProtoContext::class)
        ->not->toBe($context);
});
