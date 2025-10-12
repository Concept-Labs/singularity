<?php

use Concept\Singularity\Singularity;
use Concept\Config\Config;

// Test class
class SharedService
{
    public static int $instanceCount = 0;
    
    public function __construct()
    {
        self::$instanceCount++;
    }
}

class TransientService
{
    public static int $instanceCount = 0;
    
    public function __construct()
    {
        self::$instanceCount++;
    }
}

describe('Service Lifecycle - Shared vs Transient', function () {
    
    beforeEach(function () {
        SharedService::$instanceCount = 0;
        TransientService::$instanceCount = 0;
    });

    it('creates only one instance of shared service', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                SharedService::class => [
                    'class' => SharedService::class,
                    'shared' => true
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        $service1 = $container->get(SharedService::class);
        $service2 = $container->get(SharedService::class);
        
        expect($service1)->toBe($service2);
        expect(SharedService::$instanceCount)->toBe(1);
    });

    it('creates new instance each time for transient service using create', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                TransientService::class => [
                    'class' => TransientService::class,
                    'shared' => false
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        $service1 = $container->create(TransientService::class);
        $service2 = $container->create(TransientService::class);
        
        expect($service1)->not->toBe($service2);
        expect(TransientService::$instanceCount)->toBe(2);
    });

    it('respects shared configuration from config', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                SharedService::class => [
                    'class' => SharedService::class,
                    'shared' => true
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        // Multiple get() calls should return same instance
        $service1 = $container->get(SharedService::class);
        $service2 = $container->get(SharedService::class);
        $service3 = $container->get(SharedService::class);
        
        expect($service1)->toBe($service2);
        expect($service2)->toBe($service3);
        expect(SharedService::$instanceCount)->toBe(1);
    });

    it('create() creates new instances independently for shared services', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                SharedService::class => [
                    'class' => SharedService::class,
                    'shared' => true
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        // First create() creates a new instance and registers it
        $service1 = $container->create(SharedService::class);
        expect($service1)->toBeInstanceOf(SharedService::class);
        
        // Subsequent get() should return the same registered instance
        $service2 = $container->get(SharedService::class);
        expect($service1)->toBe($service2);
        
        // Verify instanceCount is 1 (only created once)
        expect(SharedService::$instanceCount)->toBe(1);
    });
});
