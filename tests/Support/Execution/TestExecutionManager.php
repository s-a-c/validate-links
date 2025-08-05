<?php

declare(strict_types=1);

namespace Tests\Support\Execution;

use Tests\Support\Tags\TagFilter;
use Tests\Support\Tags\TagManager;

/**
 * Test Execution Manager for PEST
 * 
 * Provides utilities for conditional test loading, test suite filtering,
 * performance threshold management, and test result aggregation.
 */
class TestExecutionManager
{
    /**
     * Execution context
     */
    private static array $context = [];

    /**
     * Performance thresholds
     */
    private static array $performanceThresholds = [
        'fast' => 0.1,      // 100ms
        'normal' => 1.0,    // 1 second
        'slow' => 5.0,      // 5 seconds
        'very_slow' => 30.0, // 30 seconds
    ];

    /**
     * Test results aggregation
     */
    private static array $testResults = [];

    /**
     * Execution statistics
     */
    private static array $executionStats = [
        'total_tests' => 0,
        'passed_tests' => 0,
        'failed_tests' => 0,
        'skipped_tests' => 0,
        'execution_time' => 0,
        'memory_usage' => 0,
    ];

    /**
     * Initialize execution manager
     */
    public static function initialize(array $context = []): void
    {
        self::$context = array_merge([
            'environment' => $_ENV['APP_ENV'] ?? 'testing',
            'ci' => !empty($_ENV['CI']),
            'parallel' => !empty($_ENV['PEST_PARALLEL']),
            'worker_id' => $_ENV['PEST_WORKER_ID'] ?? 0,
            'debug' => !empty($_ENV['PEST_DEBUG']),
        ], $context);

        self::resetStatistics();
    }

    /**
     * Check if tests should be loaded based on conditions
     */
    public static function shouldLoadTests(array $conditions): bool
    {
        foreach ($conditions as $condition => $expected) {
            switch ($condition) {
                case 'environment':
                    if (self::$context['environment'] !== $expected) {
                        return false;
                    }
                    break;
                case 'ci':
                    if (self::$context['ci'] !== $expected) {
                        return false;
                    }
                    break;
                case 'parallel':
                    if (self::$context['parallel'] !== $expected) {
                        return false;
                    }
                    break;
                case 'min_php_version':
                    if (version_compare(PHP_VERSION, $expected, '<')) {
                        return false;
                    }
                    break;
                case 'max_php_version':
                    if (version_compare(PHP_VERSION, $expected, '>')) {
                        return false;
                    }
                    break;
                case 'extensions':
                    foreach ((array) $expected as $extension) {
                        if (!extension_loaded($extension)) {
                            return false;
                        }
                    }
                    break;
                case 'env_vars':
                    foreach ((array) $expected as $envVar) {
                        if (empty($_ENV[$envVar])) {
                            return false;
                        }
                    }
                    break;
            }
        }

        return true;
    }

    /**
     * Filter test suite based on criteria
     */
    public static function filterTestSuite(array $tests, array $criteria): array
    {
        $filtered = [];

        foreach ($tests as $test) {
            if (self::shouldIncludeTest($test, $criteria)) {
                $filtered[] = $test;
            }
        }

        return $filtered;
    }

