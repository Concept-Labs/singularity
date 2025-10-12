<?php

use Concept\Singularity\Singularity;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Config\Config;

// Test service
class PluginTestService
{
    public bool $beforeCalled = false;
    public bool $afterCalled = false;
}

// Test plugin
class TestPlugin extends AbstractPlugin
{
    public static bool $beforeCalled = false;
    public static bool $afterCalled = false;
    public static ?string $serviceId = null;
    
    public static function before(ProtoContextInterface $context, mixed $args = null): void
    {
        self::$beforeCalled = true;
        self::$serviceId = $context->getServiceId();
    }
    
    public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
    {
        self::$afterCalled = true;
        if ($service instanceof PluginTestService) {
            $service->afterCalled = true;
        }
    }
}

describe('Plugin System', function () {
    
    beforeEach(function () {
        TestPlugin::$beforeCalled = false;
        TestPlugin::$afterCalled = false;
        TestPlugin::$serviceId = null;
    });

    it('can register and execute plugins', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                PluginTestService::class => [
                    'class' => PluginTestService::class
                ]
            ],
            'settings' => [
                'plugin-manager' => [
                    'plugins' => [
                        TestPlugin::class => []
                    ]
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(PluginTestService::class);
        
        expect(TestPlugin::$beforeCalled)->toBeTrue();
        expect(TestPlugin::$afterCalled)->toBeTrue();
        expect(TestPlugin::$serviceId)->toBe(PluginTestService::class);
        expect($service->afterCalled)->toBeTrue();
    });

    it('executes before hook before service creation', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                PluginTestService::class => [
                    'class' => PluginTestService::class
                ]
            ],
            'settings' => [
                'plugin-manager' => [
                    'plugins' => [
                        TestPlugin::class => []
                    ]
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        
        // Before creating service, plugin should not be called
        expect(TestPlugin::$beforeCalled)->toBeFalse();
        
        $container->create(PluginTestService::class);
        
        // After creating service, before hook should be called
        expect(TestPlugin::$beforeCalled)->toBeTrue();
    });

    it('executes after hook after service creation', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                PluginTestService::class => [
                    'class' => PluginTestService::class
                ]
            ],
            'settings' => [
                'plugin-manager' => [
                    'plugins' => [
                        TestPlugin::class => []
                    ]
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = $container->create(PluginTestService::class);
        
        expect(TestPlugin::$afterCalled)->toBeTrue();
        expect($service->afterCalled)->toBeTrue();
    });
});
