<?php

use Concept\Singularity\Singularity;
use Concept\Config\Config;

// Test classes
class SimpleService
{
    public string $name = 'SimpleService';
}

class ServiceWithDependency
{
    public function __construct(public SimpleService $dependency)
    {
    }
}

class ServiceWithOptionalDependency
{
    public function __construct(public ?SimpleService $dependency = null)
    {
    }
}

class ServiceWithDefaultValue
{
    public function __construct(public string $name = 'default')
    {
    }
}

describe('Service Creation and Dependency Injection', function () {
    
    it('can create a simple service without configuration', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                SimpleService::class => [
                    'class' => SimpleService::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(SimpleService::class);
        
        expect($service)->toBeInstanceOf(SimpleService::class);
        expect($service->name)->toBe('SimpleService');
    });

    it('can create a service with dependencies', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                SimpleService::class => [
                    'class' => SimpleService::class
                ],
                ServiceWithDependency::class => [
                    'class' => ServiceWithDependency::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(ServiceWithDependency::class);
        
        expect($service)->toBeInstanceOf(ServiceWithDependency::class);
        expect($service->dependency)->toBeInstanceOf(SimpleService::class);
    });

    it('can handle optional dependencies', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                ServiceWithOptionalDependency::class => [
                    'class' => ServiceWithOptionalDependency::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(ServiceWithOptionalDependency::class);
        
        expect($service)->toBeInstanceOf(ServiceWithOptionalDependency::class);
        expect($service->dependency)->toBeNull();
    });

    it('can handle default values', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                ServiceWithDefaultValue::class => [
                    'class' => ServiceWithDefaultValue::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(ServiceWithDefaultValue::class);
        
        expect($service)->toBeInstanceOf(ServiceWithDefaultValue::class);
        expect($service->name)->toBe('default');
    });

    it('can pass arguments to service constructor', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                ServiceWithDefaultValue::class => [
                    'class' => ServiceWithDefaultValue::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(ServiceWithDefaultValue::class, ['name' => 'custom']);
        
        expect($service)->toBeInstanceOf(ServiceWithDefaultValue::class);
        expect($service->name)->toBe('custom');
    });

    it('can configure arguments via config', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                ServiceWithDefaultValue::class => [
                    'class' => ServiceWithDefaultValue::class,
                    'arguments' => [
                        'name' => 'configured'
                    ]
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(ServiceWithDefaultValue::class);
        
        expect($service)->toBeInstanceOf(ServiceWithDefaultValue::class);
        expect($service->name)->toBe('configured');
    });
});
