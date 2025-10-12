<?php

use Concept\Singularity\Singularity;
use Concept\Singularity\SingularityInterface;
use Concept\Config\ConfigInterface;
use Psr\Container\ContainerInterface;

/**
 * PEST tests for Singularity DI Container
 *
 * These tests verify the core functionality using PEST testing framework.
 */

test('singularity can be instantiated', function () {
    $container = new Singularity();
    
    expect($container)
        ->toBeInstanceOf(Singularity::class)
        ->toBeInstanceOf(SingularityInterface::class)
        ->toBeInstanceOf(ContainerInterface::class);
});

test('singularity can be instantiated with config', function () {
    $config = Mockery::mock(ConfigInterface::class);
    $container = new Singularity($config);
    
    expect($container)->toBeInstanceOf(Singularity::class);
});

test('singularity can set config after instantiation', function () {
    $container = new Singularity();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(false);
    
    $result = $container->setConfig($config);
    
    expect($result)->toBe($container);
});

test('singularity can register a service', function () {
    $container = new Singularity();
    $service = new stdClass();
    $serviceId = stdClass::class;
    
    $container->register($serviceId, $service);
    
    expect($container->has($serviceId))->toBeTrue();
});

test('singularity can retrieve registered service', function () {
    $container = new Singularity();
    $service = new stdClass();
    $service->testProperty = 'test_value';
    $serviceId = stdClass::class;
    
    $container->register($serviceId, $service);
    $retrieved = $container->get($serviceId);
    
    expect($retrieved)
        ->toBe($service)
        ->testProperty->toBe('test_value');
});

test('singularity has returns false for non-existent service', function () {
    $container = new Singularity();
    
    expect($container->has('NonExistentService'))->toBeFalse();
});

test('singularity returns itself for container interfaces', function () {
    $container = new Singularity();
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(true);
    $config->shouldReceive('get')->andReturn([
        'settings' => [
            'plugin-manager' => [
                'plugins' => []
            ]
        ]
    ]);
    
    $container->setConfig($config);
    
    expect($container->get(SingularityInterface::class))->toBe($container);
    expect($container->get(Singularity::class))->toBe($container);
    expect($container->get(ContainerInterface::class))->toBe($container);
});
