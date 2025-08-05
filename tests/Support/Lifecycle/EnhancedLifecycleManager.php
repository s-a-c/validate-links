<?php

declare(strict_types=1);

namespace Tests\Support\Lifecycle;

/**
 * Enhanced Lifecycle Manager for PEST Tests
 * 
 * Provides advanced lifecycle management with automatic cleanup,
 * memory leak detection, test isolation verification, and resource
 * management automation.
 */
class EnhancedLifecycleManager
{
    /**
     * Registered cleanup callbacks
     */
    private static array $cleanupCallbacks = [];

    /**
     * Memory usage tracking
     */
    private static array $memorySnapshots = [];

    /**
     * Resource tracking
     */
    private static array $trackedResources = [];

    /**
     * Test isolation state
     */
    private static array $isolationState = [];

    /**
     * Performance metrics
     */
    private static array $performanceMetrics = [];

    /**
     * Lifecycle hooks registry
     */
    private static array $lifecycleHooks = [
        'beforeAll' => [],
        'beforeEach' => [],
        'afterEach' => [],
        'afterAll' => [],
        'onFailure' => [],
        'onSuccess' => [],
    ];

    /**
     * Register a cleanup callback
     */
    public static function addCleanup(callable $callback, int $priority = 0): void
    {
        self::$cleanupCallbacks[] = [
            'callback' => $callback,
            'priority' => $priority,
            'registered_at' => microtime(true),
        ];

        // Sort by priority (higher priority first)
        usort(self::$cleanupCallbacks, fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Register a lifecycle hook
     */
    public static function addHook(string $phase, callable $callback, int $priority = 0): void
    {
        if (!isset(self::$lifecycleHooks[$phase])) {
            throw new \InvalidArgumentException("Invalid lifecycle phase: {$phase}");
        }

        self::$lifecycleHooks[$phase][] = [
            'callback' => $callback,
            'priority' => $priority,
            'registered_at' => microtime(true),
        ];

        // Sort by priority
        usort(self::$lifecycleHooks[$phase], fn($a, $b) => $b['priority'] <=> $a['priority']);
    }

    /**
     * Execute lifecycle hooks for a specific phase
     */
    public static function executeHooks(string $phase, array $context = []): void
    {
        if (!isset(self::$lifecycleHooks[$phase])) {
            return;
        }

        foreach (self::$lifecycleHooks[$phase] as $hook) {
            try {
                ($hook['callback'])($context);
            } catch (\Throwable $e) {
                // Log hook execution failure but don't stop test execution
                error_log("Lifecycle hook failed in {$phase}: " . $e->getMessage());
            }
        }
    }

    /**
     * Take memory snapshot
     */
    public static function takeMemorySnapshot(string $label): void
    {
        self::$memorySnapshots[$label] = [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Check for memory leaks
     */
    public static function checkMemoryLeaks(string $beforeLabel, string $afterLabel, int $tolerance = 1024 * 1024): bool
    {
        if (!isset(self::$memorySnapshots[$beforeLabel]) || !isset(self::$memorySnapshots[$afterLabel])) {
            return false;
        }

        $before = self::$memorySnapshots[$beforeLabel]['memory_usage'];
        $after = self::$memorySnapshots[$afterLabel]['memory_usage'];
        $diff = $after - $before;

        return $diff <= $tolerance;
    }

    /**
     * Track a resource for automatic cleanup
     */
    public static function trackResource(string $type, mixed $resource, ?callable $cleanupCallback = null): string
    {
        $id = uniqid($type . '_', true);
        
        self::$trackedResources[$id] = [
            'type' => $type,
            'resource' => $resource,
            'cleanup_callback' => $cleanupCallback,
            'created_at' => microtime(true),
        ];

        return $id;
    }

    /**
     * Clean up a specific tracked resource
     */
    public static function cleanupResource(string $id): bool
    {
        if (!isset(self::$trackedResources[$id])) {
            return false;
        }

        $resource = self::$trackedResources[$id];
        
        try {
            if ($resource['cleanup_callback']) {
                ($resource['cleanup_callback'])($resource['resource']);
            } else {
                self::performDefaultCleanup($resource['type'], $resource['resource']);
            }
            
            unset(self::$trackedResources[$id]);
            return true;
        } catch (\Throwable $e) {
            error_log("Failed to cleanup resource {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Perform default cleanup based on resource type
     */
    private static function performDefaultCleanup(string $type, mixed $resource): void
    {
        switch ($type) {
            case 'file':
                if (is_string($resource) && file_exists($resource)) {
                    unlink($resource);
                }
                break;
            case 'directory':
                if (is_string($resource) && is_dir($resource)) {
                    self::removeDirectoryRecursively($resource);
                }
                break;
            case 'temp_file':
                if (is_resource($resource)) {
                    fclose($resource);
                }
                break;
            case 'curl':
                if (is_resource($resource)) {
                    curl_close($resource);
                }
                break;
            case 'database_connection':
                if (method_exists($resource, 'close')) {
                    $resource->close();
                }
                break;
        }
    }

    /**
     * Remove directory recursively
     */
    private static function removeDirectoryRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::removeDirectoryRecursively($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Verify test isolation
     */
    public static function verifyTestIsolation(): array
    {
        $issues = [];

        // Check for global state changes
        if (!empty(self::$isolationState)) {
            $currentGlobals = $GLOBALS;
            $originalGlobals = self::$isolationState['globals'] ?? [];
            
            $newGlobals = array_diff_key($currentGlobals, $originalGlobals);
            if (!empty($newGlobals)) {
                $issues[] = 'New global variables detected: ' . implode(', ', array_keys($newGlobals));
            }
        }

        // Check for unclosed resources
        $unclosedResources = array_filter(self::$trackedResources, function ($resource) {
            return $resource['type'] === 'file' || $resource['type'] === 'curl';
        });

        if (!empty($unclosedResources)) {
            $issues[] = 'Unclosed resources detected: ' . count($unclosedResources);
        }

        // Check memory usage
        $currentMemory = memory_get_usage(true);
        $baselineMemory = self::$isolationState['baseline_memory'] ?? $currentMemory;
        $memoryIncrease = $currentMemory - $baselineMemory;
        
        if ($memoryIncrease > 10 * 1024 * 1024) { // 10MB threshold
            $issues[] = 'Significant memory increase detected: ' . number_format($memoryIncrease / 1024 / 1024, 2) . 'MB';
        }

        return $issues;
    }

    /**
     * Initialize test isolation state
     */
    public static function initializeIsolationState(): void
    {
        self::$isolationState = [
            'globals' => $GLOBALS,
            'baseline_memory' => memory_get_usage(true),
            'included_files' => get_included_files(),
            'defined_constants' => get_defined_constants(),
            'declared_classes' => get_declared_classes(),
        ];
    }

    /**
     * Record performance metric
     */
    public static function recordMetric(string $name, float $value, string $unit = 'ms'): void
    {
        if (!isset(self::$performanceMetrics[$name])) {
            self::$performanceMetrics[$name] = [];
        }

        self::$performanceMetrics[$name][] = [
            'value' => $value,
            'unit' => $unit,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Get performance metrics
     */
    public static function getPerformanceMetrics(): array
    {
        $summary = [];

        foreach (self::$performanceMetrics as $name => $measurements) {
            $values = array_column($measurements, 'value');
            $summary[$name] = [
                'count' => count($values),
                'min' => min($values),
                'max' => max($values),
                'avg' => array_sum($values) / count($values),
                'unit' => $measurements[0]['unit'] ?? 'unknown',
            ];
        }

        return $summary;
    }

    /**
     * Execute all cleanup callbacks
     */
    public static function executeCleanup(): void
    {
        foreach (self::$cleanupCallbacks as $cleanup) {
            try {
                ($cleanup['callback'])();
            } catch (\Throwable $e) {
                error_log("Cleanup callback failed: " . $e->getMessage());
            }
        }

        // Clean up all tracked resources
        foreach (array_keys(self::$trackedResources) as $id) {
            self::cleanupResource($id);
        }

        // Reset state
        self::reset();
    }

    /**
     * Reset lifecycle manager state
     */
    public static function reset(): void
    {
        self::$cleanupCallbacks = [];
        self::$memorySnapshots = [];
        self::$trackedResources = [];
        self::$isolationState = [];
        self::$performanceMetrics = [];
        
        // Don't reset lifecycle hooks as they should persist across tests
    }

    /**
     * Get lifecycle statistics
     */
    public static function getStatistics(): array
    {
        return [
            'cleanup_callbacks' => count(self::$cleanupCallbacks),
            'tracked_resources' => count(self::$trackedResources),
            'memory_snapshots' => count(self::$memorySnapshots),
            'performance_metrics' => count(self::$performanceMetrics),
            'lifecycle_hooks' => array_map('count', self::$lifecycleHooks),
        ];
    }

    /**
     * Create temporary file with automatic cleanup
     */
    public static function createTempFile(string $content = '', string $suffix = '.tmp'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pest_') . $suffix;
        file_put_contents($tempFile, $content);
        
        self::trackResource('file', $tempFile);
        
        return $tempFile;
    }

    /**
     * Create temporary directory with automatic cleanup
     */
    public static function createTempDirectory(string $prefix = 'pest_'): string
    {
        $tempDir = sys_get_temp_dir() . '/' . $prefix . uniqid();
        mkdir($tempDir, 0755, true);
        
        self::trackResource('directory', $tempDir);
        
        return $tempDir;
    }

    /**
     * Monitor memory usage during callback execution
     */
    public static function monitorMemory(callable $callback, string $label = 'operation'): array
    {
        $beforeLabel = $label . '_before';
        $afterLabel = $label . '_after';
        
        self::takeMemorySnapshot($beforeLabel);
        $startTime = microtime(true);
        
        $result = $callback();
        
        $endTime = microtime(true);
        self::takeMemorySnapshot($afterLabel);
        
        $before = self::$memorySnapshots[$beforeLabel];
        $after = self::$memorySnapshots[$afterLabel];
        
        return [
            'result' => $result,
            'execution_time' => $endTime - $startTime,
            'memory_used' => $after['memory_usage'] - $before['memory_usage'],
            'peak_memory' => $after['peak_memory'],
            'memory_leak_detected' => !self::checkMemoryLeaks($beforeLabel, $afterLabel),
        ];
    }

    /**
     * Setup enhanced beforeEach hook
     */
    public static function setupEnhancedBeforeEach(): void
    {
        self::addHook('beforeEach', function () {
            self::initializeIsolationState();
            self::takeMemorySnapshot('test_start');
            self::executeHooks('beforeEach');
        }, 1000);
    }

    /**
     * Setup enhanced afterEach hook
     */
    public static function setupEnhancedAfterEach(): void
    {
        self::addHook('afterEach', function () {
            self::takeMemorySnapshot('test_end');
            
            // Check for isolation issues
            $isolationIssues = self::verifyTestIsolation();
            if (!empty($isolationIssues)) {
                error_log('Test isolation issues detected: ' . implode(', ', $isolationIssues));
            }
            
            // Check for memory leaks
            if (!self::checkMemoryLeaks('test_start', 'test_end')) {
                error_log('Potential memory leak detected in test');
            }
            
            self::executeHooks('afterEach');
            self::executeCleanup();
        }, 1000);
    }

    /**
     * Get cleanup recommendations
     */
    public static function getCleanupRecommendations(): array
    {
        $recommendations = [];
        
        if (count(self::$trackedResources) > 10) {
            $recommendations[] = 'Consider reducing the number of tracked resources per test';
        }
        
        if (count(self::$cleanupCallbacks) > 20) {
            $recommendations[] = 'Consider consolidating cleanup callbacks';
        }
        
        $memoryUsage = memory_get_usage(true);
        if ($memoryUsage > 50 * 1024 * 1024) { // 50MB
            $recommendations[] = 'High memory usage detected - consider optimizing test data';
        }
        
        return $recommendations;
    }
}