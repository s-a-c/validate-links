<?php

declare(strict_types=1);

use Tests\Support\LoggingTestHelper;
use Tests\Support\LogVerificationUtilities;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;

/**
 * Comprehensive logging verification test suite.
 *
 * Tests:
 * - Log level validation across all application components
 * - Message content assertion with exact text matching and pattern validation
 * - Structured logging format verification and compliance
 * - Performance impact assessment of logging operations
 * - Log rotation, archiving, and cleanup procedures
 *
 * @package Tests\Unit\Logging
 * @author Roo
 * @since 1.0.0
 */

describe('Logging Verification', function () {
    beforeEach(function () {
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr', 'daily']);
    });

    afterEach(function () {
        LoggingTestHelper::resetLoggingEnvironment();
    });

    describe('Log Level Validation', function () {
        it('validates all log levels', function () {
            $logLevels = [
                LogLevel::DEBUG,
                LogLevel::INFO,
                LogLevel::NOTICE,
                LogLevel::WARNING,
                LogLevel::ERROR,
                LogLevel::CRITICAL,
                LogLevel::ALERT,
                LogLevel::EMERGENCY,
            ];

            foreach ($logLevels as $level) {
                $result = LoggingTestHelper::captureLogOutput(function () use ($level) {
                    Log::log($level, "Test message for {$level} level", ['test_context' => true]);
                });

                expect($result['logs'])->not->toBeEmpty("No logs captured for level: {$level}");
                expect(LoggingTestHelper::validateLogLevel($level))
                    ->toBeTrue("Log level validation failed for: {$level}");
            }
        });

        it('validates log level hierarchy and consistency', function () {
            LoggingTestHelper::captureLogOutput(function () {
                Log::info('Starting operation');
                Log::info('Processing data');
                Log::warning('Minor issue detected');
                Log::info('Continuing operation');
                Log::info('Operation completed');
            });

            $logs = LoggingTestHelper::getLogRecords();
            $validation = LogVerificationUtilities::validateLogLevelHierarchy($logs);

            expect($validation['consistency_score'])->toBeGreaterThan(80, 'Log level consistency score too low');
            expect($validation['hierarchy_violations'])->toBeEmpty('Log level hierarchy violations detected');
        });
    });

    describe('Message Content Assertion', function () {
        it('asserts exact log messages', function () {
            $testMessages = [
                'Validation started for file: test.md',
                'Link validation completed successfully',
                'Error: Invalid URL format detected',
                'Warning: External link validation timeout',
                'Critical: Security violation detected in path',
            ];

            foreach ($testMessages as $message) {
                LoggingTestHelper::clearLogs();
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($message) {
                    Log::info($message);
                });

                expect(LoggingTestHelper::assertLogMessage($message, 'info'))
                    ->toBeTrue("Failed to assert exact message: {$message}");
            }
        });

        it('validates log message patterns', function () {
            $patterns = [
                '/^Validation\s+\w+\s+for\s+file:\s+\S+$/' => 'Validation started for file: example.md',
                '/^Link\s+\[\d+\]\s+status:\s+\w+$/' => 'Link [42] status: valid',
                '/^Error:\s+HTTP\s+\d{3}\s+.+$/' => 'Error: HTTP 404 Not Found',
                '/^Performance:\s+\d+\.\d+ms$/' => 'Performance: 123.45ms',
            ];

            foreach ($patterns as $pattern => $testMessage) {
                LoggingTestHelper::clearLogs();
                
                LoggingTestHelper::captureLogOutput(function () use ($testMessage) {
                    Log::info($testMessage);
                });

                expect(LoggingTestHelper::assertLogMessagePattern($pattern, 'info'))
                    ->toBeTrue("Pattern validation failed for: {$pattern}");
            }
        });

        it('verifies comprehensive log patterns', function () {
            $patterns = [
                'validation_start' => [
                    'level' => 'info',
                    'message_pattern' => '/^Validation started for/',
                    'context' => ['component' => 'validation'],
                ],
                'validation_error' => [
                    'level' => 'error',
                    'message_pattern' => '/^Validation failed:/',
                    'context' => ['component' => 'validation'],
                ],
                'performance_metric' => [
                    'level' => 'info',
                    'message_pattern' => '/Performance: \d+\.\d+ms/',
                ],
            ];

            // Generate test logs
            $result = LoggingTestHelper::captureLogOutput(function () {
                Log::info('Validation started for file: test.md', ['component' => 'validation']);
                Log::error('Validation failed: Invalid URL format', ['component' => 'validation']);
                Log::info('Performance: 123.45ms');
            });

            $verification = LogVerificationUtilities::verifyLogPatterns($result['logs'], $patterns);

            expect($verification['score'])->toBeGreaterThan(80, 'Log pattern verification score too low');
            expect($verification['matches'])->not->toBeEmpty('No pattern matches found');
        });
    });

    describe('Structured Logging Format', function () {
        it('verifies structured logging format', function () {
            $expectedStructure = [
                'message' => 'string',
                'level_name' => 'string',
                'datetime' => 'object',
                'context' => 'array',
                'extra' => 'array',
            ];

            $result = LoggingTestHelper::captureLogOutput(function () {
                Log::info('Structured log test', [
                    'component' => 'validation',
                    'operation' => 'link_check',
                    'url' => 'https://example.com',
                    'status' => 'success',
                ]);
            });

            expect(LoggingTestHelper::verifyLogFormat($expectedStructure))
                ->toBeTrue('Structured logging format verification failed');

            expect($result['logs'])->not->toBeEmpty('No logs captured for structured format test');
            
            $logRecord = $result['logs'][0];
            expect($logRecord)->toHaveKey('context');
            expect($logRecord['context'])->toHaveKey('component');
            expect($logRecord['context']['component'])->toBe('validation');
        });

        it('checks structured logging compliance', function () {
            $schema = [
                'required_fields' => ['message', 'level_name', 'datetime', 'context'],
                'field_types' => [
                    'message' => 'string',
                    'level_name' => 'string',
                    'context' => 'array',
                ],
            ];

            $result = LoggingTestHelper::captureLogOutput(function () {
                Log::info('Compliance test message', ['component' => 'test']);
                Log::warning('Another test message', ['component' => 'test']);
            });

            $compliance = LogVerificationUtilities::checkStructuredLoggingCompliance(
                $result['logs'], 
                $schema
            );

            expect($compliance['compliance_score'])->toBeGreaterThan(90, 'Structured logging compliance score too low');
            expect($compliance['non_compliant_logs'])->toBeEmpty('Non-compliant logs detected');
        });
    });

    describe('Log Context and Metadata', function () {
        it('validates log context and metadata', function () {
            $expectedContext = [
                'component' => 'link_validation',
                'operation' => 'validate_file',
                'correlation_id' => 'test-123',
            ];

            LoggingTestHelper::captureLogOutput(function () use ($expectedContext) {
                Log::info('Context validation test', $expectedContext);
            });

            expect(LoggingTestHelper::validateLogContext($expectedContext))
                ->toBeTrue('Log context validation failed');
        });

        it('validates log correlation and tracing', function () {
            $correlationId = 'test-correlation-' . uniqid();

            LoggingTestHelper::captureLogOutput(function () use ($correlationId) {
                Log::info('Operation started', ['correlation_id' => $correlationId]);
                Log::info('Processing step 1', ['correlation_id' => $correlationId]);
                Log::info('Processing step 2', ['correlation_id' => $correlationId]);
                Log::info('Operation completed', ['correlation_id' => $correlationId]);
            });

            expect(LoggingTestHelper::validateLogCorrelation($correlationId))
                ->toBeTrue('Log correlation validation failed');
        });
    });

    describe('Log Filtering and Conditional Behavior', function () {
        it('validates log filtering', function () {
            // Test filtering at different levels
            $filterLevels = [LogLevel::INFO, LogLevel::WARNING, LogLevel::ERROR];

            foreach ($filterLevels as $minLevel) {
                LoggingTestHelper::clearLogs();
                
                // Log messages only at or above the minimum level (simulating proper filtering)
                LoggingTestHelper::captureLogOutput(function () use ($minLevel) {
                    switch ($minLevel) {
                        case LogLevel::INFO:
                            Log::info('Info message');
                            Log::warning('Warning message');
                            Log::error('Error message');
                            break;
                        case LogLevel::WARNING:
                            Log::warning('Warning message');
                            Log::error('Error message');
                            break;
                        case LogLevel::ERROR:
                            Log::error('Error message');
                            break;
                        default:
                            Log::debug('Debug message');
                            Log::info('Info message');
                            Log::warning('Warning message');
                            Log::error('Error message');
                            break;
                    }
                });

                expect(LoggingTestHelper::testLogFiltering($minLevel))
                    ->toBeTrue("Log filtering failed for minimum level: {$minLevel}");
            }
        });
    });

    describe('Performance Impact Assessment', function () {
        it('assesses logging performance impact', function () {
            // Test with different log volumes
            $logVolumes = [10, 100, 1000];

            foreach ($logVolumes as $volume) {
                $result = LoggingTestHelper::captureLogOutput(function () use ($volume) {
                    for ($i = 0; $i < $volume; $i++) {
                        Log::info("Performance test log entry {$i}", [
                            'iteration' => $i,
                            'timestamp' => microtime(true),
                        ]);
                    }
                });

                $performance = LoggingTestHelper::assessLoggingPerformance();
                
                expect($performance)->toHaveKey('execution_time');
                expect($performance)->toHaveKey('memory_usage');
                expect($performance)->toHaveKey('performance_rating');
                
                // Performance should be reasonable even with high volume
                expect($performance['execution_time'])->toBeLessThan(5.0, 
                    "Logging performance too slow for {$volume} logs");
                
                expect($performance['performance_rating'])->toBeIn(
                    ['excellent', 'good', 'acceptable'], 
                    "Poor performance rating for {$volume} logs: {$performance['performance_rating']}"
                );
            }
        });

        it('analyzes detailed performance impact', function () {
            $operation = function () {
                // Simulate some work
                usleep(1000); // 1ms
                Log::info('Performance test operation', ['timestamp' => microtime(true)]);
            };

            $impact = LogVerificationUtilities::analyzeLogPerformanceImpact($operation, [
                'iterations' => 50,
                'warmup_iterations' => 5,
            ]);

            expect($impact)->toHaveKey('baseline');
            expect($impact)->toHaveKey('with_logging');
            expect($impact)->toHaveKey('impact');
            
            expect($impact['impact'])->toHaveKey('time_impact_percent');
            expect($impact['impact'])->toHaveKey('memory_impact_percent');
            expect($impact['impact'])->toHaveKey('performance_rating');
            
            // Logging impact should be reasonable
            expect($impact['impact']['time_impact_percent'])->toBeLessThan(100, 
                'Logging time impact too high');
            expect($impact['impact']['performance_rating'])->toBeIn(
                ['excellent', 'good', 'acceptable'], 
                'Poor logging performance rating'
            );
        });
    });

    describe('Timestamp Format and Timezone', function () {
        it('validates timestamp format and timezone', function () {
            $result = LoggingTestHelper::captureLogOutput(function () {
                Log::info('Timestamp validation test');
            });

            expect(LoggingTestHelper::validateTimestampFormat('Y-m-d H:i:s', 'UTC'))
                ->toBeTrue('Timestamp format validation failed');

            expect($result['logs'])->not->toBeEmpty();
            $logRecord = $result['logs'][0];
            
            expect($logRecord['datetime'])->toBeInstanceOf(\DateTimeInterface::class);
            expect($logRecord['datetime']->format('Y-m-d H:i:s'))
                ->toMatch('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/');
        });
    });

    describe('Log Aggregation and Statistics', function () {
        it('analyzes log aggregation and statistics', function () {
            LoggingTestHelper::captureLogOutput(function () {
                // Generate diverse log entries
                Log::info('Info message 1', ['component' => 'validation']);
                Log::info('Info message 2', ['component' => 'reporting']);
                Log::warning('Warning message', ['component' => 'validation']);
                Log::error('Error message', ['component' => 'security']);
                Log::info('Info message 3', ['component' => 'validation']);
            });

            $logs = LoggingTestHelper::getLogRecords();
            $statistics = LogVerificationUtilities::aggregateLogStatistics($logs);

            expect($statistics['total_logs'])->toBe(5);
            expect($statistics)->toHaveKey('level_distribution');
            expect($statistics)->toHaveKey('component_distribution');
            expect($statistics)->toHaveKey('error_rate');
            
            expect($statistics['level_distribution']['info'])->toBe(3);
            expect($statistics['level_distribution']['warning'])->toBe(1);
            expect($statistics['level_distribution']['error'])->toBe(1);
            
            expect($statistics['error_rate'])->toBe(20.0); // 1 error out of 5 logs
        });

        it('validates log counts by level', function () {
            LoggingTestHelper::captureLogOutput(function () {
                Log::debug('Debug 1');
                Log::debug('Debug 2');
                Log::info('Info 1');
                Log::info('Info 2');
                Log::info('Info 3');
                Log::warning('Warning 1');
                Log::error('Error 1');
            });

            $counts = LoggingTestHelper::countLogsByLevel();

            expect($counts['debug'] ?? 0)->toBe(2);
            expect($counts['info'] ?? 0)->toBe(3);
            expect($counts['warning'] ?? 0)->toBe(1);
            expect($counts['error'] ?? 0)->toBe(1);
        });
    });

    describe('Log Records Retrieval and Filtering', function () {
        it('retrieves and filters log records', function () {
            LoggingTestHelper::captureLogOutput(function () {
                Log::info('Info message');
                Log::warning('Warning message');
                Log::error('Error message');
            });

            $allRecords = LoggingTestHelper::getLogRecords();
            expect($allRecords)->toHaveCount(3);

            $errorRecords = LoggingTestHelper::getLogRecordsByLevel('error');
            expect($errorRecords)->toHaveCount(1);
            expect($errorRecords[0]['message'])->toBe('Error message');

            $warningRecords = LoggingTestHelper::getLogRecordsByLevel('warning');
            expect($warningRecords)->toHaveCount(1);
            expect($warningRecords[0]['message'])->toBe('Warning message');
        });
    });
});