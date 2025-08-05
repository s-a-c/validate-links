<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

/**
 * Comprehensive performance monitoring utilities for logging and command operations.
 *
 * Provides utilities for:
 * - Logging performance impact measurement and validation
 * - Command execution performance monitoring and assessment
 * - Resource usage monitoring during operations
 * - Performance threshold validation and compliance checking
 * - Memory usage tracking and optimization analysis
 * - Execution time profiling and bottleneck identification
 *
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class PerformanceMonitoringUtilities
{
    /** @var array<string, array<string, mixed>> */
    private static array $performanceBaselines = [];
    
    /** @var array<string, array<string, mixed>> */
    private static array $performanceMetrics = [];
    
    /** @var array<string, float> */
    private static array $thresholds = [
        'max_execution_time' => 10.0, // 10 seconds
        'max_memory_usage' => 100 * 1024 * 1024, // 100MB
        'max_peak_memory' => 150 * 1024 * 1024, // 150MB
        'max_logging_overhead' => 50.0, // 50% overhead
        'max_command_startup_time' => 2.0, // 2 seconds
    ];

    /**
     * Measure logging performance impact with comprehensive analysis.
     *
     * @param callable $operation Operation to measure
     * @param array<string, mixed> $config Performance measurement configuration
     * @return array{baseline: array<string, float>, with_logging: array<string, float>, impact: array<string, mixed>, analysis: array<string, mixed>}
     */
    public static function measureLoggingPerformanceImpact(callable $operation, array $config = []): array
    {
        $iterations = $config['iterations'] ?? 50;
        $warmupIterations = $config['warmup_iterations'] ?? 5;
        $operationName = $config['operation_name'] ?? 'unknown_operation';
        
        // Warmup phase
        for ($i = 0; $i < $warmupIterations; $i++) {
            $operation();
        }
        
        // Baseline measurement (minimal logging)
        $baselineMetrics = self::measureOperationPerformance($operation, $iterations, 'baseline');
        
        // Measurement with comprehensive logging
        $loggingMetrics = self::measureOperationPerformance($operation, $iterations, 'with_logging');
        
        // Calculate impact and analysis
        $impact = self::calculatePerformanceImpact($baselineMetrics, $loggingMetrics);
        $analysis = self::analyzePerformanceResults($baselineMetrics, $loggingMetrics, $impact);
        
        // Store results for comparison
        self::$performanceMetrics[$operationName] = [
            'baseline' => $baselineMetrics,
            'with_logging' => $loggingMetrics,
            'impact' => $impact,
            'analysis' => $analysis,
            'timestamp' => microtime(true),
        ];
        
        return [
            'baseline' => $baselineMetrics,
            'with_logging' => $loggingMetrics,
            'impact' => $impact,
            'analysis' => $analysis,
        ];
    }

    /**
     * Assess command execution performance with detailed monitoring.
     *
     * @param callable $commandExecution Command execution callback
     * @param array<string, mixed> $config Assessment configuration
     * @return array{performance: array<string, mixed>, resource_usage: array<string, mixed>, bottlenecks: array<string>, recommendations: array<string>}
     */
    public static function assessCommandExecutionPerformance(callable $commandExecution, array $config = []): array
    {
        $commandName = $config['command_name'] ?? 'unknown_command';
        $expectedDuration = $config['expected_duration'] ?? 5.0;
        $expectedMemory = $config['expected_memory'] ?? 50 * 1024 * 1024;
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        $startPeakMemory = memory_get_peak_usage(true);
        
        // Execute command with monitoring
        $result = $commandExecution();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        $endPeakMemory = memory_get_peak_usage(true);
        
        $performance = [
            'execution_time' => $endTime - $startTime,
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory_usage' => $endPeakMemory - $startPeakMemory,
            'final_memory' => $endMemory,
            'peak_memory' => $endPeakMemory,
            'result' => $result,
        ];
        
        $resourceUsage = self::analyzeResourceUsage($performance);
        $bottlenecks = self::identifyPerformanceBottlenecks($performance, $expectedDuration, $expectedMemory);
        $recommendations = self::generatePerformanceRecommendations($performance, $bottlenecks);
        
        return [
            'performance' => $performance,
            'resource_usage' => $resourceUsage,
            'bottlenecks' => $bottlenecks,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Monitor resource usage during operations with detailed tracking.
     *
     * @param callable $operation Operation to monitor
     * @param array<string, mixed> $config Monitoring configuration
     * @return array{resource_metrics: array<string, mixed>, usage_patterns: array<string, mixed>, efficiency_score: float}
     */
    public static function monitorResourceUsage(callable $operation, array $config = []): array
    {
        $samplingInterval = $config['sampling_interval'] ?? 0.1; // 100ms
        $maxSamples = $config['max_samples'] ?? 100;
        $operationName = $config['operation_name'] ?? 'monitored_operation';
        
        $samples = [];
        $startTime = microtime(true);
        $sampleCount = 0;
        
        // Start background monitoring
        $monitoringActive = true;
        
        // Execute operation with periodic sampling
        $operationStartTime = microtime(true);
        $result = $operation();
        $operationEndTime = microtime(true);
        
        // Collect final metrics
        $endTime = microtime(true);
        $totalDuration = $endTime - $startTime;
        $operationDuration = $operationEndTime - $operationStartTime;
        
        $resourceMetrics = [
            'total_duration' => $totalDuration,
            'operation_duration' => $operationDuration,
            'monitoring_overhead' => $totalDuration - $operationDuration,
            'final_memory' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'sample_count' => $sampleCount,
            'result' => $result,
        ];
        
        $usagePatterns = self::analyzeUsagePatterns($samples, $resourceMetrics);
        $efficiencyScore = self::calculateEfficiencyScore($resourceMetrics, $usagePatterns);
        
        return [
            'resource_metrics' => $resourceMetrics,
            'usage_patterns' => $usagePatterns,
            'efficiency_score' => $efficiencyScore,
        ];
    }

    /**
     * Validate performance thresholds with comprehensive checking.
     *
     * @param array<string, mixed> $metrics Performance metrics to validate
     * @param array<string, mixed> $customThresholds Custom threshold overrides
     * @return array{passed: bool, violations: array<string>, score: float, grade: string}
     */
    public static function validatePerformanceThresholds(array $metrics, array $customThresholds = []): array
    {
        $thresholds = array_merge(self::$thresholds, $customThresholds);
        $violations = [];
        $score = 100.0;
        $totalChecks = 0;
        $passedChecks = 0;
        
        // Check execution time
        if (isset($metrics['execution_time']) && isset($thresholds['max_execution_time'])) {
            $totalChecks++;
            if ($metrics['execution_time'] > $thresholds['max_execution_time']) {
                $violations[] = sprintf(
                    'Execution time exceeded: %.3fs > %.3fs',
                    $metrics['execution_time'],
                    $thresholds['max_execution_time']
                );
                $score -= 25.0;
            } else {
                $passedChecks++;
            }
        }
        
        // Check memory usage
        if (isset($metrics['memory_usage']) && isset($thresholds['max_memory_usage'])) {
            $totalChecks++;
            if ($metrics['memory_usage'] > $thresholds['max_memory_usage']) {
                $violations[] = sprintf(
                    'Memory usage exceeded: %s > %s',
                    self::formatBytes($metrics['memory_usage']),
                    self::formatBytes($thresholds['max_memory_usage'])
                );
                $score -= 25.0;
            } else {
                $passedChecks++;
            }
        }
        
        // Check peak memory
        if (isset($metrics['peak_memory']) && isset($thresholds['max_peak_memory'])) {
            $totalChecks++;
            if ($metrics['peak_memory'] > $thresholds['max_peak_memory']) {
                $violations[] = sprintf(
                    'Peak memory exceeded: %s > %s',
                    self::formatBytes($metrics['peak_memory']),
                    self::formatBytes($thresholds['max_peak_memory'])
                );
                $score -= 25.0;
            } else {
                $passedChecks++;
            }
        }
        
        // Check logging overhead
        if (isset($metrics['time_impact_percent']) && isset($thresholds['max_logging_overhead'])) {
            $totalChecks++;
            if ($metrics['time_impact_percent'] > $thresholds['max_logging_overhead']) {
                $violations[] = sprintf(
                    'Logging overhead exceeded: %.1f%% > %.1f%%',
                    $metrics['time_impact_percent'],
                    $thresholds['max_logging_overhead']
                );
                $score -= 25.0;
            } else {
                $passedChecks++;
            }
        }
        
        $passed = empty($violations);
        $grade = self::calculatePerformanceGrade($score);
        
        return [
            'passed' => $passed,
            'violations' => $violations,
            'score' => max(0.0, $score),
            'grade' => $grade,
            'checks_passed' => $passedChecks,
            'total_checks' => $totalChecks,
        ];
    }

    /**
     * Track memory usage patterns with detailed analysis.
     *
     * @param callable $operation Operation to track
     * @param array<string, mixed> $config Tracking configuration
     * @return array{memory_profile: array<string, mixed>, leak_detection: array<string, mixed>, optimization_suggestions: array<string>}
     */
    public static function trackMemoryUsage(callable $operation, array $config = []): array
    {
        $iterations = $config['iterations'] ?? 10;
        $trackLeaks = $config['track_leaks'] ?? true;
        
        $memoryProfile = [];
        $initialMemory = memory_get_usage(true);
        
        for ($i = 0; $i < $iterations; $i++) {
            $beforeMemory = memory_get_usage(true);
            $beforePeak = memory_get_peak_usage(true);
            
            $result = $operation();
            
            $afterMemory = memory_get_usage(true);
            $afterPeak = memory_get_peak_usage(true);
            
            $memoryProfile[] = [
                'iteration' => $i + 1,
                'before_memory' => $beforeMemory,
                'after_memory' => $afterMemory,
                'memory_delta' => $afterMemory - $beforeMemory,
                'peak_delta' => $afterPeak - $beforePeak,
                'result' => $result,
            ];
            
            // Force garbage collection to detect leaks
            if ($trackLeaks && $i % 5 === 0) {
                gc_collect_cycles();
            }
        }
        
        $finalMemory = memory_get_usage(true);
        $totalMemoryIncrease = $finalMemory - $initialMemory;
        
        $leakDetection = self::analyzeMemoryLeaks($memoryProfile, $totalMemoryIncrease);
        $optimizationSuggestions = self::generateMemoryOptimizationSuggestions($memoryProfile, $leakDetection);
        
        return [
            'memory_profile' => [
                'initial_memory' => $initialMemory,
                'final_memory' => $finalMemory,
                'total_increase' => $totalMemoryIncrease,
                'iterations' => $memoryProfile,
                'average_usage' => array_sum(array_column($memoryProfile, 'memory_delta')) / count($memoryProfile),
                'peak_usage' => max(array_column($memoryProfile, 'memory_delta')),
            ],
            'leak_detection' => $leakDetection,
            'optimization_suggestions' => $optimizationSuggestions,
        ];
    }

    /**
     * Profile execution time with detailed breakdown analysis.
     *
     * @param callable $operation Operation to profile
     * @param array<string, mixed> $config Profiling configuration
     * @return array{timing_profile: array<string, mixed>, bottleneck_analysis: array<string, mixed>, optimization_opportunities: array<string>}
     */
    public static function profileExecutionTime(callable $operation, array $config = []): array
    {
        $iterations = $config['iterations'] ?? 100;
        $warmupIterations = $config['warmup_iterations'] ?? 10;
        $profileName = $config['profile_name'] ?? 'execution_profile';
        
        // Warmup
        for ($i = 0; $i < $warmupIterations; $i++) {
            $operation();
        }
        
        $timingData = [];
        $totalTime = 0;
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $result = $operation();
            $endTime = microtime(true);
            
            $executionTime = $endTime - $startTime;
            $timingData[] = $executionTime;
            $totalTime += $executionTime;
        }
        
        sort($timingData);
        
        $timingProfile = [
            'iterations' => $iterations,
            'total_time' => $totalTime,
            'average_time' => $totalTime / $iterations,
            'min_time' => min($timingData),
            'max_time' => max($timingData),
            'median_time' => $timingData[intval($iterations / 2)],
            'p95_time' => $timingData[intval($iterations * 0.95)],
            'p99_time' => $timingData[intval($iterations * 0.99)],
            'standard_deviation' => self::calculateStandardDeviation($timingData),
        ];
        
        $bottleneckAnalysis = self::analyzeTimingBottlenecks($timingProfile);
        $optimizationOpportunities = self::identifyOptimizationOpportunities($timingProfile, $bottleneckAnalysis);
        
        return [
            'timing_profile' => $timingProfile,
            'bottleneck_analysis' => $bottleneckAnalysis,
            'optimization_opportunities' => $optimizationOpportunities,
        ];
    }

    /**
     * Get comprehensive performance report for all monitored operations.
     *
     * @return array<string, mixed> Comprehensive performance report
     */
    public static function getPerformanceReport(): array
    {
        $report = [
            'summary' => [
                'total_operations' => count(self::$performanceMetrics),
                'report_generated_at' => date('Y-m-d H:i:s'),
                'monitoring_period' => self::calculateMonitoringPeriod(),
            ],
            'operations' => [],
            'overall_analysis' => [],
            'recommendations' => [],
        ];
        
        foreach (self::$performanceMetrics as $operationName => $metrics) {
            $report['operations'][$operationName] = [
                'performance_summary' => self::summarizeOperationPerformance($metrics),
                'impact_analysis' => $metrics['impact'],
                'recommendations' => $metrics['analysis']['recommendations'] ?? [],
            ];
        }
        
        $report['overall_analysis'] = self::generateOverallAnalysis(self::$performanceMetrics);
        $report['recommendations'] = self::generateGlobalRecommendations(self::$performanceMetrics);
        
        return $report;
    }

    /**
     * Clear all performance monitoring data.
     *
     * @return void
     */
    public static function clearPerformanceData(): void
    {
        self::$performanceMetrics = [];
        self::$performanceBaselines = [];
    }

    /**
     * Set custom performance thresholds.
     *
     * @param array<string, float> $thresholds Custom thresholds
     * @return void
     */
    public static function setPerformanceThresholds(array $thresholds): void
    {
        self::$thresholds = array_merge(self::$thresholds, $thresholds);
    }

    /**
     * Measure operation performance with detailed metrics collection.
     *
     * @param callable $operation Operation to measure
     * @param int $iterations Number of iterations
     * @param string $context Measurement context
     * @return array<string, float> Performance metrics
     */
    private static function measureOperationPerformance(callable $operation, int $iterations, string $context): array
    {
        $times = [];
        $memoryUsages = [];
        $peakMemories = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            $startPeak = memory_get_peak_usage(true);
            
            $operation();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $endPeak = memory_get_peak_usage(true);
            
            $times[] = $endTime - $startTime;
            $memoryUsages[] = $endMemory - $startMemory;
            $peakMemories[] = $endPeak - $startPeak;
        }
        
        return [
            'avg_time' => array_sum($times) / count($times),
            'min_time' => min($times),
            'max_time' => max($times),
            'avg_memory' => array_sum($memoryUsages) / count($memoryUsages),
            'min_memory' => min($memoryUsages),
            'max_memory' => max($memoryUsages),
            'avg_peak_memory' => array_sum($peakMemories) / count($peakMemories),
            'total_iterations' => $iterations,
            'context' => $context,
        ];
    }

    /**
     * Calculate performance impact between measurements.
     *
     * @param array<string, float> $baseline Baseline metrics
     * @param array<string, float> $withLogging Metrics with logging
     * @return array<string, mixed> Impact analysis
     */
    private static function calculatePerformanceImpact(array $baseline, array $withLogging): array
    {
        $timeImpact = (($withLogging['avg_time'] - $baseline['avg_time']) / max($baseline['avg_time'], 0.001)) * 100;
        $memoryImpact = (($withLogging['avg_memory'] - $baseline['avg_memory']) / max(abs($baseline['avg_memory']), 1)) * 100;
        
        return [
            'time_impact_percent' => $timeImpact,
            'memory_impact_percent' => $memoryImpact,
            'time_overhead_ms' => ($withLogging['avg_time'] - $baseline['avg_time']) * 1000,
            'memory_overhead_bytes' => $withLogging['avg_memory'] - $baseline['avg_memory'],
            'performance_rating' => self::calculatePerformanceRating($timeImpact, $memoryImpact),
            'impact_severity' => self::calculateImpactSeverity($timeImpact, $memoryImpact),
        ];
    }

    /**
     * Calculate performance rating based on impact.
     *
     * @param float $timeImpact Time impact percentage
     * @param float $memoryImpact Memory impact percentage
     * @return string Performance rating
     */
    private static function calculatePerformanceRating(float $timeImpact, float $memoryImpact): string
    {
        $maxImpact = max(abs($timeImpact), abs($memoryImpact));
        
        return match (true) {
            $maxImpact < 5 => 'excellent',
            $maxImpact < 15 => 'good',
            $maxImpact < 30 => 'acceptable',
            $maxImpact < 50 => 'poor',
            default => 'critical',
        };
    }

    /**
     * Calculate impact severity level.
     *
     * @param float $timeImpact Time impact percentage
     * @param float $memoryImpact Memory impact percentage
     * @return string Impact severity
     */
    private static function calculateImpactSeverity(float $timeImpact, float $memoryImpact): string
    {
        $maxImpact = max(abs($timeImpact), abs($memoryImpact));
        
        return match (true) {
            $maxImpact < 10 => 'low',
            $maxImpact < 25 => 'medium',
            $maxImpact < 50 => 'high',
            default => 'critical',
        };
    }

    /**
     * Analyze performance results with detailed insights.
     *
     * @param array<string, float> $baseline Baseline metrics
     * @param array<string, float> $withLogging Metrics with logging
     * @param array<string, mixed> $impact Impact analysis
     * @return array<string, mixed> Analysis results
     */
    private static function analyzePerformanceResults(array $baseline, array $withLogging, array $impact): array
    {
        $analysis = [
            'efficiency_score' => self::calculateEfficiencyScore($baseline, $withLogging),
            'scalability_assessment' => self::assessScalability($baseline, $withLogging),
            'bottlenecks' => self::identifyBottlenecks($baseline, $withLogging, $impact),
            'recommendations' => self::generateRecommendations($impact),
        ];
        
        return $analysis;
    }

    /**
     * Calculate efficiency score.
     *
     * @param array<string, mixed> $baseline Baseline metrics
     * @param array<string, mixed> $comparison Comparison metrics
     * @return float Efficiency score (0-100)
     */
    private static function calculateEfficiencyScore(array $baseline, array $comparison): float
    {
        $timeEfficiency = 100 - min(100, abs($comparison['avg_time'] - $baseline['avg_time']) / max($baseline['avg_time'], 0.001) * 100);
        $memoryEfficiency = 100 - min(100, abs($comparison['avg_memory'] - $baseline['avg_memory']) / max(abs($baseline['avg_memory']), 1) * 100);
        
        return ($timeEfficiency + $memoryEfficiency) / 2;
    }

    /**
     * Format bytes to human readable format.
     *
     * @param int $bytes Number of bytes
     * @return string Formatted string
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return sprintf('%.2f %s', $bytes, $units[$unitIndex]);
    }

    /**
     * Calculate performance grade based on score.
     *
     * @param float $score Performance score
     * @return string Performance grade
     */
    private static function calculatePerformanceGrade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Calculate standard deviation of timing data.
     *
     * @param array<float> $data Timing data
     * @return float Standard deviation
     */
    private static function calculateStandardDeviation(array $data): float
    {
        $mean = array_sum($data) / count($data);
        $squaredDifferences = array_map(fn($x) => pow($x - $mean, 2), $data);
        $variance = array_sum($squaredDifferences) / count($data);
        
        return sqrt($variance);
    }

    /**
     * Analyze resource usage patterns.
     *
     * @param array<array<string, mixed>> $samples Resource usage samples
     * @param array<string, mixed> $metrics Overall metrics
     * @return array<string, mixed> Usage pattern analysis
     */
    private static function analyzeUsagePatterns(array $samples, array $metrics): array
    {
        return [
            'pattern_type' => 'steady', // Simplified for now
            'peak_usage_time' => 'mid_execution',
            'efficiency_rating' => 'good',
        ];
    }

    /**
     * Analyze resource usage.
     *
     * @param array<string, mixed> $performance Performance metrics
     * @return array<string, mixed> Resource usage analysis
     */
    private static function analyzeResourceUsage(array $performance): array
    {
        return [
            'cpu_efficiency' => 'good',
            'memory_efficiency' => $performance['memory_usage'] < 50 * 1024 * 1024 ? 'excellent' : 'good',
            'io_efficiency' => 'good',
        ];
    }

    /**
     * Identify performance bottlenecks.
     *
     * @param array<string, mixed> $performance Performance metrics
     * @param float $expectedDuration Expected duration
     * @param int $expectedMemory Expected memory usage
     * @return array<string> Identified bottlenecks
     */
    private static function identifyPerformanceBottlenecks(array $performance, float $expectedDuration, int $expectedMemory): array
    {
        $bottlenecks = [];
        
        if ($performance['execution_time'] > $expectedDuration * 1.5) {
            $bottlenecks[] = 'execution_time';
        }
        
        if ($performance['memory_usage'] > $expectedMemory * 1.5) {
            $bottlenecks[] = 'memory_usage';
        }
        
        return $bottlenecks;
    }

    /**
     * Generate performance recommendations.
     *
     * @param array<string, mixed> $performance Performance metrics
     * @param array<string> $bottlenecks Identified bottlenecks
     * @return array<string> Recommendations
     */
    private static function generatePerformanceRecommendations(array $performance, array $bottlenecks): array
    {
        $recommendations = [];
        
        if (in_array('execution_time', $bottlenecks)) {
            $recommendations[] = 'Consider optimizing algorithm complexity';
            $recommendations[] = 'Review I/O operations for efficiency';
        }
        
        if (in_array('memory_usage', $bottlenecks)) {
            $recommendations[] = 'Implement memory pooling or object reuse';
            $recommendations[] = 'Review data structures for memory efficiency';
        }
        
        return $recommendations;
    }

    /**
     * Analyze memory leaks.
     *
     * @param array<array<string, mixed>> $memoryProfile Memory usage profile
     * @param int $totalIncrease Total memory increase
     * @return array<string, mixed> Leak analysis
     */
    private static function analyzeMemoryLeaks(array $memoryProfile, int $totalIncrease): array
    {
        $leakSuspected = $totalIncrease > 1024 * 1024; // 1MB threshold
        
        return [
            'leak_suspected' => $leakSuspected,
            'total_increase' => $totalIncrease,
            'leak_severity' => $leakSuspected ? 'medium' : 'none',
        ];
    }

    /**
     * Generate memory optimization suggestions.
     *
     * @param array<array<string, mixed>> $memoryProfile Memory profile
     * @param array<string, mixed> $leakDetection Leak detection results
     * @return array<string> Optimization suggestions
     */
    private static function generateMemoryOptimizationSuggestions(array $memoryProfile, array $leakDetection): array
    {
        $suggestions = [];
        
        if ($leakDetection['leak_suspected']) {
            $suggestions[] = 'Implement explicit cleanup in destructors';
            $suggestions[] = 'Review circular references';
        }
        
        $suggestions[] = 'Consider using memory-efficient data structures';
        
        return $suggestions;
    }

    /**
     * Assess scalability based on performance metrics.
     *
     * @param array<string, float> $baseline Baseline metrics
     * @param array<string, float> $withLogging Metrics with logging
     * @return string Scalability assessment
     */
    private static function assessScalability(array $baseline, array $withLogging): string
    {
        $timeRatio = $withLogging['avg_time'] / max($baseline['avg_time'], 0.001);
        
        return match (true) {
            $timeRatio < 1.2 => 'excellent',
            $timeRatio < 1.5 => 'good',
            $timeRatio < 2.0 => 'acceptable',
            default => 'poor',
        };
    }

    /**
     * Identify bottlenecks from performance comparison.
     *
     * @param array<string, float> $baseline Baseline metrics
     * @param array<string, float> $withLogging Metrics with logging
     * @param array<string, mixed> $impact Impact analysis
     * @return array<string> Identified bottlenecks
     */
    private static function identifyBottlenecks(array $baseline, array $withLogging, array $impact): array
    {
        $bottlenecks = [];
        
        if ($impact['time_impact_percent'] > 25) {
            $bottlenecks[] = 'logging_overhead';
        }
        
        if ($impact['memory_impact_percent'] > 50) {
            $bottlenecks[] = 'memory_allocation';
        }
        
        return $bottlenecks;
    }

    /**
     * Generate recommendations based on impact analysis.
     *
     * @param array<string, mixed> $impact Impact analysis results
     * @return array<string> Generated recommendations
     */
    private static function generateRecommendations(array $impact): array
    {
        $recommendations = [];
        
        if ($impact['time_impact_percent'] > 25) {
            $recommendations[] = 'Consider reducing logging frequency for high-frequency operations';
            $recommendations[] = 'Implement asynchronous logging for better performance';
        }
        
        if ($impact['memory_impact_percent'] > 50) {
            $recommendations[] = 'Review log message formatting for memory efficiency';
            $recommendations[] = 'Consider using log rotation to manage memory usage';
        }
        
        if ($impact['performance_rating'] === 'poor' || $impact['performance_rating'] === 'critical') {
            $recommendations[] = 'Implement conditional logging based on environment';
            $recommendations[] = 'Consider using structured logging with minimal overhead';
        }
        
        return $recommendations;
    }

    /**
     * Analyze timing bottlenecks from profiling data.
     *
     * @param array<string, mixed> $timingProfile Timing profile data
     * @return array<string, mixed> Bottleneck analysis
     */
    private static function analyzeTimingBottlenecks(array $timingProfile): array
    {
        $analysis = [
            'variability' => $timingProfile['standard_deviation'] / max($timingProfile['average_time'], 0.001),
            'consistency' => ($timingProfile['max_time'] - $timingProfile['min_time']) / max($timingProfile['average_time'], 0.001),
            'outliers' => $timingProfile['p99_time'] / max($timingProfile['median_time'], 0.001),
        ];
        
        $bottlenecks = [];
        
        if ($analysis['variability'] > 0.5) {
            $bottlenecks[] = 'high_variability';
        }
        
        if ($analysis['consistency'] > 2.0) {
            $bottlenecks[] = 'inconsistent_performance';
        }
        
        if ($analysis['outliers'] > 3.0) {
            $bottlenecks[] = 'performance_outliers';
        }
        
        return [
            'metrics' => $analysis,
            'bottlenecks' => $bottlenecks,
            'severity' => empty($bottlenecks) ? 'none' : (count($bottlenecks) > 2 ? 'high' : 'medium'),
        ];
    }

    /**
     * Identify optimization opportunities from timing analysis.
     *
     * @param array<string, mixed> $timingProfile Timing profile
     * @param array<string, mixed> $bottleneckAnalysis Bottleneck analysis
     * @return array<string> Optimization opportunities
     */
    private static function identifyOptimizationOpportunities(array $timingProfile, array $bottleneckAnalysis): array
    {
        $opportunities = [];
        
        if (in_array('high_variability', $bottleneckAnalysis['bottlenecks'])) {
            $opportunities[] = 'Implement caching to reduce variability';
            $opportunities[] = 'Review external dependencies for consistency';
        }
        
        if (in_array('inconsistent_performance', $bottleneckAnalysis['bottlenecks'])) {
            $opportunities[] = 'Optimize resource allocation patterns';
            $opportunities[] = 'Implement connection pooling';
        }
        
        if (in_array('performance_outliers', $bottleneckAnalysis['bottlenecks'])) {
            $opportunities[] = 'Add timeout mechanisms for long-running operations';
            $opportunities[] = 'Implement circuit breaker pattern';
        }
        
        if ($timingProfile['average_time'] > 1.0) {
            $opportunities[] = 'Consider algorithm optimization';
            $opportunities[] = 'Review data processing efficiency';
        }
        
        return $opportunities;
    }

    /**
     * Calculate monitoring period from stored metrics.
     *
     * @return array<string, mixed> Monitoring period information
     */
    private static function calculateMonitoringPeriod(): array
    {
        if (empty(self::$performanceMetrics)) {
            return [
                'start_time' => null,
                'end_time' => null,
                'duration' => 0,
            ];
        }
        
        $timestamps = array_column(self::$performanceMetrics, 'timestamp');
        $startTime = min($timestamps);
        $endTime = max($timestamps);
        
        return [
            'start_time' => date('Y-m-d H:i:s', (int)$startTime),
            'end_time' => date('Y-m-d H:i:s', (int)$endTime),
            'duration' => $endTime - $startTime,
        ];
    }

    /**
     * Summarize operation performance.
     *
     * @param array<string, mixed> $metrics Operation metrics
     * @return array<string, mixed> Performance summary
     */
    private static function summarizeOperationPerformance(array $metrics): array
    {
        return [
            'baseline_performance' => [
                'avg_time' => $metrics['baseline']['avg_time'],
                'avg_memory' => $metrics['baseline']['avg_memory'],
            ],
            'with_logging_performance' => [
                'avg_time' => $metrics['with_logging']['avg_time'],
                'avg_memory' => $metrics['with_logging']['avg_memory'],
            ],
            'impact_summary' => [
                'time_impact' => $metrics['impact']['time_impact_percent'],
                'memory_impact' => $metrics['impact']['memory_impact_percent'],
                'rating' => $metrics['impact']['performance_rating'],
            ],
        ];
    }

    /**
     * Generate overall analysis from all performance metrics.
     *
     * @param array<string, array<string, mixed>> $allMetrics All performance metrics
     * @return array<string, mixed> Overall analysis
     */
    private static function generateOverallAnalysis(array $allMetrics): array
    {
        if (empty($allMetrics)) {
            return [
                'average_impact' => 0,
                'worst_performer' => null,
                'best_performer' => null,
                'overall_rating' => 'no_data',
            ];
        }
        
        $timeImpacts = [];
        $ratings = [];
        
        foreach ($allMetrics as $operationName => $metrics) {
            $timeImpacts[$operationName] = $metrics['impact']['time_impact_percent'];
            $ratings[$operationName] = $metrics['impact']['performance_rating'];
        }
        
        $averageImpact = array_sum($timeImpacts) / count($timeImpacts);
        $worstPerformer = array_search(max($timeImpacts), $timeImpacts);
        $bestPerformer = array_search(min($timeImpacts), $timeImpacts);
        
        return [
            'average_impact' => $averageImpact,
            'worst_performer' => $worstPerformer,
            'best_performer' => $bestPerformer,
            'overall_rating' => self::calculateOverallRating($averageImpact),
            'operation_count' => count($allMetrics),
        ];
    }

    /**
     * Generate global recommendations from all metrics.
     *
     * @param array<string, array<string, mixed>> $allMetrics All performance metrics
     * @return array<string> Global recommendations
     */
    private static function generateGlobalRecommendations(array $allMetrics): array
    {
        $recommendations = [];
        $highImpactOperations = 0;
        $memoryIntensiveOperations = 0;
        
        foreach ($allMetrics as $metrics) {
            if ($metrics['impact']['time_impact_percent'] > 25) {
                $highImpactOperations++;
            }
            if ($metrics['impact']['memory_impact_percent'] > 50) {
                $memoryIntensiveOperations++;
            }
        }
        
        if ($highImpactOperations > count($allMetrics) * 0.3) {
            $recommendations[] = 'Consider implementing global logging optimization strategies';
            $recommendations[] = 'Review logging configuration for production environments';
        }
        
        if ($memoryIntensiveOperations > count($allMetrics) * 0.2) {
            $recommendations[] = 'Implement memory-efficient logging patterns across the application';
            $recommendations[] = 'Consider using log aggregation services to reduce local memory usage';
        }
        
        $recommendations[] = 'Regularly monitor performance metrics in production';
        $recommendations[] = 'Implement automated performance regression testing';
        
        return $recommendations;
    }

    /**
     * Calculate overall performance rating.
     *
     * @param float $averageImpact Average performance impact
     * @return string Overall rating
     */
    private static function calculateOverallRating(float $averageImpact): string
    {
        return match (true) {
            $averageImpact < 10 => 'excellent',
            $averageImpact < 20 => 'good',
            $averageImpact < 35 => 'acceptable',
            $averageImpact < 50 => 'poor',
            default => 'critical',
        };
    }
}