<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

/**
 * Test performance profiler for monitoring and analyzing test execution performance.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class TestPerformanceProfiler
{
    /**
     * Profiler configuration.
     * 
     * @var array<string, mixed>
     */
    private static array $config = [
        'enable_profiling' => true,
        'memory_tracking' => true,
        'execution_time_tracking' => true,
    ];
    
    /**
     * Performance metrics.
     * 
     * @var array<string, mixed>
     */
    private static array $metrics = [];
    
    /**
     * Initialize the performance profiler.
     * 
     * @param array<string, mixed> $config Configuration overrides
     * @return void
     */
    public static function initialize(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$metrics = [];
    }
    
    /**
     * Start profiling a test.
     * 
     * @param string $testName Test name
     * @return void
     */
    public static function startProfiling(string $testName): void
    {
        if (!self::$config['enable_profiling']) {
            return;
        }
        
        self::$metrics[$testName] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(true),
            'peak_memory' => 0,
            'execution_time' => 0,
        ];
    }
    
    /**
     * Stop profiling a test.
     * 
     * @param string $testName Test name
     * @return void
     */
    public static function stopProfiling(string $testName): void
    {
        if (!self::$config['enable_profiling'] || !isset(self::$metrics[$testName])) {
            return;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        self::$metrics[$testName]['execution_time'] = $endTime - self::$metrics[$testName]['start_time'];
        self::$metrics[$testName]['memory_used'] = $endMemory - self::$metrics[$testName]['start_memory'];
        self::$metrics[$testName]['peak_memory'] = $peakMemory;
    }
    
    /**
     * Get performance metrics.
     * 
     * @return array<string, mixed> Performance metrics
     */
    public static function getMetrics(): array
    {
        return self::$metrics;
    }
    
    /**
     * Reset profiler state.
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$metrics = [];
    }
}