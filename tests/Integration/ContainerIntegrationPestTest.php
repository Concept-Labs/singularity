<?php

use Concept\Singularity\Singularity;
use Concept\Config\ConfigInterface;

/**
 * PEST Integration tests for Singularity Container
 */

test('container can create and retrieve simple service', function () {
    $container = new Singularity();
    $config = createBasicConfig();
    $container->setConfig($config);
    
    $service = new stdClass();
    $container->register(stdClass::class, $service);
    
    $retrieved = $container->get(stdClass::class);
    
    expect($retrieved)->toBe($service);
});

test('multiple services can coexist in container', function () {
    $container = new Singularity();
    $config = createBasicConfig();
    $container->setConfig($config);
    
    $service1 = new stdClass();
    $service1->type = 'service1';
    
    $service2 = new stdClass();
    $service2->type = 'service2';
    
    $container->register('service.1', $service1);
    $container->register('service.2', $service2);
    
    expect($container->has('service.1'))->toBeTrue();
    expect($container->has('service.2'))->toBeTrue();
    expect($container->get('service.1'))->toBe($service1);
    expect($container->get('service.2'))->toBe($service2);
    expect($container->get('service.1'))->not->toBe($container->get('service.2'));
});

test('service lifecycle works correctly', function () {
    $container = new Singularity();
    $config = createBasicConfig();
    $container->setConfig($config);
    
    $service = new stdClass();
    $service->id = 'test-id';
    $service->name = 'Test Service';
    
    $container->register('test.service', $service);
    
    expect($container->has('test.service'))->toBeTrue();
    
    $retrieved = $container->get('test.service');
    
    expect($retrieved)
        ->toBe($service)
        ->id->toBe('test-id')
        ->and($retrieved->name)->toBe('Test Service');
});

/**
 * Helper function to create basic config
 */
function createBasicConfig(): ConfigInterface
{
    $config = Mockery::mock(ConfigInterface::class);
    $config->shouldReceive('has')->andReturn(true);
    $config->shouldReceive('get')->andReturn([
        'settings' => [
            'plugin-manager' => [
                'plugins' => []
            ]
        ]
    ]);
    
    return $config;
}
