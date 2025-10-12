<?php

namespace Tests\Unit\ConfigTest {
    interface LoggerInterface
    {
        public function log(string $message): void;
    }

    class FileLogger implements LoggerInterface
    {
        public function __construct(public string $path = '/tmp/app.log')
        {
        }
        
        public function log(string $message): void
        {
            // Implementation
        }
    }

    class ConsoleLogger implements LoggerInterface
    {
        public function log(string $message): void
        {
            // Implementation
        }
    }

    class ServiceWithInterface
    {
        public function __construct(public LoggerInterface $logger)
        {
        }
    }
}

namespace Tests\Unit\ConfigTest {
    use Concept\Singularity\Singularity;
    use Concept\Config\Config;

    describe('Configuration Features', function () {
        
        it('can configure interface to class mapping', function () {
            $config = new Config();
            $config->set('singularity', [
                'preference' => [
                    LoggerInterface::class => [
                        'class' => FileLogger::class
                    ],
                    ServiceWithInterface::class => [
                        'class' => ServiceWithInterface::class
                    ]
                ]
            ]);
            
            $container = new Singularity($config);
            $service = $container->create(ServiceWithInterface::class);
            
            expect($service->logger)->toBeInstanceOf(FileLogger::class);
        });

        it('can override interface implementation', function () {
            $config = new Config();
            $config->set('singularity', [
                'preference' => [
                    LoggerInterface::class => [
                        'class' => ConsoleLogger::class
                    ],
                    ServiceWithInterface::class => [
                        'class' => ServiceWithInterface::class
                    ]
                ]
            ]);
            
            $container = new Singularity($config);
            $service = $container->create(ServiceWithInterface::class);
            
            expect($service->logger)->toBeInstanceOf(ConsoleLogger::class);
        });

        it('can configure constructor arguments', function () {
            $config = new Config();
            $config->set('singularity', [
                'preference' => [
                    FileLogger::class => [
                        'class' => FileLogger::class,
                        'arguments' => [
                            'path' => '/custom/path/app.log'
                        ]
                    ]
                ]
            ]);
            
            $container = new Singularity($config);
            $logger = $container->create(FileLogger::class);
            
            expect($logger->path)->toBe('/custom/path/app.log');
        });

        it('can configure service references as arguments', function () {
            $config = new Config();
            $config->set('singularity', [
                'preference' => [
                    LoggerInterface::class => [
                        'class' => FileLogger::class,
                        'shared' => true
                    ],
                    ServiceWithInterface::class => [
                        'class' => ServiceWithInterface::class,
                        'arguments' => [
                            'logger' => [
                                'type' => 'service',
                                'preference' => LoggerInterface::class
                            ]
                        ]
                    ]
                ]
            ]);
            
            $container = new Singularity($config);
            $service = $container->create(ServiceWithInterface::class);
            
            expect($service->logger)->toBeInstanceOf(FileLogger::class);
        });
    });
}

