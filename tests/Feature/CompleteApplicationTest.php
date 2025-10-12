<?php

namespace Tests\Feature\CompleteApp {
    interface DatabaseInterface
    {
        public function query(string $sql): array;
    }

    interface LoggerInterface
    {
        public function log(string $message): void;
    }

    class Database implements DatabaseInterface
    {
        public function __construct(
            public string $host = 'localhost',
            public string $database = 'test'
        ) {
        }
        
        public function query(string $sql): array
        {
            return [];
        }
    }

    class Logger implements LoggerInterface
    {
        public function __construct(public string $level = 'info')
        {
        }
        
        public function log(string $message): void
        {
            // Log implementation
        }
    }

    class UserRepository
    {
        public function __construct(
            public DatabaseInterface $database,
            public LoggerInterface $logger
        ) {
        }
        
        public function findUser(int $id): ?object
        {
            $this->logger->log("Finding user with id: $id");
            $results = $this->database->query("SELECT * FROM users WHERE id = $id");
            return $results[0] ?? null;
        }
    }

    class UserService
    {
        public function __construct(
            public UserRepository $repository,
            public LoggerInterface $logger
        ) {
        }
        
        public function getUser(int $id): ?object
        {
            $this->logger->log("UserService: Getting user $id");
            return $this->repository->findUser($id);
        }
    }
}

namespace Tests\Feature\CompleteApp {
    use Concept\Singularity\Singularity;
    use Concept\Config\Config;

    describe('Feature: Complete Application Scenario', function () {
        
        it('can build a complete application with dependency injection', function () {
            $config = new Config();
            $config->set('singularity', [
                'preference' => [
                    // Interface mappings
                    DatabaseInterface::class => [
                        'class' => Database::class,
                        'shared' => true,
                        'arguments' => [
                            'host' => 'localhost',
                            'database' => 'myapp'
                        ]
                    ],
                    LoggerInterface::class => [
                        'class' => Logger::class,
                        'shared' => true,
                        'arguments' => [
                            'level' => 'debug'
                        ]
                    ],
                    // Concrete classes
                    UserRepository::class => [
                        'class' => UserRepository::class,
                        'shared' => true
                    ],
                    UserService::class => [
                        'class' => UserService::class
                    ]
                ]
            ]);
            
            $container = new Singularity($config);
            
            // Get the UserService - all dependencies should be auto-injected
            $userService = $container->get(UserService::class);
            
            expect($userService)->toBeInstanceOf(UserService::class);
            expect($userService->repository)->toBeInstanceOf(UserRepository::class);
            expect($userService->logger)->toBeInstanceOf(Logger::class);
            expect($userService->repository->database)->toBeInstanceOf(Database::class);
            expect($userService->repository->logger)->toBeInstanceOf(Logger::class);
            
            // Verify shared instances
            expect($userService->logger)->toBe($userService->repository->logger);
            expect($userService->repository->database->host)->toBe('localhost');
            expect($userService->repository->database->database)->toBe('myapp');
            expect($userService->logger->level)->toBe('debug');
        });

        it('ensures shared services are singletons across dependency tree', function () {
            $config = new Config();
            $config->set('singularity', [
                'preference' => [
                    DatabaseInterface::class => [
                        'class' => Database::class,
                        'shared' => true
                    ],
                    LoggerInterface::class => [
                        'class' => Logger::class,
                        'shared' => true
                    ],
                    UserRepository::class => [
                        'class' => UserRepository::class
                    ],
                    UserService::class => [
                        'class' => UserService::class
                    ]
                ]
            ]);
            
            $container = new Singularity($config);
            
            $service1 = $container->get(UserService::class);
            $service2 = $container->get(UserService::class);
            $repository = $container->get(UserRepository::class);
            
            // Database and Logger should be same instance everywhere
            expect($service1->repository->database)->toBe($service2->repository->database);
            expect($service1->logger)->toBe($service2->logger);
            expect($repository->database)->toBe($service1->repository->database);
            expect($repository->logger)->toBe($service1->logger);
        });
    });
}

