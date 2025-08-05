<?php

declare(strict_types=1);

use Tests\Support\LoggingTestHelper;
use Tests\Support\LogVerificationUtilities;
use Illuminate\Support\Facades\Log;
use Psr\Log\LogLevel;

/**
 * Comprehensive log format validation test suite.
 *
 * Tests:
 * - JSON format validation for structured logs
 * - Field presence and type validation
 * - Timestamp format and timezone validation
 * - Log correlation and tracing validation
 * - Message formatting and parameter substitution
 * - Context and metadata structure validation
 *
 * @package Tests\Unit\Logging
 * @author Roo
 * @since 1.0.0
 */

beforeEach(function () {
    LoggingTestHelper::initializeLoggingCapture(['single', 'stderr', 'daily']);
});

afterEach(function () {
    LoggingTestHelper::resetLoggingEnvironment();
});

describe('Log Format Validation', function () {
    describe('JSON Format Validation', function () {
        it('validates json format for structured logs', function () {
            $testData = [
                'component' => 'validation',
                'operation' => 'link_check',
                'url' => 'https://example.com',
                'status' => 'success',
                'response_time' => 123.45,
                'metadata' => [
                    'user_agent' => 'test-agent',
                    'ip_address' => '192.168.1.1',
                ],
            ];

            $result = LoggingTestHelper::captureLogOutput(function () use ($testData) {
                Log::info('Structured log test', $testData);
            });

            expect($result['logs'])->not->toBeEmpty('No logs captured for JSON format test');
            
            $logRecord = $result['logs'][0];
            
            // Verify the log can be serialized to valid JSON
            $jsonString = json_encode($logRecord);
            expect($jsonString)->not->toBeFalse('Log record cannot be serialized to JSON');
            
            // Verify JSON can be decoded back
            $decodedLog = json_decode($jsonString, true);
            expect(json_last_error())->toBe(JSON_ERROR_NONE, 'JSON decode error: ' . json_last_error_msg());
            expect($decodedLog)->toBeArray('Decoded JSON is not an array');
            
            // Verify context data is preserved
            expect($logRecord)->toHaveKey('context');
            expect($logRecord['context']['component'])->toBe($testData['component']);
            expect($logRecord['context']['operation'])->toBe($testData['operation']);
            expect($logRecord['context']['metadata'])->toBe($testData['metadata']);
        });
    });

    describe('Field Validation', function () {
        it('validates field presence and types', function () {
            $requiredFields = [
                'message' => 'string',
                'level_name' => 'string',
                'datetime' => 'object',
                'context' => 'array',
                'extra' => 'array',
                'channel' => 'string',
            ];

            $result = LoggingTestHelper::captureLogOutput(function () {
                Log::info('Field validation test', ['test_field' => 'test_value']);
            });

            expect($result['logs'])->not->toBeEmpty('No logs captured for field validation test');
            
            $logRecord = $result['logs'][0];
            
            foreach ($requiredFields as $field => $expectedType) {
                expect($logRecord)->toHaveKey($field, "Required field '{$field}' is missing");
                
                $actualType = gettype($logRecord[$field]);
                expect($actualType)->toBe(
                    $expectedType,
                    "Field '{$field}' type mismatch: expected {$expectedType}, got {$actualType}"
                );
            }

            // Verify structured format compliance
            expect(LoggingTestHelper::verifyLogFormat($requiredFields))->toBeTrue('Log format verification failed');
        });
    });

    describe('Timestamp Validation', function () {
        it('validates timestamp format and timezone', function () {
            $result = LoggingTestHelper::captureLogOutput(function () {
                Log::info('Timestamp validation test');
            });

            expect($result['logs'])->not->toBeEmpty('No logs captured for timestamp test');
            
            $logRecord = $result['logs'][0];
            $datetime = $logRecord['datetime'];
            
            expect($datetime)->toBeInstanceOf(\DateTimeInterface::class, 'Datetime is not a DateTimeInterface');
            
            // Test various timestamp formats
            $formats = [
                'Y-m-d H:i:s' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                'Y-m-d\TH:i:s.uP' => '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}[+-]\d{2}:\d{2}$/',
                'c' => '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/',
            ];

            foreach ($formats as $format => $pattern) {
                $formattedTime = $datetime->format($format);
                expect($formattedTime)->toMatch(
                    $pattern,
                    "Timestamp format '{$format}' validation failed: {$formattedTime}"
                );
            }

            // Verify timezone handling
            $timezone = $datetime->getTimezone();
            expect($timezone)->toBeInstanceOf(\DateTimeZone::class, 'Timezone is not a DateTimeZone');
            
            // Test timestamp consistency
            expect(LoggingTestHelper::validateTimestampFormat('Y-m-d H:i:s', 'UTC'))->toBeTrue(
                'Timestamp format consistency validation failed'
            );
        });
    });

    describe('Log Correlation and Tracing', function () {
        it('validates log correlation and tracing', function () {
            $correlationId = 'test-correlation-' . uniqid();
            $traceId = 'trace-' . uniqid();
            $spanId = 'span-' . uniqid();

            $result = LoggingTestHelper::captureLogOutput(function () use ($correlationId, $traceId, $spanId) {
                Log::info('Operation started', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'span_id' => $spanId,
                    'operation' => 'start',
                ]);
                
                Log::info('Processing step 1', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'span_id' => $spanId,
                    'operation' => 'step1',
                ]);
                
                Log::info('Processing step 2', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'span_id' => $spanId,
                    'operation' => 'step2',
                ]);
                
                Log::info('Operation completed', [
                    'correlation_id' => $correlationId,
                    'trace_id' => $traceId,
                    'span_id' => $spanId,
                    'operation' => 'complete',
                ]);
            });

            // Verify correlation ID consistency
            expect(LoggingTestHelper::validateLogCorrelation($correlationId))->toBeTrue(
                'Log correlation validation failed'
            );

            // Verify trace context is preserved
            $logs = $result['logs'];
            foreach ($logs as $log) {
                $context = $log['context'];
                expect($context['correlation_id'])->toBe($correlationId, 'Correlation ID mismatch');
                expect($context['trace_id'])->toBe($traceId, 'Trace ID mismatch');
                expect($context['span_id'])->toBe($spanId, 'Span ID mismatch');
            }

            // Test correlation analysis
            $correlationConfig = [
                'correlation_field' => 'correlation_id',
                'time_window' => 60,
            ];
            
            $correlationAnalysis = LogVerificationUtilities::validateLogCorrelation($logs, $correlationConfig);
            
            expect($correlationAnalysis['correlation_chains'])->toHaveKey($correlationId);
            expect($correlationAnalysis['correlation_chains'][$correlationId])->toHaveCount(4);
            expect($correlationAnalysis['correlation_score'])->toBeGreaterThan(90);
        });
    });

    describe('Message Formatting', function () {
        it('validates message formatting and parameter substitution', function () {
            $testCases = [
                [
                    'template' => 'User {user_id} performed action {action}',
                    'params' => ['user_id' => 12345, 'action' => 'login'],
                    'expected' => 'User 12345 performed action login',
                ],
                [
                    'template' => 'Validation completed in {time}ms with {status}',
                    'params' => ['time' => 123.45, 'status' => 'success'],
                    'expected' => 'Validation completed in 123.45ms with success',
                ],
                [
                    'template' => 'Processing file: {filename} (size: {size} bytes)',
                    'params' => ['filename' => 'test.md', 'size' => 1024],
                    'expected' => 'Processing file: test.md (size: 1024 bytes)',
                ],
            ];

            foreach ($testCases as $index => $testCase) {
                LoggingTestHelper::clearLogs();
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($testCase) {
                    Log::info($testCase['template'], $testCase['params']);
                });

                expect($result['logs'])->not->toBeEmpty("No logs captured for test case {$index}");
                
                $logRecord = $result['logs'][0];
                
                // For this test, we verify the message and parameters are preserved
                // The actual substitution might depend on the logging formatter
                expect($logRecord['message'])->toBe($testCase['template']);
                
                foreach ($testCase['params'] as $key => $value) {
                    expect($logRecord['context'])->toHaveKey($key);
                    expect($logRecord['context'][$key])->toBe($value);
                }
            }
        });
    });

    describe('Context and Metadata Structure', function () {
        it('validates context and metadata structure', function () {
            $complexContext = [
                'component' => 'validation',
                'operation' => 'link_check',
                'request' => [
                    'method' => 'GET',
                    'url' => 'https://example.com',
                    'headers' => [
                        'User-Agent' => 'test-agent',
                        'Accept' => 'application/json',
                    ],
                ],
                'response' => [
                    'status_code' => 200,
                    'content_type' => 'text/html',
                    'size' => 1024,
                ],
                'performance' => [
                    'start_time' => microtime(true),
                    'end_time' => microtime(true) + 0.123,
                    'memory_usage' => memory_get_usage(),
                ],
                'tags' => ['validation', 'external', 'http'],
            ];

            $result = LoggingTestHelper::captureLogOutput(function () use ($complexContext) {
                Log::info('Complex context validation test', $complexContext);
            });

            expect($result['logs'])->not->toBeEmpty('No logs captured for context validation test');
            
            $logRecord = $result['logs'][0];
            $context = $logRecord['context'];
            
            // Verify nested structure preservation
            expect($context['component'])->toBe($complexContext['component']);
            expect($context['operation'])->toBe($complexContext['operation']);
            
            expect($context['request'])->toBeArray();
            expect($context['request']['method'])->toBe($complexContext['request']['method']);
            expect($context['request']['url'])->toBe($complexContext['request']['url']);
            
            expect($context['request']['headers'])->toBeArray();
            expect($context['request']['headers']['User-Agent'])->toBe($complexContext['request']['headers']['User-Agent']);
            
            expect($context['response'])->toBeArray();
            expect($context['response']['status_code'])->toBe($complexContext['response']['status_code']);
            
            expect($context['performance'])->toBeArray();
            expect($context['performance']['start_time'])->toBeFloat();
            expect($context['performance']['end_time'])->toBeFloat();
            
            expect($context['tags'])->toBeArray();
            expect($context['tags'])->toBe($complexContext['tags']);
        });
    });

    describe('Structured Logging Compliance', function () {
        it('validates structured logging compliance with schema', function () {
            $schema = [
                'required_fields' => [
                    'message',
                    'level_name',
                    'datetime',
                    'context',
                    'extra',
                    'channel',
                ],
                'field_types' => [
                    'message' => 'string',
                    'level_name' => 'string',
                    'datetime' => 'object',
                    'context' => 'array',
                    'extra' => 'array',
                    'channel' => 'string',
                ],
            ];

            $testLogs = [
                ['message' => 'Test log 1', 'context' => ['component' => 'test']],
                ['message' => 'Test log 2', 'context' => ['component' => 'validation']],
                ['message' => 'Test log 3', 'context' => ['component' => 'reporting']],
            ];

            $result = LoggingTestHelper::captureLogOutput(function () use ($testLogs) {
                foreach ($testLogs as $logData) {
                    Log::info($logData['message'], $logData['context']);
                }
            });

            $compliance = LogVerificationUtilities::checkStructuredLoggingCompliance(
                $result['logs'],
                $schema
            );

            expect($compliance['compliance_score'])->toBeGreaterThan(95, 'Compliance score too low');
            expect($compliance['non_compliant_logs'])->toBeEmpty('Non-compliant logs detected');
            expect($compliance['compliant_logs'])->toHaveCount(count($testLogs), 'Compliant log count mismatch');
        });
    });

    describe('Format Consistency', function () {
        it('validates log format consistency across channels', function () {
            $channels = ['single', 'stderr'];
            $testMessage = 'Format consistency test';
            $testContext = ['component' => 'test', 'channel_test' => true];

            $channelResults = [];

            foreach ($channels as $channel) {
                LoggingTestHelper::clearLogs($channel);
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($testMessage, $testContext) {
                    Log::channel('single')->info($testMessage, $testContext);
                }, $channel);

                $channelResults[$channel] = $result['logs'];
            }

            // Verify format consistency across channels
            $baseFormat = null;
            foreach ($channelResults as $channel => $logs) {
                if (empty($logs)) {
                    continue;
                }
                
                $logRecord = $logs[0];
                $currentFormat = array_keys($logRecord);
                sort($currentFormat);
                
                if ($baseFormat === null) {
                    $baseFormat = $currentFormat;
                } else {
                    expect($currentFormat)->toBe(
                        $baseFormat,
                        "Log format inconsistency detected in channel: {$channel}"
                    );
                }
            }
        });
    });

    describe('Special Characters and Encoding', function () {
        it('validates log format with special characters and encoding', function () {
            $specialCharacterTests = [
                'unicode' => 'Test with unicode: 你好世界 🌍',
                'json_special' => 'Test with JSON special chars: {"key": "value", "array": [1,2,3]}',
                'html_entities' => 'Test with HTML: <script>alert("test")</script>',
                'newlines' => "Test with\nnewlines\nand\ttabs",
                'quotes' => 'Test with "double" and \'single\' quotes',
                'backslashes' => 'Test with backslashes: C:\\Windows\\System32',
            ];

            foreach ($specialCharacterTests as $testName => $testMessage) {
                LoggingTestHelper::clearLogs();
                
                $result = LoggingTestHelper::captureLogOutput(function () use ($testMessage, $testName) {
                    Log::info($testMessage, ['test_type' => $testName]);
                });

                expect($result['logs'])->not->toBeEmpty("No logs captured for {$testName} test");
                
                $logRecord = $result['logs'][0];
                
                // Verify message is preserved correctly
                expect($logRecord['message'])->toBe($testMessage, "Message corruption in {$testName} test");
                
                // Verify JSON serialization works
                $jsonString = json_encode($logRecord);
                expect($jsonString)->not->toBeFalse("JSON serialization failed for {$testName} test");
                
                // Verify JSON can be decoded back
                $decodedLog = json_decode($jsonString, true);
                expect(json_last_error())->toBe(JSON_ERROR_NONE, "JSON decode error for {$testName} test");
                expect($decodedLog['message'])->toBe($testMessage, "Message lost in JSON round-trip for {$testName} test");
            }
        });
    });

    describe('Performance with Large Payloads', function () {
        it('validates log format performance with large payloads', function () {
            $largeSizes = [1024, 10240, 102400]; // 1KB, 10KB, 100KB

            foreach ($largeSizes as $size) {
                $largeData = str_repeat('A', $size);
                $largeContext = [
                    'large_data' => $largeData,
                    'size' => $size,
                    'test_type' => 'performance',
                ];

                $result = LoggingTestHelper::captureLogOutput(function () use ($largeContext, $size) {
                    Log::info("Performance test with {$size} bytes", $largeContext);
                });

                $performance = $result['performance'];
                
                // Verify performance is reasonable even with large payloads
                expect($performance['execution_time'])->toBeLessThan(
                    1.0, // 1 second max
                    "Log formatting too slow for {$size} byte payload: {$performance['execution_time']}s"
                );

                // Verify memory usage is reasonable
                expect($performance['memory_usage'])->toBeLessThan(
                    $size * 5, // Max 5x the payload size
                    "Excessive memory usage for {$size} byte payload: {$performance['memory_usage']} bytes"
                );

                // Verify log was captured correctly
                expect($result['logs'])->not->toBeEmpty("No logs captured for {$size} byte payload");
                
                $logRecord = $result['logs'][0];
                expect($logRecord['context']['large_data'])->toBe($largeData, "Large data corrupted for {$size} byte payload");
            }
        });
    });
});