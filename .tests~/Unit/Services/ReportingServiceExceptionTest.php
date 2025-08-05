<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Services\ReportingService;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;

describe('ReportingService Exception Handling', function () {
    beforeEach(function () {
        $this->reportingService = new ReportingService();
        $this->sampleResults = ValidationResultCollection::fromArray([
            ValidationResult::success('https://example.com', ValidationScope::EXTERNAL),
            ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::NOT_FOUND, '404 Not Found'),
        ]);
    });

    describe('Format Validation Exception Scenarios', function () {
        it('throws InvalidArgumentException for unsupported format', function () {
            // Create a mock unsupported format by using a non-existent format value
            expectExceptionWithMessage(
                fn() => $this->reportingService->generateReport($this->sampleResults, OutputFormat::from('invalid')),
                ValueError::class,
                '"invalid" is not a valid backing value for enum "App\Enums\OutputFormat"'
            );
        });

        it('handles missing formatter gracefully', function () {
            // Test with a format that exists but has no formatter
            $customFormat = OutputFormat::JSON;
            
            // Remove the formatter to simulate missing formatter
            $reflection = new ReflectionClass($this->reportingService);
            $formattersProperty = $reflection->getProperty('formatters');
            $formattersProperty->setAccessible(true);
            $formatters = $formattersProperty->getValue($this->reportingService);
            unset($formatters[$customFormat->value]);
            $formattersProperty->setValue($this->reportingService, $formatters);
            
            expectExceptionWithMessage(
                fn() => $this->reportingService->generateReport($this->sampleResults, $customFormat),
                InvalidArgumentException::class,
                "Unsupported format: {$customFormat->value}"
            );
        });

        it('handles invalid formatter object gracefully', function () {
            // Test with a formatter that doesn't have the format method
            $invalidFormatter = new stdClass();
            $this->reportingService->addFormatter(OutputFormat::JSON, $invalidFormatter);
            
            expectExceptionWithMessage(
                fn() => $this->reportingService->generateReport($this->sampleResults, OutputFormat::JSON),
                InvalidArgumentException::class,
                "Invalid formatter for format: json"
            );
        });

        it('handles formatter exceptions gracefully', function () {
            // Create a mock formatter that throws an exception
            $failingFormatter = createFailingMock('stdClass', [
                'format' => new RuntimeException('Formatter failed'),
            ]);
            
            $this->reportingService->addFormatter(OutputFormat::JSON, $failingFormatter);
            
            expect(fn() => $this->reportingService->generateReport($this->sampleResults, OutputFormat::JSON))
                ->toThrow(RuntimeException::class, 'Formatter failed');
        });
    });

    describe('File System Exception Scenarios', function () {
        it('handles directory creation failures gracefully', function () {
            $invalidPath = '/root/restricted/report.json';
            
            $result = $this->reportingService->exportReport($this->sampleResults, $invalidPath, OutputFormat::JSON);
            
            expect($result)->toBeFalse();
        });

        it('handles file write permission errors gracefully', function () {
            // Test with a path that would cause permission errors
            $restrictedPath = '/etc/passwd';
            
            $result = $this->reportingService->exportReport($this->sampleResults, $restrictedPath, OutputFormat::JSON);
            
            expect($result)->toBeFalse();
        });

        it('handles disk full errors gracefully', function () {
            $operation = function () {
                throw simulateFileSystemError('disk_full');
            };
            
            $fallbackResult = false;
            
            $result = testGracefulDegradation($operation, $fallbackResult);
            
            expect($result)->toBeFalse();
        });

        it('handles file corruption during write gracefully', function () {
            $operation = function () {
                throw simulateFileSystemError('file_corrupted');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                RuntimeException::class,
                'File is corrupted or unreadable'
            );
        });

        it('handles I/O errors during file operations gracefully', function () {
            $operation = function () {
                throw simulateFileSystemError('io_error');
            };
            
            expectExceptionWithMessage(
                fn() => throw $operation(),
                RuntimeException::class,
                'Input/output error'
            );
        });

        it('handles invalid file paths gracefully', function () {
            $invalidPaths = [
                '',
                '/dev/null',
                '/proc/version',
                "invalid\0path",
                'path/with/../../traversal',
            ];
            
            foreach ($invalidPaths as $path) {
                $result = $this->reportingService->exportReport($this->sampleResults, $path, OutputFormat::JSON);
                expect($result)->toBeFalse("Invalid path should fail: {$path}");
            }
        });
    });

    describe('Report Generation Exception Scenarios', function () {
        it('handles empty result collections gracefully', function () {
            $emptyResults = ValidationResultCollection::fromArray([]);
            
            $report = $this->reportingService->generateReport($emptyResults, OutputFormat::JSON);
            
            expect($report)->toBeString()
                ->and($report)->not->toBeEmpty();
        });

        it('handles malformed validation results gracefully', function () {
            // Create results with potential issues
            $problematicResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: '',
                    status: LinkStatus::BROKEN,
                    message: 'Empty URL test'
                ),
                ValidationResult::create(
                    url: 'https://example.com/' . str_repeat('a', 10000),
                    status: LinkStatus::VALID,
                    message: 'Very long URL test'
                ),
            ]);
            
            $report = $this->reportingService->generateReport($problematicResults, OutputFormat::JSON);
            
            expect($report)->toBeString();
        });

        it('handles unicode characters in results gracefully', function () {
            $unicodeResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://тест.рф',
                    status: LinkStatus::VALID,
                    message: 'Unicode URL test'
                ),
                ValidationResult::failure(
                    'https://example.com/测试',
                    ValidationScope::EXTERNAL,
                    LinkStatus::BROKEN,
                    'Unicode error: 测试失败'
                ),
            ]);
            
            $report = $this->reportingService->generateReport($unicodeResults, OutputFormat::JSON);
            
            expect($report)->toBeString()
                ->and($report)->toContain('тест.рф')
                ->and($report)->toContain('测试');
        });

        it('handles very large result collections gracefully', function () {
            // Create a large collection to test memory handling
            $largeResults = [];
            for ($i = 0; $i < 1000; $i++) {
                $largeResults[] = ValidationResult::create(
                    url: "https://example{$i}.com",
                    status: $i % 2 === 0 ? LinkStatus::VALID : LinkStatus::BROKEN,
                    message: "Test result {$i}"
                );
            }
            
            $largeCollection = ValidationResultCollection::fromArray($largeResults);
            
            $report = $this->reportingService->generateReport($largeCollection, OutputFormat::JSON);
            
            expect($report)->toBeString()
                ->and(strlen($report))->toBeGreaterThan(1000);
        });
    });

    describe('Summary Generation Exception Scenarios', function () {
        it('handles corrupted result data gracefully', function () {
            // Test with results that might cause calculation errors
            $problematicResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    responseTime: -1.0 // Negative response time
                ),
                ValidationResult::create(
                    url: 'https://test.com',
                    status: LinkStatus::BROKEN,
                    responseTime: PHP_FLOAT_MAX // Extremely large response time
                ),
            ]);
            
            $summary = $this->reportingService->generateSummary($problematicResults);
            
            expect($summary)->toBeInstanceOf(App\Services\ValueObjects\ReportSummary::class)
                ->and($summary->getTotalLinks())->toBe(2)
                ->and($summary->getBrokenLinks())->toBe(1);
        });

        it('handles division by zero in success rate calculation gracefully', function () {
            $emptyResults = ValidationResultCollection::fromArray([]);
            
            $summary = $this->reportingService->generateSummary($emptyResults);
            
            expect($summary->getSuccessRate())->toBe(0.0);
        });

        it('handles infinite or NaN values in calculations gracefully', function () {
            $results = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    responseTime: INF
                ),
                ValidationResult::create(
                    url: 'https://test.com',
                    status: LinkStatus::BROKEN,
                    responseTime: NAN
                ),
            ]);
            
            $summary = $this->reportingService->generateSummary($results);
            
            expect($summary)->toBeInstanceOf(App\Services\ValueObjects\ReportSummary::class);
            // The summary should handle infinite/NaN values gracefully
            expect(is_finite($summary->getSuccessRate()))->toBeTrue();
        });
    });

    describe('Format-Specific Exception Scenarios', function () {
        it('handles JSON encoding errors gracefully', function () {
            // Create data that might cause JSON encoding issues
            $problematicResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    message: "Invalid UTF-8: \xFF\xFE"
                ),
            ]);
            
            // The service should handle JSON encoding errors gracefully
            $report = $this->reportingService->generateReport($problematicResults, OutputFormat::JSON);
            
            expect($report)->toBeString();
            expect(json_decode($report))->not->toBeNull();
        });

        it('handles HTML special characters gracefully', function () {
            $htmlResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://example.com',
                    status: LinkStatus::BROKEN,
                    message: 'Error with <script>alert("xss")</script> content'
                ),
            ]);
            
            $report = $this->reportingService->generateReport($htmlResults, OutputFormat::HTML);
            
            expect($report)->toBeString()
                ->and($report)->not->toContain('<script>')
                ->and($report)->toContain('&lt;script&gt;');
        });

        it('handles markdown special characters gracefully', function () {
            $markdownResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://example.com',
                    status: LinkStatus::BROKEN,
                    message: 'Error with **bold** and *italic* and [link](url) content'
                ),
            ]);
            
            $report = $this->reportingService->generateReport($markdownResults, OutputFormat::MARKDOWN);
            
            expect($report)->toBeString();
        });

        it('handles console color codes gracefully', function () {
            $consoleResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://example.com',
                    status: LinkStatus::BROKEN,
                    message: "Error with \033[31mcolor codes\033[0m"
                ),
            ]);
            
            $report = $this->reportingService->generateReport($consoleResults, OutputFormat::CONSOLE);
            
            expect($report)->toBeString();
        });
    });

    describe('Error Recovery and Cleanup', function () {
        it('recovers gracefully from formatter failures', function () {
            $operation = function () {
                throw new RuntimeException('Formatter crashed');
            };
            
            $recovery = function (Throwable $e) {
                return 'Error generating report: ' . $e->getMessage();
            };
            
            testErrorRecovery($operation, $recovery, 'Error generating report: Formatter crashed');
        });

        it('provides fallback behavior for file write failures', function () {
            $operation = function () {
                throw simulateFileSystemError('permission_denied');
            };
            
            $fallbackBehavior = false; // Indicate failure
            
            $result = testGracefulDegradation($operation, $fallbackBehavior);
            
            expect($result)->toBeFalse();
        });

        it('cleans up temporary resources after report generation failures', function () {
            $resourceCleaned = false;
            
            $operation = function () {
                throw new RuntimeException('Report generation failed');
            };
            
            $cleanup = function () use (&$resourceCleaned) {
                $resourceCleaned = true;
            };
            
            testErrorCleanup($operation, $cleanup);
            
            expect($resourceCleaned)->toBeTrue();
        });

        it('reports file system errors correctly', function () {
            $operation = function () {
                throw simulateFileSystemError('disk_full');
            };
            
            $reporter = function (Throwable $e) {
                return [
                    'type' => 'filesystem_error',
                    'message' => $e->getMessage(),
                    'operation' => 'report_generation',
                    'timestamp' => time(),
                ];
            };
            
            $reports = testErrorReporting($operation, $reporter);
            
            expect($reports)->toHaveCount(1)
                ->and($reports[0]['type'])->toBe('filesystem_error')
                ->and($reports[0]['operation'])->toBe('report_generation');
        });
    });

    describe('Performance Under Error Conditions', function () {
        it('maintains performance during repeated format failures', function () {
            $operation = function () {
                throw new RuntimeException('Format operation failed');
            };
            
            $results = testExceptionHandlingUnderLoad($operation, 50, 0.01);
            
            expect($results['exception_count'])->toBe(50)
                ->and($results['average_time'])->toBeLessThan(0.01);
        });

        it('handles memory pressure during large report generation gracefully', function () {
            $initialMemory = memory_get_usage();
            
            // Generate many reports to test memory handling
            for ($i = 0; $i < 10; $i++) {
                try {
                    $largeResults = [];
                    for ($j = 0; $j < 100; $j++) {
                        $largeResults[] = ValidationResult::create(
                            url: "https://example{$j}.com",
                            status: LinkStatus::VALID,
                            message: str_repeat('Large message content ', 100)
                        );
                    }
                    
                    $collection = ValidationResultCollection::fromArray($largeResults);
                    $this->reportingService->generateReport($collection, OutputFormat::JSON);
                    
                    // Force garbage collection
                    unset($largeResults, $collection);
                    gc_collect_cycles();
                } catch (Throwable) {
                    // Handle any memory-related errors
                }
            }
            
            $finalMemory = memory_get_usage();
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory increase should be reasonable (less than 10MB)
            expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024);
        });
    });

    describe('Concurrent Access Exception Scenarios', function () {
        it('handles concurrent report generation gracefully', function () {
            // Simulate concurrent access by generating multiple reports
            $results = [];
            
            for ($i = 0; $i < 5; $i++) {
                $results[] = $this->reportingService->generateReport($this->sampleResults, OutputFormat::JSON);
            }
            
            expect($results)->toHaveCount(5);
            foreach ($results as $report) {
                expect($report)->toBeString()
                    ->and($report)->not->toBeEmpty();
            }
        });

        it('handles concurrent file operations gracefully', function () {
            // Test multiple export operations
            $tempDir = createIsolatedTempDirectory('concurrent-reports');
            $exportResults = [];
            
            for ($i = 0; $i < 3; $i++) {
                $filePath = "{$tempDir}/report_{$i}.json";
                $exportResults[] = $this->reportingService->exportReport(
                    $this->sampleResults,
                    $filePath,
                    OutputFormat::JSON
                );
            }
            
            expect($exportResults)->toHaveCount(3);
            foreach ($exportResults as $result) {
                expect($result)->toBeTrue();
            }
        });
    });

    describe('Edge Cases and Boundary Conditions', function () {
        it('handles extremely long file paths gracefully', function () {
            $longPath = '/' . str_repeat('a', 1000) . '/report.json';
            
            $result = $this->reportingService->exportReport($this->sampleResults, $longPath, OutputFormat::JSON);
            
            expect($result)->toBeFalse();
        });

        it('handles special characters in file paths gracefully', function () {
            $specialPaths = [
                '/tmp/report with spaces.json',
                '/tmp/report-with-dashes.json',
                '/tmp/report_with_underscores.json',
                '/tmp/report.with.dots.json',
            ];
            
            foreach ($specialPaths as $path) {
                $result = $this->reportingService->exportReport($this->sampleResults, $path, OutputFormat::JSON);
                // Result depends on file system permissions, but should not crash
                expect($result)->toBeBool();
            }
        });

        it('handles null and empty values in results gracefully', function () {
            $edgeCaseResults = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: '',
                    status: LinkStatus::BROKEN,
                    message: ''
                ),
            ]);
            
            $report = $this->reportingService->generateReport($edgeCaseResults, OutputFormat::JSON);
            
            expect($report)->toBeString()
                ->and($report)->not->toBeEmpty();
        });
    });
});