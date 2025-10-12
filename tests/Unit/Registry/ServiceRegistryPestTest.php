<?php

use Concept\Singularity\Registry\ServiceRegistry;
use Concept\Singularity\Registry\ServiceRegistryInterface;

/**
 * PEST tests for ServiceRegistry
 */

test('service registry implements interface', function () {
    $registry = new ServiceRegistry();
    
    expect($registry)->toBeInstanceOf(ServiceRegistryInterface::class);
});

test('service registry can register service', function () {
    $registry = new ServiceRegistry();
    $service = new stdClass();
    $serviceId = 'test.service';
    
    $registry->register($serviceId, $service);
    
    expect($registry->has($serviceId))->toBeTrue();
});

test('service registry can retrieve registered service', function () {
    $registry = new ServiceRegistry();
    $service = new stdClass();
    $service->value = 'test';
    $serviceId = 'test.service';
    
    $registry->register($serviceId, $service);
    $retrieved = $registry->get($serviceId);
    
    expect($retrieved)
        ->toBe($service)
        ->value->toBe('test');
});

test('service registry returns false for non-existent service', function () {
    $registry = new ServiceRegistry();
    
    expect($registry->has('non.existent.service'))->toBeFalse();
});

test('service registry can handle weak references', function () {
    $registry = new ServiceRegistry();
    $service = new stdClass();
    $serviceId = 'test.weak.service';
    
    $registry->register($serviceId, $service, true);
    
    expect($registry->has($serviceId))->toBeTrue();
    expect($registry->get($serviceId))->toBe($service);
});

test('service registry can handle multiple services', function () {
    $registry = new ServiceRegistry();
    $service1 = new stdClass();
    $service2 = new stdClass();
    
    $registry->register('service1', $service1);
    $registry->register('service2', $service2);
    
    expect($registry->has('service1'))->toBeTrue();
    expect($registry->has('service2'))->toBeTrue();
    expect($registry->get('service1'))->toBe($service1);
    expect($registry->get('service2'))->toBe($service2);
});
