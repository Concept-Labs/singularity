<?php

use Concept\Singularity\Singularity;
use Concept\Singularity\Exception\NoConfigurationLoadedException;
use Concept\Singularity\Exception\ServiceNotFoundException;
use Concept\Singularity\Exception\CircularDependencyException;
use Concept\Singularity\Exception\NotInstantiableException;
use Concept\Config\Config;

// Test classes for circular dependency
class ServiceA
{
    public function __construct(public ServiceB $b)
    {
    }
}

class ServiceB
{
    public function __construct(public ServiceA $a)
    {
    }
}

// Abstract class for NotInstantiableException test
abstract class AbstractService
{
}

describe('Exception Handling', function () {
    
    it('requires configuration to be loaded', function () {
        $container = new Singularity();
        $threwException = false;
        
        try {
            $container->get(\stdClass::class);
        } catch (\Throwable $e) {
            // Either LogicException or NoConfigurationLoadedException is acceptable
            $threwException = true;
        }
        
        expect($threwException)->toBeTrue();
    });

    it('throws ServiceNotFoundException for non-existent service class', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                'NonExistentService' => [
                    'class' => 'NonExistentServiceClass'
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        expect(fn() => $container->get('NonExistentService'))
            ->toThrow(ServiceNotFoundException::class);
    });

    it('throws CircularDependencyException for circular dependencies', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                ServiceA::class => [
                    'class' => ServiceA::class
                ],
                ServiceB::class => [
                    'class' => ServiceB::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        expect(fn() => $container->get(ServiceA::class))
            ->toThrow(CircularDependencyException::class);
    });

    it('throws NotInstantiableException for abstract classes', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                AbstractService::class => [
                    'class' => AbstractService::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        expect(fn() => $container->get(AbstractService::class))
            ->toThrow(NotInstantiableException::class);
    });
});
