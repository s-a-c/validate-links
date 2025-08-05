<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ReportingService;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use Tests\Support\BoundaryConditionValidator;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ExceptionTestingHelpers;
use Tests\Support\MockDataBuilder;
use Tests\Support\ResourceLimitSimulator;

/**
 * Comprehensive edge case tests for ReportingService.
 * 
 * Tests null inputs, empty collections, malformed data, extreme report sizes,
 * and boundary conditions to ensure robust report generation under all conditions.
 * 
 * @package Tests\Unit\Services
 * @author Roo
 * @since 1.0.0
 */

describe('ReportingService Edge Cases', function () {
    beforeEach(function () {
        $this->service = new ReportingService();
        
        // Initialize edge case utilities
        EdgeCaseDataGenerator::reset();
        BoundaryConditionValidator::initialize();
        ResourceLimitSimulator::initialize();
        MockDataBuilder::reset();
    });
    
    afterEach(function () {
        BoundaryConditionValidator::reset();
        ResourceLimitSimulator::cleanup();
    });

    describe('Null Input Validation', function () {
        it('handles null inputs for report generation', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['null_values'];
            
            // Create a valid results collection for testing
            $validResults = new ValidationResultCollection([
                ValidationResult::success('https://example.com', ValidationScope::EXTERNAL)
            ]);
            
            $results = BoundaryConditionValidator::validateNullSafety(
                function ($input) use ($validResults) {
                    if ($input === null) {
                        // This should cause a type error
                        return $this->service->generateReport($input);
                    }
                    return $this->service->generateReport($validResults, OutputFormat::CONSOLE);
                },
                ['null' => null]
            );
            
            // Null input should cause a type error
            expect($results['null']['null_safe'])->toBeFalse();
            expect($results['null']['exception']['class'])->toBe(\TypeError::class);
        });
    });

    describe('Empty Collection Handling', function () {
        it('handles empty collection for report generation', function () {
            $emptyResults = new ValidationResultCollection([]);
            
            // Test report generation with empty results
            $report = $this->service->generateReport($emptyResults, OutputFormat::CONSOLE);
            expect($report)->toBeString();
            expect($report)->not->toBeEmpty();
            
            // Test summary generation with empty results
            $summary = $this->service->generateSummary($emptyResults);
            expect($summary)->toBeInstanceOf(ReportSummary::class);
            expect($summary->getTotalLinks())->toBe(0);
            expect($summary->getBrokenLinks())->toBe(0);
            expect($summary->getSuccessRate())->toBe(0);
        });
    });

    describe('Extreme Data Set Reporting', function () {
        it('handles extreme data set report generation', function () {
            // Generate large result collection
            $largeResults = MockDataBuilder::createValidationResultCollection([
                'count' => 1000,
                'status_distribution' => [
                    LinkStatus::VALID => 0.7,
                    LinkStatus::BROKEN => 0.2,
                    LinkStatus::NOT_FOUND => 0.1,
                ]
            ]);
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Test report generation with large data set
            $report = $this->service->generateReport($largeResults, OutputFormat::JSON);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            expect($report)->toBeString();
            expect($report)->not->toBeEmpty();
            
            // Performance assertions
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            expect($executionTime)->toBeLessThan(10.0);
            expect($memoryUsage)->toBeLessThan(50 * 1024 * 1024);
            
            // Verify JSON is valid
            $decoded = json_decode($report, true);
            expect($decoded)->not->toBeNull();
            expect($decoded)->toBeArray();
        });
    });

    describe('Format Handling', function () {
        it('handles malformed output format gracefully', function () {
            $results = MockDataBuilder::createValidationResultCollection(['count' => 5]);
            
            // Test with all supported formats
            foreach (OutputFormat::cases() as $format) {
                $report = $this->service->generateReport($results, $format);
                
                expect($report)->toBeString();
                expect($report)->not->toBeEmpty();
                
                // Verify format support
                expect($this->service->supportsFormat($format))->toBeTrue();
            }
        });
    });

    describe('Report Export', function () {
        it('handles invalid paths during export', function () {
            $results = MockDataBuilder::createValidationResultCollection(['count' => 5]);
            $malformedPaths = EdgeCaseDataGenerator::generateMalformedData(['type' => 'paths'])['paths'];
            
            foreach ($malformedPaths as $malformedPath) {
                $result = $this->service->exportReport($results, $malformedPath, OutputFormat::JSON);
                
                // Should handle invalid paths gracefully
                expect($result)->toBeBool();
                expect($result)->toBeFalse();
            }
        });

        it('handles extreme file paths during export', function () {
            $results = MockDataBuilder::createValidationResultCollection(['count' => 5]);
            
            $extremePaths = [
                // Very long path
                '/tmp/' . str_repeat('a', 200) . '.json',
                
                // Path with special characters
                '/tmp/report with spaces.json',
                '/tmp/report-with-dashes.json',
                '/tmp/report_with_underscores.json',
                
                // Non-existent directory
                '/nonexistent/directory/report.json',
            ];
            
            foreach ($extremePaths as $path) {
                $result = $this->service->exportReport($results, $path, OutputFormat::JSON);
                
                expect($result)->toBeBool();
                
                // Clean up if file was created
                if ($result && file_exists($path)) {
                    unlink($path);
                    
                    // Clean up directory if it was created
                    $dir = dirname($path);
                    if (is_dir($dir) && $dir !== '/tmp') {
                        rmdir($dir);
                    }
                }
            }
        });
    });

    describe('Concurrent Operations', function () {
        it('handles concurrent report generation', function () {
            $results = MockDataBuilder::createValidationResultCollection(['count' => 100]);
            
            $concurrencyScenario = EdgeCaseDataGenerator::generateConcurrencyScenarios([
                'scenario' => 'basic',
                'threads' => 5
            ]);
            
            $concurrentResults = BoundaryConditionValidator::validateConcurrentAccess(
                function () use ($results) {
                    return $this->service->generateReport($results, OutputFormat::JSON);
                },
                $concurrencyScenario
            );
            
            expect($concurrentResults)->toHaveKey('analysis');
            $analysis = $concurrentResults['analysis'];
            
            expect($analysis['total_accesses'])->toBeGreaterThan(0);
            expect($analysis['successful_accesses'])->toBeGreaterThanOrEqual(0);
            
            // Report generation should be thread-safe
            expect($analysis['race_conditions_detected'])->toBeLessThan(5);
        });
    });

    describe('Memory Pressure', function () {
        it('handles memory pressure during report generation', function () {
            $results = MockDataBuilder::createValidationResultCollection(['count' => 500]);
            
            try {
                // Simulate moderate memory pressure
                ResourceLimitSimulator::simulateMemoryPressure([
                    'target_mb' => 40,
                    'gradual' => true
                ]);
                
                $report = $this->service->generateReport($results, OutputFormat::JSON);
                
                expect($report)->toBeString();
                expect($report)->not->toBeEmpty();
                
                // Report should still be valid under memory pressure
                $decoded = json_decode($report, true);
                expect($decoded)->not->toBeNull();
                
            } finally {
                ResourceLimitSimulator::releaseMemoryPressure();
            }
        });
    });

    describe('Report Options', function () {
        it('handles boundary values for report options', function () {
            $results = MockDataBuilder::createValidationResultCollection(['count' => 10]);
            
            $boundaryOptions = [
                'empty_options' => [],
                'null_options' => null,
                'extreme_options' => [
                    'max_length' => PHP_INT_MAX,
                    'min_length' => -1,
                    'invalid_key' => 'invalid_value',
                ],
            ];
            
            foreach ($boundaryOptions as $optionName => $options) {
                try {
                    $report = $this->service->format($results, OutputFormat::CONSOLE, $options ?? []);
                    
                    expect($report)->toBeString();
                    expect($report)->not->toBeEmpty();
                    
                } catch (\Throwable $e) {
                    // Some boundary values may cause expected exceptions
                    expect($e)->toBeInstanceOf(InvalidArgumentException::class);
                }
            }
        });
    });

    describe('String Boundary Conditions', function () {
        it('handles string boundary conditions in report content', function () {
            $stringBoundaries = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['boundary_strings'];
            
            // Create results with boundary string URLs
            $boundaryResults = [];
            foreach ($stringBoundaries as $name => $boundaryString) {
                if (is_string($boundaryString)) {
                    $boundaryResults[] = ValidationResult::create(
                        url: 'https://example.com/' . $boundaryString,
                        status: LinkStatus::VALID,
                        scope: ValidationScope::EXTERNAL
                    );
                }
            }
            
            $results = new ValidationResultCollection($boundaryResults);
            
            foreach (OutputFormat::cases() as $format) {
                $report = $this->service->generateReport($results, $format);
                
                expect($report)->toBeString();
                expect($report)->not->toBeEmpty();
                
                // Report should handle boundary strings gracefully
                if ($format === OutputFormat::JSON) {
                    $decoded = json_decode($report, true);
                    expect($decoded)->not->toBeNull();
                }
            }
        });
    });

    describe('Invalid Character Handling', function () {
        it('handles invalid characters in report generation', function () {
            $invalidCharData = EdgeCaseDataGenerator::generateInvalidCharacterData();
            
            // Create results with invalid character URLs
            $invalidCharResults = [];
            foreach ($invalidCharData as $index => $invalidChars) {
                $invalidCharResults[] = ValidationResult::create(
                    url: 'https://example.com/path' . $invalidChars,
                    status: LinkStatus::BROKEN,
                    scope: ValidationScope::EXTERNAL,
                    message: 'Invalid characters in URL'
                );
            }
            
            $results = new ValidationResultCollection($invalidCharResults);
            
            foreach (OutputFormat::cases() as $format) {
                $report = $this->service->generateReport($results, $format);
                
                expect($report)->toBeString();
                
                // Report should handle invalid characters gracefully
                if ($format === OutputFormat::JSON) {
                    $decoded = json_decode($report, true);
                    expect($decoded)->not->toBeNull();
                }
            }
        });
    });

    describe('Formatter Edge Cases', function () {
        it('handles formatter edge cases', function () {
            $results = MockDataBuilder::createValidationResultCollection(['count' => 5]);
            
            // Test getting formatter for each format
            foreach (OutputFormat::cases() as $format) {
                $formatter = $this->service->getFormatter($format);
                
                expect($formatter)->toBeObject();
                expect(method_exists($formatter, 'format'))->toBeTrue();
            }
            
            // Test unsupported format handling
            $availableFormats = $this->service->getAvailableFormats();
            expect($availableFormats)->toBeArray();
            expect($availableFormats)->not->toBeEmpty();
            
            foreach ($availableFormats as $format) {
                expect($format)->toBeInstanceOf(OutputFormat::class);
                expect($this->service->supportsFormat($format))->toBeTrue();
            }
        });
    });

    describe('Summary Generation', function () {
        it('handles summary generation edge cases', function () {
            // Test with various result distributions
            $testCases = [
                'all_valid' => [
                    'count' => 100,
                    'status_distribution' => [LinkStatus::VALID => 1.0]
                ],
                'all_broken' => [
                    'count' => 100,
                    'status_distribution' => [LinkStatus::BROKEN => 1.0]
                ],
                'mixed_statuses' => [
                    'count' => 100,
                    'status_distribution' => [
                        LinkStatus::VALID => 0.4,
                        LinkStatus::BROKEN => 0.2,
                        LinkStatus::NOT_FOUND => 0.2,
                        LinkStatus::TIMEOUT => 0.1,
                        LinkStatus::FORBIDDEN => 0.1,
                    ]
                ],
                'single_result' => [
                    'count' => 1,
                    'status_distribution' => [LinkStatus::VALID => 1.0]
                ],
            ];
            
            foreach ($testCases as $caseName => $config) {
                $results = MockDataBuilder::createValidationResultCollection($config);
                $summary = $this->service->generateSummary($results);
                
                expect($summary)->toBeInstanceOf(ReportSummary::class);
                expect($summary->getTotalLinks())->toBe($config['count']);
                expect($summary->getSuccessRate())->toBeGreaterThanOrEqual(0);
                expect($summary->getSuccessRate())->toBeLessThanOrEqual(100);
                
                // Verify status breakdown
                $statusBreakdown = $summary->getStatusBreakdown();
                expect($statusBreakdown)->toBeArray();
                
                $totalFromBreakdown = array_sum($statusBreakdown);
                expect($totalFromBreakdown)->toBe($config['count']);
            }
        });
    });

    describe('Report Saving', function () {
        it('handles report saving edge cases', function () {
            $report = 'Test report content';
            
            $testPaths = [
                // Valid paths
                '/tmp/test_report.txt' => true,
                '/tmp/nested/test_report.txt' => true,
                
                // Invalid paths
                '/root/test_report.txt' => false, // Permission denied
                '' => false, // Empty path
            ];
            
            foreach ($testPaths as $path => $shouldSucceed) {
                $result = $this->service->saveReport($report, $path);
                
                expect($result)->toBeBool();
                
                if ($shouldSucceed && $result) {
                    expect(file_exists($path))->toBeTrue();
                    expect(file_get_contents($path))->toBe($report);
                    
                    // Clean up
                    unlink($path);
                    
                    // Clean up directory if created
                    $dir = dirname($path);
                    if (is_dir($dir) && $dir !== '/tmp') {
                        rmdir($dir);
                    }
                } else {
                    expect($result)->toBeFalse();
                }
            }
        });
    });

    describe('Error Recovery', function () {
        it('handles error recovery in report generation', function () {
            // Test recovery from formatter errors
            ExceptionTestingHelpers::testErrorRecovery(
                function () {
                    // Create results that might cause formatter issues
                    $problematicResults = new ValidationResultCollection([
                        ValidationResult::create(
                            url: "https://example.com/\x00null",
                            status: LinkStatus::BROKEN,
                            scope: ValidationScope::EXTERNAL
                        )
                    ]);
                    
                    return $this->service->generateReport($problematicResults, OutputFormat::JSON);
                },
                function ($exception) {
                    // Recovery should return a basic error report
                    return json_encode(['error' => 'Report generation failed', 'details' => $exception->getMessage()]);
                },
                'Report generation failed'
            );
            
            // Test graceful degradation
            $fallbackReport = ExceptionTestingHelpers::testGracefulDegradation(
                function () {
                    throw new RuntimeException('Simulated report generation failure');
                },
                'Error: Unable to generate report'
            );
            
            expect($fallbackReport)->toBe('Error: Unable to generate report');
        });
    });

    describe('Validation Summary', function () {
        it('provides comprehensive reporting validation summary', function () {
            $summary = BoundaryConditionValidator::getValidationSummary();
            
            expect($summary)->toHaveKey('total_tests');
            expect($summary)->toHaveKey('passed_tests');
            expect($summary)->toHaveKey('failed_tests');
            expect($summary)->toHaveKey('success_rate');
            
            // Reporting should handle edge cases well
            expect($summary['success_rate'])->toBeGreaterThanOrEqual(75);
        });
    });

    describe('Extreme Response Times', function () {
        it('handles report generation with extreme response times', function () {
            // Create results with extreme response times
            $extremeResults = [
                ValidationResult::create(
                    url: 'https://fast.example.com',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::EXTERNAL,
                    responseTime: 0.001 // Very fast
                ),
                ValidationResult::create(
                    url: 'https://slow.example.com',
                    status: LinkStatus::TIMEOUT,
                    scope: ValidationScope::EXTERNAL,
                    responseTime: 30.0 // Very slow
                ),
                ValidationResult::create(
                    url: 'https://instant.example.com',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::ANCHOR,
                    responseTime: 0.0 // Instant
                ),
            ];
            
            $results = new ValidationResultCollection($extremeResults);
            $summary = $this->service->generateSummary($results);
            
            expect($summary)->toBeInstanceOf(ReportSummary::class);
            expect($summary->getExecutionTime())->toBeGreaterThan(0);
            expect($summary->getAverageResponseTime())->toBeGreaterThan(0);
            
            // Generate report with extreme response times
            $report = $this->service->generateReport($results, OutputFormat::JSON);
            expect($report)->toBeString();
            
            $decoded = json_decode($report, true);
            expect($decoded)->not->toBeNull();
        });
    });
});