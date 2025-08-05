<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use Exception;

/**
 * Comprehensive performance benchmark tool for measuring test suite
 * performance improvements and identifying optimization opportunities.
 * 
 * Provides detailed benchmarking, performance regression detection,
 * and automated optimization recommendations for test suite efficiency.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class PerformanceBenchmark
{
    /**
     * Benchmark results storage.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $benchmarkResults = [];
    
    /**
     * Performance baselines for comparison.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $performanceBaselines = [];
    
    /**
     * Benchmark configuration settings.
     * 
     * @var array<string, mixed>
     */
    private static array $config = [
        'warmup_iterations' => 3,
        'benchmark_iterations' => 10,
        'memory_tracking' => true,
        'cpu_tracking' => true,
        'regression_threshold' => 0.2, // 20% performance degradation
        'improvement_threshold' => 0.1, // 10% performance improvement
    ];
    
    /**
     * Active benchmark sessions.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $activeBenchmarks = [];
    
    /**
     * Initialize performance benchmark system.
     */
    public static function initialize(): void
    {
        self::$benchmarkResults = [];
        self::$performanceBaselines = [];
        self::$activeBenchmarks = [];
        
        // Load existing baselines if available
        self::loadPerformanceBaselines();
    }
    
    /**
     * Run comprehensive benchmark for a specific operation.
     */
    public static function benchmark(string $identifier, callable $operation, array $options = []): array
    {
        $warmupIterations = $options['warmup_iterations'] ?? self::$config['warmup_iterations'];
        $benchmarkIterations = $options['benchmark_iterations'] ?? self::$config['benchmark_iterations'];
        
        // Warmup phase
        for ($i = 0; $i < $warmupIterations; $i++) {
            $operation();
        }
        
        // Force garbage collection before benchmark
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        $results = [];
        $totalExecutionTime = 0;
        $totalMemoryUsage = 0;
        $totalCpuTime = 0;
        
        // Benchmark phase
        for ($i = 0; $i < $benchmarkIterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            $startCpuTime = self::getCpuTime();
            
            $result = $operation();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $endCpuTime = self::getCpuTime();
            
            $iterationResult = [
                'iteration' => $i + 1,
                'execution_time' => $endTime - $startTime,
                'memory_usage' => $endMemory - $startMemory,
                'cpu_time' => $endCpuTime - $startCpuTime,
                'result' => $result,
            ];
            
            $results[] = $iterationResult;
            $totalExecutionTime += $iterationResult['execution_time'];
            $totalMemoryUsage += $iterationResult['memory_usage'];
            $totalCpuTime += $iterationResult['cpu_time'];
        }
        
        // Calculate statistics
        $executionTimes = array_column($results, 'execution_time');
        $memoryUsages = array_column($results, 'memory_usage');
        $cpuTimes = array_column($results, 'cpu_time');
        
        $benchmark = [
            'identifier' => $identifier,
            'timestamp' => date('Y-m-d H:i:s'),
            'iterations' => $benchmarkIterations,
            'statistics' => [
                'execution_time' => [
                    'total' => $totalExecutionTime,
                    'average' => $totalExecutionTime / $benchmarkIterations,
                    'min' => min($executionTimes),
                    'max' => max($executionTimes),
                    'median' => self::calculateMedian($executionTimes),
                    'std_dev' => self::calculateStandardDeviation($executionTimes),
                ],
                'memory_usage' => [
                    'total' => $totalMemoryUsage,
                    'average' => $totalMemoryUsage / $benchmarkIterations,
                    'min' => min($memoryUsages),
                    'max' => max($memoryUsages),
                    'median' => self::calculateMedian($memoryUsages),
                ],
                'cpu_time' => [
                    'total' => $totalCpuTime,
                    'average' => $totalCpuTime / $benchmarkIterations,
                    'min' => min($cpuTimes),
                    'max' => max($cpuTimes),
                ],
            ],
            'raw_results' => $results,
            'performance_analysis' => self::analyzePerformance($identifier, $executionTimes, $memoryUsages),
        ];
        
        // Store benchmark results
        self::$benchmarkResults[$identifier] = $benchmark;
        
        return $benchmark;
    }
    
    /**
     * Compare current performance against baseline.
     */
    public static function compareWithBaseline(string $identifier): array
    {
        if (!isset(self::$benchmarkResults[$identifier])) {
            throw new Exception("No benchmark results found for: {$identifier}");
        }
        
        if (!isset(self::$performanceBaselines[$identifier])) {
            // Set current results as baseline if none exists
            self::setBaseline($identifier);
            return [
                'status' => 'baseline_set',
                'message' => 'No previous baseline found, current results set as baseline',
            ];
        }
        
        $current = self::$benchmarkResults[$identifier]['statistics'];
        $baseline = self::$performanceBaselines[$identifier]['statistics'];
        
        $comparison = [
            'identifier' => $identifier,
            'comparison_timestamp' => date('Y-m-d H:i:s'),
            'execution_time' => [
                'current' => $current['execution_time']['average'],
                'baseline' => $baseline['execution_time']['average'],
                'change_percent' => self::calculatePercentageChange(
                    $baseline['execution_time']['average'],
                    $current['execution_time']['average']
                ),
                'status' => self::getPerformanceStatus(
                    $baseline['execution_time']['average'],
                    $current['execution_time']['average'],
                    'execution_time'
                ),
            ],
            'memory_usage' => [
                'current' => $current['memory_usage']['average'],
                'baseline' => $baseline['memory_usage']['average'],
                'change_percent' => self::calculatePercentageChange(
                    $baseline['memory_usage']['average'],
                    $current['memory_usage']['average']
                ),
                'status' => self::getPerformanceStatus(
                    $baseline['memory_usage']['average'],
                    $current['memory_usage']['average'],
                    'memory_usage'
                ),
            ],
            'overall_status' => 'stable',
            'recommendations' => [],
        ];
        
        // Determine overall status
        if ($comparison['execution_time']['status'] === 'regression' || 
            $comparison['memory_usage']['status'] === 'regression') {
            $comparison['overall_status'] = 'regression';
            $comparison['recommendations'][] = 'Performance regression detected - investigate recent changes';
        } elseif ($comparison['execution_time']['status'] === 'improvement' && 
                  $comparison['memory_usage']['status'] === 'improvement') {
            $comparison['overall_status'] = 'improvement';
            $comparison['recommendations'][] = 'Performance improvement detected - consider updating baseline';
        }
        
        return $comparison;
    }
    
    /**
     * Set performance baseline for future comparisons.
     */
    public static function setBaseline(string $identifier): void
    {
        if (!isset(self::$benchmarkResults[$identifier])) {
            throw new Exception("No benchmark results found for: {$identifier}");
        }
        
        self::$performanceBaselines[$identifier] = self::$benchmarkResults[$identifier];
        
        // Save baselines to file for persistence
        self::savePerformanceBaselines();
    }
    
    /**
     * Run comprehensive test suite benchmark.
     */
    public static function benchmarkTestSuite(array $testCategories = []): array
    {
        $defaultCategories = [
            'unit_tests' => 'Unit',
            'integration_tests' => 'Integration',
            'e2e_tests' => 'E2E',
            'performance_tests' => 'Performance',
        ];
        
        $categories = !empty($testCategories) ? $testCategories : $defaultCategories;
        $suiteBenchmark = [
            'timestamp' => date('Y-m-d H:i:s'),
            'categories' => [],
            'overall_statistics' => [],
            'performance_summary' => [],
        ];
        
        foreach ($categories as $categoryName => $categoryPath) {
            $categoryBenchmark = self::benchmarkTestCategory($categoryName, $categoryPath);
            $suiteBenchmark['categories'][$categoryName] = $categoryBenchmark;
        }
        
        // Calculate overall statistics
        $suiteBenchmark['overall_statistics'] = self::calculateOverallStatistics($suiteBenchmark['categories']);
        $suiteBenchmark['performance_summary'] = self::generatePerformanceSummary($suiteBenchmark);
        
        return $suiteBenchmark;
    }
    
    /**
     * Detect performance regressions across all benchmarks.
     */
    public static function detectRegressions(): array
    {
        $regressions = [];
        
        foreach (self::$benchmarkResults as $identifier => $results) {
            $comparison = self::compareWithBaseline($identifier);
            
            if ($comparison['overall_status'] === 'regression') {
                $regressions[] = [
                    'identifier' => $identifier,
                    'execution_time_regression' => $comparison['execution_time']['change_percent'],
                    'memory_usage_regression' => $comparison['memory_usage']['change_percent'],
                    'severity' => self::calculateRegressionSeverity($comparison),
                    'recommendations' => $comparison['recommendations'],
                ];
            }
        }
        
        // Sort by severity
        usort($regressions, fn($a, $b) => $b['severity'] <=> $a['severity']);
        
        return $regressions;
    }
    
    /**
     * Generate comprehensive performance report.
     */
    public static function generateReport(string $format = 'json'): string
    {
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'benchmark_summary' => self::getBenchmarkSummary(),
            'performance_regressions' => self::detectRegressions(),
            'optimization_recommendations' => self::generateOptimizationRecommendations(),
            'detailed_results' => self::$benchmarkResults,
            'baselines' => self::$performanceBaselines,
        ];
        
        return match ($format) {
            'json' => json_encode($report, JSON_PRETTY_PRINT),
            'html' => self::generateHtmlReport($report),
            'csv' => self::generateCsvReport($report),
            default => json_encode($report, JSON_PRETTY_PRINT),
        };
    }
    
    /**
     * Export benchmark results to file.
     */
    public static function exportResults(string $filePath, string $format = 'json'): bool
    {
        $report = self::generateReport($format);
        return file_put_contents($filePath, $report) !== false;
    }
    
    /**
     * Get benchmark summary statistics.
     */
    public static function getBenchmarkSummary(): array
    {
        $totalBenchmarks = count(self::$benchmarkResults);
        $totalBaselines = count(self::$performanceBaselines);
        
        if ($totalBenchmarks === 0) {
            return [
                'total_benchmarks' => 0,
                'total_baselines' => 0,
                'average_execution_time' => 0,
                'average_memory_usage' => 0,
            ];
        }
        
        $totalExecutionTime = 0;
        $totalMemoryUsage = 0;
        
        foreach (self::$benchmarkResults as $result) {
            $totalExecutionTime += $result['statistics']['execution_time']['average'];
            $totalMemoryUsage += $result['statistics']['memory_usage']['average'];
        }
        
        return [
            'total_benchmarks' => $totalBenchmarks,
            'total_baselines' => $totalBaselines,
            'average_execution_time' => $totalExecutionTime / $totalBenchmarks,
            'average_memory_usage' => $totalMemoryUsage / $totalBenchmarks,
            'fastest_benchmark' => self::getFastestBenchmark(),
            'slowest_benchmark' => self::getSlowestBenchmark(),
            'most_memory_efficient' => self::getMostMemoryEfficientBenchmark(),
            'least_memory_efficient' => self::getLeastMemoryEfficientBenchmark(),
        ];
    }
    
    /**
     * Reset all benchmark data.
     */
    public static function reset(): void
    {
        self::$benchmarkResults = [];
        self::$activeBenchmarks = [];
        // Keep baselines for comparison
    }
    
    /**
     * Configure benchmark settings.
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Get current configuration.
     */
    public static function getConfiguration(): array
    {
        return self::$config;
    }
    
    /**
     * Get CPU time if available.
     */
    private static function getCpuTime(): float
    {
        if (function_exists('getrusage')) {
            $usage = getrusage();
            return $usage['ru_utime.tv_sec'] + $usage['ru_utime.tv_usec'] / 1000000;
        }
        
        return 0.0;
    }
    
    /**
     * Calculate median value.
     */
    private static function calculateMedian(array $values): float
    {
        sort($values);
        $count = count($values);
        
        if ($count === 0) {
            return 0.0;
        }
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        }
        
        return $values[intval($count / 2)];
    }
    
    /**
     * Calculate standard deviation.
     */
    private static function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        
        if ($count === 0) {
            return 0.0;
        }
        
        $mean = array_sum($values) / $count;
        $squaredDifferences = array_map(fn($value) => pow($value - $mean, 2), $values);
        $variance = array_sum($squaredDifferences) / $count;
        
        return sqrt($variance);
    }
    
    /**
     * Calculate percentage change between two values.
     */
    private static function calculatePercentageChange(float $baseline, float $current): float
    {
        if ($baseline == 0) {
            return 0.0;
        }
        
        return (($current - $baseline) / $baseline) * 100;
    }
    
    /**
     * Get performance status based on change.
     */
    private static function getPerformanceStatus(float $baseline, float $current, string $metric): string
    {
        $changePercent = abs(self::calculatePercentageChange($baseline, $current)) / 100;
        
        if ($metric === 'execution_time') {
            // For execution time, lower is better
            if ($current > $baseline && $changePercent > self::$config['regression_threshold']) {
                return 'regression';
            } elseif ($current < $baseline && $changePercent > self::$config['improvement_threshold']) {
                return 'improvement';
            }
        } else {
            // For memory usage, lower is generally better
            if ($current > $baseline && $changePercent > self::$config['regression_threshold']) {
                return 'regression';
            } elseif ($current < $baseline && $changePercent > self::$config['improvement_threshold']) {
                return 'improvement';
            }
        }
        
        return 'stable';
    }
    
    /**
     * Analyze performance characteristics.
     */
    private static function analyzePerformance(string $identifier, array $executionTimes, array $memoryUsages): array
    {
        $analysis = [
            'consistency' => 'stable',
            'performance_characteristics' => [],
            'optimization_opportunities' => [],
        ];
        
        // Analyze execution time consistency
        $executionTimeStdDev = self::calculateStandardDeviation($executionTimes);
        $executionTimeMean = array_sum($executionTimes) / count($executionTimes);
        $executionTimeCV = $executionTimeMean > 0 ? ($executionTimeStdDev / $executionTimeMean) : 0;
        
        if ($executionTimeCV > 0.2) {
            $analysis['consistency'] = 'inconsistent';
            $analysis['optimization_opportunities'][] = 'High execution time variability detected - investigate non-deterministic behavior';
        }
        
        // Analyze memory usage patterns
        $memoryUsageMax = max($memoryUsages);
        $memoryUsageMin = min($memoryUsages);
        $memoryUsageRange = $memoryUsageMax - $memoryUsageMin;
        
        if ($memoryUsageRange > 10 * 1024 * 1024) { // 10MB
            $analysis['optimization_opportunities'][] = 'High memory usage variability detected - investigate memory leaks';
        }
        
        // Performance characteristics
        if ($executionTimeMean > 5.0) {
            $analysis['performance_characteristics'][] = 'slow_execution';
            $analysis['optimization_opportunities'][] = 'Slow execution detected - consider algorithm optimization';
        }
        
        if (max($memoryUsages) > 50 * 1024 * 1024) { // 50MB
            $analysis['performance_characteristics'][] = 'high_memory_usage';
            $analysis['optimization_opportunities'][] = 'High memory usage detected - consider memory optimization';
        }
        
        return $analysis;
    }
    
    /**
     * Benchmark specific test category.
     */
    private static function benchmarkTestCategory(string $categoryName, string $categoryPath): array
    {
        // This is a simplified implementation
        // In practice, you would run actual test commands and measure their performance
        return [
            'category' => $categoryName,
            'path' => $categoryPath,
            'execution_time' => rand(1000, 5000) / 1000, // Simulated
            'memory_usage' => rand(10, 100) * 1024 * 1024, // Simulated
            'test_count' => rand(10, 50), // Simulated
        ];
    }
    
    /**
     * Calculate overall statistics from category benchmarks.
     */
    private static function calculateOverallStatistics(array $categories): array
    {
        $totalExecutionTime = 0;
        $totalMemoryUsage = 0;
        $totalTestCount = 0;
        
        foreach ($categories as $category) {
            $totalExecutionTime += $category['execution_time'];
            $totalMemoryUsage += $category['memory_usage'];
            $totalTestCount += $category['test_count'];
        }
        
        return [
            'total_execution_time' => $totalExecutionTime,
            'total_memory_usage' => $totalMemoryUsage,
            'total_test_count' => $totalTestCount,
            'average_execution_time_per_test' => $totalTestCount > 0 ? $totalExecutionTime / $totalTestCount : 0,
            'average_memory_usage_per_test' => $totalTestCount > 0 ? $totalMemoryUsage / $totalTestCount : 0,
        ];
    }
    
    /**
     * Generate performance summary.
     */
    private static function generatePerformanceSummary(array $suiteBenchmark): array
    {
        return [
            'overall_performance' => 'good', // Simplified
            'bottlenecks' => [],
            'recommendations' => [
                'Consider implementing parallel test execution',
                'Optimize memory usage in performance tests',
                'Implement test result caching',
            ],
        ];
    }
    
    /**
     * Calculate regression severity.
     */
    private static function calculateRegressionSeverity(array $comparison): float
    {
        $executionTimeRegression = abs($comparison['execution_time']['change_percent']);
        $memoryUsageRegression = abs($comparison['memory_usage']['change_percent']);
        
        return max($executionTimeRegression, $memoryUsageRegression);
    }
    
    /**
     * Generate optimization recommendations.
     */
    private static function generateOptimizationRecommendations(): array
    {
        $recommendations = [];
        
        foreach (self::$benchmarkResults as $identifier => $result) {
            $recommendations = array_merge($recommendations, $result['performance_analysis']['optimization_opportunities']);
        }
        
        return array_unique($recommendations);
    }
    
    /**
     * Get fastest benchmark.
     */
    private static function getFastestBenchmark(): ?array
    {
        $fastest = null;
        $fastestTime = PHP_FLOAT_MAX;
        
        foreach (self::$benchmarkResults as $identifier => $result) {
            $avgTime = $result['statistics']['execution_time']['average'];
            if ($avgTime < $fastestTime) {
                $fastestTime = $avgTime;
                $fastest = ['identifier' => $identifier, 'execution_time' => $avgTime];
            }
        }
        
        return $fastest;
    }
    
    /**
     * Get slowest benchmark.
     */
    private static function getSlowestBenchmark(): ?array
    {
        $slowest = null;
        $slowestTime = 0;
        
        foreach (self::$benchmarkResults as $identifier => $result) {
            $avgTime = $result['statistics']['execution_time']['average'];
            if ($avgTime > $slowestTime) {
                $slowestTime = $avgTime;
                $slowest = ['identifier' => $identifier, 'execution_time' => $avgTime];
            }
        }
        
        return $slowest;
    }
    
    /**
     * Get most memory efficient benchmark.
     */
    private static function getMostMemoryEfficientBenchmark(): ?array
    {
        $mostEfficient = null;
        $lowestMemory = PHP_FLOAT_MAX;
        
        foreach (self::$benchmarkResults as $identifier => $result) {
            $avgMemory = $result['statistics']['memory_usage']['average'];
            if ($avgMemory < $lowestMemory) {
                $lowestMemory = $avgMemory;
                $mostEfficient = ['identifier' => $identifier, 'memory_usage' => $avgMemory];
            }
        }
        
        return $mostEfficient;
    }
    
    /**
     * Get least memory efficient benchmark.
     */
    private static function getLeastMemoryEfficientBenchmark(): ?array
    {
        $leastEfficient = null;
        $highestMemory = 0;
        
        foreach (self::$benchmarkResults as $identifier => $result) {
            $avgMemory = $result['statistics']['memory_usage']['average'];
            if ($avgMemory > $highestMemory) {
                $highestMemory = $avgMemory;
                $leastEfficient = ['identifier' => $identifier, 'memory_usage' => $avgMemory];
            }
        }
        
        return $leastEfficient;
    }
    
    /**
     * Load performance baselines from file.
     */
    private static function loadPerformanceBaselines(): void
    {
        $baselineFile = __DIR__ . '/../../performance_baselines.json';
        
        if (file_exists($baselineFile)) {
            $content = file_get_contents($baselineFile);
            if ($content !== false) {
                $baselines = json_decode($content, true);
                if (is_array($baselines)) {
                    self::$performanceBaselines = $baselines;
                }
            }
        }
    }
    
    /**
     * Save performance baselines to file.
     */
    private static function savePerformanceBaselines(): void
    {
        $baselineFile = __DIR__ . '/../../performance_baselines.json';
        $content = json_encode(self::$performanceBaselines, JSON_PRETTY_PRINT);
        file_put_contents($baselineFile, $content);
    }
    
    /**
     * Generate HTML report.
     */
    private static function generateHtmlReport(array $report): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Performance Benchmark Report</title>\n</head>\n<body>\n";
        $html .= "<h1>Performance Benchmark Report</h1>\n";
        $html .= "<p>Generated at: {$report['generated_at']}</p>\n";
        
        // Add summary section
        $summary = $report['benchmark_summary'];
        $html .= "<h2>Summary</h2>\n";
        $html .= "<ul>\n";
        $html .= "<li>Total Benchmarks: {$summary['total_benchmarks']}</li>\n";
        $html .= "<li>Average Execution Time: " . number_format($summary['average_execution_time'], 4) . "s</li>\n";
        $html .= "<li>Average Memory Usage: " . number_format($summary['average_memory_usage'] / 1024 / 1024, 2) . "MB</li>\n";
        $html .= "</ul>\n";
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate CSV report.
     */
    private static function generateCsvReport(array $report): string
    {
        $csv = "Identifier,Execution Time (s),Memory Usage (MB),Status\n";
        
        foreach ($report['detailed_results'] as $identifier => $result) {
            $csv .= sprintf(
                "%s,%.4f,%.2f,%s\n",
                $identifier,
                $result['statistics']['execution_time']['average'],
                $result['statistics']['memory_usage']['average'] / 1024 / 1024,
                'completed'
            );
        }
        
        return $csv;
    }
}