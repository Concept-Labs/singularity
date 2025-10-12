<?php

use Concept\Singularity\Plugin\ContractEnforce\Initialization\AutoConfigure;
use Concept\Singularity\Plugin\AbstractPlugin;
use Concept\Singularity\Context\ProtoContextInterface;
use Concept\Singularity\Contract\Initialization\AutoConfigureInterface;
use Concept\Config\ConfigInterface;

/**
 * PEST tests for AutoConfigure plugin
 */

test('autoconfigure extends abstract plugin', function () {
    $reflection = new ReflectionClass(AutoConfigure::class);
    
    expect($reflection->isSubclassOf(AbstractPlugin::class))->toBeTrue();
});

test('autoconfigure calls __configure method on service', function () {
    $service = new class implements AutoConfigureInterface {
        public bool $configured = false;
        
        public function __configure(ConfigInterface $config): void
        {
            $this->configured = true;
        }
    };
    
    $context = Mockery::mock(ProtoContextInterface::class);
    $context->shouldReceive('getServiceClass')->andReturn(get_class($service));
    $context->shouldReceive('getReflectionMethod')->andReturn(
        new ReflectionMethod($service, '__configure')
    );
    $config = Mockery::mock(ConfigInterface::class);
    $context->shouldReceive('getPreferenceConfig')->andReturn($config);
    
    AutoConfigure::after($service, $context, null);
    
    expect($service->configured)->toBeTrue();
});

test('autoconfigure throws exception for non-autoconfigurable service', function () {
    $service = new stdClass();
    $context = Mockery::mock(ProtoContextInterface::class);
    $context->shouldReceive('getServiceClass')->andReturn(stdClass::class);
    
    AutoConfigure::after($service, $context, null);
})->throws(\Concept\Singularity\Exception\RuntimeException::class);
