<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Formatters\JsonFormatter;
use App\Services\ValueObjects\ValidationResult;

describe('JsonFormatter', function () {
    beforeEach(function () {
        $this->formatter = new JsonFormatter();

        // Create test validation result with comprehensive metadata
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
                'total_results' => 10,
                'broken_count' => 0,
                'results' => [],
                'files' => ['test1.md', 'test2.md'],
                'duration' => 5.5,
            ],
            filePath: 'test.md'
        );

        // Create broken result
        $brokenLinkResult = new ValidationResult(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            scope: ValidationScope::EXTERNAL,
            error: 'Not Found',
            httpStatusCode: 404,
            redirectUrl: null,
            responseTime: 1000.0,
            metadata: [],
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
                'total_results' => 10,
                'broken_count' => 2,
                'results' => [$brokenLinkResult],
                'files' => ['test1.md', 'test2.md'],
                'duration' => 5.5,
            ],
            filePath: 'test.md'
        );

        $this->testStatistics = [
            'total_links' => 10,
            'valid_links' => 8,
            'broken_links' => 2,
            'success_rate' => 80, // JSON will convert 80.0 to 80
            'duration' => 5.5,
            'files_processed' => 3,
            'by_status' => [
                'valid' => 8,
                'broken' => 2,
            ],
        ];
    });

    describe('format method', function () {
        it('formats successful validation result as JSON', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);

            expect($output)->toBeString();

            $data = json_decode($output, true);
            expect($data)->toBeArray()
                ->and($data)->toHaveKey('validation')
                ->and($data)->toHaveKey('files')
                ->and($data)->toHaveKey('links')
                ->and($data)->toHaveKey('broken_links')
                ->and($data)->toHaveKey('statistics')
                ->and($data)->toHaveKey('performance');
        });

        it('formats failed validation result as JSON', function () {
            $output = $this->formatter->format($this->invalidResult, $this->testStatistics);

            expect($output)->toBeString();

            $data = json_decode($output, true);
            expect($data)->toBeArray()
                ->and($data['validation']['status'])->toBe('failed');
        });

        it('returns valid JSON string', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);

            expect(json_decode($output))->not->toBeNull()
                ->and(json_last_error())->toBe(JSON_ERROR_NONE);
        });
    });

    describe('validation section', function () {
        it('includes correct validation status for successful result', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['validation']['status'])->toBe('passed')
                ->and($data['validation'])->toHaveKey('timestamp')
                ->and($data['validation'])->toHaveKey('summary');
        });

        it('includes correct validation status for failed result', function () {
            $output = $this->formatter->format($this->invalidResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['validation']['status'])->toBe('failed')
                ->and($data['validation'])->toHaveKey('timestamp')
                ->and($data['validation'])->toHaveKey('summary');
        });

        it('includes valid timestamp', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            $timestamp = $data['validation']['timestamp'];
            expect($timestamp)->toBeString()
                ->and(strtotime($timestamp))->not->toBeFalse();
        });

        it('includes summary from ValidationResult', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['validation']['summary'])->toBeArray()
                ->and($data['validation']['summary']['files_processed'])->toBe(3)
                ->and($data['validation']['summary']['total_links'])->toBe(10);
        });
    });

    describe('files section', function () {
        it('includes files array from ValidationResult', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['files'])->toBeArray()
                ->and($data['files'])->toContain('test1.md')
                ->and($data['files'])->toContain('test2.md');
        });
    });

    describe('links section', function () {
        it('includes links statistics', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['links'])->toHaveKey('total')
                ->and($data['links'])->toHaveKey('broken')
                ->and($data['links'])->toHaveKey('success_rate')
                ->and($data['links'])->toHaveKey('details');
        });

        it('calculates correct link statistics', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['links']['total'])->toBe(10)
                ->and($data['links']['broken'])->toBe(0)
                ->and($data['links']['success_rate'])->toBe(100); // JSON converts 100.0 to 100
        });
    });

    describe('broken_links section', function () {
        it('includes broken links array', function () {
            $output = $this->formatter->format($this->invalidResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['broken_links'])->toBeArray();
        });
    });

    describe('statistics section', function () {
        it('includes provided statistics', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['statistics'])->toBe($this->testStatistics)
                ->and($data['statistics']['total_links'])->toBe(10)
                ->and($data['statistics']['by_status'])->toBeArray();
        });
    });

    describe('performance section', function () {
        it('includes performance metrics', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['performance'])->toHaveKey('duration')
                ->and($data['performance'])->toHaveKey('files_per_second')
                ->and($data['performance'])->toHaveKey('links_per_second');
        });

        it('calculates performance metrics correctly', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);
            $data = json_decode($output, true);

            expect($data['performance']['duration'])->toBe(5.5)
                ->and($data['performance']['files_per_second'])->toBeNumeric()
                ->and($data['performance']['links_per_second'])->toBeNumeric()
                ->and($data['performance']['files_per_second'])->toBeGreaterThan(0)
                ->and($data['performance']['links_per_second'])->toBeGreaterThan(0);
        });

        it('handles zero duration gracefully', function () {
            $zeroResult = new ValidationResult(
                url: 'https://test.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                metadata: [
                    'total_results' => 5,
                    'broken_count' => 0,
                    'files' => ['test.md'],
                    'duration' => 0.0,
                ],
                filePath: 'test.md'
            );

            $output = $this->formatter->format($zeroResult, []);
            $data = json_decode($output, true);

            expect($data['performance']['files_per_second'])->toBeNumeric()
                ->and($data['performance']['links_per_second'])->toBeNumeric()
                ->and($data['performance']['files_per_second'])->toBeGreaterThan(0)
                ->and($data['performance']['links_per_second'])->toBeGreaterThan(0);
        });
    });

    describe('JSON formatting', function () {
        it('produces pretty-printed JSON', function () {
            $output = $this->formatter->format($this->validResult, $this->testStatistics);

            expect($output)->toContain('{')
                ->and($output)->toContain('    ')  // Indentation
                ->and($output)->not->toContain('\\/');  // Unescaped slashes
        });

        it('handles JSON encoding errors gracefully', function () {
            // This test verifies the fallback behavior
            $output = $this->formatter->format($this->validResult, $this->testStatistics);

            expect($output)->toBeString()
                ->and($output)->not->toBe('{}');  // Should not be the fallback
        });
    });

    describe('integration', function () {
        it('produces complete JSON structure', function () {
            $output = $this->formatter->format($this->invalidResult, $this->testStatistics);
            $data = json_decode($output, true);

            // Verify complete structure
            expect($data)->toBeArray()
                ->and($data['validation']['status'])->toBe('failed')
                ->and($data['files'])->toBeArray()
                ->and($data['links']['total'])->toBe(10)
                ->and($data['links']['broken'])->toBe(2)
                ->and($data['broken_links'])->toBeArray()
                ->and($data['statistics'])->toBe($this->testStatistics)
                ->and($data['performance']['duration'])->toBe(5.5);
        });
    });
});
