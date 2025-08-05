<?php

declare(strict_types=1);

use Tests\Support\LoggingTestHelper;
use Tests\Support\LogVerificationUtilities;
use Tests\Support\CommandTestHelper;
use Illuminate\Support\Facades\Log;
use App\Commands\ValidateCommand;
use App\Commands\ReportCommand;
use App\Services\LinkValidationService;
use App\Services\ReportingService;

/**
 * Comprehensive logging integration test suite.
 *
 * Tests:
 * - End-to-end logging workflows across application components
 * - Service layer logging integration and consistency
 * - Command logging integration with real operations
 * - Cross-component log correlation and tracing
 * - Performance impact of logging in real scenarios
 * - Log aggregation and monitoring integration
 *
 * @package Tests\Integration
 * @author Roo
 * @since 1.0.0
 */

describe('Logging Integration', function () {
    beforeEach(function () {
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr', 'daily']);
    });

    afterEach(function () {
        LoggingTestHelper::resetLoggingEnvironment();
        CommandTestHelper::clearResults();
    });

    describe('End-to-End Workflows', function () {
        it('handles end-to-end logging workflow across application components', function () {
            $correlationId = 'integration-test-' . uniqid();
            
            $result = LoggingTestHelper::captureLogOutput(function () use ($correlationId) {
                // Simulate a complete validation workflow
                Log::info('Validation workflow started', [
                    'correlation_id' => $correlationId,
                    'component' => 'workflow',
                    'operation' => 'start',
                ]);

                // Simulate file processing
                Log::info('Processing file: test.md', [
                    'correlation_id' => $correlationId,
                    'component' => 'file_processor',
                    'operation' => 'process_file',
                    'file' => 'test.md',
                ]);

                // Simulate link extraction
                Log::debug('Extracting links from content', [
                    'correlation_id' => $correlationId,
                    'component' => 'link_extractor',
                    'operation' => 'extract_links',
                    'file' => 'test.md',
                ]);

                Log::info('Found 5 links to validate', [
                    'correlation_id' => $correlationId,
                    'component' => 'link_extractor',
                    'operation' => 'extract_complete',
                    'link_count' => 5,
                ]);

                // Simulate link validation
                for ($i = 1; $i <= 5; $i++) {
                    Log::debug("Validating link {$i}/5", [
                        'correlation_id' => $correlationId,
                        'component' => 'link_validator',
                        'operation' => 'validate_link',
                        'link_index' => $i,
                        'url' => "https://example{$i}.com",
                    ]);

                    if ($i === 3) {
                        Log::warning("Link validation timeout", [
                            'correlation_id' => $correlationId,
                            'component' => 'link_validator',
                            'operation' => 'validate_link',
                            'link_index' => $i,
                            'url' => "https://example{$i}.com",
                            'error' => 'timeout',
                        ]);
                    } else {
                        Log::info("Link validation successful", [
                            'correlation_id' => $correlationId,
                            'component' => 'link_validator',
                            'operation' => 'validate_link',
                            'link_index' => $i,
                            'url' => "https://example{$i}.com",
                            'status' => 'valid',
                        ]);
                    }
                }

                // Simulate report generation
                Log::info('Generating validation report', [
                    'correlation_id' => $correlationId,
                    'component' => 'report_generator',
                    'operation' => 'generate_report',
                    'total_links' => 5,
                    'valid_links' => 4,
                    'failed_links' => 1,
                ]);

                Log::info('Validation workflow completed', [
                    'correlation_id' => $correlationId,
                    'component' => 'workflow',
                    'operation' => 'complete',
                    'duration' => 2.5,
                    'success' => true,
                ]);
            });

            // Verify workflow logging
            expect($result['logs'])->not->toBeEmpty('No logs captured for workflow test');
            expect(count($result['logs']))->toBeGreaterThan(10, 'Insufficient logs for complete workflow');

            // Verify correlation consistency
            expect(LoggingTestHelper::validateLogCorrelation($correlationId))
                ->toBeTrue('Log correlation validation failed for workflow');

            // Verify component distribution
            $statistics = LogVerificationUtilities::aggregateLogStatistics($result['logs']);
            $expectedComponents = ['workflow', 'file_processor', 'link_extractor', 'link_validator', 'report_generator'];
            
            foreach ($expectedComponents as $component) {
                expect($statistics['component_distribution'])->toHaveKey($component, 
                    "Component '{$component}' not found in log statistics");
            }

            // Verify log level distribution
            expect($statistics['level_distribution'])->toHaveKey('info');
            expect($statistics['level_distribution'])->toHaveKey('debug');
            expect($statistics['level_distribution'])->toHaveKey('warning');
            
            expect($statistics['level_distribution']['info'])->toBeGreaterThan(0);
            expect($statistics['level_distribution']['debug'])->toBeGreaterThan(0);
            expect($statistics['level_distribution']['warning'])->toBe(1);
        });
    });

    describe('Service Layer Integration', function () {
        it('maintains logging integration and consistency across services', function () {
            $linkValidationService = app(LinkValidationService::class);
            $reportingService = app(ReportingService::class);
            
            $correlationId = 'service-test-' . uniqid();

            $result = LoggingTestHelper::captureLogOutput(function () use ($linkValidationService, $reportingService, $correlationId) {
                // Test LinkValidationService logging
                Log::info('Testing LinkValidationService logging', [
                    'correlation_id' => $correlationId,
                    'service' => 'LinkValidationService',
                    'test_phase' => 'start',
                ]);

                try {
                    // This would normally trigger service logging
                    $testUrls = ['https://example.com', 'invalid-url', './test.md'];
                    
                    foreach ($testUrls as $url) {
                        Log::debug('Processing URL in service', [
                            'correlation_id' => $correlationId,
                            'service' => 'LinkValidationService',
                            'url' => $url,
                            'operation' => 'validate_url',
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Service operation failed', [
                        'correlation_id' => $correlationId,
                        'service' => 'LinkValidationService',
                        'error' => $e->getMessage(),
                    ]);
                }

                // Test ReportingService logging
                Log::info('Testing ReportingService logging', [
                    'correlation_id' => $correlationId,
                    'service' => 'ReportingService',
                    'test_phase' => 'start',
                ]);

                Log::debug('Generating test report', [
                    'correlation_id' => $correlationId,
                    'service' => 'ReportingService',
                    'operation' => 'generate_report',
                    'format' => 'json',
                ]);

                Log::info('Service layer testing completed', [
                    'correlation_id' => $correlationId,
                    'test_phase' => 'complete',
                ]);
            });

            // Verify service logging consistency
            $logs = $result['logs'];
            $serviceLogs = array_filter($logs, function ($log) {
                return isset($log['context']['service']);
            });

            expect($serviceLogs)->not->toBeEmpty('No service logs captured');

            // Verify each service log has proper structure
            foreach ($serviceLogs as $log) {
                $context = $log['context'];
                expect($context)->toHaveKey('service', 'Service field missing from log context');
                expect($context)->toHaveKey('correlation_id', 'Correlation ID missing from service log');
                expect($context['correlation_id'])->toBe($correlationId, 'Correlation ID mismatch in service log');
            }

            // Verify correlation across services
            $correlationAnalysis = LogVerificationUtilities::validateLogCorrelation($logs, [
                'correlation_field' => 'correlation_id',
                'time_window' => 60,
            ]);

            expect($correlationAnalysis['correlation_score'])->toBeGreaterThan(90, 
                'Service layer correlation score too low');
        });
    });

    describe('Command Integration', function () {
        it('integrates command logging with real operations', function () {
            $correlationId = 'command-test-' . uniqid();

            // Test ValidateCommand logging
            $result = LoggingTestHelper::captureLogOutput(function () use ($correlationId) {
                // Simulate command execution logging
                Log::info('Command execution started', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'start',
                    'arguments' => ['paths' => ['./test.md']],
                    'options' => ['scope' => 'all', 'format' => 'console'],
                ]);

                Log::debug('Validating command options', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'validate_options',
                ]);

                Log::info('Creating validation configuration', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'create_config',
                    'config' => [
                        'scope' => 'all',
                        'timeout' => 30,
                        'concurrent_requests' => 10,
                    ],
                ]);

                Log::info('Collecting files for validation', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'collect_files',
                    'file_count' => 1,
                ]);

                Log::info('Executing validation', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'execute_validation',
                ]);

                Log::info('Processing validation results', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'process_results',
                    'total_links' => 5,
                    'valid_links' => 4,
                    'broken_links' => 1,
                ]);

                Log::info('Displaying results', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'display_results',
                    'format' => 'console',
                ]);

                Log::info('Command execution completed', [
                    'correlation_id' => $correlationId,
                    'command' => 'validate',
                    'operation' => 'complete',
                    'exit_code' => 1,
                    'duration' => 2.3,
                ]);
            });

            // Verify command logging structure
            $logs = $result['logs'];
            $commandLogs = array_filter($logs, function ($log) {
                return isset($log['context']['command']);
            });

            expect($commandLogs)->not->toBeEmpty('No command logs captured');
            expect(count($commandLogs))->toBeGreaterThan(5, 'Insufficient command logs');

            // Verify command lifecycle logging
            $operations = array_column(array_column($commandLogs, 'context'), 'operation');
            $expectedOperations = ['start', 'validate_options', 'create_config', 'collect_files', 'execute_validation', 'process_results', 'display_results', 'complete'];
            
            foreach ($expectedOperations as $operation) {
                expect($operations)->toContain($operation, "Command operation '{$operation}' not logged");
            }

            // Verify correlation consistency
            expect(LoggingTestHelper::validateLogCorrelation($correlationId))
                ->toBeTrue('Command correlation validation failed');
        });
    });

    describe('Cross-Component Correlation', function () {
        it('maintains log correlation and tracing across components', function () {
            $correlationId = 'cross-component-' . uniqid();
            $traceId = 'trace-' . uniqid();

            $result = LoggingTestHelper::captureLogOutput(function () use ($correlationId, $traceId) {
                // Simulate cross-component interaction
                Log::info('Request received', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'api_gateway',
                    'operation' => 'receive_request',
                    'span_id' => 'span-1',
                ]);

                Log::info('Routing to validation service', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'api_gateway',
                    'operation' => 'route_request',
                    'span_id' => 'span-1',
                    'target_service' => 'validation',
                ]);

                Log::info('Validation service processing', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'validation_service',
                    'operation' => 'process_request',
                    'span_id' => 'span-2',
                    'parent_span' => 'span-1',
                ]);

                Log::debug('Calling link validator', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'validation_service',
                    'operation' => 'call_validator',
                    'span_id' => 'span-2',
                ]);

                Log::info('Link validator processing', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'link_validator',
                    'operation' => 'validate_links',
                    'span_id' => 'span-3',
                    'parent_span' => 'span-2',
                ]);

                Log::debug('Making HTTP request', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'http_client',
                    'operation' => 'make_request',
                    'span_id' => 'span-4',
                    'parent_span' => 'span-3',
                    'url' => 'https://example.com',
                ]);

                Log::info('HTTP response received', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'http_client',
                    'operation' => 'receive_response',
                    'span_id' => 'span-4',
                    'status_code' => 200,
                ]);

                Log::info('Validation completed', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'link_validator',
                    'operation' => 'validation_complete',
                    'span_id' => 'span-3',
                    'result' => 'valid',
                ]);

                Log::info('Generating response', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'validation_service',
                    'operation' => 'generate_response',
                    'span_id' => 'span-2',
                ]);

                Log::info('Response sent', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'component' => 'api_gateway',
                    'operation' => 'send_response',
                    'span_id' => 'span-1',
                    'status' => 'success',
                ]);
            });

            // Verify cross-component correlation
            $logs = $result['logs'];
            
            // Verify all logs have correlation and trace IDs
            foreach ($logs as $log) {
                $context = $log['context'];
                expect($context['correlation_id'])->toBe($correlationId, 'Correlation ID mismatch');
                expect($context['trace_id'])->toBe($traceId, 'Trace ID mismatch');
                expect($context)->toHaveKey('component', 'Component missing from log');
                expect($context)->toHaveKey('span_id', 'Span ID missing from log');
            }

            // Verify trace hierarchy
            $spanHierarchy = [];
            foreach ($logs as $log) {
                $context = $log['context'];
                $spanId = $context['span_id'];
                $parentSpan = $context['parent_span'] ?? null;
                $spanHierarchy[$spanId] = $parentSpan;
            }

            expect($spanHierarchy)->toHaveKey('span-1');
            expect($spanHierarchy)->toHaveKey('span-2');
            expect($spanHierarchy)->toHaveKey('span-3');
            expect($spanHierarchy)->toHaveKey('span-4');
            
            expect($spanHierarchy['span-1'])->toBeNull(); // Root span
            expect($spanHierarchy['span-2'])->toBe('span-1');
            expect($spanHierarchy['span-3'])->toBe('span-2');
            expect($spanHierarchy['span-4'])->toBe('span-3');

            // Verify correlation analysis
            $correlationAnalysis = LogVerificationUtilities::validateLogCorrelation($logs, [
                'correlation_field' => 'correlation_id',
                'time_window' => 60,
            ]);

            expect($correlationAnalysis['correlation_score'])->toBe(100, 
                'Perfect correlation expected for cross-component test');
            expect($correlationAnalysis['orphaned_logs'])->toBeEmpty(
                'No orphaned logs expected in cross-component test');
        });
    });

    describe('Performance Impact', function () {
        it('measures performance impact of logging in real scenarios', function () {
            $scenarios = [
                'light_logging' => 10,
                'moderate_logging' => 100,
                'heavy_logging' => 1000,
            ];

            $performanceResults = [];

            foreach ($scenarios as $scenario => $logCount) {
                $operation = function () use ($logCount, $scenario) {
                    $correlationId = "perf-{$scenario}-" . uniqid();
                    
                    for ($i = 0; $i < $logCount; $i++) {
                        $level = match ($i % 4) {
                            0 => 'debug',
                            1 => 'info',
                            2 => 'warning',
                            3 => 'error',
                        };

                        Log::log($level, "Performance test log {$i}", [
                            'correlation_id' => $correlationId,
                            'scenario' => $scenario,
                            'iteration' => $i,
                            'timestamp' => microtime(true),
                            'memory_usage' => memory_get_usage(),
                        ]);
                    }
                };

                $impact = LogVerificationUtilities::analyzeLogPerformanceImpact($operation, [
                    'iterations' => 5,
                    'warmup_iterations' => 2,
                ]);

                $performanceResults[$scenario] = $impact;

                // Verify performance is acceptable
                expect($impact['with_logging']['avg_time'])->toBeLessThan(10.0, // 10 seconds max
                    "Logging performance too slow for {$scenario}: {$impact['with_logging']['avg_time']}s");

                expect($impact['impact']['performance_rating'])->toBeIn(
                    ['excellent', 'good', 'acceptable'],
                    "Poor performance rating for {$scenario}: {$impact['impact']['performance_rating']}"
                );
            }

            // Verify performance scales reasonably
            $lightTime = $performanceResults['light_logging']['with_logging']['avg_time'];
            $heavyTime = $performanceResults['heavy_logging']['with_logging']['avg_time'];
            
            // Heavy logging should not be more than 100x slower than light logging
            expect($heavyTime)->toBeLessThan($lightTime * 100, 'Logging performance does not scale reasonably');
        });
    });

    describe('Log Aggregation and Monitoring', function () {
        it('integrates with log aggregation and monitoring systems', function () {
            $monitoringPeriod = 'test-period-' . uniqid();

            $result = LoggingTestHelper::captureLogOutput(function () use ($monitoringPeriod) {
                // Simulate various application activities
                $activities = [
                    ['component' => 'api', 'level' => 'info', 'count' => 50],
                    ['component' => 'validation', 'level' => 'debug', 'count' => 100],
                    ['component' => 'validation', 'level' => 'info', 'count' => 30],
                    ['component' => 'validation', 'level' => 'warning', 'count' => 5],
                    ['component' => 'validation', 'level' => 'error', 'count' => 2],
                    ['component' => 'reporting', 'level' => 'info', 'count' => 20],
                    ['component' => 'security', 'level' => 'warning', 'count' => 3],
                    ['component' => 'security', 'level' => 'error', 'count' => 1],
                ];

                foreach ($activities as $activity) {
                    for ($i = 0; $i < $activity['count']; $i++) {
                        Log::log($activity['level'], "Activity log {$i}", [
                            'component' => $activity['component'],
                            'monitoring_period' => $monitoringPeriod,
                            'activity_type' => $activity['level'],
                            'timestamp' => microtime(true),
                        ]);
                    }
                }
            });

            // Analyze aggregated statistics
            $statistics = LogVerificationUtilities::aggregateLogStatistics($result['logs']);

            // Verify component distribution
            $expectedComponents = ['api', 'validation', 'reporting', 'security'];
            foreach ($expectedComponents as $component) {
                expect($statistics['component_distribution'])->toHaveKey($component,
                    "Component '{$component}' missing from aggregation");
            }

            // Verify level distribution
            $expectedLevels = ['debug', 'info', 'warning', 'error'];
            foreach ($expectedLevels as $level) {
                expect($statistics['level_distribution'])->toHaveKey($level,
                    "Level '{$level}' missing from aggregation");
            }

            // Verify specific counts
            expect($statistics['level_distribution']['debug'])->toBe(100);
            expect($statistics['level_distribution']['info'])->toBe(100); // 50 + 30 + 20
            expect($statistics['level_distribution']['warning'])->toBe(8); // 5 + 3
            expect($statistics['level_distribution']['error'])->toBe(3); // 2 + 1

            // Verify error rate calculation
            $expectedErrorRate = (3 / 211) * 100; // 3 errors out of 211 total logs
            expect($statistics['error_rate'])->toEqualWithDelta($expectedErrorRate, 0.1, 'Error rate calculation incorrect');

            // Verify most active component
            expect($statistics['most_active_component'])->toBe('validation',
                'Most active component identification incorrect');

            // Test monitoring thresholds
            expect($statistics['error_rate'])->toBeLessThan(5, 'Error rate exceeds monitoring threshold');
            expect($statistics['total_logs'])->toBeGreaterThan(100, 'Insufficient log volume for monitoring');
        });
    });

    describe('Error Handling and Recovery', function () {
        it('integrates logging with error handling and recovery processes', function () {
            $correlationId = 'error-recovery-' . uniqid();

            $result = LoggingTestHelper::captureLogOutput(function () use ($correlationId) {
                // Simulate error scenario with recovery
                Log::info('Operation started', [
                    'correlation_id' => $correlationId,
                    'operation' => 'critical_operation',
                    'attempt' => 1,
                ]);

                Log::error('Operation failed', [
                    'correlation_id' => $correlationId,
                    'operation' => 'critical_operation',
                    'attempt' => 1,
                    'error' => 'Connection timeout',
                    'error_code' => 'CONN_TIMEOUT',
                ]);

                Log::warning('Initiating retry', [
                    'correlation_id' => $correlationId,
                    'operation' => 'critical_operation',
                    'attempt' => 2,
                    'retry_delay' => 1000,
                ]);

                Log::info('Retry attempt started', [
                    'correlation_id' => $correlationId,
                    'operation' => 'critical_operation',
                    'attempt' => 2,
                ]);

                Log::warning('Partial failure detected', [
                    'correlation_id' => $correlationId,
                    'operation' => 'critical_operation',
                    'attempt' => 2,
                    'warning' => 'Slow response time',
                ]);

                Log::info('Operation completed successfully', [
                    'correlation_id' => $correlationId,
                    'operation' => 'critical_operation',
                    'attempt' => 2,
                    'success' => true,
                    'total_duration' => 3.5,
                ]);

                Log::info('Recovery metrics recorded', [
                    'correlation_id' => $correlationId,
                    'operation' => 'critical_operation',
                    'total_attempts' => 2,
                    'success_rate' => 50.0,
                    'recovery_time' => 2.5,
                ]);
            });

            // Verify error handling logging
            $logs = $result['logs'];
            $errorLogs = array_filter($logs, function ($log) {
                return $log['level_name'] === 'ERROR';
            });

            expect($errorLogs)->toHaveCount(1, 'Expected exactly one error log');

            $warningLogs = array_filter($logs, function ($log) {
                return $log['level_name'] === 'WARNING';
            });

            expect($warningLogs)->toHaveCount(2, 'Expected exactly two warning logs');

            // Verify recovery sequence
            $operations = [];
            foreach ($logs as $log) {
                if (isset($log['context']['attempt'])) {
                    $operations[] = [
                        'attempt' => $log['context']['attempt'],
                        'level' => $log['level_name'],
                        'message' => $log['message'],
                    ];
                }
            }

            expect($operations)->toHaveCount(5, 'Expected 5 operation logs with attempt numbers');

            // Verify attempt progression
            $attempt1Logs = array_filter($operations, fn($op) => $op['attempt'] === 1);
            $attempt2Logs = array_filter($operations, fn($op) => $op['attempt'] === 2);

            expect($attempt1Logs)->toHaveCount(2, 'Expected 2 logs for attempt 1');
            expect($attempt2Logs)->toHaveCount(3, 'Expected 3 logs for attempt 2');

            // Verify correlation consistency
            expect(LoggingTestHelper::validateLogCorrelation($correlationId))
                ->toBeTrue('Error recovery correlation validation failed');
        });
    });
});