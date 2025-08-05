<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use App\Services\ReportingService;
use App\Services\StatisticsService;
use App\Services\GitHubAnchorService;
use Mockery;
use Mockery\MockInterface;
use Exception;
use Throwable;
use ReflectionClass;
use ReflectionException;

/**
 * Container test helper for dependency injection testing.
 * 
 * Provides utilities for testing service container functionality,
 * service resolution, binding validation, and dependency management.
 * 
 * @author StandAloneComplex
 * @since 1.0.0
 */
final class ContainerTestHelper
{
    /** @var array<string, string> */
    private static array $coreServiceBindings = [
        SecurityValidationInterface::class => SecurityValidationService::class,
        ReportingInterface::class => ReportingService::class,
        StatisticsInterface::class => StatisticsService::class,
        GitHubAnchorInterface::class => GitHubAnchorService::class,
        LinkValidationInterface::class => LinkValidationService::class,
    ];
    
    /** @var array<string, array<string>> */
    private static array $serviceDependencies = [
        LinkValidationService::class => [SecurityValidationInterface::class],
        SecurityValidationService::class => [],
        ReportingService::class => [],
        StatisticsService::class => [],
        GitHubAnchorService::class => [],
    ];
    
    /** @var array<string, mixed> */
    private static array $testBindings = [];
    
    /** @var array<string> */
    private static array $containerErrors = [];

