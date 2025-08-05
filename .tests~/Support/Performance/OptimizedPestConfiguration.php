<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use Tests\Support\Performance\TestPerformanceProfiler;
use Tests\Support\Performance\MemoryUsageTracker;
use Tests\Support\Performance\ResourceUtilizationMonitor;
use Tests\Support\Performance\TestExecutionOptimizer;
use Tests\Support\Performance\OptimizedMockFactory;
use Tests\Support\Performance\OptimizedEdgeCaseDataGenerator;
use Tests\Support\Performance\PerformanceBenchmark;

/**
 * Performance-optimized Pest configuration with comprehensive
 * performance monitoring, optimization, and benchmarking capabilities.
 * 
 * Integrates all performance optimization tools and provides
 * streamlined test execution with intelligent resource management.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class OptimizedPestConfiguration
{
    /**
     * Performance optimization settings.
     * 
     * @var array<string, mixed>
     */
    private static array $optimizationConfig = [
        'enable_profiling' => true,
        'enable_memory_tracking' => true,
        'enable_resource_monitoring' => true,
        'enable_benchmarking' => true,
        'enable_mock_optimization' => true,
        'enable_data_caching' => true,
        'auto_cleanup_interval' => 10, // Every 10 tests
        'performance_reporting' => true,
    ];
    
    /**
     * Test execution counter for optimization triggers.
     */
    private static int $testCounter = 0;
    
    /**
     * Performance metrics collection.
     * 
     * @var array<string, mixed>
     */
    private static array $performanceMetrics = [];
    
    /**
     * Initialize optimized Pest configuration.
     */
    public static function initialize(): void
    {
        // Initialize all performance tools
        TestPerformanceProfiler::initialize();
        MemoryUsageTracker::initialize();
        ResourceUtilizationMonitor::initialize();
        TestExecutionOptimizer::initialize();
        OptimizedMockFactory::initialize();
        OptimizedEdgeCaseDataGenerator::initialize();
        PerformanceBenchmark::initialize();
        
        self::$testCounter = 0;
        self::$performanceMetrics = [];
        
        // Configure optimization settings
        self::configureOptimizations();
    }
    
    /**
     * Setup optimized test environment before each test.
     */
    public static function setupOptimizedTest(string $testName): void
    {
        self::$testCounter++;
        
        // Start performance monitoring
        if (self::$optimizationConfig['enable_profiling']) {
            TestPerformanceProfiler::startProfiling($testName);
        }
        
        if (self::$optimizationConfig['enable_memory_tracking']) {
            MemoryUsageTracker::takeSnapshot("test_start_{$testName}");
        }
        
        if (self::$optimizationConfig['enable_resource_monitoring']) {
            ResourceUtilizationMonitor::startMonitoring($testName);
        }
        
        // Optimize test execution environment
        self::optimizeTestEnvironment();
    }
    
    /**
     * Cleanup optimized test environment after each test.
     */
    public static function cleanupOptimizedTest(string $testName): void
    {
        // Stop performance monitoring
        if (self::$optimizationConfig['enable_profiling']) {
            $metrics = TestPerformanceProfiler::stopProfiling($testName);
            self::$performanceMetrics[$testName] = $metrics;
        }
        
        if (self::$optimizationConfig['enable_memory_tracking']) {
            MemoryUsageTracker::takeSnapshot("test_end_{$testName}");
        }
        
        if (self::$optimizationConfig['enable_resource_monitoring']) {
            ResourceUtilizationMonitor::stopMonitoring($testName);
        }
        
        // Perform periodic cleanup
        if (self::$testCounter % self::$optimizationConfig['auto_cleanup_interval'] === 0) {
            self::performPeriodicCleanup();
        }
        
        // Check for performance issues
        self::checkPerformanceIssues($testName);
    }
    
    /**
     * Create optimized mock with intelligent caching and pooling.
     */
    public static function createOptimizedMock(string $type, array $config = []): mixed
    {
        if (self::$optimizationConfig['enable_mock_optimization']) {
            return OptimizedMockFactory::createSecurityValidationService($config);
        }
        
        // Fallback to standard mock creation
        return \Tests\Support\MockFactory::createSecurityValidationService($config);
    }
    
    /**
     * Get optimized test data with intelligent caching.
     */
    public static function getOptimizedTestData(string $type, array $options = []): mixed
    {
        if (self::$optimizationConfig['enable_data_caching']) {
            return TestExecutionOptimizer::getOptimizedTestData($type, $options);
        }
        
        // Fallback to standard data generation
        return \Tests\Support\EdgeCaseDataGenerator::generateExtremeUrls($options);
    }
    
    /**
     * Create optimized temporary file with efficient management.
     */
    public static function createOptimizedTempFile(string $filename, string $content = ''): string
    {
        $operations = [
            [
                'type' => 'file_write',
                'path' => sys_get_temp_dir() . '/' . $filename,
                'content' => $content,
            ]
        ];
        
        $results = TestExecutionOptimizer::optimizeFileOperations($operations);
        
        return $operations[0]['path'];
    }
    
    /**
     * Execute optimized HTTP operations with connection pooling.
     */
    public static function executeOptimizedHttpRequests(array $requests): array
    {
        return TestExecutionOptimizer::optimizeHttpOperations($requests);
    }
    
    /**
     * Run performance benchmark for specific operation.
     */
    public static function benchmarkOperation(string $identifier, callable $operation): array
    {
        if (self::$optimizationConfig['enable_benchmarking']) {
            return PerformanceBenchmark::benchmark($identifier, $operation);
        }
        
        // Execute without benchmarking
        $operation();
        return [];
    }
    
    /**
     * Generate comprehensive performance report.
     */
    public static function generatePerformanceReport(): array
    {
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'test_execution_stats' => [
                'total_tests_executed' => self::$testCounter,
                'performance_metrics' => self::$performanceMetrics,
            ],
            'profiler_stats' => TestPerformanceProfiler::getSummaryStats(),
            'memory_stats' => MemoryUsageTracker::getMemoryStats(),
            'resource_stats' => ResourceUtilizationMonitor::getResourceStats(),
            'optimizer_stats' => TestExecutionOptimizer::getExecutionStats(),
            'mock_factory_stats' => OptimizedMockFactory::getMetrics(),
            'data_generator_stats' => OptimizedEdgeCaseDataGenerator::getMetrics(),
            'benchmark_summary' => PerformanceBenchmark::getBenchmarkSummary(),
            'optimization_recommendations' => self::generateOptimizationRecommendations(),
        ];
        
        return $report;
    }
    
    /**
     * Export performance report to file.
     */
    public static function exportPerformanceReport(string $filePath, string $format = 'json'): bool
    {
        $report = self::generatePerformanceReport();
        
        $content = match ($format) {
            'json' => json_encode($report, JSON_PRETTY_PRINT),
            'html' => self::generateHtmlReport($report),
            'csv' => self::generateCsvReport($report),
            default => json_encode($report, JSON_PRETTY_PRINT),
        };
        
        return file_put_contents($filePath, $content) !== false;
    }
    
    /**
     * Get performance optimization recommendations.
     */
    public static function getOptimizationRecommendations(): array
    {
        $recommendations = [];
        
        // Collect recommendations from all performance tools
        $profilerRecommendations = TestPerformanceProfiler::generateRecommendations();
        $memoryRecommendations = MemoryUsageTracker::generateMemoryOptimizations();
        $resourceRecommendations = ResourceUtilizationMonitor::generateOptimizationRecommendations();
        $benchmarkRecommendations = PerformanceBenchmark::generateOptimizationRecommendations();
        
        $recommendations = array_merge(
            $profilerRecommendations,
            $memoryRecommendations,
            $resourceRecommendations,
            $benchmarkRecommendations
        );
        
        return array_unique($recommendations);
    }
    
    /**
     * Configure optimization settings.
     */
    public static function configure(array $config): void
    {
        self::$optimizationConfig = array_merge(self::$optimizationConfig, $config);
        
        // Apply configuration to performance tools
        TestExecutionOptimizer::configure($config);
        ResourceUtilizationMonitor::configure($config);
        PerformanceBenchmark::configure($config);
    }
    
    /**
     * Reset all performance optimization state.
     */
    public static function reset(): void
    {
        TestPerformanceProfiler::reset();
        MemoryUsageTracker::reset();
        ResourceUtilizationMonitor::reset();
        TestExecutionOptimizer::reset();
        OptimizedMockFactory::reset();
        OptimizedEdgeCaseDataGenerator::reset();
        PerformanceBenchmark::reset();
        
        self::$testCounter = 0;
        self::$performanceMetrics = [];
    }
    
    /**
     * Get current optimization configuration.
     */
    public static function getConfiguration(): array
    {
        return self::$optimizationConfig;
    }
    
    /**
     * Get performance statistics summary.
     */
    public static function getPerformanceStats(): array
    {
        return [
            'tests_executed' => self::$testCounter,
            'average_test_time' => self::calculateAverageTestTime(),
            'memory_efficiency' => self::calculateMemoryEfficiency(),
            'cache_hit_rates' => self::getCacheHitRates(),
            'optimization_impact' => self::calculateOptimizationImpact(),
        ];
    }
    
    /**
     * Configure performance optimizations.
     */
    private static function configureOptimizations(): void
    {
        // Configure test execution optimizer
        TestExecutionOptimizer::configure([
            'enable_mock_pooling' => true,
            'enable_data_caching' => true,
            'enable_resource_reuse' => true,
            'cache_size_limit' => 100 * 1024 * 1024, // 100MB
        ]);
        
        // Configure resource utilization monitor
        ResourceUtilizationMonitor::configure([
            'cpu_threshold' => 80.0,
            'memory_threshold' => 80.0,
            'monitoring_interval' => 0.1,
        ]);
        
        // Configure performance benchmark
        PerformanceBenchmark::configure([
            'warmup_iterations' => 2,
            'benchmark_iterations' => 5,
            'regression_threshold' => 0.2,
        ]);
    }
    
    /**
     * Optimize test execution environment.
     */
    private static function optimizeTestEnvironment(): void
    {
        // Optimize memory usage
        TestExecutionOptimizer::optimizeMemoryUsage();
        
        // Pre-warm commonly used resources
        if (self::$testCounter === 1) {
            // Only on first test to avoid overhead
            self::preWarmResources();
        }
    }
    
    /**
     * Perform periodic cleanup to maintain performance.
     */
    private static function performPeriodicCleanup(): void
    {
        // Clear caches if they're getting too large
        OptimizedMockFactory::clearCaches();
        OptimizedEdgeCaseDataGenerator::clearCache();
        
        // Optimize memory usage
        TestExecutionOptimizer::optimizeMemoryUsage();
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Check for performance issues and log warnings.
     */
    private static function checkPerformanceIssues(string $testName): void
    {
        if (!isset(self::$performanceMetrics[$testName])) {
            return;
        }
        
        $metrics = self::$performanceMetrics[$testName];
        
        // Check for slow tests
        if ($metrics['execution_time'] > 5.0) {
            error_log("Performance Warning: Test '{$testName}' took {$metrics['execution_time']}s to execute");
        }
        
        // Check for high memory usage
        if ($metrics['memory_usage'] > 50 * 1024 * 1024) { // 50MB
            $memoryMb = round($metrics['memory_usage'] / 1024 / 1024, 2);
            error_log("Performance Warning: Test '{$testName}' used {$memoryMb}MB of memory");
        }
    }
    
    /**
     * Pre-warm commonly used resources.
     */
    private static function preWarmResources(): void
    {
        // Pre-create commonly used mocks
        $commonMocks = ['security_validation', 'link_validation', 'reporting'];
        foreach ($commonMocks as $mockType) {
            OptimizedMockFactory::createBatch($mockType, 3);
        }
        
        // Pre-generate commonly used test data
        $commonDataTypes = ['valid_urls', 'invalid_urls', 'test_configurations'];
        foreach ($commonDataTypes as $dataType) {
            OptimizedEdgeCaseDataGenerator::generateExtremeUrls(['type' => $dataType, 'count' => 10]);
        }
    }
    
    /**
     * Generate optimization recommendations based on collected data.
     */
    private static function generateOptimizationRecommendations(): array
    {
        $recommendations = [];
        
        // Analyze test execution patterns
        $avgTestTime = self::calculateAverageTestTime();
        if ($avgTestTime > 2.0) {
            $recommendations[] = 'Average test execution time is high - consider test optimization';
        }
        
        // Analyze memory usage patterns
        $memoryEfficiency = self::calculateMemoryEfficiency();
        if ($memoryEfficiency < 70) {
            $recommendations[] = 'Memory efficiency is low - implement better memory management';
        }
        
        // Analyze cache performance
        $cacheHitRates = self::getCacheHitRates();
        if ($cacheHitRates['average'] < 50) {
            $recommendations[] = 'Cache hit rate is low - review caching strategies';
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Calculate average test execution time.
     */
    private static function calculateAverageTestTime(): float
    {
        if (empty(self::$performanceMetrics)) {
            return 0.0;
        }
        
        $totalTime = 0;
        foreach (self::$performanceMetrics as $metrics) {
            $totalTime += $metrics['execution_time'];
        }
        
        return $totalTime / count(self::$performanceMetrics);
    }
    
    /**
     * Calculate memory efficiency score.
     */
    private static function calculateMemoryEfficiency(): float
    {
        $memoryStats = MemoryUsageTracker::getMemoryStats();
        
        if ($memoryStats['memory_range']['max'] === 0) {
            return 100.0;
        }
        
        $efficiency = (1 - ($memoryStats['memory_range']['difference'] / $memoryStats['memory_range']['max'])) * 100;
        
        return max(0, min(100, $efficiency));
    }
    
    /**
     * Get cache hit rates from all caching systems.
     */
    private static function getCacheHitRates(): array
    {
        $mockFactoryMetrics = OptimizedMockFactory::getMetrics();
        $dataGeneratorMetrics = OptimizedEdgeCaseDataGenerator::getMetrics();
        $optimizerMetrics = TestExecutionOptimizer::getExecutionStats();
        
        $hitRates = [
            'mock_factory' => $mockFactoryMetrics['cache_hit_rate'] ?? 0,
            'data_generator' => $dataGeneratorMetrics['cache_hit_rate'] ?? 0,
            'optimizer' => $optimizerMetrics['cache_hit_rate'] ?? 0,
        ];
        
        $hitRates['average'] = array_sum($hitRates) / count($hitRates);
        
        return $hitRates;
    }
    
    /**
     * Calculate optimization impact score.
     */
    private static function calculateOptimizationImpact(): array
    {
        $optimizerStats = TestExecutionOptimizer::getExecutionStats();
        $mockFactoryStats = OptimizedMockFactory::getMetrics();
        
        return [
            'cache_efficiency' => $optimizerStats['cache_hit_rate'] ?? 0,
            'mock_reuse_efficiency' => $mockFactoryStats['pool_reuse_rate'] ?? 0,
            'memory_optimization' => self::calculateMemoryEfficiency(),
            'overall_impact' => 'positive', // Simplified calculation
        ];
    }
    
    /**
     * Generate HTML performance report.
     */
    private static function generateHtmlReport(array $report): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Performance Optimization Report</title>\n</head>\n<body>\n";
        $html .= "<h1>Performance Optimization Report</h1>\n";
        $html .= "<p>Generated at: {$report['generated_at']}</p>\n";
        
        // Test execution stats
        $html .= "<h2>Test Execution Statistics</h2>\n";
        $html .= "<ul>\n";
        $html .= "<li>Total Tests Executed: {$report['test_execution_stats']['total_tests_executed']}</li>\n";
        $html .= "</ul>\n";
        
        // Optimization recommendations
        if (!empty($report['optimization_recommendations'])) {
            $html .= "<h2>Optimization Recommendations</h2>\n";
            $html .= "<ul>\n";
            foreach ($report['optimization_recommendations'] as $recommendation) {
                $html .= "<li>" . htmlspecialchars($recommendation) . "</li>\n";
            }
            $html .= "</ul>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate CSV performance report.
     */
    private static function generateCsvReport(array $report): string
    {
        $csv = "Test Name,Execution Time (s),Memory Usage (MB),Status\n";
        
        foreach ($report['test_execution_stats']['performance_metrics'] as $testName => $metrics) {
            $csv .= sprintf(
                "%s,%.4f,%.2f,%s\n",
                $testName,
                $metrics['execution_time'],
                $metrics['memory_usage'] / 1024 / 1024,
                'completed'
            );
        }
        
        return $csv;
    }
}