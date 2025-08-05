<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase;
use Mockery;
use Mockery\MockInterface;
use Exception;
use Throwable;

/**
 * Comprehensive test isolation manager for dependency injection testing.
 * 
 * Provides bulletproof test isolation with complete container state management,
 * service binding isolation, and cross-test contamination prevention.
 * 
 * @author StandAloneComplex
 * @since 1.0.0
 */
final class TestIsolationManager
{
    /** @var array<string, mixed> */
    private static array $originalBindings = [];
    
    /** @var array<string, mixed> */
    private static array $originalSingletons = [];
    
    /** @var array<string, mixed> */
    private static array $originalInstances = [];
    
    /** @var array<string, callable> */
    private static array $isolationCallbacks = [];
    
    /** @var bool */
    private static bool $isolationActive = false;
    
    /** @var array<string> */
    private static array $isolationErrors = [];
    
    /** @var Application|null */
    private static ?Application $originalApp = null;
    
    /** @var array<string, mixed> */
    private static array $testSpecificBindings = [];

    /**
     * Initialize test isolation for a test case.
     */
    public static function initializeIsolation(TestCase $testCase): void
    {
        if (self::$isolationActive) {
            self::forceCleanup();
        }
        
        try {
            // Store original application state
            self::$originalApp = $testCase->app ?? null;
            
            if (self::$originalApp) {
                self::captureContainerState(self::$originalApp);
            }
            
            self::$isolationActive = true;
            self::$isolationErrors = [];
            self::$testSpecificBindings = [];
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Failed to initialize isolation: " . $e->getMessage();
            throw new Exception("Test isolation initialization failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Create an isolated container for testing.
     */
    public static function createIsolatedContainer(): Application
    {
        try {
            // Create a fresh application instance
            $app = require __DIR__ . '/../../bootstrap/app.php';
            
            // Clear any existing bindings that might interfere
            self::clearContainerBindings($app);
            
            // Register test-specific bindings
            self::registerTestBindings($app);
            
            return $app;
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Failed to create isolated container: " . $e->getMessage();
            throw new Exception("Isolated container creation failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Reset container state to clean baseline.
     */
    public static function resetContainerState(Application $app): void
    {
        try {
            // Clear all resolved instances
            self::clearResolvedInstances($app);
            
            // Clear all singleton instances
            self::clearSingletonInstances($app);
            
            // Reset service provider state
            self::resetServiceProviders($app);
            
            // Clear any cached services
            self::clearServiceCache($app);
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Failed to reset container state: " . $e->getMessage();
            throw new Exception("Container state reset failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate container bindings and dependencies.
     */
    public static function validateContainerBindings(Application $app): array
    {
        $validationResults = [
            'valid_bindings' => [],
            'invalid_bindings' => [],
            'missing_dependencies' => [],
            'circular_dependencies' => [],
        ];
        
        try {
            // Get all registered bindings
            $bindings = self::getContainerBindings($app);
            
            foreach ($bindings as $abstract => $binding) {
                try {
                    // Test if binding can be resolved
                    $resolved = $app->make($abstract);
                    $validationResults['valid_bindings'][] = [
                        'abstract' => $abstract,
                        'concrete' => get_class($resolved),
                        'is_singleton' => $app->isShared($abstract),
                    ];
                } catch (Throwable $e) {
                    $validationResults['invalid_bindings'][] = [
                        'abstract' => $abstract,
                        'error' => $e->getMessage(),
                        'type' => get_class($e),
                    ];
                }
            }
            
            // Check for circular dependencies
            $circularDeps = self::detectCircularDependencies($app, $bindings);
            $validationResults['circular_dependencies'] = $circularDeps;
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Binding validation failed: " . $e->getMessage();
            $validationResults['validation_error'] = $e->getMessage();
        }
        
        return $validationResults;
    }

    /**
     * Isolate test environment completely.
     */
    public static function isolateTestEnvironment(TestCase $testCase): void
    {
        try {
            // Initialize base isolation
            self::initializeIsolation($testCase);
            
            // Clear mock state
            MockLifecycleManager::reset();
            
            // Reset HTTP mock factory
            if (class_exists(HttpMockFactory::class)) {
                HttpMockFactory::reset();
            }
            
            // Clear any global state
            self::clearGlobalTestState();
            
            // Register isolation cleanup
            self::registerIsolationCleanup();
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Environment isolation failed: " . $e->getMessage();
            throw new Exception("Test environment isolation failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Comprehensive cleanup of all test resources.
     */
    public static function cleanupTestResources(): void
    {
        try {
            // Run custom isolation callbacks
            foreach (self::$isolationCallbacks as $name => $callback) {
                try {
                    $callback();
                } catch (Throwable $e) {
                    self::$isolationErrors[] = "Isolation callback '{$name}' failed: " . $e->getMessage();
                }
            }
            
            // Restore original container state
            if (self::$originalApp) {
                self::restoreContainerState(self::$originalApp);
            }
            
            // Clean up mock lifecycle
            MockLifecycleManager::cleanup();
            
            // Clear test-specific bindings
            self::$testSpecificBindings = [];
            
            // Reset isolation state
            self::$isolationActive = false;
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Resource cleanup failed: " . $e->getMessage();
            // Don't throw here to avoid masking test failures
        }
    }

    /**
     * Validate test isolation effectiveness.
     */
    public static function validateTestIsolation(Application $app): array
    {
        $isolationResults = [
            'container_state_clean' => false,
            'no_singleton_leakage' => false,
            'no_binding_conflicts' => false,
            'mock_state_clean' => false,
            'isolation_errors' => self::$isolationErrors,
        ];
        
        try {
            // Check container state
            $isolationResults['container_state_clean'] = self::isContainerStateClean($app);
            
            // Check singleton leakage
            $isolationResults['no_singleton_leakage'] = self::checkSingletonLeakage($app);
            
            // Check binding conflicts
            $isolationResults['no_binding_conflicts'] = self::checkBindingConflicts($app);
            
            // Check mock state
            $isolationResults['mock_state_clean'] = !MockLifecycleManager::hasVerificationErrors();
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Isolation validation failed: " . $e->getMessage();
            $isolationResults['validation_error'] = $e->getMessage();
        }
        
        return $isolationResults;
    }

    /**
     * Prevent cross-test contamination.
     */
    public static function preventCrossTestContamination(): void
    {
        try {
            // Clear static caches
            self::clearStaticCaches();
            
            // Reset global variables
            self::resetGlobalVariables();
            
            // Clear file system state
            self::clearFileSystemState();
            
            // Reset environment variables
            self::resetEnvironmentVariables();
            
        } catch (Throwable $e) {
            self::$isolationErrors[] = "Cross-test contamination prevention failed: " . $e->getMessage();
        }
    }

    /**
     * Register an isolation callback.
     */
    public static function registerIsolationCallback(string $name, callable $callback): void
    {
        self::$isolationCallbacks[$name] = $callback;
    }

    /**
     * Get isolation statistics.
     */
    public static function getIsolationStatistics(): array
    {
        return [
            'isolation_active' => self::$isolationActive,
            'isolation_errors' => count(self::$isolationErrors),
            'isolation_callbacks' => count(self::$isolationCallbacks),
            'test_bindings' => count(self::$testSpecificBindings),
            'original_bindings_captured' => count(self::$originalBindings),
        ];
    }

    /**
     * Force cleanup in case of emergency.
     */
    public static function forceCleanup(): void
    {
        try {
            self::cleanupTestResources();
        } catch (Throwable $e) {
            // Log but don't throw
            error_log("Force cleanup failed: " . $e->getMessage());
        } finally {
            // Reset all state regardless
            self::$isolationActive = false;
            self::$isolationErrors = [];
            self::$isolationCallbacks = [];
            self::$testSpecificBindings = [];
            self::$originalBindings = [];
            self::$originalSingletons = [];
            self::$originalInstances = [];
            self::$originalApp = null;
        }
    }

    /**
     * Capture current container state.
     */
    private static function captureContainerState(Application $app): void
    {
        // Capture bindings using reflection to access protected properties
        $reflection = new \ReflectionClass($app);
        
        if ($reflection->hasProperty('bindings')) {
            $bindingsProperty = $reflection->getProperty('bindings');
            $bindingsProperty->setAccessible(true);
            self::$originalBindings = $bindingsProperty->getValue($app) ?? [];
        }
        
        if ($reflection->hasProperty('instances')) {
            $instancesProperty = $reflection->getProperty('instances');
            $instancesProperty->setAccessible(true);
            self::$originalInstances = $instancesProperty->getValue($app) ?? [];
        }
    }

    /**
     * Restore original container state.
     */
    private static function restoreContainerState(Application $app): void
    {
        // Restore using reflection
        $reflection = new \ReflectionClass($app);
        
        if ($reflection->hasProperty('bindings') && !empty(self::$originalBindings)) {
            $bindingsProperty = $reflection->getProperty('bindings');
            $bindingsProperty->setAccessible(true);
            $bindingsProperty->setValue($app, self::$originalBindings);
        }
        
        if ($reflection->hasProperty('instances') && !empty(self::$originalInstances)) {
            $instancesProperty = $reflection->getProperty('instances');
            $instancesProperty->setAccessible(true);
            $instancesProperty->setValue($app, self::$originalInstances);
        }
    }

    /**
     * Clear container bindings.
     */
    private static function clearContainerBindings(Application $app): void
    {
        $reflection = new \ReflectionClass($app);
        
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
        $reflection = new \ReflectionClass($app);
        
        if ($reflection->hasProperty('instances')) {
            $instancesProperty = $reflection->getProperty('instances');
            $instancesProperty->setAccessible(true);
            $instancesProperty->setValue($app, []);
        }
    }

    /**
     * Clear singleton instances.
     */
    private static function clearSingletonInstances(Application $app): void
    {
        // Laravel doesn't have a separate singleton storage, they're in instances
        self::clearResolvedInstances($app);
    }

    /**
     * Reset service providers.
     */
    private static function resetServiceProviders(Application $app): void
    {
        $reflection = new \ReflectionClass($app);
        
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
     * Clear service cache.
     */
    private static function clearServiceCache(Application $app): void
    {
        // Clear any cached services or resolved instances
        if (method_exists($app, 'forgetInstance')) {
            // Clear specific instances if method exists
            foreach (self::$testSpecificBindings as $abstract => $binding) {
                $app->forgetInstance($abstract);
            }
        }
    }

    /**
     * Register test-specific bindings.
     */
    private static function registerTestBindings(Application $app): void
    {
        // Register any test-specific service bindings
        foreach (self::$testSpecificBindings as $abstract => $concrete) {
            $app->bind($abstract, $concrete);
        }
    }

    /**
     * Get container bindings.
     */
    private static function getContainerBindings(Application $app): array
    {
        $reflection = new \ReflectionClass($app);
        
        if ($reflection->hasProperty('bindings')) {
            $bindingsProperty = $reflection->getProperty('bindings');
            $bindingsProperty->setAccessible(true);
            return $bindingsProperty->getValue($app) ?? [];
        }
        
        return [];
    }

    /**
     * Detect circular dependencies.
     */
    private static function detectCircularDependencies(Application $app, array $bindings): array
    {
        $circularDeps = [];
        $resolving = [];
        
        foreach (array_keys($bindings) as $abstract) {
            try {
                self::checkCircularDependency($app, $abstract, $resolving, $circularDeps);
            } catch (Throwable $e) {
                // Dependency resolution failed, might be circular
                if (str_contains($e->getMessage(), 'circular') || str_contains($e->getMessage(), 'recursive')) {
                    $circularDeps[] = [
                        'abstract' => $abstract,
                        'error' => $e->getMessage(),
                    ];
                }
            }
        }
        
        return $circularDeps;
    }

    /**
     * Check for circular dependency in a specific binding.
     */
    private static function checkCircularDependency(Application $app, string $abstract, array &$resolving, array &$circularDeps): void
    {
        if (in_array($abstract, $resolving, true)) {
            $circularDeps[] = [
                'abstract' => $abstract,
                'chain' => $resolving,
                'error' => 'Circular dependency detected',
            ];
            return;
        }
        
        $resolving[] = $abstract;
        
        try {
            $app->make($abstract);
        } finally {
            array_pop($resolving);
        }
    }

    /**
     * Check if container state is clean.
     */
    private static function isContainerStateClean(Application $app): bool
    {
        try {
            $bindings = self::getContainerBindings($app);
            $instances = self::getContainerInstances($app);
            
            // Check if we have unexpected bindings or instances
            return count($bindings) === 0 && count($instances) === 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Check for singleton leakage.
     */
    private static function checkSingletonLeakage(Application $app): bool
    {
        try {
            $instances = self::getContainerInstances($app);
            
            // Check if any singletons from previous tests are still present
            foreach ($instances as $abstract => $instance) {
                if (!in_array($abstract, array_keys(self::$testSpecificBindings), true)) {
                    return false; // Found leaked singleton
                }
            }
            
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Check for binding conflicts.
     */
    private static function checkBindingConflicts(Application $app): bool
    {
        try {
            $bindings = self::getContainerBindings($app);
            
            // Check if any unexpected bindings exist
            foreach (array_keys($bindings) as $abstract) {
                if (!in_array($abstract, array_keys(self::$testSpecificBindings), true)) {
                    return false; // Found conflicting binding
                }
            }
            
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Get container instances.
     */
    private static function getContainerInstances(Application $app): array
    {
        $reflection = new \ReflectionClass($app);
        
        if ($reflection->hasProperty('instances')) {
            $instancesProperty = $reflection->getProperty('instances');
            $instancesProperty->setAccessible(true);
            return $instancesProperty->getValue($app) ?? [];
        }
        
        return [];
    }

    /**
     * Clear static caches.
     */
    private static function clearStaticCaches(): void
    {
        // Clear any static caches that might affect tests
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        }
    }

    /**
     * Reset global variables.
     */
    private static function resetGlobalVariables(): void
    {
        // Reset any global variables that might affect tests
        global $fixture_cache;
        $fixture_cache = [];
    }

    /**
     * Clear file system state.
     */
    private static function clearFileSystemState(): void
    {
        // Clear any temporary files or directories
        $tempDir = sys_get_temp_dir() . '/validate-links-tests';
        if (is_dir($tempDir)) {
            self::recursiveRemoveDirectory($tempDir);
        }
    }

    /**
     * Reset environment variables.
     */
    private static function resetEnvironmentVariables(): void
    {
        // Reset any test-specific environment variables
        // This is a placeholder for environment-specific cleanup
    }

    /**
     * Clear global test state.
     */
    private static function clearGlobalTestState(): void
    {
        // Clear any global test state
        self::clearStaticCaches();
        self::resetGlobalVariables();
    }

    /**
     * Register isolation cleanup.
     */
    private static function registerIsolationCleanup(): void
    {
        // Register cleanup to run at the end of the test
        register_shutdown_function([self::class, 'forceCleanup']);
    }

    /**
     * Recursively remove directory.
     */
    private static function recursiveRemoveDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::recursiveRemoveDirectory($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}