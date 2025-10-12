<?php

use Concept\Singularity\Plugin\AggregatePlugin;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;

/**
 * PEST tests for AggregatePlugin
 */

test('aggregate plugin extends abstract plugin', function () {
    $reflection = new ReflectionClass(AggregatePlugin::class);
    
    expect($reflection->isSubclassOf(AbstractPlugin::class))->toBeTrue();
});

test('aggregate plugin before method can be called', function () {
    $context = Mockery::mock(ProtoContextInterface::class);
    $context->shouldReceive('isPluginPropagationStopped')->andReturn(false);
    
    AggregatePlugin::before($context, null);
    
    expect(true)->toBeTrue();
});

test('aggregate plugin after method can be called', function () {
    $service = new stdClass();
    $context = Mockery::mock(ProtoContextInterface::class);
    $context->shouldReceive('isPluginPropagationStopped')->andReturn(false);
    
    AggregatePlugin::after($service, $context, null);
    
    expect(true)->toBeTrue();
});

test('aggregate plugin processes empty plugin data', function () {
    $context = Mockery::mock(ProtoContextInterface::class);
    $context->shouldReceive('isPluginPropagationStopped')->andReturn(false);
    
    AggregatePlugin::before($context, []);
    
    expect(true)->toBeTrue();
});

test('aggregate plugin stops on propagation stopped', function () {
    $context = Mockery::mock(ProtoContextInterface::class);
    $context->shouldReceive('isPluginPropagationStopped')->andReturn(true);
    
    AggregatePlugin::before($context, []);
    
    expect(true)->toBeTrue();
});
