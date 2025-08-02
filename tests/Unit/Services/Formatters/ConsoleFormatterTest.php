<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Formatters\ConsoleFormatter;
use App\Services\ValueObjects\ValidationResult;

describe('ConsoleFormatter', function () {
    beforeEach(function () {
        $this->formatter = new ConsoleFormatter();

        // Create test validation result with proper metadata structure
        $this->validResult = new ValidationResult(
            url: 'https://example.com',
            status: LinkStatus::VALID,
            scope: ValidationScope::EXTERNAL,
            error: null,
            httpStatusCode: 200,
            redirectUrl: null,
            responseTime: 150.5,
            metadata: [
                'summary' => [
                    'files_processed' => 3,
                    'total_links' => 10,
                    'broken_links' => 0,
                    'success_rate' => 100.0,
                    'duration' => 5.5,
                ],
                'results' => [],
                'files' => ['test.md'],
            ],
            filePath: 'test.md'
        );

        // Create broken result with broken links in metadata
        $brokenLinkResult = new ValidationResult(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            scope: ValidationScope::EXTERNAL,
            error: 'Not Found',
            httpStatusCode: 404,
            redirectUrl: null,
            responseTime: 1000.0,
            metadata: ['file' => 'test.md', 'line' => 2],
            filePath: 'test.md'
        );

        $this->invalidResult = new ValidationResult(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            scope: ValidationScope::EXTERNAL,
            error: 'Not Found',
            httpStatusCode: 404,
            redirectUrl: null,
            responseTime: 1000.0,
            metadata: [
                'summary' => [
                    'files_processed' => 3,
                    'total_links' => 10,
                    'broken_links' => 2,
                    'success_rate' => 80.0,
                    'duration' => 5.5,
                ],
                'results' => [$brokenLinkResult],
                'files' => ['test.md'],
            ],
            filePath: 'test.md'
        );

        $this->testStatistics = [
            'total_links' => 10,
            'valid_links' => 8,
            'broken_links' => 2,
            'success_rate' => 80.0,
            'duration' => 5.5,
            'files_processed' => 3,
            'by_status' => [
                'valid' => 8,
                'broken' => 2,
            ],
        ];
    });

    describe('format method', function () {
        it('formats successful validation result', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);

            expect($output)->toBeString()
                ->and($output)->toContain('✅ PASSED')
                ->and($output)->toContain('Link Validation')
                ->and($output)->toContain('📊 Validation Summary')
                ->and($output)->toContain('📈 Detailed Statistics');
        });

        it('formats failed validation result with broken links', function () {
            $output = $this->formatter->format($this->invalidResult, $this->testStatistics);

            expect($output)->toBeString()
                ->and($output)->toContain('❌ FAILED')
                ->and($output)->toContain('💥 Broken Links Details')
                ->and($output)->toContain('https://broken.com')
                ->and($output)->toContain('Not Found');
        });

        it('includes all required sections', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);

            expect($output)->toContain('🔗 Link Validation')
                ->and($output)->toContain('📊 Validation Summary')
                ->and($output)->toContain('📈 Detailed Statistics');
        });
    });

    describe('formatHeader method', function () {
        it('formats successful header correctly', function () {
            $result = $this->validResult;
            $output = $this->formatter->format($result, []);

            expect($output)->toContain('<fg=green;options=bold>🔗 Link Validation ✅ PASSED</>');
        });

        it('formats failed header correctly', function () {
            $result = $this->invalidResult;
            $output = $this->formatter->format($result, []);

            expect($output)->toContain('<fg=red;options=bold>🔗 Link Validation ❌ FAILED</>');
        });
    });

    describe('formatSummary method', function () {
        it('formats summary with all statistics', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);

            // These come from validResult's metadata summary, not testStatistics
            expect($output)->toContain('📁 Files processed: 3')
                ->and($output)->toContain('🔗 Total links: 10')
                ->and($output)->toContain('❌ Broken links: 0')  // validResult has 0 broken links
                ->and($output)->toContain('📈 Success rate: 100%')  // validResult has 100% success rate
                ->and($output)->toContain('⏱️  Duration: 5.5s');
        });

        it('handles missing summary fields gracefully', function () {
            $resultWithIncompleteMetadata = new ValidationResult(
                url: 'https://test.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                metadata: [
                    'summary' => ['total_links' => 5],  // Only partial summary
                ],
                filePath: 'test.md'
            );

            $output = $this->formatter->format($resultWithIncompleteMetadata, []);

            expect($output)->toContain('📁 Files processed: 0')
                ->and($output)->toContain('🔗 Total links: 5')
                ->and($output)->toContain('❌ Broken links: 0');
        });

        it('handles non-scalar summary values gracefully', function () {
            $resultWithInvalidMetadata = new ValidationResult(
                url: 'https://test.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                metadata: [
                    'summary' => [
                        'total_links' => ['invalid' => 'array'],
                        'files_processed' => null,
                    ],
                ],
                filePath: 'test.md'
            );

            $output = $this->formatter->format($resultWithInvalidMetadata, []);

            expect($output)->toContain('📁 Files processed: 0')
                ->and($output)->toContain('🔗 Total links: 0');
        });
    });

    describe('formatBrokenLinks method', function () {
        it('formats broken links details', function () {
            // Create multiple broken results
            $broken1 = new ValidationResult(
                url: 'https://broken1.com',
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: 'Connection timeout',
                httpStatusCode: 408,
                responseTime: 5000.0,
                metadata: [],
                filePath: 'test1.md'
            );

            $broken2 = new ValidationResult(
                url: 'https://broken2.com',
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: 'Not found',
                httpStatusCode: 404,
                responseTime: 1000.0,
                metadata: [],
                filePath: 'test2.md'
            );

            $result = new ValidationResult(
                url: 'https://test.com',
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: 'Multiple failures',
                metadata: [
                    'results' => [$broken1, $broken2],
                    'summary' => ['broken_links' => 2],
                ],
                filePath: 'test.md'
            );

            $output = $this->formatter->format($result, []);

            expect($output)->toContain('💥 Broken Links Details')
                ->and($output)->toContain('🔴 https://broken1.com')
                ->and($output)->toContain('❗ Reason: Connection timeout')
                ->and($output)->toContain('🔴 https://broken2.com')
                ->and($output)->toContain('❗ Reason: Not found');
        });

        it('handles broken links with validation failures', function () {
            $output = $this->formatter->format($this->invalidResult, []);

            expect($output)->toContain('💥 Broken Links Details')
                ->and($output)->toContain('🔴 https://broken.com')
                ->and($output)->toContain('❗ Reason: Not Found');
        });
    });

    describe('formatStatistics method', function () {
        it('formats nested statistics correctly', function () {
            $stats = [
                'simple_stat' => 42,
                'nested_stat' => [
                    'sub1' => 10,
                    'sub2' => 20,
                ],
                'string_stat' => 'test_value',
            ];

            $output = $this->formatter->format($this->validResult, $stats);

            expect($output)->toContain('📈 Detailed Statistics')
                ->and($output)->toContain('simple_stat: 42')
                ->and($output)->toContain('nested_stat:')
                ->and($output)->toContain('sub1: 10')
                ->and($output)->toContain('sub2: 20')
                ->and($output)->toContain('string_stat: test_value');
        });

        it('handles non-scalar values in statistics', function () {
            $stats = [
                'object_stat' => new stdClass(),
                'null_stat' => null,
                'array_stat' => [
                    'valid' => 5,
                    'invalid_object' => new stdClass(),
                ],
            ];

            $output = $this->formatter->format($this->validResult, $stats);

            expect($output)->toContain('📈 Detailed Statistics')
                ->and($output)->toContain('object_stat: unknown')
                ->and($output)->toContain('null_stat: unknown')
                ->and($output)->toContain('array_stat:')
                ->and($output)->toContain('valid: 5')
                ->and($output)->toContain('invalid_object: unknown');
        });
    });

    describe('integration', function () {
        it('produces complete formatted output', function () {
            $output = $this->formatter->format($this->invalidResult, $this->testStatistics);

            // Verify all sections are present and properly formatted
            expect($output)->toBeString()
                ->and(strlen($output))->toBeGreaterThan(100)
                ->and($output)->toContain('❌ FAILED')
                ->and($output)->toContain('📊 Validation Summary')
                ->and($output)->toContain('💥 Broken Links Details')
                ->and($output)->toContain('📈 Detailed Statistics')
                ->and($output)->toContain('https://broken.com')
                ->and($output)->toContain('Not Found');
        });
    });
});