    /**
     * Check if individual test should be included
     */
    private static function shouldIncludeTest(array $test, array $criteria): bool
    {
        // Check tags
        if (!empty($criteria['tags'])) {
            $filter = new TagFilter();
            $filter->include($criteria['tags']);
            
            if (!$filter->shouldExecute($test['tags'] ?? [])) {
                return false;
            }
        }

        // Check performance category
        if (!empty($criteria['performance_category'])) {
            $expectedTime = self::$performanceThresholds[$criteria['performance_category']] ?? null;
            if ($expectedTime && ($test['expected_time'] ?? 0) > $expectedTime) {
                return false;
            }
        }

        // Check file patterns
        if (!empty($criteria['file_patterns'])) {
            $testFile = $test['file'] ?? '';
            $matches = false;
            
            foreach ($criteria['file_patterns'] as $pattern) {
                if (fnmatch($pattern, $testFile)) {
                    $matches = true;
                    break;
                }
            }
            
            if (!$matches) {
                return false;
            }
        }

        // Check custom conditions
        if (!empty($criteria['custom_conditions'])) {
            foreach ($criteria['custom_conditions'] as $condition) {
                if (!$condition($test)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Group tests by criteria
     */
    public static function groupTests(array $tests, string $groupBy): array
    {
        $groups = [];

        foreach ($tests as $test) {
            $groupKey = self::getGroupKey($test, $groupBy);
            
            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }
            
            $groups[$groupKey][] = $test;
        }

        return $groups;
    }

    /**
     * Get group key for test
     */
    private static function getGroupKey(array $test, string $groupBy): string
    {
        switch ($groupBy) {
            case 'tags':
                $tags = $test['tags'] ?? [];
                return empty($tags) ? 'untagged' : implode(',', $tags);
            case 'file':
                return dirname($test['file'] ?? 'unknown');
            case 'performance':
                $time = $test['expected_time'] ?? 0;
                foreach (self::$performanceThresholds as $category => $threshold) {
                    if ($time <= $threshold) {
                        return $category;
                    }
                }
                return 'very_slow';
            case 'suite':
                return $test['suite'] ?? 'default';
            default:
                return 'default';
        }
    }

    /**
     * Set performance threshold
     */
    public static function setPerformanceThreshold(string $category, float $threshold): void
    {
        self::$performanceThresholds[$category] = $threshold;
    }

    /**
     * Get performance threshold
     */
    public static function getPerformanceThreshold(string $category): ?float
    {
        return self::$performanceThresholds[$category] ?? null;
    }

    /**
     * Check if test meets performance threshold
     */
    public static function meetsPerformanceThreshold(float $executionTime, string $category): bool
    {
        $threshold = self::getPerformanceThreshold($category);
        return $threshold === null || $executionTime <= $threshold;
    }

    /**
     * Record test result
     */
    public static function recordTestResult(array $result): void
    {
        self::$testResults[] = array_merge([
            'timestamp' => microtime(true),
            'worker_id' => self::$context['worker_id'],
        ], $result);

        // Update statistics
        self::updateStatistics($result);
    }

    /**
     * Update execution statistics
     */
    private static function updateStatistics(array $result): void
    {
        self::$executionStats['total_tests']++;
        
        switch ($result['status'] ?? 'unknown') {
            case 'passed':
                self::$executionStats['passed_tests']++;
                break;
            case 'failed':
                self::$executionStats['failed_tests']++;
                break;
            case 'skipped':
                self::$executionStats['skipped_tests']++;
                break;
        }

        if (isset($result['execution_time'])) {
            self::$executionStats['execution_time'] += $result['execution_time'];
        }

        if (isset($result['memory_usage'])) {
            self::$executionStats['memory_usage'] = max(
                self::$executionStats['memory_usage'],
                $result['memory_usage']
            );
        }
    }

    /**
     * Get aggregated test results
     */
    public static function getAggregatedResults(): array
    {
        $results = [
            'summary' => self::$executionStats,
            'performance_analysis' => self::analyzePerformance(),
            'tag_analysis' => self::analyzeByTags(),
            'failure_analysis' => self::analyzeFailures(),
        ];

        return $results;
    }

    /**
     * Analyze performance results
     */
    private static function analyzePerformance(): array
    {
        $performanceData = [];
        $executionTimes = [];

        foreach (self::$testResults as $result) {
            if (isset($result['execution_time'])) {
                $executionTimes[] = $result['execution_time'];
            }
        }

        if (!empty($executionTimes)) {
            sort($executionTimes);
            $count = count($executionTimes);
            
            $performanceData = [
                'total_tests' => $count,
                'min_time' => min($executionTimes),
                'max_time' => max($executionTimes),
                'avg_time' => array_sum($executionTimes) / $count,
                'median_time' => $executionTimes[intval($count / 2)],
                'p95_time' => $executionTimes[intval($count * 0.95)],
                'slow_tests' => self::getSlowTests(),
            ];
        }

        return $performanceData;
    }

    /**
     * Get slow tests
     */
    private static function getSlowTests(int $limit = 10): array
    {
        $slowTests = array_filter(self::$testResults, function ($result) {
            return isset($result['execution_time']) && 
                   $result['execution_time'] > self::$performanceThresholds['normal'];
        });

        usort($slowTests, function ($a, $b) {
            return ($b['execution_time'] ?? 0) <=> ($a['execution_time'] ?? 0);
        });

        return array_slice($slowTests, 0, $limit);
    }

    /**
     * Analyze results by tags
     */
    private static function analyzeByTags(): array
    {
        $tagStats = [];

        foreach (self::$testResults as $result) {
            $tags = $result['tags'] ?? ['untagged'];
            
            foreach ($tags as $tag) {
                if (!isset($tagStats[$tag])) {
                    $tagStats[$tag] = [
                        'total' => 0,
                        'passed' => 0,
                        'failed' => 0,
                        'skipped' => 0,
                        'avg_time' => 0,
                        'total_time' => 0,
                    ];
                }

                $tagStats[$tag]['total']++;
                $tagStats[$tag][$result['status'] ?? 'unknown']++;
                
                if (isset($result['execution_time'])) {
                    $tagStats[$tag]['total_time'] += $result['execution_time'];
                    $tagStats[$tag]['avg_time'] = $tagStats[$tag]['total_time'] / $tagStats[$tag]['total'];
                }
            }
        }

        return $tagStats;
    }

    /**
     * Analyze failures
     */
    private static function analyzeFailures(): array
    {
        $failures = array_filter(self::$testResults, function ($result) {
            return ($result['status'] ?? '') === 'failed';
        });

        $failurePatterns = [];
        foreach ($failures as $failure) {
            $error = $failure['error'] ?? 'Unknown error';
            $pattern = self::extractErrorPattern($error);
            
            if (!isset($failurePatterns[$pattern])) {
                $failurePatterns[$pattern] = 0;
            }
            $failurePatterns[$pattern]++;
        }

        return [
            'total_failures' => count($failures),
            'failure_patterns' => $failurePatterns,
            'recent_failures' => array_slice($failures, -5),
        ];
    }

    /**
     * Extract error pattern from error message
     */
    private static function extractErrorPattern(string $error): string
    {
        // Remove file paths and line numbers
        $pattern = preg_replace('/\/[^\s]+:\d+/', '[FILE:LINE]', $error);
        
        // Remove specific values but keep structure
        $pattern = preg_replace('/\d+/', '[NUMBER]', $pattern);
        $pattern = preg_replace('/"[^"]*"/', '[STRING]', $pattern);
        
        return $pattern;
    }

    /**
     * Generate execution report
     */
    public static function generateExecutionReport(): array
    {
        $report = [
            'execution_context' => self::$context,
            'performance_thresholds' => self::$performanceThresholds,
            'results' => self::getAggregatedResults(),
            'recommendations' => self::generateRecommendations(),
            'generated_at' => date('Y-m-d H:i:s'),
        ];

        return $report;
    }

    /**
     * Generate recommendations based on results
     */
    private static function generateRecommendations(): array
    {
        $recommendations = [];
        $stats = self::$executionStats;

        // Performance recommendations
        $avgTime = $stats['total_tests'] > 0 ? $stats['execution_time'] / $stats['total_tests'] : 0;
        if ($avgTime > self::$performanceThresholds['normal']) {
            $recommendations[] = 'Consider optimizing slow tests or running them separately';
        }

        // Failure rate recommendations
        $failureRate = $stats['total_tests'] > 0 ? $stats['failed_tests'] / $stats['total_tests'] : 0;
        if ($failureRate > 0.1) {
            $recommendations[] = 'High failure rate detected - review failing tests';
        }

        // Memory usage recommendations
        if ($stats['memory_usage'] > 100 * 1024 * 1024) { // 100MB
            $recommendations[] = 'High memory usage detected - consider memory optimization';
        }

        // Parallel execution recommendations
        if (!self::$context['parallel'] && $stats['total_tests'] > 50) {
            $recommendations[] = 'Consider enabling parallel execution for faster test runs';
        }

        return $recommendations;
    }

    /**
     * Reset statistics
     */
    public static function resetStatistics(): void
    {
        self::$executionStats = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'skipped_tests' => 0,
            'execution_time' => 0,
            'memory_usage' => 0,
        ];
        
        self::$testResults = [];
    }

    /**
     * Get execution context
     */
    public static function getContext(): array
    {
        return self::$context;
    }

    /**
     * Update execution context
     */
    public static function updateContext(array $context): void
    {
        self::$context = array_merge(self::$context, $context);
    }

    /**
     * Export results to file
     */
    public static function exportResults(string $format = 'json', string $filename = null): string
    {
        $results = self::generateExecutionReport();
        $filename = $filename ?: 'test_execution_report_' . date('Y-m-d_H-i-s');

        switch ($format) {
            case 'json':
                $content = json_encode($results, JSON_PRETTY_PRINT);
                $filename .= '.json';
                break;
            case 'csv':
                $content = self::convertToCsv($results);
                $filename .= '.csv';
                break;
            default:
                throw new \InvalidArgumentException("Unsupported format: {$format}");
        }

        $filepath = storage_path('logs/' . $filename);
        file_put_contents($filepath, $content);

        return $filepath;
    }

    /**
     * Convert results to CSV format
     */
    private static function convertToCsv(array $results): string
    {
        $csv = "Test Name,Status,Execution Time,Memory Usage,Tags\n";
        
        foreach (self::$testResults as $result) {
            $csv .= sprintf(
                "%s,%s,%s,%s,%s\n",
                $result['name'] ?? 'Unknown',
                $result['status'] ?? 'Unknown',
                $result['execution_time'] ?? '0',
                $result['memory_usage'] ?? '0',
                implode(';', $result['tags'] ?? [])
            );
        }

        return $csv;
    }
}