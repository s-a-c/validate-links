<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;

describe('Validation Workflow Feature Tests', function () {
    beforeEach(function () {
        // Setup: Create test environment
        $this->tempDir = sys_get_temp_dir() . '/validate-links-validation-tests';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        $this->linkValidation = app(LinkValidationInterface::class);
        $this->reporting = app(ReportingInterface::class);
    });

    afterEach(function () {
        // Teardown: Clean up test files
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    });

    describe('Complete File Validation Workflow', function () {
        it('validates markdown file with mixed link types', function () {
            // Setup: Create comprehensive markdown file
            $markdownFile = $this->tempDir . '/comprehensive.md';
            $content = <<<'MD'
# Comprehensive Test Document

## Table of Contents
- [External Links](#external-links)
- [Internal Links](#internal-links)
- [Anchor Links](#anchor-links)

## External Links
[Valid HTTPS](https://httpbin.org/status/200)
[Valid HTTP](http://httpbin.org/status/200)
[Broken Link](https://httpbin.org/status/404)
[Server Error](https://httpbin.org/status/500)
[Redirect](https://httpbin.org/redirect/2)

## Internal Links
[README](./README.md)
[Config](./config.json)
[Missing File](./missing.md)

## Anchor Links
[Valid Anchor](#external-links)
[Invalid Anchor](#non-existent)

## Special Cases
[Empty Link]()
[Malformed](not-a-valid-url)
[JavaScript](javascript:alert('test'))

## Images and Media
![Image](https://httpbin.org/image/png)
[PDF Document](https://httpbin.org/pdf)
MD;
            file_put_contents($markdownFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
                'security_checks' => true,
                'follow_redirects' => true,
            ]);

            // Execute: Complete validation workflow
            $results = $this->linkValidation->validateFile($markdownFile, $config);
            $summary = $this->reporting->generateSummary($results);
            $report = $this->reporting->generateReport($results, OutputFormat::JSON);

            // Assert: Comprehensive validation results
            expect($results->count())->toBeGreaterThan(10)
                ->and($summary->getTotalLinks())->toBe($results->count())
                ->and($summary->getBrokenLinks())->toBeGreaterThan(0)
                ->and($report)->toBeString()->not->toBeEmpty();

            // Verify different link types are processed
            $externalLinks = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::EXTERNAL
            );
            $internalLinks = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::INTERNAL
            );
            $anchorLinks = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::ANCHOR
            );

            expect($externalLinks->count())->toBeGreaterThan(0)
                ->and($internalLinks->count())->toBeGreaterThan(0)
                ->and($anchorLinks->count())->toBeGreaterThan(0);
        });

        it('validates HTML file with complex structure', function () {
            // Setup: Create HTML file with various link types
            $htmlFile = $this->tempDir . '/test.html';
            $content = <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <title>Test HTML Document</title>
</head>
<body>
    <h1 id="main-title">Test Document</h1>
    
    <nav>
        <a href="#section1">Section 1</a>
        <a href="#section2">Section 2</a>
        <a href="#missing">Missing Section</a>
    </nav>
    
    <section id="section1">
        <h2>External Links</h2>
        <a href="https://httpbin.org/status/200">Valid Link</a>
        <a href="https://httpbin.org/status/404">Broken Link</a>
        <a href="http://httpbin.org/get">HTTP Link</a>
    </section>
    
    <section id="section2">
        <h2>Internal Links</h2>
        <a href="./style.css">Stylesheet</a>
        <a href="./script.js">JavaScript</a>
        <a href="./missing.html">Missing Page</a>
    </section>
    
    <footer>
        <a href="mailto:test@example.com">Email</a>
        <a href="tel:+1234567890">Phone</a>
        <a href="javascript:void(0)">JavaScript Link</a>
    </footer>
</body>
</html>
HTML;
            file_put_contents($htmlFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
                'security_checks' => true,
            ]);

            // Execute: Validate HTML file
            $results = $this->linkValidation->validateFile($htmlFile, $config);

            // Assert: HTML validation works correctly
            expect($results->count())->toBeGreaterThan(5);

            // Check for security violations (javascript: links)
            $securityViolations = $results->filter(fn($result) => 
                $result->getStatus() === LinkStatus::SECURITY_VIOLATION
            );
            expect($securityViolations->count())->toBeGreaterThan(0);
        });

        it('processes multiple files in batch', function () {
            // Setup: Create multiple test files
            $files = [];
            for ($i = 1; $i <= 5; $i++) {
                $file = $this->tempDir . "/file{$i}.md";
                $content = <<<MD
# File {$i}

[External Link {$i}](https://httpbin.org/status/200)
[Internal Link {$i}](./other{$i}.md)
[Anchor Link {$i}](#file-{$i})
MD;
                file_put_contents($file, $content);
                $files[] = $file;
            }

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
            ]);

            // Execute: Validate multiple files
            $allResults = [];
            foreach ($files as $file) {
                $results = $this->linkValidation->validateFile($file, $config);
                $allResults[] = $results;
            }

            // Assert: Batch processing works
            expect(count($allResults))->toBe(5);
            
            foreach ($allResults as $results) {
                expect($results->count())->toBeGreaterThan(0);
            }
        });
    });

    describe('Scope-Specific Validation Workflows', function () {
        it('validates only external links when scope is external', function () {
            // Setup: Create file with mixed link types
            $testFile = $this->tempDir . '/external-only.md';
            $content = <<<'MD'
[External Valid](https://httpbin.org/status/200)
[External Broken](https://httpbin.org/status/404)
[Internal Link](./README.md)
[Anchor Link](#section)
MD;
            file_put_contents($testFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate with external scope only
            $results = $this->linkValidation->validateFile($testFile, $config);

            // Assert: Only external links are validated
            $externalResults = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::EXTERNAL
            );
            $internalResults = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::INTERNAL
            );
            $anchorResults = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::ANCHOR
            );

            expect($externalResults->count())->toBeGreaterThan(0)
                ->and($internalResults->count())->toBe(0)
                ->and($anchorResults->count())->toBe(0);
        });

        it('validates only internal links when scope is internal', function () {
            // Setup: Create test files
            $mainFile = $this->tempDir . '/main.md';
            $targetFile = $this->tempDir . '/target.md';
            file_put_contents($targetFile, '# Target File');

            $content = <<<'MD'
[External Link](https://httpbin.org/status/200)
[Valid Internal](./target.md)
[Missing Internal](./missing.md)
[Anchor Link](#section)
MD;
            file_put_contents($mainFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate with internal scope only
            $results = $this->linkValidation->validateFile($mainFile, $config);

            // Assert: Only internal links are validated
            $internalResults = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::INTERNAL
            );
            $externalResults = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::EXTERNAL
            );

            expect($internalResults->count())->toBeGreaterThan(0)
                ->and($externalResults->count())->toBe(0);
        });

        it('validates only anchor links when scope is anchor', function () {
            // Setup: Create file with anchor links
            $testFile = $this->tempDir . '/anchors.md';
            $content = <<<'MD'
# Main Title

[External Link](https://httpbin.org/status/200)
[Internal Link](./other.md)
[Valid Anchor](#main-title)
[Invalid Anchor](#missing-section)

## Section 1
[Section Anchor](#section-1)
MD;
            file_put_contents($testFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ANCHOR],
                'timeout' => 10,
            ]);

            // Execute: Validate with anchor scope only
            $results = $this->linkValidation->validateFile($testFile, $config);

            // Assert: Only anchor links are validated
            $anchorResults = $results->filter(fn($result) => 
                $result->getScope() === ValidationScope::ANCHOR
            );
            $otherResults = $results->filter(fn($result) => 
                $result->getScope() !== ValidationScope::ANCHOR
            );

            expect($anchorResults->count())->toBeGreaterThan(0)
                ->and($otherResults->count())->toBe(0);
        });
    });

    describe('Configuration-Driven Workflows', function () {
        it('applies timeout configuration across validation', function () {
            // Setup: Create file with potentially slow links
            $testFile = $this->tempDir . '/timeout-test.md';
            $content = <<<'MD'
[Fast Link](https://httpbin.org/status/200)
[Slow Link](https://httpbin.org/delay/3)
[Very Slow Link](https://httpbin.org/delay/8)
MD;
            file_put_contents($testFile, $content);

            $shortTimeoutConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 2,
            ]);

            $longTimeoutConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Test with different timeout configurations
            $shortResults = $this->linkValidation->validateFile($testFile, $shortTimeoutConfig);
            $longResults = $this->linkValidation->validateFile($testFile, $longTimeoutConfig);

            // Assert: Timeout configuration affects results
            expect($shortResults->count())->toBeGreaterThan(0)
                ->and($longResults->count())->toBeGreaterThan(0);

            // Short timeout should have more timeouts/failures
            $shortTimeouts = $shortResults->filter(fn($result) => 
                $result->getStatus() === LinkStatus::TIMEOUT
            );
            $longTimeouts = $longResults->filter(fn($result) => 
                $result->getStatus() === LinkStatus::TIMEOUT
            );

            expect($shortTimeouts->count())->toBeGreaterThanOrEqual($longTimeouts->count());
        });

        it('applies security configuration consistently', function () {
            // Setup: Create file with security-sensitive links
            $testFile = $this->tempDir . '/security-test.md';
            $content = <<<'MD'
[Safe HTTPS](https://httpbin.org/status/200)
[JavaScript Link](javascript:alert('xss'))
[FTP Link](ftp://example.com/file.txt)
[Data URL](data:text/html,<script>alert('xss')</script>)
MD;
            file_put_contents($testFile, $content);

            $secureConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'security_checks' => true,
            ]);

            $insecureConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'security_checks' => false,
            ]);

            // Execute: Test with different security configurations
            $secureResults = $this->linkValidation->validateFile($testFile, $secureConfig);
            $insecureResults = $this->linkValidation->validateFile($testFile, $insecureConfig);

            // Assert: Security configuration affects results
            $secureViolations = $secureResults->filter(fn($result) => 
                $result->getStatus() === LinkStatus::SECURITY_VIOLATION
            );
            $insecureViolations = $insecureResults->filter(fn($result) => 
                $result->getStatus() === LinkStatus::SECURITY_VIOLATION
            );

            expect($secureViolations->count())->toBeGreaterThan($insecureViolations->count());
        });
    });

    describe('Reporting Workflow Integration', function () {
        it('generates comprehensive reports from validation results', function () {
            // Setup: Create file and validate
            $testFile = $this->tempDir . '/report-test.md';
            $content = <<<'MD'
[Valid Link](https://httpbin.org/status/200)
[Broken Link](https://httpbin.org/status/404)
[Internal Link](./missing.md)
[Anchor Link](#missing-section)
MD;
            file_put_contents($testFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
            ]);

            // Execute: Validate and generate reports
            $results = $this->linkValidation->validateFile($testFile, $config);
            
            $consoleReport = $this->reporting->generateReport($results, OutputFormat::CONSOLE);
            $jsonReport = $this->reporting->generateReport($results, OutputFormat::JSON);
            $htmlReport = $this->reporting->generateReport($results, OutputFormat::HTML);
            $markdownReport = $this->reporting->generateReport($results, OutputFormat::MARKDOWN);

            // Assert: All report formats are generated
            expect($consoleReport)->toBeString()->not->toBeEmpty()
                ->and($jsonReport)->toBeString()->not->toBeEmpty()
                ->and($htmlReport)->toBeString()->not->toBeEmpty()
                ->and($markdownReport)->toBeString()->not->toBeEmpty();

            // Verify JSON report structure
            $jsonData = json_decode($jsonReport, true);
            expect($jsonData)->toBeArray()
                ->and($jsonData)->toHaveKey('summary')
                ->and($jsonData)->toHaveKey('results')
                ->and($jsonData['summary'])->toHaveKey('total_links')
                ->and($jsonData['summary'])->toHaveKey('broken_links')
                ->and($jsonData['summary'])->toHaveKey('success_rate');
        });

        it('exports reports to files with proper formatting', function () {
            // Setup: Create validation results
            $testFile = $this->tempDir . '/export-test.md';
            file_put_contents($testFile, '[Test Link](https://httpbin.org/status/200)');

            $config = ValidationConfig::withDefaults();
            $results = $this->linkValidation->validateFile($testFile, $config);

            // Execute: Export reports to files
            $jsonFile = $this->tempDir . '/report.json';
            $htmlFile = $this->tempDir . '/report.html';
            $markdownFile = $this->tempDir . '/report.md';

            $jsonSuccess = $this->reporting->exportReport($results, $jsonFile, OutputFormat::JSON);
            $htmlSuccess = $this->reporting->exportReport($results, $htmlFile, OutputFormat::HTML);
            $markdownSuccess = $this->reporting->exportReport($results, $markdownFile, OutputFormat::MARKDOWN);

            // Assert: Files are exported successfully
            expect($jsonSuccess)->toBeTrue()
                ->and($htmlSuccess)->toBeTrue()
                ->and($markdownSuccess)->toBeTrue()
                ->and(file_exists($jsonFile))->toBeTrue()
                ->and(file_exists($htmlFile))->toBeTrue()
                ->and(file_exists($markdownFile))->toBeTrue();

            // Verify file contents
            $jsonContent = file_get_contents($jsonFile);
            expect(json_validate($jsonContent))->toBeTrue();

            $htmlContent = file_get_contents($htmlFile);
            expect($htmlContent)->toContain('<html>')
                ->and($htmlContent)->toContain('</html>');
        });
    });

    describe('Error Recovery Workflows', function () {
        it('continues validation after encountering errors', function () {
            // Setup: Create file with mix of valid and problematic links
            $testFile = $this->tempDir . '/error-recovery.md';
            $content = <<<'MD'
[Valid Link](https://httpbin.org/status/200)
[Broken Link](https://httpbin.org/status/404)
[Timeout Link](https://httpbin.org/delay/15)
[Invalid URL](not-a-url)
[Another Valid](https://httpbin.org/get)
MD;
            file_put_contents($testFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 5,
            ]);

            // Execute: Validate with error conditions
            $results = $this->linkValidation->validateFile($testFile, $config);

            // Assert: Validation continues despite errors
            expect($results->count())->toBe(5);

            $validResults = $results->filter(fn($result) => 
                $result->getStatus() === LinkStatus::VALID
            );
            $errorResults = $results->filter(fn($result) => 
                !$result->isValid()
            );

            expect($validResults->count())->toBeGreaterThan(0)
                ->and($errorResults->count())->toBeGreaterThan(0);
        });

        it('handles file system errors gracefully', function () {
            // Setup: Create scenarios with file system issues
            $nonExistentFile = $this->tempDir . '/missing.md';
            
            $config = ValidationConfig::withDefaults();

            // Execute: Validate non-existent file
            $results = $this->linkValidation->validateFile($nonExistentFile, $config);

            // Assert: File system errors are handled
            expect($results->count())->toBe(1);
            
            $result = $results->first();
            expect($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getUrl())->toBe($nonExistentFile);
        });
    });
});
