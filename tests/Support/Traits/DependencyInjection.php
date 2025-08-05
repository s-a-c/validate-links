<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

use Mockery;
use Mockery\MockInterface;

/**
 * Dependency Injection Trait for PEST Tests
 * 
 * Provides advanced dependency injection patterns for test doubles,
 * including automatic mock registration, service binding, and cleanup.
 */
trait DependencyInjection
{
    /**
     * Registry of created mocks for cleanup
     * @var array<MockInterface>
     */
    private array $mockRegistry = [];
    
    /**
     * Registry of bound services for cleanup
     * @var array<string>
     */
    private array $serviceBindings = [];
    
    /**
     * Create and register a mock with automatic cleanup
     *
     * @param string $interface Interface or class to mock
     * @param array<string, mixed> $config Mock configuration
     * @return MockInterface
     */
    protected function mock(string $interface, array $config = []): MockInterface
    {
        $mock = Mockery::mock($interface);
        
        // Apply configuration
        if (!empty($config)) {
            $this->configureMock($mock, $config);
        }
        
        // Register for cleanup
        $this->mockRegistry[] = $mock;
        
        return $mock;
    }
    
    /**
     * Create a partial mock with automatic cleanup
     *
     * @param string $class Class to partially mock
     * @param array<string, mixed> $config Mock configuration
     * @return MockInterface
     */
    protected function partialMock(string $class, array $config = []): MockInterface
    {
        /** @var MockInterface $mock */
        $mock = Mockery::mock($class)->makePartial();
        
        if (!empty($config)) {
            $this->configureMock($mock, $config);
        }
        
        $this->mockRegistry[] = $mock;
        
        return $mock;
    }
    
    /**
     * Create a spy with automatic cleanup
     *
     * @param string $interface Interface or class to spy
     * @param array<string, mixed> $config Spy configuration
     * @return MockInterface
     */
    protected function spy(string $interface, array $config = []): MockInterface
    {
        $spy = Mockery::spy($interface);
        
        if (!empty($config)) {
            $this->configureMock($spy, $config);
        }
        
        $this->mockRegistry[] = $spy;
        
        return $spy;
    }
    
    /**
     * Bind a service in the container with automatic cleanup
     * 
     * @param string $abstract Service abstract/interface
     * @param mixed $concrete Service implementation or factory
     * @param bool $singleton Whether to bind as singleton
     * @return void
     */
    protected function bindService(string $abstract, mixed $concrete, bool $singleton = false): void
    {
        if ($singleton) {
            app()->singleton($abstract, $concrete);
        } else {
            app()->bind($abstract, $concrete);
        }
        
        $this->serviceBindings[] = $abstract;
    }
    
    /**
     * Bind a mock as a service with automatic cleanup
     * 
     * @param string $abstract Service abstract/interface
     * @param array $config Mock configuration
     * @param bool $singleton Whether to bind as singleton
     * @return MockInterface
     */
    protected function bindMock(string $abstract, array $config = [], bool $singleton = true): MockInterface
    {
        $mock = $this->mock($abstract, $config);
        $this->bindService($abstract, $mock, $singleton);
        
        return $mock;
    }
    
    /**
     * Replace a service with a mock
     *
     * @param string $abstract Service abstract/interface
     * @param array<string, mixed> $config Mock configuration
     * @return MockInterface
     */
    protected function replaceMock(string $abstract, array $config = []): MockInterface
    {
        // Forget existing binding
        app()->forgetInstance($abstract);
        
        return $this->bindMock($abstract, $config);
    }
    
    /**
     * Create a fake implementation
     *
     * @param string $interface Interface to fake
     * @param array<string, mixed> $methods Methods to implement
     * @return object
     */
    protected function fake(string $interface, array $methods = []): object
    {
        $fake = new class($methods) {
            /** @var array<string, mixed> */
            private array $methods;
            
            /** @param array<string, mixed> $methods */
            public function __construct(array $methods)
            {
                $this->methods = $methods;
            }
            
            /** @param array<mixed> $arguments */
            public function __call(string $name, array $arguments): mixed
            {
                if (isset($this->methods[$name])) {
                    return is_callable($this->methods[$name])
                        ? ($this->methods[$name])(...$arguments)
                        : $this->methods[$name];
                }
                
                return null;
            }
        };
        
        $this->bindService($interface, $fake);
        
        return $fake;
    }
    
    /**
     * Configure a mock with expectations
     *
     * @param MockInterface $mock Mock to configure
     * @param array<string, mixed> $config Configuration array
     * @return void
     */
    private function configureMock(MockInterface $mock, array $config): void
    {
        foreach ($config as $method => $expectation) {
            if (is_array($expectation)) {
                // Complex expectation with parameters
                $mockExpectation = $mock->shouldReceive($method);
                
                if (isset($expectation['with'])) {
                    $withArgs = is_array($expectation['with']) ? $expectation['with'] : [$expectation['with']];
                    $mockExpectation->with(...$withArgs);
                }
                
                if (isset($expectation['andReturn'])) {
                    $mockExpectation->andReturn($expectation['andReturn']);
                }
                
                if (isset($expectation['andThrow'])) {
                    $mockExpectation->andThrow($expectation['andThrow']);
                }
                
                if (isset($expectation['times'])) {
                    $mockExpectation->times($expectation['times']);
                }
                
                if (isset($expectation['once']) && $expectation['once']) {
                    $mockExpectation->once();
                }
                
                if (isset($expectation['never']) && $expectation['never']) {
                    $mockExpectation->never();
                }
            } else {
                // Simple return value
                $mock->shouldReceive($method)->andReturn($expectation);
            }
        }
    }
    
    /**
     * Assert that a mock was called with specific parameters
     *
     * @param MockInterface $mock Mock to check
     * @param string $method Method name
     * @param array<mixed> $parameters Expected parameters
     * @return void
     */
    protected function assertMockCalled(MockInterface $mock, string $method, array $parameters = []): void
    {
        $mock->shouldHaveReceived($method, $parameters);
    }
    
    /**
     * Assert that a mock was not called
     * 
     * @param MockInterface $mock Mock to check
     * @param string $method Method name
     * @return void
     */
    protected function assertMockNotCalled(MockInterface $mock, string $method): void
    {
        $mock->shouldNotHaveReceived($method);
    }
    
    /**
     * Clean up all mocks and service bindings
     * Call this in afterEach() or tearDown()
     * 
     * @return void
     */
    protected function cleanupDependencyInjection(): void
    {
        // Clean up mocks
        foreach ($this->mockRegistry as $mock) {
            if ($mock instanceof MockInterface) {
                $mock->mockery_teardown();
            }
        }
        $this->mockRegistry = [];
        
        // Clean up service bindings
        foreach ($this->serviceBindings as $abstract) {
            app()->forgetInstance($abstract);
        }
        $this->serviceBindings = [];
        
        // Close Mockery
        Mockery::close();
    }
}