<?php

use Concept\Singularity\Factory\FactoryInterface;

/**
 * PEST tests for Factory pattern
 */

test('factory interface defines create method', function () {
    expect(method_exists(FactoryInterface::class, 'create'))->toBeTrue();
});

test('custom factory can be implemented', function () {
    $factory = new class implements FactoryInterface {
        public function create(string $serviceId, array $args = []): object
        {
            return new stdClass();
        }
    };
    
    $service = $factory->create(stdClass::class);
    
    expect($service)->toBeInstanceOf(stdClass::class);
});

test('factory can create service with arguments', function () {
    $factory = new class implements FactoryInterface {
        public function create(string $serviceId, array $args = []): object
        {
            $obj = new stdClass();
            $obj->args = $args;
            return $obj;
        }
    };
    
    $args = ['key' => 'value'];
    $service = $factory->create(stdClass::class, $args);
    
    expect($service->args)->toBe($args);
});

test('factory can validate required arguments', function () {
    $factory = new class implements FactoryInterface {
        public function create(string $serviceId, array $args = []): object
        {
            if (!isset($args['required'])) {
                throw new InvalidArgumentException('Missing required argument: required');
            }
            return new stdClass();
        }
    };
    
    $factory->create(stdClass::class, []);
})->throws(InvalidArgumentException::class);

test('factory can set default values', function () {
    $factory = new class implements FactoryInterface {
        public function create(string $serviceId, array $args = []): object
        {
            $obj = new stdClass();
            $obj->status = $args['status'] ?? 'active';
            $obj->createdAt = $args['createdAt'] ?? new DateTime();
            return $obj;
        }
    };
    
    $service = $factory->create(stdClass::class);
    
    expect($service->status)->toBe('active');
    expect($service->createdAt)->toBeInstanceOf(DateTime::class);
});
