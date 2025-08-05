<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

/**
 * Performance assessment utilities for comprehensive testing validation.
 *
 * Provides utilities for:
 * - Performance benchmark establishment and validation
 * - Regression testing for performance degradation detection
 * - Load testing simulation and assessment
 * - Performance comparison and trend analysis
 * - Resource utilization assessment and optimization
 * - Performance reporting and visualization support
 *
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class PerformanceAssessmentUtilities
{
    /** @var array<string, array<string, mixed>> */
    private static array $benchmarks = [];
    
    /** @var array<string, array<string, mixed>> */
    private static array $assessmentHistory = [];
    
    /** @var array<string, mixed> */
    private static array $assessmentConfig = [
        'regression_threshold' => 15.0, // 15% performance degradation threshold
        'improvement_threshold' => 10.0, // 10% performance improvement threshold
        'load_test_duration' => 30.0, // 30 seconds load test duration
        'concurrent_operations' => 10, // Number of concurrent operations for load testing
        'benchmark_iterations' => 100, // Iterations for benchmark establishment
    ];

    /**
     * Establish performance benchmarks for operations.
     *
     * @param array<string, callable> $operations Operations to benchmark
     * @param array<string, mixed> $config Benchmark configuration
     * @return array{benchmarks: array<string, array<string, mixed>>, summary: array<string, mixed>}
     */
    public static function establishBenchmarks(array $operations, array $config = []): array
    {
        $iterations = $config['iterations'] ?? self::$assessmentConfig['benchmark_iterations'];
        $warmupIterations = $config['warmup_iterations'] ?? 10;
        $benchmarkResults = [];
        
        foreach ($operations as $operationName => $operation) {
            // Warmup phase
            for ($i = 0; $i < $warmupIterations; $i++) {
                $operation();
            }
            
            // Benchmark measurement
            $timings = [];
            $memoryUsages = [];
            $peakMemories = [];
            
            for ($i = 0; $i < $iterations; $i++) {
                $startTime = microtime(true);
                $startMemory = memory_get_usage(true);
                $startPeak = memory_get_peak_usage(true);
                
                $result = $operation();
                
                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);
                $endPeak = memory_get_peak_usage(true);
                
                $timings[] = $endTime - $startTime;
                $memoryUsages[] = $endMemory - $startMemory;
                $peakMemories[] = $endPeak - $startPeak;
            }
            
            // Calculate benchmark statistics
            sort($timings);
            sort($memoryUsages);
            sort($peakMemories);
            
            $benchmark = [
                'operation_name' => $operationName,
                'iterations' => $iterations,
                'timing_stats' => [
                    'mean' => array_sum($timings) / count($timings),
                    'median' => $timings[intval(count($timings) / 2)],
                    'min' => min($timings),
                    'max' => max($timings),
                    'p95' => $timings[intval(count($timings) * 0.95)],
                    'p99' => $timings[intval(count($timings) * 0.99)],
                    'std_dev' => self::calculateStandardDeviation($timings),
                ],
                'memory_stats' => [
                    'mean' => array_sum($memoryUsages) / count($memoryUsages),
                    'median' => $memoryUsages[intval(count($memoryUsages) / 2)],
                    'min' => min($memoryUsages),
                    'max' => max($memoryUsages),
                    'p95' => $memoryUsages[intval(count($memoryUsages) * 0.95)],
                ],
                'peak_memory_stats' => [
                    'mean' => array_sum($peakMemories) / count($peakMemories),
                    'median' => $peakMemories[intval(count($peakMemories) / 2)],
                    'max' => max($peakMemories),
                ],
                'established_at' => microtime(true),
                'environment' => self::captureEnvironmentInfo(),
            ];
            
            $benchmarkResults[$operationName] = $benchmark;
            self::$benchmarks[$operationName] = $benchmark;
        }
        
        $summary = self::generateBenchmarkSummary($benchmarkResults);
        
        return [
            'benchmarks' => $benchmarkResults,
            'summary' => $summary,
        ];
    }

    /**
     * Assess performance against established benchmarks.
     *
     * @param array<string, callable> $operations Operations to assess
     * @param array<string, mixed> $config Assessment configuration
     * @return array{assessment: array<string, array<string, mixed>>, regression_analysis: array<string, mixed>, recommendations: array<string>}
     */
    public static function assessPerformanceAgainstBenchmarks(array $operations, array $config = []): array
    {
        $iterations = $config['iterations'] ?? 50;
        $assessmentResults = [];
        $regressions = [];
        $improvements = [];
        
        foreach ($operations as $operationName => $operation) {
            if (!isset(self::$benchmarks[$operationName])) {
                throw new \InvalidArgumentException("No benchmark found for operation: {$operationName}");
            }
            
            $benchmark = self::$benchmarks[$operationName];
            
            // Measure current performance
            $currentPerformance = self::measureCurrentPerformance($operation, $iterations);
            
            // Compare against benchmark
            $comparison = self::compareAgainstBenchmark($currentPerformance, $benchmark);
            
            // Detect regressions and improvements
            if ($comparison['timing_regression_percent'] > self::$assessmentConfig['regression_threshold']) {
                $regressions[] = [
                    'operation' => $operationName,
                    'type' => 'timing',
                    'regression_percent' => $comparison['timing_regression_percent'],
                    'severity' => self::calculateRegressionSeverity($comparison['timing_regression_percent']),
                ];
            }
            
            if ($comparison['memory_regression_percent'] > self::$assessmentConfig['regression_threshold']) {
                $regressions[] = [
                    'operation' => $operationName,
                    'type' => 'memory',
                    'regression_percent' => $comparison['memory_regression_percent'],
                    'severity' => self::calculateRegressionSeverity($comparison['memory_regression_percent']),
                ];
            }
            
            if ($comparison['timing_improvement_percent'] > self::$assessmentConfig['improvement_threshold']) {
                $improvements[] = [
                    'operation' => $operationName,
                    'type' => 'timing',
                    'improvement_percent' => $comparison['timing_improvement_percent'],
                ];
            }
            
            $assessmentResults[$operationName] = [
                'current_performance' => $currentPerformance,
                'benchmark_comparison' => $comparison,
                'performance_verdict' => self::determinePerformanceVerdict($comparison),
                'assessment_timestamp' => microtime(true),
            ];
        }
        
        $regressionAnalysis = [
            'regressions_detected' => $regressions,
            'improvements_detected' => $improvements,
            'overall_status' => empty($regressions) ? 'passed' : 'failed',
            'regression_count' => count($regressions),
            'improvement_count' => count($improvements),
        ];
        
        $recommendations = self::generateAssessmentRecommendations($assessmentResults, $regressionAnalysis);
        
        // Store assessment history
        self::$assessmentHistory[] = [
            'assessment_results' => $assessmentResults,
            'regression_analysis' => $regressionAnalysis,
            'timestamp' => microtime(true),
        ];
        
        return [
            'assessment' => $assessmentResults,
            'regression_analysis' => $regressionAnalysis,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Perform load testing simulation with comprehensive analysis.
     *
     * @param callable $operation Operation to load test
     * @param array<string, mixed> $config Load test configuration
     * @return array{load_test_results: array<string, mixed>, performance_under_load: array<string, mixed>, scalability_analysis: array<string, mixed>}
     */
    public static function performLoadTestingSimulation(callable $operation, array $config = []): array
    {
        $duration = $config['duration'] ?? self::$assessmentConfig['load_test_duration'];
        $concurrentOperations = $config['concurrent_operations'] ?? self::$assessmentConfig['concurrent_operations'];
        $operationName = $config['operation_name'] ?? 'load_test_operation';
        
        $startTime = microtime(true);
        $endTime = $startTime + $duration;
        $results = [];
        $totalOperations = 0;
        $successfulOperations = 0;
        $failedOperations = 0;
        $responseTimes = [];
        $memoryUsages = [];
        
        // Simulate concurrent load
        while (microtime(true) < $endTime) {
            $batchStartTime = microtime(true);
            $batchResults = [];
            
            // Execute concurrent operations
            for ($i = 0; $i < $concurrentOperations; $i++) {
                $operationStartTime = microtime(true);
                $operationStartMemory = memory_get_usage(true);
                
                try {
                    $result = $operation();
                    $operationEndTime = microtime(true);
                    $operationEndMemory = memory_get_usage(true);
                    
                    $responseTime = $operationEndTime - $operationStartTime;
                    $memoryUsage = $operationEndMemory - $operationStartMemory;
                    
                    $responseTimes[] = $responseTime;
                    $memoryUsages[] = $memoryUsage;
                    $successfulOperations++;
                    
                    $batchResults[] = [
                        'success' => true,
                        'response_time' => $responseTime,
                        'memory_usage' => $memoryUsage,
                        'result' => $result,
                    ];
                } catch (\Throwable $e) {
                    $failedOperations++;
                    $batchResults[] = [
                        'success' => false,
                        'error' => $e->getMessage(),
                        'response_time' => microtime(true) - $operationStartTime,
                    ];
                }
                
                $totalOperations++;
            }
            
            $batchEndTime = microtime(true);
            $results[] = [
                'batch_start_time' => $batchStartTime,
                'batch_end_time' => $batchEndTime,
                'batch_duration' => $batchEndTime - $batchStartTime,
                'operations' => $batchResults,
                'memory_at_batch_end' => memory_get_usage(true),
                'peak_memory_at_batch_end' => memory_get_peak_usage(true),
            ];
            
            // Small delay to prevent overwhelming the system
            usleep(10000); // 10ms
        }
        
        $actualDuration = microtime(true) - $startTime;
        $throughput = $totalOperations / $actualDuration;
        $successRate = $totalOperations > 0 ? ($successfulOperations / $totalOperations) * 100 : 0;
        
        // Calculate performance statistics
        sort($responseTimes);
        sort($memoryUsages);
        
        $loadTestResults = [
            'duration' => $actualDuration,
            'total_operations' => $totalOperations,
            'successful_operations' => $successfulOperations,
            'failed_operations' => $failedOperations,
            'success_rate' => $successRate,
            'throughput' => $throughput,
            'concurrent_operations' => $concurrentOperations,
        ];
        
        $performanceUnderLoad = [
            'response_time_stats' => [
                'mean' => !empty($responseTimes) ? array_sum($responseTimes) / count($responseTimes) : 0,
                'median' => !empty($responseTimes) ? $responseTimes[intval(count($responseTimes) / 2)] : 0,
                'min' => !empty($responseTimes) ? min($responseTimes) : 0,
                'max' => !empty($responseTimes) ? max($responseTimes) : 0,
                'p95' => !empty($responseTimes) ? $responseTimes[intval(count($responseTimes) * 0.95)] : 0,
                'p99' => !empty($responseTimes) ? $responseTimes[intval(count($responseTimes) * 0.99)] : 0,
            ],
            'memory_usage_stats' => [
                'mean' => !empty($memoryUsages) ? array_sum($memoryUsages) / count($memoryUsages) : 0,
                'median' => !empty($memoryUsages) ? $memoryUsages[intval(count($memoryUsages) / 2)] : 0,
                'max' => !empty($memoryUsages) ? max($memoryUsages) : 0,
            ],
            'error_rate' => $totalOperations > 0 ? ($failedOperations / $totalOperations) * 100 : 0,
        ];
        
        $scalabilityAnalysis = self::analyzeScalability($loadTestResults, $performanceUnderLoad);
        
        return [
            'load_test_results' => $loadTestResults,
            'performance_under_load' => $performanceUnderLoad,
            'scalability_analysis' => $scalabilityAnalysis,
        ];
    }

    /**
     * Compare performance trends over time.
     *
     * @param string $operationName Operation name to analyze
     * @param array<string, mixed> $config Trend analysis configuration
     * @return array{trend_analysis: array<string, mixed>, performance_trajectory: string, recommendations: array<string>}
     */
    public static function analyzePerformanceTrends(string $operationName, array $config = []): array
    {
        $lookbackPeriod = $config['lookback_period'] ?? 30; // days
        $minDataPoints = $config['min_data_points'] ?? 5;
        
        // Filter assessment history for the specified operation
        $relevantAssessments = array_filter(
            self::$assessmentHistory,
            fn($assessment) => isset($assessment['assessment_results'][$operationName])
        );
        
        if (count($relevantAssessments) < $minDataPoints) {
            return [
                'trend_analysis' => [
                    'status' => 'insufficient_data',
                    'data_points' => count($relevantAssessments),
                    'required_data_points' => $minDataPoints,
                ],
                'performance_trajectory' => 'unknown',
                'recommendations' => ['Collect more performance data over time for trend analysis'],
            ];
        }
        
        // Extract performance metrics over time
        $timeSeriesData = [];
        foreach ($relevantAssessments as $assessment) {
            $operationData = $assessment['assessment_results'][$operationName];
            $timeSeriesData[] = [
                'timestamp' => $assessment['timestamp'],
                'timing_mean' => $operationData['current_performance']['timing_stats']['mean'],
                'memory_mean' => $operationData['current_performance']['memory_stats']['mean'],
                'performance_score' => $operationData['benchmark_comparison']['overall_performance_score'],
            ];
        }
        
        // Sort by timestamp
        usort($timeSeriesData, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);
        
        // Calculate trends
        $timingTrend = self::calculateTrend(array_column($timeSeriesData, 'timing_mean'));
        $memoryTrend = self::calculateTrend(array_column($timeSeriesData, 'memory_mean'));
        $scoreTrend = self::calculateTrend(array_column($timeSeriesData, 'performance_score'));
        
        $trendAnalysis = [
            'data_points' => count($timeSeriesData),
            'time_period' => [
                'start' => date('Y-m-d H:i:s', (int)$timeSeriesData[0]['timestamp']),
                'end' => date('Y-m-d H:i:s', (int)end($timeSeriesData)['timestamp']),
            ],
            'timing_trend' => $timingTrend,
            'memory_trend' => $memoryTrend,
            'performance_score_trend' => $scoreTrend,
            'overall_trend_direction' => self::determineOverallTrendDirection($timingTrend, $memoryTrend, $scoreTrend),
        ];
        
        $performanceTrajectory = self::determinePerformanceTrajectory($trendAnalysis);
        $recommendations = self::generateTrendRecommendations($trendAnalysis, $performanceTrajectory);
        
        return [
            'trend_analysis' => $trendAnalysis,
            'performance_trajectory' => $performanceTrajectory,
            'recommendations' => $recommendations,
        ];
    }

    /**
     * Generate comprehensive performance assessment report.
     *
     * @param array<string, mixed> $config Report configuration
     * @return array<string, mixed> Comprehensive performance report
     */
    public static function generatePerformanceAssessmentReport(array $config = []): array
    {
        $includeHistory = $config['include_history'] ?? true;
        $includeTrends = $config['include_trends'] ?? true;
        $includeRecommendations = $config['include_recommendations'] ?? true;
        
        $report = [
            'report_metadata' => [
                'generated_at' => date('Y-m-d H:i:s'),
                'report_version' => '1.0.0',
                'assessment_period' => self::calculateAssessmentPeriod(),
            ],
            'benchmark_summary' => self::generateBenchmarkOverview(),
            'current_performance_status' => self::generateCurrentPerformanceStatus(),
        ];
        
        if ($includeHistory && !empty(self::$assessmentHistory)) {
            $report['assessment_history'] = self::generateAssessmentHistorySummary();
        }
        
        if ($includeTrends) {
            $report['trend_analysis'] = self::generateTrendAnalysisSummary();
        }
        
        if ($includeRecommendations) {
            $report['recommendations'] = self::generateGlobalPerformanceRecommendations();
        }
        
        $report['performance_score'] = self::calculateOverallPerformanceScore();
        $report['action_items'] = self::generateActionItems($report);
        
        return $report;
    }

    /**
     * Clear all assessment data and benchmarks.
     *
     * @return void
     */
    public static function clearAssessmentData(): void
    {
        self::$benchmarks = [];
        self::$assessmentHistory = [];
    }

    /**
     * Set assessment configuration.
     *
     * @param array<string, mixed> $config Configuration settings
     * @return void
     */
    public static function setAssessmentConfig(array $config): void
    {
        self::$assessmentConfig = array_merge(self::$assessmentConfig, $config);
    }

    /**
     * Get current assessment configuration.
     *
     * @return array<string, mixed> Current configuration
     */
    public static function getAssessmentConfig(): array
    {
        return self::$assessmentConfig;
    }

    /**
     * Calculate standard deviation for a dataset.
     *
     * @param array<float> $data Dataset
     * @return float Standard deviation
     */
    private static function calculateStandardDeviation(array $data): float
    {
        if (empty($data)) {
            return 0.0;
        }
        
        $mean = array_sum($data) / count($data);
        $squaredDifferences = array_map(fn($x) => pow($x - $mean, 2), $data);
        $variance = array_sum($squaredDifferences) / count($data);
        
        return sqrt($variance);
    }

    /**
     * Capture current environment information.
     *
     * @return array<string, mixed> Environment information
     */
    private static function captureEnvironmentInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'timestamp' => microtime(true),
            'date' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate benchmark summary.
     *
     * @param array<string, array<string, mixed>> $benchmarks Benchmark results
     * @return array<string, mixed> Benchmark summary
     */
    private static function generateBenchmarkSummary(array $benchmarks): array
    {
        $totalOperations = count($benchmarks);
        $avgTimingMean = 0;
        $avgMemoryMean = 0;
        
        if ($totalOperations > 0) {
            $timingMeans = array_column(array_column($benchmarks, 'timing_stats'), 'mean');
            $memoryMeans = array_column(array_column($benchmarks, 'memory_stats'), 'mean');
            
            $avgTimingMean = array_sum($timingMeans) / count($timingMeans);
            $avgMemoryMean = array_sum($memoryMeans) / count($memoryMeans);
        }
        
        return [
            'total_operations' => $totalOperations,
            'average_timing_mean' => $avgTimingMean,
            'average_memory_mean' => $avgMemoryMean,
            'benchmark_quality' => self::assessBenchmarkQuality($benchmarks),
        ];
    }

    /**
     * Measure current performance of an operation.
     *
     * @param callable $operation Operation to measure
     * @param int $iterations Number of iterations
     * @return array<string, mixed> Current performance metrics
     */
    private static function measureCurrentPerformance(callable $operation, int $iterations): array
    {
        $timings = [];
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
            
            $timings[] = $endTime - $startTime;
            $memoryUsages[] = $endMemory - $startMemory;
            $peakMemories[] = $endPeak - $startPeak;
        }
        
        sort($timings);
        sort($memoryUsages);
        sort($peakMemories);
        
        return [
            'timing_stats' => [
                'mean' => array_sum($timings) / count($timings),
                'median' => $timings[intval(count($timings) / 2)],
                'min' => min($timings),
                'max' => max($timings),
                'p95' => $timings[intval(count($timings) * 0.95)],
            ],
            'memory_stats' => [
                'mean' => array_sum($memoryUsages) / count($memoryUsages),
                'median' => $memoryUsages[intval(count($memoryUsages) / 2)],
                'max' => max($memoryUsages),
            ],
            'peak_memory_stats' => [
                'mean' => array_sum($peakMemories) / count($peakMemories),
                'max' => max($peakMemories),
            ],
            'iterations' => $iterations,
            'measured_at' => microtime(true),
        ];
    }

    /**
     * Compare current performance against benchmark.
     *
     * @param array<string, mixed> $current Current performance
     * @param array<string, mixed> $benchmark Benchmark performance
     * @return array<string, mixed> Comparison results
     */
    private static function compareAgainstBenchmark(array $current, array $benchmark): array
    {
        $timingRegression = (($current['timing_stats']['mean'] - $benchmark['timing_stats']['mean']) / max($benchmark['timing_stats']['mean'], 0.001)) * 100;
        $memoryRegression = (($current['memory_stats']['mean'] - $benchmark['memory_stats']['mean']) / max(abs($benchmark['memory_stats']['mean']), 1)) * 100;
        
        $timingImprovement = $timingRegression < 0 ? abs($timingRegression) : 0;
        $memoryImprovement = $memoryRegression < 0 ? abs($memoryRegression) : 0;
        
        $overallScore = 100 - (max(0, $timingRegression) + max(0, $memoryRegression)) / 2;
        
        return [
            'timing_regression_percent' => max(0, $timingRegression),
            'memory_regression_percent' => max(0, $memoryRegression),
            'timing_improvement_percent' => $timingImprovement,
            'memory_improvement_percent' => $memoryImprovement,
            'overall_performance_score' => max(0, $overallScore),
            'comparison_timestamp' => microtime(true),
        ];
    }

    /**
     * Calculate regression severity.
     *
     * @param float $regressionPercent Regression percentage
     * @return string Severity level
     */
    private static function calculateRegressionSeverity(float $regressionPercent): string
    {
        return match (true) {
            $regressionPercent < 15 => 'low',
            $regressionPercent < 30 => 'medium',
            $regressionPercent < 50 => 'high',
            default => 'critical',
        };
    }

    /**
     * Determine performance verdict.
     *
     * @param array<string, mixed> $comparison Comparison results
     * @return string Performance verdict
     */
    private static function determinePerformanceVerdict(array $comparison): string
    {
        $score = $comparison['overall_performance_score'];
        
        return match (true) {
            $score >= 95 => 'excellent',
            $score >= 85 => 'good',
            $score >= 70 => 'acceptable',
            $score >= 50 => 'poor',
            default => 'critical',
        };
    }

    /**
     * Generate assessment recommendations.
     *
     * @param array<string, array<string, mixed>> $assessmentResults Assessment results
     * @param array<string, mixed> $regressionAnalysis Regression analysis
     * @return array<string> Recommendations
     */
    private static function generateAssessmentRecommendations(array $assessmentResults, array $regressionAnalysis): array
    {
        $recommendations = [];
        
        if ($regressionAnalysis['regression_count'] > 0) {
            $recommendations[] = 'Investigate performance regressions and implement optimizations';
            $recommendations[] = 'Review recent code changes that may have impacted performance';
        }
        
        if ($regressionAnalysis['improvement_count'] > 0) {
            $recommendations[] = 'Document performance improvements for future reference';
            $recommendations[] = 'Consider applying similar optimizations to other operations';
        }
        
        foreach ($assessmentResults as $operationName => $result) {
            if ($result['performance_verdict'] === 'poor' || $result['performance_verdict'] === 'critical') {
                $recommendations[] = "Prioritize optimization for operation: {$operationName}";
            }
        }
        
        return array_unique($recommendations);
    }

    /**
     * Analyze scalability from load test results.
     *
     * @param array<string, mixed> $loadTestResults Load test results
     * @param array<string, mixed> $performanceUnderLoad Performance under load
     * @return array<string, mixed> Scalability analysis
     */
    private static function analyzeScalability(array $loadTestResults, array $performanceUnderLoad): array
    {
        $throughputRating = match (true) {
            $loadTestResults['throughput'] > 100 => 'excellent',
            $loadTestResults['throughput'] > 50 => 'good',
            $loadTestResults['throughput'] > 20 => 'acceptable',
            $loadTestResults['throughput'] > 5 => 'poor',
            default => 'critical',
        };
        
        $reliabilityRating = match (true) {
            $loadTestResults['success_rate'] > 99 => 'excellent',
            $loadTestResults['success_rate'] > 95 => 'good',
            $loadTestResults['success_rate'] > 90 => 'acceptable',
            $loadTestResults['success_rate'] > 80 => 'poor',
            default => 'critical',
        };
        
        return [
            'throughput_rating' => $throughputRating,
            'reliability_rating' => $reliabilityRating,
            'scalability_score' => self::calculateScalabilityScore($loadTestResults, $performanceUnderLoad),
            'bottlenecks' => self::identifyLoadTestBottlenecks($performanceUnderLoad),
            'recommendations' => self::generateScalabilityRecommendations($throughputRating, $reliabilityRating),
        ];
    }

    /**
     * Calculate scalability score.
     *
     * @param array<string, mixed> $loadTestResults Load test results
     * @param array<string, mixed> $performanceUnderLoad Performance under load
     * @return float Scalability score (0-100)
     */
    private static function calculateScalabilityScore(array $loadTestResults, array $performanceUnderLoad): float
    {
        $throughputScore = min(100, $loadTestResults['throughput'] / 100 * 100);
        $reliabilityScore = $loadTestResults['success_rate'];
        $responseTimeScore = max(0, 100 - ($performanceUnderLoad['response_time_stats']['p95'] * 100));
        
        return ($throughputScore + $reliabilityScore + $responseTimeScore) / 3;
    }

    /**
     * Identify load test bottlenecks.
     *
     * @param array<string, mixed> $performanceUnderLoad Performance under load
     * @return array<string> Identified bottlenecks
     */
    private static function identifyLoadTestBottlenecks(array $performanceUnderLoad): array
    {
        $bottlenecks = [];
        
        if ($performanceUnderLoad['response_time_stats']['p95'] > 1.0) {
            $bottlenecks[] = 'high_response_time';
        }
        
        if ($performanceUnderLoad['error_rate'] > 5.0) {
            $bottlenecks[] = 'high_error_rate';
        }
        
        if ($performanceUnderLoad['memory_usage_stats']['max'] > 100 * 1024 * 1024) {
            $bottlenecks[] = 'high_memory_usage';
        }
        
        return $bottlenecks;
    }

    /**
     * Generate scalability recommendations.
     *
     * @param string $throughputRating Throughput rating
     * @param string $reliabilityRating Reliability rating
     * @return array<string> Scalability recommendations
     */
    private static function generateScalabilityRecommendations(string $throughputRating, string $reliabilityRating): array
    {
        $recommendations = [];
        
        if ($throughputRating === 'poor' || $throughputRating === 'critical') {
            $recommendations[] = 'Implement caching strategies to improve throughput';
            $recommendations[] = 'Consider horizontal scaling options';
            $recommendations[] = 'Optimize database queries and connections';
        }
        
        if ($reliabilityRating === 'poor' || $reliabilityRating === 'critical') {
            $recommendations[] = 'Implement circuit breaker patterns';
            $recommendations[] = 'Add retry mechanisms with exponential backoff';
            $recommendations[] = 'Review error handling and recovery procedures';
        }
        
        return $recommendations;
    }

    /**
     * Calculate trend for a dataset.
     *
     * @param array<float> $data Dataset
     * @return array<string, mixed> Trend analysis
     */
    private static function calculateTrend(array $data): array
    {
        if (count($data) < 2) {
            return [
                'direction' => 'unknown',
                'slope' => 0,
                'confidence' => 'low',
            ];
        }
        
        $n = count($data);
        $sumX = array_sum(range(0, $n - 1));
        $sumY = array_sum($data);
        $sumXY = 0;
        $sumXX = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $i * $data[$i];
            $sumXX += $i * $i;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        
        $direction = match (true) {
            $slope > 0.05 => 'improving',
            $slope < -0.05 => 'degrading',
            default => 'stable',
        };
        
        $confidence = abs($slope) > 0.1 ? 'high' : (abs($slope) > 0.02 ? 'medium' : 'low');
        
        return [
            'direction' => $direction,
            'slope' => $slope,
            'confidence' => $confidence,
        ];
    }

    /**
     * Determine overall trend direction.
     *
     * @param array<string, mixed> $timingTrend Timing trend
     * @param array<string, mixed> $memoryTrend Memory trend
     * @param array<string, mixed> $scoreTrend Score trend
     * @return string Overall trend direction
     */
    private static function determineOverallTrendDirection(array $timingTrend, array $memoryTrend, array $scoreTrend): string
    {
        $trends = [$timingTrend['direction'], $memoryTrend['direction'], $scoreTrend['direction']];
        $degradingCount = count(array_filter($trends, fn($t) => $t === 'degrading'));
        $improvingCount = count(array_filter($trends, fn($t) => $t === 'improving'));
        
        if ($degradingCount > $improvingCount) {
            return 'degrading';
        } elseif ($improvingCount > $degradingCount) {
            return 'improving';
        } else {
            return 'stable';
        }
    }

    /**
     * Determine performance trajectory.
     *
     * @param array<string, mixed> $trendAnalysis Trend analysis
     * @return string Performance trajectory
     */
    private static function determinePerformanceTrajectory(array $trendAnalysis): string
    {
        $direction = $trendAnalysis['overall_trend_direction'];
        $timingConfidence = $trendAnalysis['timing_trend']['confidence'];
        $scoreConfidence = $trendAnalysis['performance_score_trend']['confidence'];
        
        if ($direction === 'degrading' && ($timingConfidence === 'high' || $scoreConfidence === 'high')) {
            return 'declining';
        } elseif ($direction === 'improving' && ($timingConfidence === 'high' || $scoreConfidence === 'high')) {
            return 'improving';
        } elseif ($direction === 'stable') {
            return 'stable';
        } else {
            return 'uncertain';
        }
    }

    /**
     * Generate trend recommendations.
     *
     * @param array<string, mixed> $trendAnalysis Trend analysis
     * @param string $performanceTrajectory Performance trajectory
     * @return array<string> Trend recommendations
     */
    private static function generateTrendRecommendations(array $trendAnalysis, string $performanceTrajectory): array
    {
        $recommendations = [];
        
        switch ($performanceTrajectory) {
            case 'declining':
                $recommendations[] = 'Immediate investigation required for performance degradation';
                $recommendations[] = 'Review recent changes and their performance impact';
                $recommendations[] = 'Implement performance monitoring alerts';
                break;
                
            case 'improving':
                $recommendations[] = 'Document successful optimizations for future reference';
                $recommendations[] = 'Consider applying similar improvements to other areas';
                break;
                
            case 'stable':
                $recommendations[] = 'Maintain current performance levels';
                $recommendations[] = 'Continue regular performance monitoring';
                break;
                
            case 'uncertain':
                $recommendations[] = 'Collect more performance data for better trend analysis';
                $recommendations[] = 'Increase monitoring frequency';
                break;
        }
        
        return $recommendations;
    }

    /**
     * Assess benchmark quality.
     *
     * @param array<string, array<string, mixed>> $benchmarks Benchmark data
     * @return string Benchmark quality assessment
     */
    private static function assessBenchmarkQuality(array $benchmarks): string
    {
        if (empty($benchmarks)) {
            return 'no_benchmarks';
        }
        
        $totalVariability = 0;
        $benchmarkCount = 0;
        
        foreach ($benchmarks as $benchmark) {
            $timingStats = $benchmark['timing_stats'];
            $variability = $timingStats['std_dev'] / max($timingStats['mean'], 0.001);
            $totalVariability += $variability;
            $benchmarkCount++;
        }
        
        $avgVariability = $totalVariability / $benchmarkCount;
        
        return match (true) {
            $avgVariability < 0.1 => 'excellent',
            $avgVariability < 0.2 => 'good',
            $avgVariability < 0.4 => 'acceptable',
            default => 'poor',
        };
    }

    /**
     * Calculate assessment period.
     *
     * @return array<string, mixed> Assessment period information
     */
    private static function calculateAssessmentPeriod(): array
    {
        if (empty(self::$assessmentHistory)) {
            return [
                'start_date' => null,
                'end_date' => null,
                'duration_days' => 0,
            ];
        }
        
        $timestamps = array_column(self::$assessmentHistory, 'timestamp');
        $startTime = min($timestamps);
        $endTime = max($timestamps);
        $durationDays = ($endTime - $startTime) / (24 * 60 * 60);
        
        return [
            'start_date' => date('Y-m-d', (int)$startTime),
            'end_date' => date('Y-m-d', (int)$endTime),
            'duration_days' => round($durationDays, 1),
        ];
    }

    /**
     * Generate benchmark overview.
     *
     * @return array<string, mixed> Benchmark overview
     */
    private static function generateBenchmarkOverview(): array
    {
        return [
            'total_benchmarks' => count(self::$benchmarks),
            'benchmark_operations' => array_keys(self::$benchmarks),
            'benchmark_quality' => self::assessBenchmarkQuality(self::$benchmarks),
            'last_updated' => !empty(self::$benchmarks) ? max(array_column(self::$benchmarks, 'established_at')) : null,
        ];
    }

    /**
     * Generate current performance status.
     *
     * @return array<string, mixed> Current performance status
     */
    private static function generateCurrentPerformanceStatus(): array
    {
        if (empty(self::$assessmentHistory)) {
            return [
                'status' => 'no_assessments',
                'last_assessment' => null,
                'overall_health' => 'unknown',
            ];
        }
        
        $latestAssessment = end(self::$assessmentHistory);
        $regressionCount = $latestAssessment['regression_analysis']['regression_count'];
        $improvementCount = $latestAssessment['regression_analysis']['improvement_count'];
        
        $overallHealth = match (true) {
            $regressionCount === 0 && $improvementCount > 0 => 'excellent',
            $regressionCount === 0 => 'good',
            $regressionCount <= 2 => 'acceptable',
            default => 'poor',
        };
        
        return [
            'status' => 'assessed',
            'last_assessment' => date('Y-m-d H:i:s', (int)$latestAssessment['timestamp']),
            'overall_health' => $overallHealth,
            'regression_count' => $regressionCount,
            'improvement_count' => $improvementCount,
        ];
    }

    /**
     * Generate assessment history summary.
     *
     * @return array<string, mixed> Assessment history summary
     */
    private static function generateAssessmentHistorySummary(): array
    {
        $totalAssessments = count(self::$assessmentHistory);
        $totalRegressions = array_sum(array_column(array_column(self::$assessmentHistory, 'regression_analysis'), 'regression_count'));
        $totalImprovements = array_sum(array_column(array_column(self::$assessmentHistory, 'regression_analysis'), 'improvement_count'));
        
        return [
            'total_assessments' => $totalAssessments,
            'total_regressions' => $totalRegressions,
            'total_improvements' => $totalImprovements,
            'assessment_frequency' => self::calculateAssessmentFrequency(),
        ];
    }

    /**
     * Calculate assessment frequency.
     *
     * @return string Assessment frequency description
     */
    private static function calculateAssessmentFrequency(): string
    {
        if (count(self::$assessmentHistory) < 2) {
            return 'insufficient_data';
        }
        
        $timestamps = array_column(self::$assessmentHistory, 'timestamp');
        sort($timestamps);
        
        $intervals = [];
        for ($i = 1; $i < count($timestamps); $i++) {
            $intervals[] = $timestamps[$i] - $timestamps[$i - 1];
        }
        
        $avgInterval = array_sum($intervals) / count($intervals);
        $avgIntervalHours = $avgInterval / 3600;
        
        return match (true) {
            $avgIntervalHours < 1 => 'very_frequent',
            $avgIntervalHours < 24 => 'frequent',
            $avgIntervalHours < 168 => 'regular',
            default => 'infrequent',
        };
    }

    /**
     * Generate trend analysis summary.
     *
     * @return array<string, mixed> Trend analysis summary
     */
    private static function generateTrendAnalysisSummary(): array
    {
        $trendSummaries = [];
        
        foreach (array_keys(self::$benchmarks) as $operationName) {
            $trendAnalysis = self::analyzePerformanceTrends($operationName);
            $trendSummaries[$operationName] = [
                'trajectory' => $trendAnalysis['performance_trajectory'],
                'trend_direction' => $trendAnalysis['trend_analysis']['overall_trend_direction'] ?? 'unknown',
            ];
        }
        
        return [
            'operation_trends' => $trendSummaries,
            'overall_trend_health' => self::assessOverallTrendHealth($trendSummaries),
        ];
    }

    /**
     * Assess overall trend health.
     *
     * @param array<string, array<string, string>> $trendSummaries Trend summaries
     * @return string Overall trend health
     */
    private static function assessOverallTrendHealth(array $trendSummaries): string
    {
        if (empty($trendSummaries)) {
            return 'unknown';
        }
        
        $decliningCount = count(array_filter($trendSummaries, fn($t) => $t['trajectory'] === 'declining'));
        $improvingCount = count(array_filter($trendSummaries, fn($t) => $t['trajectory'] === 'improving'));
        $totalOperations = count($trendSummaries);
        
        $decliningRatio = $decliningCount / $totalOperations;
        $improvingRatio = $improvingCount / $totalOperations;
        
        return match (true) {
            $decliningRatio > 0.5 => 'poor',
            $decliningRatio > 0.3 => 'concerning',
            $improvingRatio > 0.5 => 'excellent',
            $improvingRatio > 0.3 => 'good',
            default => 'stable',
        };
    }

    /**
     * Generate global performance recommendations.
     *
     * @return array<string> Global recommendations
     */
    private static function generateGlobalPerformanceRecommendations(): array
    {
        $recommendations = [];
        
        if (empty(self::$benchmarks)) {
            $recommendations[] = 'Establish performance benchmarks for critical operations';
        }
        
        if (empty(self::$assessmentHistory)) {
            $recommendations[] = 'Begin regular performance assessments';
        } else {
            $latestAssessment = end(self::$assessmentHistory);
            if ($latestAssessment['regression_analysis']['regression_count'] > 0) {
                $recommendations[] = 'Address identified performance regressions';
            }
        }
        
        $recommendations[] = 'Implement continuous performance monitoring';
        $recommendations[] = 'Set up automated performance regression alerts';
        $recommendations[] = 'Document performance optimization strategies';
        
        return $recommendations;
    }

    /**
     * Calculate overall performance score.
     *
     * @return array<string, mixed> Overall performance score
     */
    private static function calculateOverallPerformanceScore(): array
    {
        if (empty(self::$assessmentHistory)) {
            return [
                'score' => 0,
                'grade' => 'N/A',
                'status' => 'no_data',
            ];
        }
        
        $latestAssessment = end(self::$assessmentHistory);
        $regressionCount = $latestAssessment['regression_analysis']['regression_count'];
        $improvementCount = $latestAssessment['regression_analysis']['improvement_count'];
        $totalOperations = count($latestAssessment['assessment_results']);
        
        $baseScore = 100;
        $regressionPenalty = $regressionCount * 15; // 15 points per regression
        $improvementBonus = $improvementCount * 5; // 5 points per improvement
        
        $score = max(0, min(100, $baseScore - $regressionPenalty + $improvementBonus));
        
        $grade = match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
        
        return [
            'score' => $score,
            'grade' => $grade,
            'status' => 'calculated',
            'regression_count' => $regressionCount,
            'improvement_count' => $improvementCount,
            'total_operations' => $totalOperations,
        ];
    }

    /**
     * Generate action items from performance report.
     *
     * @param array<string, mixed> $report Performance report
     * @return array<string> Action items
     */
    private static function generateActionItems(array $report): array
    {
        $actionItems = [];
        
        $performanceScore = $report['performance_score'];
        if ($performanceScore['score'] < 70) {
            $actionItems[] = 'URGENT: Address performance issues - score below acceptable threshold';
        }
        
        if ($performanceScore['regression_count'] > 0) {
            $actionItems[] = "Investigate and fix {$performanceScore['regression_count']} performance regression(s)";
        }
        
        $currentStatus = $report['current_performance_status'];
        if ($currentStatus['overall_health'] === 'poor') {
            $actionItems[] = 'Implement immediate performance optimization measures';
        }
        
        if (isset($report['trend_analysis']['overall_trend_health']) && $report['trend_analysis']['overall_trend_health'] === 'poor') {
            $actionItems[] = 'Address declining performance trends across operations';
        }
        
        if (empty($actionItems)) {
            $actionItems[] = 'Continue monitoring and maintain current performance levels';
        }
        
        return $actionItems;
    }
}