    /**
     * Create an isolated container for testing.
     */
    public static function createIsolatedContainer(): Application
    {
        try {
            // Create fresh application instance
            $app = require __DIR__ . '/../../bootstrap/app.php';
            
            // Clear existing bindings
            self::clearAllBindings($app);
            
            // Register core service bindings
            self::registerCoreServices($app);
            
            return $app;
            
        } catch (Throwable $e) {
            self::$containerErrors[] = "Failed to create isolated container: " . $e->getMessage();
            throw new Exception("Container creation failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Reset container to clean state.
     */
    public static function resetContainerState(Application $app): void
    {
        try {
            // Clear all resolved instances
            self::clearResolvedInstances($app);
            
            // Clear all bindings
            self::clearAllBindings($app);
            
            // Clear singletons
            self::clearSingletons($app);
            
            // Reset service providers
            self::resetServiceProviders($app);
            
            // Re-register core services
            self::registerCoreServices($app);
            
        } catch (Throwable $e) {
            self::$containerErrors[] = "Failed to reset container state: " . $e->getMessage();
            throw new Exception("Container reset failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate all container bindings.
     */
    public static function validateContainerBindings(Application $app): array
    {
        $results = [
            'valid_bindings' => [],
            'invalid_bindings' => [],
            'missing_dependencies' => [],
            'resolution_errors' => [],
            'binding_conflicts' => [],
        ];
        
        try {
            foreach (self::$coreServiceBindings as $interface => $implementation) {
                try {
                    // Test binding resolution
                    $instance = $app->make($interface);
                    
                    $results['valid_bindings'][] = [
                        'interface' => $interface,
                        'implementation' => get_class($instance),
                        'expected' => $implementation,
                        'matches' => $instance instanceof $implementation,
                        'is_singleton' => $app->isShared($interface),
                    ];
                    
                    // Validate dependencies
                    $depResults = self::validateServiceDependencies($app, $implementation);
                    if (!empty($depResults['missing'])) {
                        $results['missing_dependencies'][$interface] = $depResults['missing'];
                    }
                    
                } catch (Throwable $e) {
                    $results['invalid_bindings'][] = [
                        'interface' => $interface,
                        'implementation' => $implementation,
                        'error' => $e->getMessage(),
                        'type' => get_class($e),
                    ];
                }
            }
            
            // Check for binding conflicts
            $conflicts = self::detectBindingConflicts($app);
            $results['binding_conflicts'] = $conflicts;
            
        } catch (Throwable $e) {
            $results['resolution_errors'][] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Test service resolution for all registered services.
     */
    public static function testServiceResolution(Application $app): array
    {
        $results = [
            'successful_resolutions' => [],
            'failed_resolutions' => [],
            'resolution_times' => [],
        ];
        
        foreach (self::$coreServiceBindings as $interface => $implementation) {
            $startTime = microtime(true);
            
            try {
                $instance = $app->make($interface);
                $resolutionTime = microtime(true) - $startTime;
                
                $results['successful_resolutions'][] = [
                    'interface' => $interface,
                    'implementation' => get_class($instance),
                    'resolution_time' => $resolutionTime,
                    'memory_usage' => memory_get_usage(true),
                ];
                
                $results['resolution_times'][$interface] = $resolutionTime;
                
            } catch (Throwable $e) {
                $resolutionTime = microtime(true) - $startTime;
                
                $results['failed_resolutions'][] = [
                    'interface' => $interface,
                    'implementation' => $implementation,
                    'error' => $e->getMessage(),
                    'resolution_time' => $resolutionTime,
                ];
            }
        }
        
        return $results;
    }

    /**
     * Validate interface bindings.
     */
    public static function validateInterfaceBindings(Application $app): array
    {
        $results = [
            'correct_bindings' => [],
            'incorrect_bindings' => [],
            'missing_bindings' => [],
        ];
        
        foreach (self::$coreServiceBindings as $interface => $expectedImplementation) {
            try {
                if (!$app->bound($interface)) {
                    $results['missing_bindings'][] = [
                        'interface' => $interface,
                        'expected' => $expectedImplementation,
                    ];
                    continue;
                }
                
                $instance = $app->make($interface);
                $actualImplementation = get_class($instance);
                
                if ($instance instanceof $expectedImplementation) {
                    $results['correct_bindings'][] = [
                        'interface' => $interface,
                        'implementation' => $actualImplementation,
                        'expected' => $expectedImplementation,
                    ];
                } else {
                    $results['incorrect_bindings'][] = [
                        'interface' => $interface,
                        'actual' => $actualImplementation,
                        'expected' => $expectedImplementation,
                    ];
                }
                
            } catch (Throwable $e) {
                $results['incorrect_bindings'][] = [
                    'interface' => $interface,
                    'error' => $e->getMessage(),
                    'expected' => $expectedImplementation,
                ];
            }
        }
        
        return $results;
    }

    /**
     * Test service lifecycle (singleton vs transient).
     */
    public static function testServiceLifecycle(Application $app): array
    {
        $results = [
            'singleton_services' => [],
            'transient_services' => [],
            'lifecycle_errors' => [],
        ];
        
        foreach (self::$coreServiceBindings as $interface => $implementation) {
            try {
                // Test if service is singleton
                $instance1 = $app->make($interface);
                $instance2 = $app->make($interface);
                
                if ($instance1 === $instance2) {
                    $results['singleton_services'][] = [
                        'interface' => $interface,
                        'implementation' => get_class($instance1),
                        'is_shared' => $app->isShared($interface),
                    ];
                } else {
                    $results['transient_services'][] = [
                        'interface' => $interface,
                        'implementation' => get_class($instance1),
                        'is_shared' => $app->isShared($interface),
                    ];
                }
                
            } catch (Throwable $e) {
                $results['lifecycle_errors'][] = [
                    'interface' => $interface,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Validate dependency graph integrity.
     */
    public static function validateDependencyGraph(Application $app): array
    {
        $results = [
            'valid_dependencies' => [],
            'missing_dependencies' => [],
            'circular_dependencies' => [],
            'dependency_chain' => [],
        ];
        
        foreach (self::$serviceDependencies as $service => $dependencies) {
            try {
                $depResults = self::validateServiceDependencies($app, $service);
                
                if (empty($depResults['missing'])) {
                    $results['valid_dependencies'][$service] = $depResults['resolved'];
                } else {
                    $results['missing_dependencies'][$service] = $depResults['missing'];
                }
                
                // Build dependency chain
                $chain = self::buildDependencyChain($service, []);
                $results['dependency_chain'][$service] = $chain;
                
                // Check for circular dependencies
                if (self::hasCircularDependency($service, [])) {
                    $results['circular_dependencies'][] = $service;
                }
                
            } catch (Throwable $e) {
                $results['missing_dependencies'][$service] = [$e->getMessage()];
            }
        }
        
        return $results;
    }

    /**
     * Create mock service bindings for testing.
     */
    public static function createMockServiceBindings(Application $app, array $mockConfig = []): array
    {
        $mocks = [];
        
        foreach (self::$coreServiceBindings as $interface => $implementation) {
            $mockName = $mockConfig[$interface]['name'] ?? basename(str_replace('\\', '/', $interface));
            
            try {
                $mock = Mockery::mock($interface);
                
                // Apply mock configuration if provided
                if (isset($mockConfig[$interface]['setup'])) {
                    $mockConfig[$interface]['setup']($mock);
                }
                
                // Bind mock to container
                $app->bind($interface, fn() => $mock);
                
                // Register with mock lifecycle manager
                MockLifecycleManager::registerMock($mockName, $mock);
                
                $mocks[$interface] = $mock;
                
            } catch (Throwable $e) {
                self::$containerErrors[] = "Failed to create mock for {$interface}: " . $e->getMessage();
            }
        }
        
        return $mocks;
    }

    /**
     * Test container performance under load.
     */
    public static function testContainerPerformance(Application $app, int $iterations = 1000): array
    {
        $results = [
            'resolution_times' => [],
            'memory_usage' => [],
            'average_resolution_time' => 0,
            'peak_memory' => 0,
            'performance_issues' => [],
        ];
        
        $totalTime = 0;
        $initialMemory = memory_get_usage(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            foreach (self::$coreServiceBindings as $interface => $implementation) {
                $startTime = microtime(true);
                $startMemory = memory_get_usage(true);
                
                try {
                    $instance = $app->make($interface);
                    $resolutionTime = microtime(true) - $startTime;
                    $memoryUsed = memory_get_usage(true) - $startMemory;
                    
                    $results['resolution_times'][$interface][] = $resolutionTime;
                    $results['memory_usage'][$interface][] = $memoryUsed;
                    
                    $totalTime += $resolutionTime;
                    
                    // Check for performance issues
                    if ($resolutionTime > 0.1) { // 100ms threshold
                        $results['performance_issues'][] = [
                            'interface' => $interface,
                            'iteration' => $i,
                            'resolution_time' => $resolutionTime,
                            'issue' => 'slow_resolution',
                        ];
                    }
                    
                } catch (Throwable $e) {
                    $results['performance_issues'][] = [
                        'interface' => $interface,
                        'iteration' => $i,
                        'error' => $e->getMessage(),
                        'issue' => 'resolution_failure',
                    ];
                }
            }
        }
        
        $results['average_resolution_time'] = $totalTime / ($iterations * count(self::$coreServiceBindings));
        $results['peak_memory'] = memory_get_peak_usage(true) - $initialMemory;
        
        return $results;
    }

    /**
     * Get container statistics.
     */
    public static function getContainerStatistics(Application $app): array
    {
        $stats = [
            'total_bindings' => 0,
            'singleton_bindings' => 0,
            'resolved_instances' => 0,
            'service_providers' => 0,
            'memory_usage' => memory_get_usage(true),
            'container_errors' => count(self::$containerErrors),
        ];
        
        try {
            $bindings = self::getContainerBindings($app);
            $stats['total_bindings'] = count($bindings);
            
            $instances = self::getContainerInstances($app);
            $stats['resolved_instances'] = count($instances);
            
            // Count singleton bindings
            foreach (array_keys($bindings) as $abstract) {
                if ($app->isShared($abstract)) {
                    $stats['singleton_bindings']++;
                }
            }
            
            // Count service providers
            $providers = self::getServiceProviders($app);
            $stats['service_providers'] = count($providers);
            
        } catch (Throwable $e) {
            self::$containerErrors[] = "Failed to get container statistics: " . $e->getMessage();
        }
        
        return $stats;
    }

    /**
     * Add test-specific binding.
     */
    public static function addTestBinding(string $abstract, mixed $concrete): void
    {
        self::$testBindings[$abstract] = $concrete;
    }

    /**
     * Clear test bindings.
     */
    public static function clearTestBindings(): void
    {
        self::$testBindings = [];
    }

    /**
     * Get container errors.
     */
    public static function getContainerErrors(): array
    {
        return self::$containerErrors;
    }

    /**
     * Clear container errors.
     */
    public static function clearContainerErrors(): void
    {
        self::$containerErrors = [];
    }

    /**
     * Clear all bindings from container.
     */
    private static function clearAllBindings(Application $app): void
    {
        $reflection = new ReflectionClass($app);
        
        if ($reflection->hasProperty('bindings')) {
            $bindingsProperty = $reflection->getProperty('bindings');
            $bindingsProperty->setAccessible(true);
            $bindingsProperty->setValue($app, []);
        }
    }

    /**
     * Clear resolved instances.
     */
    private static function clearResolvedInstances(Application $app): void
    {
        $reflection = new ReflectionClass($app);
        
        if ($reflection->hasProperty('instances')) {
            $instancesProperty = $reflection->getProperty('instances');
            $instancesProperty->setAccessible(true);
            $instancesProperty->setValue($app, []);
        }
    }

    /**
     * Clear singletons.
     */
    private static function clearSingletons(Application $app): void
    {
        // In Laravel, singletons are stored in the instances array
        self::clearResolvedInstances($app);
    }

    /**
     * Reset service providers.
     */
    private static function resetServiceProviders(Application $app): void
    {
        $reflection = new ReflectionClass($app);
        
        if ($reflection->hasProperty('serviceProviders')) {
            $providersProperty = $reflection->getProperty('serviceProviders');
            $providersProperty->setAccessible(true);
            $providersProperty->setValue($app, []);
        }
        
        if ($reflection->hasProperty('loadedProviders')) {
            $loadedProperty = $reflection->getProperty('loadedProviders');
            $loadedProperty->setAccessible(true);
            $loadedProperty->setValue($app, []);
        }
    }

    /**
     * Register core services.
     */
    private static function registerCoreServices(Application $app): void
    {
        foreach (self::$coreServiceBindings as $interface => $implementation) {
            if ($interface === LinkValidationInterface::class) {
                // LinkValidationService has dependencies
                $app->singleton($interface, function (Application $app) use ($implementation) {
                    return new $implementation(
                        $app->make(SecurityValidationInterface::class)
                    );
                });
            } else {
                // Other services have no dependencies
                $app->singleton($interface, $implementation);
            }
        }
        
        // Register test-specific bindings
        foreach (self::$testBindings as $abstract => $concrete) {
            $app->bind($abstract, $concrete);
        }
    }

    /**
     * Validate service dependencies.
     */
    private static function validateServiceDependencies(Application $app, string $service): array
    {
        $results = [
            'resolved' => [],
            'missing' => [],
        ];
        
        $dependencies = self::$serviceDependencies[$service] ?? [];
        
        foreach ($dependencies as $dependency) {
            try {
                $instance = $app->make($dependency);
                $results['resolved'][] = [
                    'dependency' => $dependency,
                    'implementation' => get_class($instance),
                ];
            } catch (Throwable $e) {
                $results['missing'][] = [
                    'dependency' => $dependency,
                    'error' => $e->getMessage(),
                ];
            }
        }
        
        return $results;
    }

    /**
     * Detect binding conflicts.
     */
    private static function detectBindingConflicts(Application $app): array
    {
        $conflicts = [];
        
        try {
            $bindings = self::getContainerBindings($app);
            
            foreach ($bindings as $abstract => $binding) {
                // Check if binding conflicts with expected core services
                if (isset(self::$coreServiceBindings[$abstract])) {
                    $expected = self::$coreServiceBindings[$abstract];
                    $actual = $binding['concrete'] ?? null;
                    
                    if ($actual !== $expected && !is_callable($actual)) {
                        $conflicts[] = [
                            'abstract' => $abstract,
                            'expected' => $expected,
                            'actual' => $actual,
                        ];
                    }
                }
            }
            
        } catch (Throwable $e) {
            self::$containerErrors[] = "Failed to detect binding conflicts: " . $e->getMessage();
        }
        
        return $conflicts;
    }

    /**
     * Build dependency chain for a service.
     */
    private static function buildDependencyChain(string $service, array $visited = []): array
    {
        if (in_array($service, $visited, true)) {
            return ['circular' => true, 'chain' => $visited];
        }
        
        $visited[] = $service;
        $dependencies = self::$serviceDependencies[$service] ?? [];
        $chain = [$service];
        
        foreach ($dependencies as $dependency) {
            $subChain = self::buildDependencyChain($dependency, $visited);
            $chain = array_merge($chain, $subChain);
        }
        
        return $chain;
    }

    /**
     * Check for circular dependency.
     */
    private static function hasCircularDependency(string $service, array $visited = []): bool
    {
        if (in_array($service, $visited, true)) {
            return true;
        }
        
        $visited[] = $service;
        $dependencies = self::$serviceDependencies[$service] ?? [];
        
        foreach ($dependencies as $dependency) {
            if (self::hasCircularDependency($dependency, $visited)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get container bindings using reflection.
     */
    private static function getContainerBindings(Application $app): array
    {
        try {
            $reflection = new ReflectionClass($app);
            
            if ($reflection->hasProperty('bindings')) {
                $bindingsProperty = $reflection->getProperty('bindings');
                $bindingsProperty->setAccessible(true);
                return $bindingsProperty->getValue($app) ?? [];
            }
        } catch (ReflectionException $e) {
            self::$containerErrors[] = "Failed to get bindings: " . $e->getMessage();
        }
        
        return [];
    }

    /**
     * Get container instances using reflection.
     */
    private static function getContainerInstances(Application $app): array
    {
        try {
            $reflection = new ReflectionClass($app);
            
            if ($reflection->hasProperty('instances')) {
                $instancesProperty = $reflection->getProperty('instances');
                $instancesProperty->setAccessible(true);
                return $instancesProperty->getValue($app) ?? [];
            }
        } catch (ReflectionException $e) {
            self::$containerErrors[] = "Failed to get instances: " . $e->getMessage();
        }
        
        return [];
    }

    /**
     * Get service providers using reflection.
     */
    private static function getServiceProviders(Application $app): array
    {
        try {
            $reflection = new ReflectionClass($app);
            
            if ($reflection->hasProperty('serviceProviders')) {
                $providersProperty = $reflection->getProperty('serviceProviders');
                $providersProperty->setAccessible(true);
                return $providersProperty->getValue($app) ?? [];
            }
        } catch (ReflectionException $e) {
            self::$containerErrors[] = "Failed to get service providers: " . $e->getMessage();
        }
        
        return [];
    }
}