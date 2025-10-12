<?php

use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Plugin\PluginInterface;
use Concept\Singularity\Context\ProtoContextInterface;

/**
 * PEST tests for AbstractPlugin
 */

test('abstract plugin implements plugin interface', function () {
    $plugin = new class extends AbstractPlugin {};
    
    expect($plugin)->toBeInstanceOf(PluginInterface::class);
});

test('abstract plugin before method can be called', function () {
    $context = Mockery::mock(ProtoContextInterface::class);
    
    $plugin = new class extends AbstractPlugin {
        public static function before(ProtoContextInterface $context, mixed $args = null): void
        {
            parent::before($context, $args);
        }
    };
    
    $plugin::before($context, null);
    
    expect(true)->toBeTrue();
});

test('abstract plugin after method can be called', function () {
    $service = new stdClass();
    $context = Mockery::mock(ProtoContextInterface::class);
    
    $plugin = new class extends AbstractPlugin {
        public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
        {
            parent::after($service, $context, $args);
        }
    };
    
    $plugin::after($service, $context, null);
    
    expect(true)->toBeTrue();
});

test('custom plugin can override before method', function () {
    $context = Mockery::mock(ProtoContextInterface::class);
    $context->shouldReceive('getServiceId')->andReturn('TestService');
    
    $plugin = new class extends AbstractPlugin {
        private static bool $wasCalled = false;
        
        public static function before(ProtoContextInterface $context, mixed $args = null): void
        {
            self::$wasCalled = true;
        }
        
        public static function wasCalled(): bool
        {
            return self::$wasCalled;
        }
    };
    
    $plugin::before($context, null);
    
    expect($plugin::wasCalled())->toBeTrue();
});

test('custom plugin can override after method', function () {
    $service = new stdClass();
    $context = Mockery::mock(ProtoContextInterface::class);
    
    $plugin = new class extends AbstractPlugin {
        private static bool $wasCalled = false;
        
        public static function after(object $service, ProtoContextInterface $context, mixed $args = null): void
        {
            self::$wasCalled = true;
        }
        
        public static function wasCalled(): bool
        {
            return self::$wasCalled;
        }
    };
    
    $plugin::after($service, $context, null);
    
    expect($plugin::wasCalled())->toBeTrue();
});
