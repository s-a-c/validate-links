<?php

declare(strict_types=1);

use Tests\Support\CommandTestHelper;
use Tests\Support\LoggingTestHelper;
use Tests\Support\MockFactory;
use App\Commands\ValidateCommand;
use App\Commands\ReportCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ReportSummary;

/**
 * Comprehensive command integration test suite.
 *
 * Tests:
 * - Integration testing with real command handlers and service dependencies
 * - Command execution with real service dependencies
 * - Command option parsing and validation integration
 * - Command error handling and recovery mechanisms integration
 * - Command output and exit code handling validation
 * - End-to-end command workflow testing
 *
 * @package Tests\Integration
 * @author Roo
 * @since 1.0.0
 */

describe('Command Integration Tests', function () {
    beforeEach(function () {
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr']);
        CommandTestHelper::clearResults();
    });

    afterEach(function () {
        LoggingTestHelper::resetLoggingEnvironment();
        CommandTestHelper::clearResults();
        MockFactory::verifyAndClearMocks();
    });

    it('integrates ValidateCommand with real service dependencies', function () {
        // Create realistic service mocks
        $linkValidation = MockFactory::createLinkValidationService([
            'safe_urls' => ['https://example.com', 'https://github.com'],
            'malicious_urls' => ['https://malicious.com'],
        ]);

        $reporting = MockFactory::createReportingService();

        // Test command execution with various scenarios
        $testScenarios = [
            'basic_validation' => [
                'command' => 'validate',
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'all',
                    '--format' => 'console',
                ],
                'expected_exit_code' => 0,
            ],
            'json_output' => [
                'command' => 'validate',
                'parameters' => [
                    'paths' => ['./docs'],
                    '--scope' => 'external',
                    '--format' => 'json',
                    '--output' => './report.json',
                ],
                'expected_exit_code' => 0,
            ],
            'concurrent_validation' => [
                'command' => 'validate',
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'all',
                    '--concurrent' => '20',
                    '--timeout' => '60',
                ],
                'expected_exit_code' => 0,
            ],
            'interactive_mode' => [
                'command' => 'validate',
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--interactive' => true,
                ],
                'expected_exit_code' => 0,
            ],
        ];

        foreach ($testScenarios as $scenarioName => $scenario) {
            $result = CommandTestHelper::executeCommandWithCapture(
                $scenario['command'],
                $scenario['parameters']
            );

            expect($result['exit_code'])->toBe($scenario['expected_exit_code'])
                ->and($result['output'])->not->toBeEmpty()
                ->and($result['performance']['execution_time'])->toBeLessThan(10.0);
        }
    });

    it('integrates ReportCommand with different output formats', function () {
        $linkValidation = MockFactory::createLinkValidationService();
        $reporting = MockFactory::createReportingService();

        $formatTests = [
            'html_report' => [
                'parameters' => [
                    'paths' => ['./docs'],
                    '--format' => 'html',
                    '--output' => './report.html',
                    '--detailed' => true,
                ],
                'expected_format' => 'html',
            ],
            'markdown_report' => [
                'parameters' => [
                    'paths' => ['./README.md'],
                    '--format' => 'markdown',
                    '--output' => './report.md',
                ],
                'expected_format' => 'markdown',
            ],
            'json_report' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--format' => 'json',
                    '--output' => './report.json',
                ],
                'expected_format' => 'json',
            ],
            'console_report' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--format' => 'console',
                ],
                'expected_format' => 'console',
            ],
        ];

        foreach ($formatTests as $testName => $test) {
            $result = CommandTestHelper::executeCommandWithCapture(
                'report',
                $test['parameters']
            );

            expect($result['exit_code'])->toBe(0)
                ->and($result['output'])->not->toBeEmpty();

            // Verify output format expectations
            $outputExpectations = getOutputExpectationsForFormat($test['expected_format']);
            $outputValidation = CommandTestHelper::assertCommandOutput($result['output'], $outputExpectations);

            expect($outputValidation['valid'])->toBeTrue();
        }
    });

    it('handles command integration with error scenarios and recovery', function () {
        $linkValidation = MockFactory::createLinkValidationService([
            'safe_urls' => ['https://example.com'],
            'malicious_urls' => ['https://malicious.com', 'javascript:alert(1)'],
        ]);

        $reporting = MockFactory::createReportingService();

        $errorScenarios = [
            'invalid_scope' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'invalid_scope',
                ],
                'expected_exit_code' => 1,
                'expected_error_pattern' => '/Invalid scope/',
            ],
            'invalid_format' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--format' => 'xml',
                ],
                'expected_exit_code' => 1,
                'expected_error_pattern' => '/Invalid format/',
            ],
            'out_of_range_timeout' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--timeout' => '999',
                ],
                'expected_exit_code' => 1,
                'expected_error_pattern' => '/timeout.*between/',
            ],
            'invalid_concurrency' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--concurrent' => '100',
                ],
                'expected_exit_code' => 1,
                'expected_error_pattern' => '/concurrency.*between/',
            ],
            'nonexistent_file' => [
                'parameters' => [
                    'paths' => ['./nonexistent.md'],
                    '--scope' => 'all',
                ],
                'expected_exit_code' => 2,
                'expected_error_pattern' => '/not found|does not exist/',
            ],
        ];

        foreach ($errorScenarios as $scenarioName => $scenario) {
            $result = CommandTestHelper::executeCommandWithCapture(
                'validate',
                $scenario['parameters']
            );

            expect($result['exit_code'])->toBe($scenario['expected_exit_code']);

            $errorOutput = $result['error_output'] ?: $result['output'];
            expect($errorOutput)->toMatch($scenario['expected_error_pattern']);
        }
    });

    it('monitors command integration performance', function () {
        $linkValidation = MockFactory::createLinkValidationService();
        $reporting = MockFactory::createReportingService();

        $performanceTests = [
            'small_dataset' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'internal',
                ],
                'max_execution_time' => 5.0,
                'max_memory_usage' => 50 * 1024 * 1024, // 50MB
            ],
            'medium_dataset' => [
                'parameters' => [
                    'paths' => ['./docs'],
                    '--scope' => 'all',
                    '--concurrent' => '10',
                ],
                'max_execution_time' => 15.0,
                'max_memory_usage' => 100 * 1024 * 1024, // 100MB
            ],
            'high_concurrency' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'external',
                    '--concurrent' => '50',
                    '--timeout' => '30',
                ],
                'max_execution_time' => 10.0,
                'max_memory_usage' => 75 * 1024 * 1024, // 75MB
            ],
        ];

        foreach ($performanceTests as $testName => $test) {
            $resourceUsage = CommandTestHelper::monitorResourceUsage(function () use ($test) {
                return CommandTestHelper::executeCommandWithCapture('validate', $test['parameters']);
            });

            expect($resourceUsage['execution_time'])->toBeLessThan($test['max_execution_time'])
                ->and($resourceUsage['memory_usage'])->toBeLessThan($test['max_memory_usage']);

            // Verify command succeeded
            $result = $resourceUsage['result'];
            expect($result['exit_code'])->toBe(0);
        }
    });

    it('validates command integration with logging and output', function () {
        $linkValidation = MockFactory::createLinkValidationService();
        $reporting = MockFactory::createReportingService();

        $correlationId = 'integration-test-' . uniqid();

        $loggingResult = LoggingTestHelper::captureLogOutput(function () use ($correlationId) {
            return CommandTestHelper::executeCommandWithCapture('validate', [
                'paths' => ['./test.md'],
                '--scope' => 'all',
                '--format' => 'console',
                '--verbose' => true,
            ]);
        });

        $commandResult = $loggingResult['result'];
        $logs = $loggingResult['logs'];

        // Verify command execution
        expect($commandResult['exit_code'])->toBe(0)
            ->and($logs)->not->toBeEmpty();

        // Verify output format
        $outputExpectations = [
            'contains' => ['validation', 'links'],
            'patterns' => ['/\d+\s+(links?|files?)/', '/✅|❌|⚠️/'],
            'min_length' => 10,
        ];

        $outputValidation = CommandTestHelper::assertCommandOutput($commandResult['output'], $outputExpectations);
        expect($outputValidation['valid'])->toBeTrue();

        // Verify performance logging
        $performance = CommandTestHelper::assessCommandPerformance('validate');
        expect($performance['performance_rating'])->toBeIn(['excellent', 'good', 'acceptable']);
    });

    it('tests command integration with configuration combinations', function () {
        $linkValidation = MockFactory::createLinkValidationService();
        $reporting = MockFactory::createReportingService();

        $configurationTests = [
            'minimal_config' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                ],
                'description' => 'Minimal configuration with defaults',
            ],
            'comprehensive_config' => [
                'parameters' => [
                    'paths' => ['./test.md', './docs'],
                    '--scope' => 'internal,external',
                    '--format' => 'json',
                    '--output' => './comprehensive-report.json',
                    '--timeout' => '60',
                    '--concurrent' => '15',
                    '--max-redirects' => '10',
                    '--user-agent' => 'integration-test-agent/1.0',
                    '--follow-redirects' => true,
                    '--no-cache' => true,
                ],
                'description' => 'Comprehensive configuration with all options',
            ],
            'security_focused' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'external',
                    '--check-external' => true,
                    '--timeout' => '30',
                    '--user-agent' => 'security-scanner/1.0',
                ],
                'description' => 'Security-focused validation',
            ],
            'performance_optimized' => [
                'parameters' => [
                    'paths' => ['./docs'],
                    '--scope' => 'internal',
                    '--concurrent' => '25',
                    '--timeout' => '10',
                    '--no-cache' => true,
                ],
                'description' => 'Performance-optimized configuration',
            ],
            'ci_mode' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'all',
                    '--format' => 'json',
                    '--ci' => true,
                    '--fail-fast' => true,
                ],
                'description' => 'CI/CD pipeline configuration',
            ],
        ];

        foreach ($configurationTests as $testName => $test) {
            $result = CommandTestHelper::executeCommandWithCapture('validate', $test['parameters']);

            expect($result['exit_code'])->toBe(0)
                ->and($result['output'])->not->toBeEmpty();

            // Verify performance thresholds
            $thresholds = [
                'max_execution_time' => 30.0,
                'max_memory_usage' => 100 * 1024 * 1024, // 100MB
                'expected_exit_code' => 0,
            ];

            $performanceValidation = CommandTestHelper::validatePerformanceThresholds(
                $result['performance'],
                $thresholds
            );

            expect($performanceValidation['passed'])->toBeTrue();
        }
    });

    it('handles command integration with file system operations', function () {
        $linkValidation = MockFactory::createLinkValidationService();
        $reporting = MockFactory::createReportingService();

        // Create temporary test files
        $tempDir = sys_get_temp_dir() . '/validate-links-test-' . uniqid();
        mkdir($tempDir, 0755, true);

        $testFiles = [
            'simple.md' => '# Test\n[Link](https://example.com)',
            'complex.md' => '# Complex\n[Internal](./simple.md)\n[External](https://github.com)\n[Anchor](#section)',
            'empty.md' => '',
            'unicode.md' => '# 测试\n[链接](https://example.com)',
        ];

        foreach ($testFiles as $filename => $content) {
            file_put_contents($tempDir . '/' . $filename, $content);
        }

        try {
            $fileSystemTests = [
                'single_file' => [
                    'parameters' => [
                        'paths' => [$tempDir . '/simple.md'],
                        '--scope' => 'all',
                    ],
                ],
                'multiple_files' => [
                    'parameters' => [
                        'paths' => [$tempDir . '/simple.md', $tempDir . '/complex.md'],
                        '--scope' => 'all',
                    ],
                ],
                'directory_scan' => [
                    'parameters' => [
                        'paths' => [$tempDir],
                        '--scope' => 'internal',
                    ],
                ],
                'unicode_file' => [
                    'parameters' => [
                        'paths' => [$tempDir . '/unicode.md'],
                        '--scope' => 'external',
                    ],
                ],
                'output_to_file' => [
                    'parameters' => [
                        'paths' => [$tempDir . '/simple.md'],
                        '--scope' => 'all',
                        '--format' => 'json',
                        '--output' => $tempDir . '/report.json',
                    ],
                ],
            ];

            foreach ($fileSystemTests as $testName => $test) {
                $result = CommandTestHelper::executeCommandWithCapture('validate', $test['parameters']);

                expect($result['exit_code'])->toBe(0)
                    ->and($result['output'])->not->toBeEmpty();

                // Verify output file creation if specified
                if (isset($test['parameters']['--output'])) {
                    $outputFile = $test['parameters']['--output'];
                    expect($outputFile)->toBeFile()
                        ->and(filesize($outputFile))->toBeGreaterThan(0);
                }
            }

        } finally {
            // Cleanup temporary files
            array_map('unlink', glob($tempDir . '/*'));
            rmdir($tempDir);
        }
    });

    it('tests command integration with concurrent execution scenarios', function () {
        $linkValidation = MockFactory::createLinkValidationService();
        $reporting = MockFactory::createReportingService();

        $concurrentTests = [
            'low_concurrency' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'external',
                    '--concurrent' => '5',
                    '--timeout' => '30',
                ],
                'expected_performance' => 'good',
            ],
            'medium_concurrency' => [
                'parameters' => [
                    'paths' => ['./docs'],
                    '--scope' => 'all',
                    '--concurrent' => '15',
                    '--timeout' => '45',
                ],
                'expected_performance' => 'acceptable',
            ],
            'high_concurrency' => [
                'parameters' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'external',
                    '--concurrent' => '30',
                    '--timeout' => '60',
                ],
                'expected_performance' => 'acceptable',
            ],
        ];

        foreach ($concurrentTests as $testName => $test) {
            $resourceUsage = CommandTestHelper::monitorResourceUsage(function () use ($test) {
                return CommandTestHelper::executeCommandWithCapture('validate', $test['parameters']);
            });

            $result = $resourceUsage['result'];
            expect($result['exit_code'])->toBe(0);

            // Verify performance is within acceptable bounds
            $performance = CommandTestHelper::assessCommandPerformance('validate');
            expect($performance['performance_rating'])->toBeIn(['excellent', 'good', 'acceptable']);

            // Verify resource usage is reasonable
            expect($resourceUsage['execution_time'])->toBeLessThan(20.0);
        }
    });
});

/**
 * Get output expectations for different formats.
 *
 * @param string $format Output format
 * @return array<string, mixed> Output expectations
 */
function getOutputExpectationsForFormat(string $format): array
{
    return match ($format) {
        'json' => [
            'format' => 'json',
            'patterns' => ['/^\{.*\}$/s'],
            'min_length' => 10,
        ],
        'html' => [
            'contains' => ['<html>', '<body>', '</html>'],
            'patterns' => ['/<[^>]+>/'],
            'min_length' => 50,
        ],
        'markdown' => [
            'contains' => ['#'],
            'patterns' => ['/^#+\s+/', '/\[.*\]\(.*\)/'],
            'min_length' => 20,
        ],
        'console' => [
            'patterns' => ['/\d+/', '/links?/', '/validation/i'],
            'min_length' => 10,
        ],
        default => [
            'min_length' => 1,
        ],
    };
}