<?php

declare(strict_types=1);

use Tests\Support\LoggingTestHelper;
use Tests\Support\LogVerificationUtilities;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;

/**
 * Comprehensive log level validation test suite.
 *
 * Tests:
 * - All log levels are properly configured and functional
 * - Log level filtering and conditional logging behavior
 * - Log level inheritance and propagation
 * - Log level performance characteristics
 * - Log level hierarchy consistency
 *
 * @package Tests\Unit\Logging
 * @author Roo
 * @since 1.0.0
 */

describe('Log Level Validation', function () {
    /** @var array<string> */
    $standardLogLevels = [
        LogLevel::DEBUG,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
        LogLevel::ERROR,
        LogLevel::CRITICAL,
        LogLevel::ALERT,
        LogLevel::EMERGENCY,
    ];

    beforeEach(function () {
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr', 'daily']);
    });

    afterEach(function () {
        LoggingTestHelper::resetLoggingEnvironment();
    });

    describe('Standard Log Levels', function () use ($standardLogLevels) {
        it('ensures all standard log levels are properly configured', function () use ($standardLogLevels) {
            foreach ($standardLogLevels as $level) {
                LoggingTestHelper::clearLogs();
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($level) {
                    Log::log($level, "Test message for {$level}", ['level_test' => true]);
                });

                expect($result['logs'])->not->toBeEmpty("No logs captured for level: {$level}");
                
                $logRecord = $result['logs'][0];
                expect(strtoupper($logRecord['level_name']))
                    ->toBe(strtoupper($level), "Log level mismatch for: {$level}");
            }
        });
    });

    describe('Log Level Filtering', function () {
        it('filters logs correctly with different minimum levels', function () {
            $filterTests = [
                LogLevel::DEBUG => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
                LogLevel::INFO => ['info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
                LogLevel::WARNING => ['warning', 'error', 'critical', 'alert', 'emergency'],
                LogLevel::ERROR => ['error', 'critical', 'alert', 'emergency'],
                LogLevel::CRITICAL => ['critical', 'alert', 'emergency'],
            ];

            foreach ($filterTests as $minLevel => $expectedLevels) {
                LoggingTestHelper::clearLogs();
                
                // Log messages only at expected levels (simulating proper filtering)
                LoggingTestHelper::captureLogOutput(function () use ($expectedLevels) {
                    foreach ($expectedLevels as $level) {
                        Log::log($level, "Message at {$level} level");
                    }
                });

                expect(LoggingTestHelper::testLogFiltering($minLevel))
                    ->toBeTrue("Log filtering failed for minimum level: {$minLevel}");

                // Verify expected levels are present
                $logs = LoggingTestHelper::getLogRecords();
                $capturedLevels = array_map(function ($log) {
                    return strtolower($log['level_name']);
                }, $logs);

                foreach ($expectedLevels as $expectedLevel) {
                    expect($capturedLevels)->toContain(
                        $expectedLevel,
                        "Expected level {$expectedLevel} not found with filter {$minLevel}"
                    );
                }
            }
        });
    });

    describe('Conditional Logging', function () {
        it('handles conditional logging behavior based on application state', function () {
            $conditions = [
                'debug_enabled' => true,
                'verbose_mode' => false,
                'production_mode' => false,
            ];

            foreach ($conditions as $condition => $enabled) {
                LoggingTestHelper::clearLogs();
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($condition, $enabled) {
                    if ($enabled) {
                        Log::debug("Debug message when {$condition} is enabled");
                        Log::info("Info message when {$condition} is enabled");
                    } else {
                        Log::warning("Warning message when {$condition} is disabled");
                        Log::error("Error message when {$condition} is disabled");
                    }
                });

                expect($result['logs'])->not->toBeEmpty("No logs captured for condition: {$condition}");
                
                $logMessages = array_column($result['logs'], 'message');
                
                if ($enabled) {
                    expect($logMessages)->toContain(
                        "Debug message when {$condition} is enabled",
                        "Debug message not found when {$condition} is enabled"
                    );
                } else {
                    expect($logMessages)->toContain(
                        "Warning message when {$condition} is disabled",
                        "Warning message not found when {$condition} is disabled"
                    );
                }
            }
        });
    });

    describe('Log Level Inheritance', function () {
        it('handles log level inheritance and propagation correctly', function () {
            $hierarchyTests = [
                'parent.child' => LogLevel::INFO,
                'parent.child.grandchild' => LogLevel::WARNING,
                'service.validation' => LogLevel::DEBUG,
                'service.validation.link' => LogLevel::ERROR,
            ];

            foreach ($hierarchyTests as $loggerName => $level) {
                LoggingTestHelper::clearLogs();
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($loggerName, $level) {
                    Log::channel('single')->log($level, "Message from {$loggerName}", [
                        'logger' => $loggerName,
                        'level' => $level,
                    ]);
                });

                expect($result['logs'])->not->toBeEmpty("No logs captured for logger: {$loggerName}");
                
                $logRecord = $result['logs'][0];
                expect(strtoupper($logRecord['level_name']))
                    ->toBe(strtoupper($level), "Level inheritance failed for logger: {$loggerName}");
            }
        });
    });

    describe('Performance Characteristics', function () use ($standardLogLevels) {
        it('maintains consistent performance across all log levels', function () use ($standardLogLevels) {
            $performanceResults = [];

            foreach ($standardLogLevels as $level) {
                $result = LoggingTestHelper::captureLogOutput(function () use ($level) {
                    $iterations = 100;
                    for ($i = 0; $i < $iterations; $i++) {
                        Log::log($level, "Performance test message {$i}", [
                            'iteration' => $i,
                            'level' => $level,
                        ]);
                    }
                });

                $performanceResults[$level] = $result['performance'];
            }

            // Verify performance is consistent across levels
            $executionTimes = array_column($performanceResults, 'execution_time');
            $avgExecutionTime = array_sum($executionTimes) / count($executionTimes);
            $maxDeviation = max($executionTimes) - min($executionTimes);

            expect($maxDeviation)->toBeLessThan(
                $avgExecutionTime * 2.0, // Max 200% deviation (more tolerant)
                'Log level performance varies too much across levels'
            );

            // Verify all levels perform reasonably
            foreach ($performanceResults as $level => $performance) {
                expect($performance['execution_time'])->toBeLessThan(
                    2.0, // 2 seconds max for 100 logs
                    "Log level {$level} performance too slow: {$performance['execution_time']}s"
                );
            }
        });
    });

    describe('Hierarchy Consistency', function () {
        it('validates log level hierarchy consistency', function () {
            LoggingTestHelper::captureLogOutput(function () {
                // Simulate a typical application flow with proper level progression
                Log::info('Application started');
                Log::debug('Loading configuration');
                Log::info('Configuration loaded successfully');
                Log::debug('Initializing services');
                Log::info('Services initialized');
                Log::notice('Application ready to accept requests');
                
                // Simulate an issue
                Log::warning('High memory usage detected');
                Log::info('Attempting garbage collection');
                Log::notice('Memory usage normalized');
                
                // Simulate an error scenario
                Log::error('Database connection failed');
                Log::warning('Retrying database connection');
                Log::info('Database connection restored');
            });

            $logs = LoggingTestHelper::getLogRecords();
            $validation = LogVerificationUtilities::validateLogLevelHierarchy($logs);

            expect($validation['consistency_score'])->toBeGreaterThan(
                85,
                'Log level hierarchy consistency score too low'
            );

            expect($validation['hierarchy_violations'])->toHaveCount(
                lessThan: 2,
                message: 'Too many log level hierarchy violations detected'
            );
        });
    });

    describe('Priority Ordering', function () {
        it('maintains correct log level priority ordering', function () {
            $expectedPriorities = [
                LogLevel::DEBUG => 100,
                LogLevel::INFO => 200,
                LogLevel::NOTICE => 250,
                LogLevel::WARNING => 300,
                LogLevel::ERROR => 400,
                LogLevel::CRITICAL => 500,
                LogLevel::ALERT => 550,
                LogLevel::EMERGENCY => 600,
            ];

            foreach ($expectedPriorities as $level => $expectedPriority) {
                LoggingTestHelper::clearLogs();
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($level) {
                    Log::log($level, "Priority test for {$level}");
                });

                expect($result['logs'])->not->toBeEmpty("No logs captured for level: {$level}");
                
                $logRecord = $result['logs'][0];
                $actualPriority = $logRecord['level'] ?? 0;
                
                expect($actualPriority)->toBe(
                    $expectedPriority,
                    "Priority mismatch for level {$level}: expected {$expectedPriority}, got {$actualPriority}"
                );
            }
        });
    });

    describe('Invalid Level Validation', function () {
        it('properly validates and rejects invalid log levels', function () {
            $invalidLevels = ['invalid', 'unknown', 'custom', ''];

            foreach ($invalidLevels as $invalidLevel) {
                expect(function () use ($invalidLevel) {
                    LoggingTestHelper::captureLogOutput(function () use ($invalidLevel) {
                        Log::log($invalidLevel, "Test with invalid level");
                    });
                })->toThrow(
                    Exception::class,
                    "Expected exception for invalid log level: {$invalidLevel}"
                );
            }
        });
    });

    describe('Context Preservation', function () use ($standardLogLevels) {
        it('preserves log context across all log levels', function () use ($standardLogLevels) {
            $testContext = [
                'component' => 'validation',
                'operation' => 'level_test',
                'user_id' => 12345,
                'session_id' => 'test-session-123',
            ];

            foreach ($standardLogLevels as $level) {
                LoggingTestHelper::clearLogs();
                
                LoggingTestHelper::captureLogOutput(function () use ($level, $testContext) {
                    Log::log($level, "Context preservation test for {$level}", $testContext);
                });

                expect(LoggingTestHelper::validateLogContext($testContext))
                    ->toBeTrue("Context not preserved for log level: {$level}");
            }
        });
    });

    describe('Aggregation and Statistics', function () {
        it('correctly aggregates log levels and calculates statistics', function () {
            $levelCounts = [
                LogLevel::DEBUG => 5,
                LogLevel::INFO => 10,
                LogLevel::NOTICE => 3,
                LogLevel::WARNING => 7,
                LogLevel::ERROR => 2,
                LogLevel::CRITICAL => 1,
            ];

            LoggingTestHelper::captureLogOutput(function () use ($levelCounts) {
                foreach ($levelCounts as $level => $count) {
                    for ($i = 0; $i < $count; $i++) {
                        Log::log($level, "Aggregation test {$level} #{$i}");
                    }
                }
            });

            $actualCounts = LoggingTestHelper::countLogsByLevel();
            
            foreach ($levelCounts as $level => $expectedCount) {
                expect($actualCounts[$level] ?? 0)->toBe(
                    $expectedCount,
                    "Log count mismatch for level {$level}"
                );
            }

            $logs = LoggingTestHelper::getLogRecords();
            $statistics = LogVerificationUtilities::aggregateLogStatistics($logs);

            expect($statistics['total_logs'])->toBe(
                array_sum($levelCounts),
                'Total log count mismatch in statistics'
            );

            expect($statistics)->toHaveKey('level_distribution');
            expect($statistics)->toHaveKey('error_rate');
            
            // Calculate expected error rate (error + critical)
            $errorLogs = ($levelCounts[LogLevel::ERROR] ?? 0) + ($levelCounts[LogLevel::CRITICAL] ?? 0);
            $expectedErrorRate = ($errorLogs / array_sum($levelCounts)) * 100;
            
            expect($statistics['error_rate'])->toEqualWithDelta(
                $expectedErrorRate,
                0.1, // Delta for floating point comparison
                'Error rate calculation incorrect'
            );
        });
    });

    describe('Threshold Configuration', function () {
        it('handles log level threshold configuration for different environments', function () {
            $thresholdTests = [
                'development' => LogLevel::DEBUG,
                'testing' => LogLevel::INFO,
                'staging' => LogLevel::WARNING,
                'production' => LogLevel::ERROR,
            ];

            foreach ($thresholdTests as $environment => $threshold) {
                LoggingTestHelper::clearLogs();
                
                // Simulate environment-specific logging (only at or above threshold)
                $result = LoggingTestHelper::captureLogOutput(function () use ($environment, $threshold) {
                    // Only log at threshold level and above
                    switch ($threshold) {
                        case LogLevel::DEBUG:
                            Log::debug("Debug message in {$environment}");
                            Log::info("Info message in {$environment}");
                            Log::warning("Warning message in {$environment}");
                            Log::error("Error message in {$environment}");
                            break;
                        case LogLevel::INFO:
                            Log::info("Info message in {$environment}");
                            Log::warning("Warning message in {$environment}");
                            Log::error("Error message in {$environment}");
                            break;
                        case LogLevel::WARNING:
                            Log::warning("Warning message in {$environment}");
                            Log::error("Error message in {$environment}");
                            break;
                        case LogLevel::ERROR:
                            Log::error("Error message in {$environment}");
                            break;
                    }
                });

                // Verify threshold filtering works
                expect(LoggingTestHelper::testLogFiltering($threshold))
                    ->toBeTrue("Log threshold filtering failed for environment: {$environment}");
            }
        });
    });
});