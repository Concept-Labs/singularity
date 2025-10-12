<?php

use Concept\Singularity\Singularity;
use Concept\Singularity\SingularityInterface;
use Concept\Config\Config;
use Psr\Container\ContainerInterface;

describe('Singularity Container', function () {
    
    it('can be instantiated', function () {
        $container = new Singularity();
        expect($container)->toBeInstanceOf(Singularity::class);
    });

    it('implements ContainerInterface', function () {
        $container = new Singularity();
        expect($container)->toBeInstanceOf(ContainerInterface::class);
    });

    it('implements SingularityInterface', function () {
        $container = new Singularity();
        expect($container)->toBeInstanceOf(SingularityInterface::class);
    });

    it('can be instantiated with config', function () {
        $config = new Config();
        $container = new Singularity($config);
        expect($container)->toBeInstanceOf(Singularity::class);
    });

    it('can set config after instantiation', function () {
        $container = new Singularity();
        $config = new Config();
        $result = $container->setConfig($config);
        expect($result)->toBe($container);
    });

    it('returns itself when requesting container interface', function () {
        $config = new Config();
        $config->set('singularity', []);
        $container = new Singularity($config);
        
        $result = $container->get(ContainerInterface::class);
        expect($result)->toBe($container);
    });

    it('returns itself when requesting SingularityInterface', function () {
        $config = new Config();
        $config->set('singularity', []);
        $container = new Singularity($config);
        
        $result = $container->get(SingularityInterface::class);
        expect($result)->toBe($container);
    });

    it('returns itself when requesting Singularity class', function () {
        $config = new Config();
        $config->set('singularity', []);
        $container = new Singularity($config);
        
        $result = $container->get(Singularity::class);
        expect($result)->toBe($container);
    });

    it('can register a service', function () {
        $container = new Singularity();
        $service = new stdClass();
        $result = $container->register('test.service', $service);
        
        expect($result)->toBe($container);
        expect($container->has('test.service'))->toBeTrue();
    });

    it('can retrieve a registered service', function () {
        $config = new Config();
        $config->set('singularity', [
            'preference' => [
                'test.service' => [
                    'class' => \stdClass::class
                ]
            ]
        ]);
        
        $container = new Singularity($config);
        $service = new stdClass();
        $service->value = 'test';
        
        $container->register('test.service', $service);
        $retrieved = $container->get('test.service');
        
        expect($retrieved)->toBe($service);
        expect($retrieved->value)->toBe('test');
    });

    it('has method returns false for non-registered service', function () {
        $container = new Singularity();
        expect($container->has('non.existent.service'))->toBeFalse();
    });

    it('has method returns true for registered service', function () {
        $container = new Singularity();
        $service = new stdClass();
        $container->register('test.service', $service);
        
        expect($container->has('test.service'))->toBeTrue();
    });
});
