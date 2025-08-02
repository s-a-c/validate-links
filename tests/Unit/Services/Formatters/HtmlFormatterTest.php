<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Formatters\HtmlFormatter;
use App\Services\ValueObjects\ValidationResult;

describe('HtmlFormatter', function () {
    beforeEach(function () {
        $this->formatter = new HtmlFormatter();
        
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
        it('returns valid HTML document', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toBeString()
                ->and($output)->toContain('<!DOCTYPE html>')
                ->and($output)->toContain('<html lang="en">')
                ->and($output)->toContain('</html>')
                ->and($output)->toContain('<head>')
                ->and($output)->toContain('<body>');
        });

        it('includes CSS styles', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toContain('<style>')
                ->and($output)->toContain('</style>')
                ->and($output)->toContain('font-family:')
                ->and($output)->toContain('.container')
                ->and($output)->toContain('.header');
        });

        it('replaces content placeholder', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->not->toContain('{{CONTENT}}')
                ->and($output)->toContain('<div class="header">')
                ->and($output)->toContain('🔗 Link Validation Report');
        });
    });

    describe('generateContent method', function () {
        it('includes header section', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toContain('<div class="header">')
                ->and($output)->toContain('<h1>🔗 Link Validation Report</h1>')
                ->and($output)->toContain('class="timestamp"')
                ->and($output)->toContain('Generated:');
        });

        it('includes timestamp in correct format', function () {
            $output = $this->formatter->format($this->validResult);
            
            // Check that timestamp follows YYYY-MM-DD HH:MM:SS format
            expect($output)->toMatch('/Generated: \d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/');
        });

        it('includes summary section', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toContain('<div class="summary">')
                ->and($output)->toContain('<h2>📊 Summary</h2>')
                ->and($output)->toContain('<div class="stats-grid">');
        });

        it('displays statistics from ValidationResult', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toContain('files_processed')
                ->and($output)->toContain('total_links')
                ->and($output)->toContain('class="stat-item"')
                ->and($output)->toContain('class="stat-label"')
                ->and($output)->toContain('class="stat-value"');
        });
    });

    describe('status section', function () {
        it('shows success status for valid result', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toContain('<div class="status success">')
                ->and($output)->toContain('<h2>✅ Status: Success</h2>')
                ->and($output)->toContain('All links validated successfully!');
        });

        it('shows error status for invalid result', function () {
            $output = $this->formatter->format($this->invalidResult);
            
            expect($output)->toContain('<div class="status error">')
                ->and($output)->toContain('<h2>❌ Status: Failed</h2>')
                ->and($output)->toContain('Found 2 broken link(s).');
        });
    });

    describe('broken links section', function () {
        it('does not show broken links section for valid result', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->not->toContain('<div class="broken-links">')
                ->and($output)->not->toContain('<h2>❌ Broken Links</h2>');
        });

        it('shows broken links section for invalid result', function () {
            $output = $this->formatter->format($this->invalidResult);
            
            expect($output)->toContain('<div class="broken-links">')
                ->and($output)->toContain('<h2>❌ Broken Links</h2>')
                ->and($output)->toContain('<table class="links-table">');
        });

        it('groups broken links by type', function () {
            $output = $this->formatter->format($this->invalidResult);
            
            expect($output)->toContain('external Links')
                ->and($output)->toContain('internal Links')
                ->and($output)->toContain('<thead>')
                ->and($output)->toContain('<th>File</th>')
                ->and($output)->toContain('<th>Link</th>')
                ->and($output)->toContain('<th>Reason</th>');
        });

        it('displays broken link details in table', function () {
            $output = $this->formatter->format($this->invalidResult);
            
            expect($output)->toContain('https://broken-external.com')
                ->and($output)->toContain('/broken-internal')
                ->and($output)->toContain('Connection timeout')
                ->and($output)->toContain('File not found')
                ->and($output)->toContain('<code>')
                ->and($output)->toContain('</code>');
        });

        it('escapes HTML in broken link data', function () {
            // Create result with HTML characters
            $htmlResult = new ValidationResult(
                url: 'https://example.com/<script>alert("xss")</script>',
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: 'Error with <tags> & "quotes"',
                metadata: [
                    'broken_count' => 1,
                    'results' => [
                        new ValidationResult(
                            url: 'https://example.com/<script>alert("xss")</script>',
                            status: LinkStatus::BROKEN,
                            scope: ValidationScope::EXTERNAL,
                            error: 'Error with <tags> & "quotes"',
                            metadata: [],
                            filePath: 'test<file>.md'
                        ),
                    ],
                ],
                filePath: 'test<file>.md'
            );
            
            $output = $this->formatter->format($htmlResult);
            
            expect($output)->not->toContain('<script>')
                ->and($output)->toContain('&lt;script&gt;')
                ->and($output)->toContain('&lt;tags&gt;')
                ->and($output)->toContain('&quot;quotes&quot;');
        });
    });

    describe('HTML structure and validation', function () {
        it('produces well-formed HTML', function () {
            $output = $this->formatter->format($this->invalidResult);
            
            // Check for proper HTML structure
            expect($output)->toContain('<!DOCTYPE html>')
                ->and($output)->toContain('<meta charset="UTF-8">')
                ->and($output)->toContain('<title>Link Validation Report</title>')
                ->and($output)->toContain('<div class="container">');
        });

        it('includes responsive viewport meta tag', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toContain('<meta name="viewport" content="width=device-width, initial-scale=1.0">');
        });

        it('includes comprehensive CSS styling', function () {
            $output = $this->formatter->format($this->validResult);
            
            expect($output)->toContain('.stats-grid')
                ->and($output)->toContain('.stat-item')
                ->and($output)->toContain('.status.success')
                ->and($output)->toContain('.status.error')
                ->and($output)->toContain('.links-table');
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
                ->and($output)->toContain('<div class="stats-grid">')
                ->and($output)->toContain('✅ Status: Success');
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
            
            expect($output)->toContain('string_value')
                ->and($output)->toContain('unknown');
        });
    });
});
