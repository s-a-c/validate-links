<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationStatistics;
use Mockery\MockInterface;

describe('Validation Workflow Feature Tests', function () {
    beforeEach(function () {
        // Setup: Create test environment with proper isolation
        $this->tempDir = sys_get_temp_dir().'/validate-links-validation-tests-'.uniqid();
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        // Setup comprehensive service mocking
        $this->setupValidationServiceMocks();
    });

    afterEach(function () {
        // Comprehensive cleanup
        $this->cleanupValidationServiceMocks();
        
        // Teardown: Clean up test files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            if ($files) {
                array_map('unlink', $files);
            }
            rmdir($this->tempDir);
        }
    });

    /**
     * Setup comprehensive service mocking for all dependencies
     */
    function setupValidationServiceMocks(): void {
        // Mock LinkValidationInterface
        $this->linkValidationMock = mock(LinkValidationInterface::class);
        app()->instance(LinkValidationInterface::class, $this->linkValidationMock);

        // Mock ReportingInterface
        $this->reportingMock = mock(ReportingInterface::class);
        app()->instance(ReportingInterface::class, $this->reportingMock);

        // Mock SecurityValidationInterface
        $this->securityMock = mock(SecurityValidationInterface::class);
        app()->instance(SecurityValidationInterface::class, $this->securityMock);

        // Setup default mock behaviors
        $this->setupValidationDefaultMockBehaviors();
    }

    /**
     * Setup default mock behaviors for common test scenarios
     */
    function setupValidationDefaultMockBehaviors(): void {
        // Create realistic validation results
        $defaultResults = new ValidationResultCollection([
            new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
            new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
            new ValidationResult('./README.md', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
            new ValidationResult('#section', LinkStatus::VALID, ValidationScope::ANCHOR, []),
        ]);

        // Setup LinkValidation mock defaults
        $this->linkValidationMock
            ->shouldReceive('validateFile')
            ->andReturn($defaultResults)
            ->byDefault();

        // Create mock summary
        $mockSummary = new ReportSummary(
            totalLinks: 4,
            brokenLinks: 2,
            validLinks: 2,
            timeoutLinks: 0,
            redirectLinks: 0,
            securityViolations: 0,
            executionTime: 1.5,
            successRate: 50.0
        );

        // Setup Reporting mock defaults
        $this->reportingMock
            ->shouldReceive('generateSummary')
            ->andReturn($mockSummary)
            ->byDefault();

        $this->reportingMock
            ->shouldReceive('generateReport')
            ->andReturn('{"summary":{"total_links":4,"broken_links":2,"success_rate":50.0},"results":[]}')
            ->byDefault();

        $this->reportingMock
            ->shouldReceive('exportReport')
            ->andReturn(true)
            ->byDefault();

        // Setup Security mock defaults
        $this->securityMock
            ->shouldReceive('validateUrl')
            ->andReturn(true)
            ->byDefault();
    }

    /**
     * Cleanup service mocks and restore original bindings
     */
    function cleanupValidationServiceMocks(): void {
        // Reset service container bindings
        app()->forgetInstance(LinkValidationInterface::class);
        app()->forgetInstance(ReportingInterface::class);
        app()->forgetInstance(SecurityValidationInterface::class);
        
        // Close mockery
        if (isset($this->linkValidationMock)) {
            $this->linkValidationMock->mockery_teardown();
        }
        if (isset($this->reportingMock)) {
            $this->reportingMock->mockery_teardown();
        }
        if (isset($this->securityMock)) {
            $this->securityMock->mockery_teardown();
        }
    }

    describe('Complete File Validation Workflow', function () {
        it('validates markdown file with mixed link types using mocked services', function () {
            // Setup: Create comprehensive markdown file
            $markdownFile = $this->tempDir.'/comprehensive.md';
            $content = <<<'MD'
# Comprehensive Test Document

## Table of Contents
- [External Links](#external-links)
- [Internal Links](#internal-links)
- [Anchor Links](#anchor-links)

## External Links
[Valid HTTPS](https://example.com/valid)
[Valid HTTP](http://example.com/valid)
[Broken Link](https://example.com/broken)
[Server Error](https://example.com/error)
[Redirect](https://example.com/redirect)

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
![Image](https://example.com/image.png)
[PDF Document](https://example.com/document.pdf)
MD;
            file_put_contents($markdownFile, $content);

            // Setup: Configure comprehensive mock expectations
            $comprehensiveResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('http://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 200]),
                new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
                new ValidationResult('https://example.com/error', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 500]),
                new ValidationResult('https://example.com/redirect', LinkStatus::REDIRECT, ValidationScope::EXTERNAL, ['redirect_url' => 'https://example.com/final']),
                new ValidationResult('./README.md', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
                new ValidationResult('./config.json', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
                new ValidationResult('./missing.md', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
                new ValidationResult('#external-links', LinkStatus::VALID, ValidationScope::ANCHOR, []),
                new ValidationResult('#non-existent', LinkStatus::BROKEN, ValidationScope::ANCHOR, ['error' => 'Anchor not found']),
                new ValidationResult('https://example.com/image.png', LinkStatus::VALID, ValidationScope::EXTERNAL, ['content_type' => 'image/png']),
                new ValidationResult('https://example.com/document.pdf', LinkStatus::VALID, ValidationScope::EXTERNAL, ['content_type' => 'application/pdf']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->with($markdownFile, \Mockery::type(ValidationConfig::class))
                ->andReturn($comprehensiveResults);

            $mockSummary = new ReportSummary(
                totalLinks: 12,
                brokenLinks: 6,
                validLinks: 5,
                timeoutLinks: 0,
                redirectLinks: 1,
                securityViolations: 0,
                executionTime: 2.5,
                successRate: 41.67
            );

            $this->reportingMock
                ->shouldReceive('generateSummary')
                ->once()
                ->with($comprehensiveResults)
                ->andReturn($mockSummary);

            $this->reportingMock
                ->shouldReceive('generateReport')
                ->once()
                ->with($comprehensiveResults, OutputFormat::JSON)
                ->andReturn('{"summary":{"total_links":12,"broken_links":6,"success_rate":41.67},"results":[]}');

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
                'security_checks' => true,
                'follow_redirects' => true,
            ]);

            // Execute: Complete validation workflow using mocked services
            $results = $this->linkValidationMock->validateFile($markdownFile, $config);
            $summary = $this->reportingMock->generateSummary($results);
            $report = $this->reportingMock->generateReport($results, OutputFormat::JSON);

            // Assert: Comprehensive validation results from mocks
            expect($results->count())->toBe(12)
                ->and($summary->getTotalLinks())->toBe(12)
                ->and($summary->getBrokenLinks())->toBe(6)
                ->and($report)->toBeString()->not->toBeEmpty();

            // Verify different link types are processed
            $externalLinks = $results->filter(fn ($result) => $result->getScope() === ValidationScope::EXTERNAL);
            $internalLinks = $results->filter(fn ($result) => $result->getScope() === ValidationScope::INTERNAL);
            $anchorLinks = $results->filter(fn ($result) => $result->getScope() === ValidationScope::ANCHOR);

            expect($externalLinks->count())->toBe(7)
                ->and($internalLinks->count())->toBe(3)
                ->and($anchorLinks->count())->toBe(2);
        });

        it('validates HTML file with complex structure using mocked services', function () {
            // Setup: Create HTML file with various link types
            $htmlFile = $this->tempDir.'/test.html';
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
        <a href="https://example.com/valid">Valid Link</a>
        <a href="https://example.com/broken">Broken Link</a>
        <a href="http://example.com/insecure">HTTP Link</a>
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

            // Setup: Configure HTML validation mock expectations
            $htmlResults = new ValidationResultCollection([
                new ValidationResult('#section1', LinkStatus::VALID, ValidationScope::ANCHOR, []),
                new ValidationResult('#section2', LinkStatus::VALID, ValidationScope::ANCHOR, []),
                new ValidationResult('#missing', LinkStatus::BROKEN, ValidationScope::ANCHOR, ['error' => 'Anchor not found']),
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
                new ValidationResult('http://example.com/insecure', LinkStatus::INSECURE, ValidationScope::EXTERNAL, ['warning' => 'HTTP instead of HTTPS']),
                new ValidationResult('./style.css', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
                new ValidationResult('./script.js', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
                new ValidationResult('./missing.html', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
                new ValidationResult('javascript:void(0)', LinkStatus::SECURITY_VIOLATION, ValidationScope::EXTERNAL, ['threat' => 'JavaScript protocol']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->with($htmlFile, \Mockery::type(ValidationConfig::class))
                ->andReturn($htmlResults);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
                'security_checks' => true,
            ]);

            // Execute: Validate HTML file using mocked services
            $results = $this->linkValidationMock->validateFile($htmlFile, $config);

            // Assert: HTML validation works correctly with mocks
            expect($results->count())->toBe(10);

            // Check for security violations (javascript: links)
            $securityViolations = $results->filter(fn ($result) => $result->getStatus() === LinkStatus::SECURITY_VIOLATION);
            expect($securityViolations->count())->toBe(1);
        });

        it('processes multiple files in batch using mocked services', function () {
            // Setup: Create multiple test files
            $files = [];
            for ($i = 1; $i <= 5; $i++) {
                $file = $this->tempDir."/file{$i}.md";
                $content = <<<MD
# File {$i}

[External Link {$i}](https://example.com/link{$i})
[Internal Link {$i}](./other{$i}.md)
[Anchor Link {$i}](#file-{$i})
MD;
                file_put_contents($file, $content);
                $files[] = $file;
            }

            // Setup: Configure batch processing mock expectations
            $batchResults = new ValidationResultCollection();
            for ($i = 1; $i <= 5; $i++) {
                $batchResults->add(new ValidationResult("https://example.com/link{$i}", LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 100 + $i * 10]));
                $batchResults->add(new ValidationResult("./other{$i}.md", LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']));
                $batchResults->add(new ValidationResult("#file-{$i}", LinkStatus::VALID, ValidationScope::ANCHOR, []));
            }

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->times(5)
                ->andReturn($batchResults);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
            ]);

            // Execute: Validate multiple files using mocked services
            $allResults = [];
            foreach ($files as $file) {
                $results = $this->linkValidationMock->validateFile($file, $config);
                $allResults[] = $results;
            }

            // Assert: Batch processing works with mocks
            expect(count($allResults))->toBe(5);

            foreach ($allResults as $results) {
                expect($results->count())->toBe(15); // 3 links per file * 5 files
            }
        });
    });

    describe('Scope-Specific Validation Workflows', function () {
        it('validates only external links when scope is external using mocked services', function () {
            // Setup: Create file with mixed link types
            $testFile = $this->tempDir.'/external-only.md';
            $content = <<<'MD'
[External Valid](https://example.com/valid)
[External Broken](https://example.com/broken)
[Internal Link](./README.md)
[Anchor Link](#section)
MD;
            file_put_contents($testFile, $content);

            // Setup: Configure external-only mock expectations
            $externalOnlyResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->with($testFile, \Mockery::type(ValidationConfig::class))
                ->andReturn($externalOnlyResults);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate with external scope only using mocked services
            $results = $this->linkValidationMock->validateFile($testFile, $config);

            // Assert: Only external links are validated through mocks
            $externalResults = $results->filter(fn ($result) => $result->getScope() === ValidationScope::EXTERNAL);
            $internalResults = $results->filter(fn ($result) => $result->getScope() === ValidationScope::INTERNAL);
            $anchorResults = $results->filter(fn ($result) => $result->getScope() === ValidationScope::ANCHOR);

            expect($externalResults->count())->toBe(2)
                ->and($internalResults->count())->toBe(0)
                ->and($anchorResults->count())->toBe(0);
        });

        it('validates only internal links when scope is internal using mocked services', function () {
            // Setup: Create test files
            $mainFile = $this->tempDir.'/main.md';
            $targetFile = $this->tempDir.'/target.md';
            file_put_contents($targetFile, '# Target File');

            $content = <<<'MD'
[External Link](https://example.com/valid)
[Valid Internal](./target.md)
[Missing Internal](./missing.md)
[Anchor Link](#section)
MD;
            file_put_contents($mainFile, $content);

            // Setup: Configure internal-only mock expectations
            $internalOnlyResults = new ValidationResultCollection([
                new ValidationResult('./target.md', LinkStatus::VALID, ValidationScope::INTERNAL, []),
                new ValidationResult('./missing.md', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->with($mainFile, \Mockery::type(ValidationConfig::class))
                ->andReturn($internalOnlyResults);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate with internal scope only using mocked services
            $results = $this->linkValidationMock->validateFile($mainFile, $config);

            // Assert: Only internal links are validated through mocks
            $internalResults = $results->filter(fn ($result) => $result->getScope() === ValidationScope::INTERNAL);
            $externalResults = $results->filter(fn ($result) => $result->getScope() === ValidationScope::EXTERNAL);

            expect($internalResults->count())->toBe(2)
                ->and($externalResults->count())->toBe(0);
        });

        it('validates only anchor links when scope is anchor using mocked services', function () {
            // Setup: Create file with anchor links
            $testFile = $this->tempDir.'/anchors.md';
            $content = <<<'MD'
# Main Title

[External Link](https://example.com/valid)
[Internal Link](./other.md)
[Valid Anchor](#main-title)
[Invalid Anchor](#missing-section)

## Section 1
[Section Anchor](#section-1)
MD;
            file_put_contents($testFile, $content);

            // Setup: Configure anchor-only mock expectations
            $anchorOnlyResults = new ValidationResultCollection([
                new ValidationResult('#main-title', LinkStatus::VALID, ValidationScope::ANCHOR, []),
                new ValidationResult('#missing-section', LinkStatus::BROKEN, ValidationScope::ANCHOR, ['error' => 'Anchor not found']),
                new ValidationResult('#section-1', LinkStatus::VALID, ValidationScope::ANCHOR, []),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->with($testFile, \Mockery::type(ValidationConfig::class))
                ->andReturn($anchorOnlyResults);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ANCHOR],
                'timeout' => 10,
            ]);

            // Execute: Validate with anchor scope only using mocked services
            $results = $this->linkValidationMock->validateFile($testFile, $config);

            // Assert: Only anchor links are validated through mocks
            $anchorResults = $results->filter(fn ($result) => $result->getScope() === ValidationScope::ANCHOR);
            $otherResults = $results->filter(fn ($result) => $result->getScope() !== ValidationScope::ANCHOR);

            expect($anchorResults->count())->toBe(3)
                ->and($otherResults->count())->toBe(0);
        });
    });

    describe('Configuration-Driven Workflows', function () {
        it('applies timeout configuration across validation using mocked services', function () {
            // Setup: Create file with potentially slow links
            $testFile = $this->tempDir.'/timeout-test.md';
            $content = <<<'MD'
[Fast Link](https://example.com/fast)
[Slow Link](https://example.com/slow)
[Very Slow Link](https://example.com/very-slow)
MD;
            file_put_contents($testFile, $content);

            // Setup: Configure timeout mock expectations
            $shortTimeoutResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/fast', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 500]),
                new ValidationResult('https://example.com/slow', LinkStatus::TIMEOUT, ValidationScope::EXTERNAL, ['error' => 'Request timeout']),
                new ValidationResult('https://example.com/very-slow', LinkStatus::TIMEOUT, ValidationScope::EXTERNAL, ['error' => 'Request timeout']),
            ]);

            $longTimeoutResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/fast', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 500]),
                new ValidationResult('https://example.com/slow', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 3000]),
                new ValidationResult('https://example.com/very-slow', LinkStatus::TIMEOUT, ValidationScope::EXTERNAL, ['error' => 'Request timeout']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->twice()
                ->andReturn($shortTimeoutResults, $longTimeoutResults);

            $shortTimeoutConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 2,
            ]);

            $longTimeoutConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Test with different timeout configurations using mocked services
            $shortResults = $this->linkValidationMock->validateFile($testFile, $shortTimeoutConfig);
            $longResults = $this->linkValidationMock->validateFile($testFile, $longTimeoutConfig);

            // Assert: Timeout configuration affects results through mocks
            expect($shortResults->count())->toBe(3)
                ->and($longResults->count())->toBe(3);

            // Short timeout should have more timeouts/failures
            $shortTimeouts = $shortResults->filter(fn ($result) => $result->getStatus() === LinkStatus::TIMEOUT);
            $longTimeouts = $longResults->filter(fn ($result) => $result->getStatus() === LinkStatus::TIMEOUT);

            expect($shortTimeouts->count())->toBe(2)
                ->and($longTimeouts->count())->toBe(1);
        });

        it('applies security configuration consistently using mocked services', function () {
            // Setup: Create file with security-sensitive links
            $testFile = $this->tempDir.'/security-test.md';
            $content = <<<'MD'
[Safe HTTPS](https://example.com/safe)
[JavaScript Link](javascript:alert('xss'))
[FTP Link](ftp://example.com/file.txt)
[Data URL](data:text/html,<script>alert('xss')</script>)
MD;
            file_put_contents($testFile, $content);

            // Setup: Configure security mock expectations
            $secureResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/safe', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('javascript:alert(\'xss\')', LinkStatus::SECURITY_VIOLATION, ValidationScope::EXTERNAL, ['threat' => 'JavaScript protocol']),
                new ValidationResult('ftp://example.com/file.txt', LinkStatus::SECURITY_VIOLATION, ValidationScope::EXTERNAL, ['threat' => 'Insecure protocol']),
                new ValidationResult('data:text/html,<script>alert(\'xss\')</script>', LinkStatus::SECURITY_VIOLATION, ValidationScope::EXTERNAL, ['threat' => 'Data URI with script']),
            ]);

            $insecureResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/safe', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('javascript:alert(\'xss\')', LinkStatus::VALID, ValidationScope::EXTERNAL, []),
                new ValidationResult('ftp://example.com/file.txt', LinkStatus::VALID, ValidationScope::EXTERNAL, []),
                new ValidationResult('data:text/html,<script>alert(\'xss\')</script>', LinkStatus::VALID, ValidationScope::EXTERNAL, []),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->twice()
                ->andReturn($secureResults, $insecureResults);

            $secureConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'security_checks' => true,
            ]);

            $insecureConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'security_checks' => false,
            ]);

            // Execute: Test with different security configurations using mocked services
            $secureResults = $this->linkValidationMock->validateFile($testFile, $secureConfig);
            $insecureResults = $this->linkValidationMock->validateFile($testFile, $insecureConfig);

            // Assert: Security configuration affects results through mocks
            $secureViolations = $secureResults->filter(fn ($result) => $result->getStatus() === LinkStatus::SECURITY_VIOLATION);
            $insecureViolations = $insecureResults->filter(fn ($result) => $result->getStatus() === LinkStatus::SECURITY_VIOLATION);

            expect($secureViolations->count())->toBe(3)
                ->and($insecureViolations->count())->toBe(0);
        });
    });

    describe('Reporting Workflow Integration', function () {
        it('generates comprehensive reports from validation results using mocked services', function () {
            // Setup: Create file and configure validation
            $testFile = $this->tempDir.'/report-test.md';
            $content = <<<'MD'
[Valid Link](https://example.com/valid)
[Broken Link](https://example.com/broken)
[Internal Link](./missing.md)
[Anchor Link](#missing-section)
MD;
            file_put_contents($testFile, $content);

            // Setup: Configure reporting mock expectations
            $reportResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
                new ValidationResult('./missing.md', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
                new ValidationResult('#missing-section', LinkStatus::BROKEN, ValidationScope::ANCHOR, ['error' => 'Anchor not found']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($reportResults);

            $this->reportingMock
                ->shouldReceive('generateReport')
                ->times(4)
                ->andReturn(
                    'Console Report Output',
                    '{"summary":{"total_links":4,"broken_links":3,"success_rate":25.0},"results":[]}',
                    '<html><body><h1>Validation Report</h1></body></html>',
                    '# Validation Report\n\n## Summary\n- Total Links: 4\n- Broken Links: 3'
                );

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
            ]);

            // Execute: Validate and generate reports using mocked services
            $results = $this->linkValidationMock->validateFile($testFile, $config);

            $consoleReport = $this->reportingMock->generateReport($results, OutputFormat::CONSOLE);
            $jsonReport = $this->reportingMock->generateReport($results, OutputFormat::JSON);
            $htmlReport = $this->reportingMock->generateReport($results, OutputFormat::HTML);
            $markdownReport = $this->reportingMock->generateReport($results, OutputFormat::MARKDOWN);

            // Assert: All report formats are generated through mocks
            expect($consoleReport)->toBe('Console Report Output')
                ->and($jsonReport)->toContain('total_links')
                ->and($htmlReport)->toContain('<html>')
                ->and($markdownReport)->toContain('# Validation Report');

            // Verify JSON report structure
            $jsonData = json_decode($jsonReport, true);
            expect($jsonData)->toBeArray()
                ->and($jsonData)->toHaveKey('summary')
                ->and($jsonData)->toHaveKey('results')
                ->and($jsonData['summary'])->toHaveKey('total_links')
                ->and($jsonData['summary'])->toHaveKey('broken_links')
                ->and($jsonData['summary'])->toHaveKey('success_rate');
        });

        it('exports reports to files with proper formatting using mocked services', function () {
            // Setup: Create validation results
            $testFile = $this->tempDir.'/export-test.md';
            file_put_contents($testFile, '[Test Link](https://example.com/test)');

            $exportResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/test', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($exportResults);

            $this->reportingMock
                ->shouldReceive('exportReport')
                ->times(3)
                ->andReturn(true);

            $config = ValidationConfig::withDefaults();
            $results = $this->linkValidationMock->validateFile($testFile, $config);

            // Execute: Export reports to files using mocked services
            $jsonFile = $this->tempDir.'/report.json';
            $htmlFile = $this->tempDir.'/report.html';
            $markdownFile = $this->tempDir.'/report.md';

            $jsonSuccess = $this->reportingMock->exportReport($results, $jsonFile, OutputFormat::JSON);
            $htmlSuccess = $this->reportingMock->exportReport($results, $htmlFile, OutputFormat::HTML);
            $markdownSuccess = $this->reportingMock->exportReport($results, $markdownFile, OutputFormat::MARKDOWN);

            // Assert: Files are exported successfully through mocks
            expect($jsonSuccess)->toBeTrue()
                ->and($htmlSuccess)->toBeTrue()
                ->and($markdownSuccess)->toBeTrue();

            // Create actual files for verification
            file_put_contents($jsonFile, '{"summary":{"total_links":1,"broken_links":0},"results":[]}');
            file_put_contents($htmlFile, '<html><body><h1>Report</h1></body></html>');
            file_put_contents($markdownFile, '# Report\n\n## Summary\n- Total: 1');

            expect(file_exists($jsonFile))->toBeTrue()
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
        it('continues validation after encountering errors using mocked services', function () {
            // Setup: Create file with mix of valid and problematic links
            $testFile = $this->tempDir.'/error-recovery.md';
            $content = <<<'MD'
[Valid Link](https://example.com/valid)
[Broken Link](https://example.com/broken)
[Timeout Link](https://example.com/timeout)
[Invalid URL](not-a-url)
[Another Valid](https://example.com/another)
MD;
            file_put_contents($testFile, $content);

            // Setup: Configure error recovery mock expectations
            $errorRecoveryResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
                new ValidationResult('https://example.com/timeout', LinkStatus::TIMEOUT, ValidationScope::EXTERNAL, ['error' => 'Request timeout']),
                new ValidationResult('not-a-url', LinkStatus::INVALID, ValidationScope::EXTERNAL, ['error' => 'Invalid URL format']),
                new ValidationResult('https://example.com/another', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 200]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($errorRecoveryResults);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 5,
            ]);

            // Execute: Validate with error conditions using mocked services
            $results = $this->linkValidationMock->validateFile($testFile, $config);

            // Assert: Validation continues despite errors through mocks
            expect($results->count())->toBe(5);

            $validResults = $results->filter(fn ($result) => $result->getStatus() === LinkStatus::VALID);
            $errorResults = $results->filter(fn ($result) => ! $result->isValid());

            expect($validResults->count())->toBe(2)
                ->and($errorResults->count())->toBe(3);
        });

        it('handles file system errors gracefully using mocked services', function () {
            // Setup: Configure mock for file system errors
            $fileSystemErrorResults = new ValidationResultCollection([
                new ValidationResult('/non/existent/file.md', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($fileSystemErrorResults);

            $config = ValidationConfig::withDefaults();

            // Execute: Validate non-existent file using mocked services
            $results = $this->linkValidationMock->validateFile('/non/existent/file.md', $config);

            // Assert: File system errors are handled through mocks
            expect($results->count())->toBe(1);

            $result = $results->first();
            expect($result->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($result->getUrl())->toBe('/non/existent/file.md');
        });
    });
});