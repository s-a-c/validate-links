<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Formatters\MarkdownFormatter;
use App\Services\ValueObjects\ValidationResult;

describe('MarkdownFormatter', function () {
    beforeEach(function () {
        $this->formatter = new MarkdownFormatter();

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

        // Create broken results for testing
        $brokenExternal = new ValidationResult(
            url: 'https://broken-external.com',
            status: LinkStatus::BROKEN,
            scope: ValidationScope::EXTERNAL,
            error: 'Connection timeout',
            httpStatusCode: 408,
            responseTime: 5000.0,
            metadata: [],
            filePath: 'test1.md'
        );

        $brokenInternal = new ValidationResult(
            url: '/broken-internal',
            status: LinkStatus::BROKEN,
            scope: ValidationScope::INTERNAL,
            error: 'File not found',
            httpStatusCode: 404,
            responseTime: 100.0,
            metadata: [],
            filePath: 'test2.md'
        );

        $this->invalidResult = new ValidationResult(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            scope: ValidationScope::EXTERNAL,
            error: 'Multiple failures',
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
                'results' => [$brokenExternal, $brokenInternal],
                'files' => ['test1.md', 'test2.md'],
                'duration' => 5.5,
            ],
            filePath: 'test.md'
        );
    });

    describe('format method', function () {
        it('returns valid markdown string', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->toBeString()
                ->and($output)->toContain('# Link Validation Report')
                ->and($output)->toContain('## Summary')
                ->and($output)->toContain('**Generated:**');
        });

        it('includes proper markdown structure', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->toContain('# ')  // H1 header
                ->and($output)->toContain('## ')  // H2 headers
                ->and($output)->toContain('**')  // Bold text
                ->and($output)->toContain('- ');  // List items
        });
    });

    describe('header section', function () {
        it('includes main title', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->toContain('# Link Validation Report');
        });

        it('includes timestamp in correct format', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->toContain('**Generated:**')
                ->and($output)->toMatch('/\*\*Generated:\*\* \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');
        });
    });

    describe('summary section', function () {
        it('includes summary header', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->toContain('## Summary');
        });

        it('displays statistics as markdown list', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->toContain('- **files_processed:** 3')
                ->and($output)->toContain('- **total_links:** 10')
                ->and($output)->toContain('- **broken_links:** 0')
                ->and($output)->toContain('- **success_rate:** 100')
                ->and($output)->toContain('- **duration:** 5.5');
        });

        it('handles non-scalar statistics values', function () {
            $invalidStatsResult = new ValidationResult(
                url: 'https://test.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                metadata: [
                    'summary' => [
                        'valid_stat' => 'string_value',
                        'invalid_stat' => ['array' => 'value'],
                        'null_stat' => null,
                    ],
                ],
                filePath: 'test.md'
            );

            $output = $this->formatter->format($invalidStatsResult);

            expect($output)->toContain('- **valid_stat:** string_value')
                ->and($output)->toContain('- **invalid_stat:** unknown')
                ->and($output)->toContain('- **null_stat:** unknown');
        });
    });

    describe('status section for valid result', function () {
        it('shows success status', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->toContain('## Status: ✅ Success')
                ->and($output)->toContain('All links validated successfully!');
        });
    });

    describe('status section for invalid result', function () {
        it('shows failed status', function () {
            $output = $this->formatter->format($this->invalidResult);

            expect($output)->toContain('## Status: ❌ Failed')
                ->and($output)->toContain('Found 2 broken link(s).');
        });
    });

    describe('broken links section', function () {
        it('does not show broken links section for valid result', function () {
            $output = $this->formatter->format($this->validResult);

            expect($output)->not->toContain('## Broken Links')
                ->and($output)->not->toContain('| File | Link | Reason |');
        });

        it('shows broken links section for invalid result', function () {
            $output = $this->formatter->format($this->invalidResult);

            expect($output)->toContain('## Broken Links')
                ->and($output)->toContain('### external Links (1)')
                ->and($output)->toContain('### internal Links (1)');
        });

        it('includes markdown table headers', function () {
            $output = $this->formatter->format($this->invalidResult);

            expect($output)->toContain('| File | Link | Reason |')
                ->and($output)->toContain('|------|------|--------|');
        });

        it('displays broken link details in table rows', function () {
            $output = $this->formatter->format($this->invalidResult);

            expect($output)->toContain('| unknown | `https://broken-external.com` | Connection timeout |')
                ->and($output)->toContain('| unknown | `/broken-internal` | File not found |');
        });

        it('uses basename for file paths', function () {
            $longPathResult = new ValidationResult(
                url: 'https://broken.com',
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: 'Error',
                metadata: [
                    'broken_count' => 1,
                    'results' => [
                        new ValidationResult(
                            url: 'https://broken.com',
                            status: LinkStatus::BROKEN,
                            scope: ValidationScope::EXTERNAL,
                            error: 'Error',
                            metadata: [],
                            filePath: '/very/long/path/to/test.md'
                        ),
                    ],
                ],
                filePath: '/very/long/path/to/test.md'
            );

            $output = $this->formatter->format($longPathResult);

            expect($output)->toContain('| unknown |')
                ->and($output)->not->toContain('/very/long/path/to/');
        });

        it('wraps URLs in code blocks', function () {
            $output = $this->formatter->format($this->invalidResult);

            expect($output)->toContain('`https://broken-external.com`')
                ->and($output)->toContain('`/broken-internal`');
        });

        it('groups links by type with counts', function () {
            $output = $this->formatter->format($this->invalidResult);

            expect($output)->toContain('### external Links (1)')
                ->and($output)->toContain('### internal Links (1)');
        });
    });

    describe('markdown formatting', function () {
        it('uses proper markdown syntax', function () {
            $output = $this->formatter->format($this->invalidResult);

            // Check for proper markdown elements
            expect($output)->toContain('# ')  // H1
                ->and($output)->toContain('## ')  // H2
                ->and($output)->toContain('### ')  // H3
                ->and($output)->toContain('**')  // Bold
                ->and($output)->toContain('- ')  // List items
                ->and($output)->toContain('| ')  // Table cells
                ->and($output)->toContain('`');  // Code blocks
        });

        it('includes proper line breaks', function () {
            $output = $this->formatter->format($this->validResult);

            // Check for empty lines between sections
            expect($output)->toContain("# Link Validation Report\n\n")
                ->and($output)->toContain("## Summary\n\n")
                ->and($output)->toContain("## Status: ✅ Success\n\n");
        });

        it('ends with proper line breaks', function () {
            $output = $this->formatter->format($this->invalidResult);

            // Should end with newline after broken links section
            expect($output)->toMatch('/\n$/');
        });
    });

    describe('edge cases', function () {
        it('handles empty statistics gracefully', function () {
            $emptyResult = new ValidationResult(
                url: 'https://test.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                metadata: ['summary' => []],
                filePath: 'test.md'
            );

            $output = $this->formatter->format($emptyResult);

            expect($output)->toBeString()
                ->and($output)->toContain('## Summary')
                ->and($output)->toContain('## Status: ✅ Success');
        });

        it('handles result with no broken links metadata', function () {
            $noMetadataResult = new ValidationResult(
                url: 'https://test.com',
                status: LinkStatus::VALID,
                scope: ValidationScope::EXTERNAL,
                metadata: [],
                filePath: 'test.md'
            );

            $output = $this->formatter->format($noMetadataResult);

            expect($output)->toBeString()
                ->and($output)->toContain('# Link Validation Report')
                ->and($output)->not->toContain('## Broken Links');
        });

        it('handles special characters in link data', function () {
            $specialCharsResult = new ValidationResult(
                url: 'https://broken.com',
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: 'Error with | pipes and `backticks`',
                metadata: [
                    'broken_count' => 1,
                    'results' => [
                        new ValidationResult(
                            url: 'https://example.com/path|with|pipes',
                            status: LinkStatus::BROKEN,
                            scope: ValidationScope::EXTERNAL,
                            error: 'Error with | pipes and `backticks`',
                            metadata: [],
                            filePath: 'test|file.md'
                        ),
                    ],
                ],
                filePath: 'test|file.md'
            );

            $output = $this->formatter->format($specialCharsResult);

            expect($output)->toContain('unknown')  // File shows as 'unknown'
                ->and($output)->toContain('https://example.com/path|with|pipes')
                ->and($output)->toContain('Error with | pipes and `backticks`');
        });
    });

    describe('integration', function () {
        it('produces complete markdown document', function () {
            $output = $this->formatter->format($this->invalidResult);

            // Verify complete structure
            expect($output)->toBeString()
                ->and($output)->toContain('# Link Validation Report')
                ->and($output)->toContain('**Generated:**')
                ->and($output)->toContain('## Summary')
                ->and($output)->toContain('- **files_processed:** 3')
                ->and($output)->toContain('## Status: ❌ Failed')
                ->and($output)->toContain('Found 2 broken link(s).')
                ->and($output)->toContain('## Broken Links')
                ->and($output)->toContain('### external Links (1)')
                ->and($output)->toContain('### internal Links (1)')
                ->and($output)->toContain('| File | Link | Reason |')
                ->and($output)->toContain('| unknown | `https://broken-external.com` | Connection timeout |')
                ->and($output)->toContain('| unknown | `/broken-internal` | File not found |');
        });
    });
});
