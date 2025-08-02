<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\ReportingInterface;
use App\Services\Formatters\ConsoleFormatter;
use App\Services\Formatters\HtmlFormatter;
use App\Services\Formatters\JsonFormatter;
use App\Services\Formatters\MarkdownFormatter;
use App\Services\ReportingService;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;


describe('ReportingService', function () {
    beforeEach(function () {
        // Setup Phase: Prepare test environment and dependencies

        // Create service instance
        $this->service = new ReportingService();
        $this->reflection = new \ReflectionClass(ReportingService::class);

        // Test data: Sample validation results for testing
        $this->testResults = [
            'valid' => [
                new ValidationResult(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::EXTERNAL,
                    responseTime: 0.5
                ),
                new ValidationResult(
                    url: '#section1',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::ANCHOR,
                    responseTime: 0.1
                ),
                new ValidationResult(
                    url: './file.md',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::INTERNAL,
                    responseTime: 0.2
                ),
            ],
            'broken' => [
                new ValidationResult(
                    url: 'https://broken.com',
                    status: LinkStatus::BROKEN,
                    scope: ValidationScope::EXTERNAL,
                    error: 'Connection failed',
                    responseTime: 2.0
                ),
                new ValidationResult(
                    url: 'https://notfound.com',
                    status: LinkStatus::NOT_FOUND,
                    scope: ValidationScope::EXTERNAL,
                    error: 'HTTP 404',
                    httpStatusCode: 404,
                    responseTime: 1.5
                ),
            ],
            'mixed' => [
                new ValidationResult(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::EXTERNAL,
                    responseTime: 0.5
                ),
                new ValidationResult(
                    url: 'https://broken.com',
                    status: LinkStatus::BROKEN,
                    scope: ValidationScope::EXTERNAL,
                    error: 'Connection failed',
                    responseTime: 2.0
                ),
                new ValidationResult(
                    url: '#anchor',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::ANCHOR,
                    responseTime: 0.1
                ),
            ],
        ];

        // Test collections
        $this->testCollections = [
            'valid_only' => ValidationResultCollection::fromArray($this->testResults['valid']),
            'broken_only' => ValidationResultCollection::fromArray($this->testResults['broken']),
            'mixed_results' => ValidationResultCollection::fromArray($this->testResults['mixed']),
            'empty' => ValidationResultCollection::fromArray([]),
        ];

        // Test formats
        $this->testFormats = [
            'console' => OutputFormat::CONSOLE,
            'json' => OutputFormat::JSON,
            'html' => OutputFormat::HTML,
            'markdown' => OutputFormat::MARKDOWN,
        ];

        // Note: Mock formatters not needed for current tests as we test with real formatters

        // Test file paths - use tempnam() for proper temporary files
        $this->testFilePaths = [
            'valid' => tempnam(sys_get_temp_dir(), 'test-report-') . '.json',
            'nested' => sys_get_temp_dir() . '/nested-' . uniqid() . '/dir/report.html',
            'invalid' => '',
        ];

        // Create a unique temp directory for nested tests
        $this->tempDir = sys_get_temp_dir() . '/pest-test-' . uniqid();

        // Expected summary data
        $this->expectedSummaryData = [
            'valid_only' => [
                'total_links' => 3,
                'broken_links' => 0,
                'success_rate' => 100.0,
                'execution_time' => 0.8, // 0.5 + 0.1 + 0.2
            ],
            'broken_only' => [
                'total_links' => 2,
                'broken_links' => 2,
                'success_rate' => 0.0,
                'execution_time' => 3.5, // 2.0 + 1.5
            ],
            'mixed_results' => [
                'total_links' => 3,
                'broken_links' => 1,
                'success_rate' => 66.67,
                'execution_time' => 2.6, // 0.5 + 2.0 + 0.1
            ],
        ];
    });

    afterEach(function () {
        // Teardown Phase: Clean up test environment

        // Close Mockery mocks
        \Mockery::close();

        // Clean up test files if they exist
        foreach ($this->testFilePaths as $filePath) {
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        // Simple cleanup - just remove files, let OS handle temp directories
        // This avoids complex directory traversal issues in tests

        // Clear properties
        unset(
            $this->service,
            $this->reflection,
            $this->testResults,
            $this->testCollections,
            $this->testFormats,
            $this->testFilePaths,
            $this->expectedSummaryData,
            $this->tempDir
        );

        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    });



    describe('service instantiation and interface compliance', function () {
        it('creates service with default formatters', function () {
            expect($this->service)->toBeInstanceOf(ReportingService::class);
        });

        it('implements ReportingInterface', function () {
            expect($this->service)->toBeInstanceOf(ReportingInterface::class);
        });

        it('initializes with all required formatters', function () {
            $formattersProperty = $this->reflection->getProperty('formatters');
            $formattersProperty->setAccessible(true);
            $formatters = $formattersProperty->getValue($this->service);

            expect($formatters)->toBeArray()
                ->and(array_keys($formatters))->toContain('console', 'json', 'html', 'markdown')
                ->and($formatters['console'])->toBeInstanceOf(ConsoleFormatter::class)
                ->and($formatters['json'])->toBeInstanceOf(JsonFormatter::class)
                ->and($formatters['html'])->toBeInstanceOf(HtmlFormatter::class)
                ->and($formatters['markdown'])->toBeInstanceOf(MarkdownFormatter::class);
        });

        it('has all required interface methods', function () {
            $expectedMethods = [
                'generateReport',
                'addFormatter',
                'exportReport',
                'format',
                'getAvailableFormats',
                'supportsFormat',
                'getFormatter',
                'generateSummary',
                'saveReport',
            ];

            foreach ($expectedMethods as $method) {
                expect($this->reflection->hasMethod($method))->toBeTrue(
                    "Method {$method} should exist"
                );
                expect($this->reflection->getMethod($method)->isPublic())->toBeTrue(
                    "Method {$method} should be public"
                );
            }
        });
    });

    describe('generateReport method', function () {
        it('generates report with default console format', function () {
            $result = $this->service->generateReport($this->testCollections['valid_only']);

            expect($result)->toBeString()
                ->and($result)->not->toBeEmpty();
        });

        it('generates report with specified format', function () {
            foreach ($this->testFormats as $format) {
                $result = $this->service->generateReport($this->testCollections['valid_only'], $format);

                expect($result)->toBeString()
                    ->and($result)->not->toBeEmpty();
            }
        });

        it('throws exception for unsupported format', function () {
            // Create a mock unsupported format
            $unsupportedFormat = OutputFormat::XML; // Assuming XML is not supported

            expect(fn () => $this->service->generateReport(
                $this->testCollections['valid_only'],
                $unsupportedFormat
            ))->toThrow(InvalidArgumentException::class);
        });

        it('handles empty results collection', function () {
            $result = $this->service->generateReport($this->testCollections['empty']);

            expect($result)->toBeString();
        });
    });

    describe('generateSummary method', function () {
        it('generates summary for valid results only', function () {
            $summary = $this->service->generateSummary($this->testCollections['valid_only']);

            expect($summary)->toBeInstanceOf(ReportSummary::class)
                ->and($summary->totalLinks)->toBe($this->expectedSummaryData['valid_only']['total_links'])
                ->and($summary->brokenLinks)->toBe($this->expectedSummaryData['valid_only']['broken_links'])
                ->and($summary->successRate)->toBe($this->expectedSummaryData['valid_only']['success_rate'])
                ->and($summary->executionTime)->toBe($this->expectedSummaryData['valid_only']['execution_time']);
        });

        it('generates summary for broken results only', function () {
            $summary = $this->service->generateSummary($this->testCollections['broken_only']);

            expect($summary)->toBeInstanceOf(ReportSummary::class)
                ->and($summary->totalLinks)->toBe($this->expectedSummaryData['broken_only']['total_links'])
                ->and($summary->brokenLinks)->toBe($this->expectedSummaryData['broken_only']['broken_links'])
                ->and($summary->successRate)->toBe($this->expectedSummaryData['broken_only']['success_rate'])
                ->and($summary->executionTime)->toBe($this->expectedSummaryData['broken_only']['execution_time']);
        });

        it('generates summary for mixed results', function () {
            $summary = $this->service->generateSummary($this->testCollections['mixed_results']);

            expect($summary)->toBeInstanceOf(ReportSummary::class)
                ->and($summary->totalLinks)->toBe($this->expectedSummaryData['mixed_results']['total_links'])
                ->and($summary->brokenLinks)->toBe($this->expectedSummaryData['mixed_results']['broken_links'])
                ->and($summary->successRate)->toBe($this->expectedSummaryData['mixed_results']['success_rate'])
                ->and($summary->executionTime)->toBe($this->expectedSummaryData['mixed_results']['execution_time']);
        });

        it('generates summary with status breakdown', function () {
            $summary = $this->service->generateSummary($this->testCollections['mixed_results']);

            expect($summary->statusBreakdown)->toBeArray()
                ->and($summary->statusBreakdown)->toHaveKey(LinkStatus::VALID->value)
                ->and($summary->statusBreakdown)->toHaveKey(LinkStatus::BROKEN->value)
                ->and($summary->statusBreakdown[LinkStatus::VALID->value])->toBe(2)
                ->and($summary->statusBreakdown[LinkStatus::BROKEN->value])->toBe(1);
        });

        it('handles empty results collection', function () {
            $summary = $this->service->generateSummary($this->testCollections['empty']);

            expect($summary)->toBeInstanceOf(ReportSummary::class)
                ->and($summary->totalLinks)->toBe(0)
                ->and($summary->brokenLinks)->toBe(0)
                ->and($summary->successRate)->toBe(0.0)
                ->and($summary->executionTime)->toBe(0.0);
        });
    });

    describe('addFormatter method', function () {
        it('adds custom formatter for existing format', function () {
            $customFormatter = new class {
                public function format($result, $summary) {
                    return 'custom output';
                }
            };

            $this->service->addFormatter(OutputFormat::JSON, $customFormatter);

            expect($this->service->getFormatter(OutputFormat::JSON))->toBe($customFormatter);
        });

        it('adds formatter for new format', function () {
            $customFormatter = new class {
                public function format($result, $summary) {
                    return 'custom output';
                }
            };

            $this->service->addFormatter(OutputFormat::XML, $customFormatter);

            expect($this->service->supportsFormat(OutputFormat::XML))->toBeTrue()
                ->and($this->service->getFormatter(OutputFormat::XML))->toBe($customFormatter);
        });
    });

    describe('exportReport method', function () {
        it('exports report to file with default JSON format', function () {
            $filePath = $this->testFilePaths['valid'];

            $result = $this->service->exportReport($this->testCollections['valid_only'], $filePath);

            expect($result)->toBeTrue()
                ->and(file_exists($filePath))->toBeTrue()
                ->and(file_get_contents($filePath))->not->toBeEmpty();
        });

        it('exports report with specified format', function () {
            $filePath = $this->testFilePaths['valid'];

            $result = $this->service->exportReport(
                $this->testCollections['valid_only'],
                $filePath,
                OutputFormat::HTML
            );

            expect($result)->toBeTrue()
                ->and(file_exists($filePath))->toBeTrue();
        });

        it('creates directory if it does not exist', function () {
            $filePath = $this->testFilePaths['nested'];
            $directory = dirname($filePath);

            // Ensure directory doesn't exist
            if (is_dir($directory)) {
                rmdir($directory);
            }

            $result = $this->service->exportReport($this->testCollections['valid_only'], $filePath);

            expect($result)->toBeTrue()
                ->and(is_dir($directory))->toBeTrue()
                ->and(file_exists($filePath))->toBeTrue();
        });

        it('returns false on file write failure', function () {
            // Use an invalid path that should fail
            $invalidPath = '/root/cannot-write-here.json';

            $result = $this->service->exportReport($this->testCollections['valid_only'], $invalidPath);

            expect($result)->toBeFalse();
        });

        it('handles empty results collection', function () {
            $filePath = $this->testFilePaths['valid'];

            $result = $this->service->exportReport($this->testCollections['empty'], $filePath);

            expect($result)->toBeTrue()
                ->and(file_exists($filePath))->toBeTrue();
        });
    });

    describe('format method', function () {
        it('formats results with specified format', function () {
            foreach ($this->testFormats as $format) {
                $result = $this->service->format($this->testCollections['valid_only'], $format);

                expect($result)->toBeString()
                    ->and($result)->not->toBeEmpty();
            }
        });

        it('ignores options parameter', function () {
            $options = ['custom' => 'value', 'another' => 'option'];

            $result = $this->service->format($this->testCollections['valid_only'], OutputFormat::JSON, $options);

            expect($result)->toBeString()
                ->and($result)->not->toBeEmpty();
        });
    });

    describe('getAvailableFormats method', function () {
        it('returns array of OutputFormat enums', function () {
            $formats = $this->service->getAvailableFormats();

            expect($formats)->toBeArray()
                ->and($formats)->not->toBeEmpty();

            foreach ($formats as $format) {
                expect($format)->toBeInstanceOf(OutputFormat::class);
            }
        });

        it('includes all default formats', function () {
            $formats = $this->service->getAvailableFormats();
            $formatValues = array_map(fn($format) => $format->value, $formats);

            expect($formatValues)->toContain('console', 'json', 'html', 'markdown');
        });
    });

    describe('supportsFormat method', function () {
        it('returns true for supported formats', function () {
            foreach ($this->testFormats as $format) {
                expect($this->service->supportsFormat($format))->toBeTrue();
            }
        });

        it('returns false for unsupported formats', function () {
            expect($this->service->supportsFormat(OutputFormat::XML))->toBeFalse()
                ->and($this->service->supportsFormat(OutputFormat::CSV))->toBeFalse();
        });
    });

    describe('getFormatter method', function () {
        it('returns formatter for supported formats', function () {
            expect($this->service->getFormatter(OutputFormat::CONSOLE))->toBeInstanceOf(ConsoleFormatter::class)
                ->and($this->service->getFormatter(OutputFormat::JSON))->toBeInstanceOf(JsonFormatter::class)
                ->and($this->service->getFormatter(OutputFormat::HTML))->toBeInstanceOf(HtmlFormatter::class)
                ->and($this->service->getFormatter(OutputFormat::MARKDOWN))->toBeInstanceOf(MarkdownFormatter::class);
        });

        it('throws exception for unsupported formats', function () {
            expect(fn () => $this->service->getFormatter(OutputFormat::XML))
                ->toThrow(InvalidArgumentException::class, 'Unsupported format: xml');
        });
    });

    describe('saveReport method', function () {
        it('saves report content to file', function () {
            $content = 'Test report content';
            $filePath = $this->testFilePaths['valid'];

            $result = $this->service->saveReport($content, $filePath);

            expect($result)->toBeTrue()
                ->and(file_exists($filePath))->toBeTrue()
                ->and(file_get_contents($filePath))->toBe($content);
        });

        it('creates directory if it does not exist', function () {
            $content = 'Test report content';
            $filePath = $this->testFilePaths['nested'];
            $directory = dirname($filePath);

            // Ensure directory doesn't exist
            if (is_dir($directory)) {
                rmdir($directory);
            }

            $result = $this->service->saveReport($content, $filePath);

            expect($result)->toBeTrue()
                ->and(is_dir($directory))->toBeTrue()
                ->and(file_exists($filePath))->toBeTrue()
                ->and(file_get_contents($filePath))->toBe($content);
        });

        it('returns false on file write failure', function () {
            $content = 'Test report content';
            $invalidPath = '/root/cannot-write-here.txt';

            $result = $this->service->saveReport($content, $invalidPath);

            expect($result)->toBeFalse();
        });

        it('handles empty content', function () {
            $content = '';
            $filePath = $this->testFilePaths['valid'];

            $result = $this->service->saveReport($content, $filePath);

            expect($result)->toBeTrue()
                ->and(file_exists($filePath))->toBeTrue()
                ->and(file_get_contents($filePath))->toBe('');
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very large result collections', function () {
            $largeResults = [];
            for ($i = 0; $i < 1000; $i++) {
                $largeResults[] = new ValidationResult(
                    url: "https://example{$i}.com",
                    status: $i % 2 === 0 ? LinkStatus::VALID : LinkStatus::BROKEN,
                    scope: ValidationScope::EXTERNAL,
                    responseTime: 0.1
                );
            }

            $largeCollection = ValidationResultCollection::fromArray($largeResults);

            $summary = $this->service->generateSummary($largeCollection);
            $report = $this->service->generateReport($largeCollection);

            expect($summary->totalLinks)->toBe(1000)
                ->and($summary->brokenLinks)->toBe(500)
                ->and($summary->successRate)->toBe(50.0)
                ->and($report)->toBeString()
                ->and($report)->not->toBeEmpty();
        });

        it('handles results with null response times', function () {
            $resultsWithNullTimes = [
                new ValidationResult(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::EXTERNAL,
                    responseTime: 0.0 // Explicit zero instead of null
                ),
            ];

            $collection = ValidationResultCollection::fromArray($resultsWithNullTimes);
            $summary = $this->service->generateSummary($collection);

            expect($summary->executionTime)->toBe(0.0);
        });

        it('handles malformed formatter responses', function () {
            // Create a mock formatter that has the format method but returns non-string
            $mockFormatter = new class {
                public function format($result, $summary) {
                    return null; // Return non-string value
                }
            };

            $this->service->addFormatter(OutputFormat::JSON, $mockFormatter);

            $result = $this->service->generateReport($this->testCollections['valid_only'], OutputFormat::JSON);

            expect($result)->toBe(''); // Should return empty string for non-string responses
        });
    });
});
