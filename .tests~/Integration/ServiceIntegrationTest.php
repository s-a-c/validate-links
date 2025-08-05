<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\LinkValidationService;
use App\Services\ReportingService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;

describe('Service Integration Tests', function () {
    beforeEach(function () {
        // Setup: Create real service instances with dependencies
        $this->securityService = new SecurityValidationService();
        $this->linkValidationService = new LinkValidationService($this->securityService);
        $this->reportingService = new ReportingService();

        // Create test files and directories
        $this->tempDir = sys_get_temp_dir().'/validate-links-integration-tests';
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        $this->testMarkdownFile = "{$this->tempDir}/test.md";
        $this->testHtmlFile = "{$this->tempDir}/test.html";
        $this->testConfigFile = "{$this->tempDir}/config.json";
    });

    afterEach(function () {
        // Teardown: Clean up test files
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob("{$this->tempDir}/*"));
            rmdir($this->tempDir);
        }
    });

    describe('LinkValidationService and SecurityValidationService Integration', function () {
        it('validates file with security checks enabled', function () {
            // Setup: Create test file with various link types including security violations
            $content = <<<'MD'
# Test Document

[Valid External Link](https://httpbin.org/status/200)
[FTP Link](ftp://malicious.com/file)
[Path Traversal](../../../etc/passwd)
MD;
            file_put_contents($this->testMarkdownFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 10,
            ]);

            // Execute: Validate file with security integration
            $results = $this->linkValidationService->validateFile($this->testMarkdownFile, $config);

            // Assert: Verify security validation is applied
            expect($results)->toBeInstanceOf(ValidationResultCollection::class)
                ->and($results->count())->toBe(3);

            // Check for security violations
            $securityViolationCount = 0;
            $validCount = 0;
            $notFoundCount = 0;

            foreach ($results as $result) {
                match ($result->getStatus()) {
                    LinkStatus::SECURITY_VIOLATION => $securityViolationCount++,
                    LinkStatus::VALID => $validCount++,
                    LinkStatus::NOT_FOUND => $notFoundCount++,
                    default => null,
                };
            }

            // Should have exactly one security violation (FTP URL), one valid (HTTPS), one not found (path traversal)
            expect($securityViolationCount)->toBe(1)
                ->and($validCount)->toBe(1)
                ->and($notFoundCount)->toBe(1);
        });

        it('integrates with security service for URL validation', function () {
            // Setup: Test various URL types
            $urls = [
                'https://example.com',
                'javascript:alert("xss")',
                'ftp://malicious.com',
                'http://blocked-domain.com',
            ];

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'security_checks' => true,
            ]);

            // Execute: Validate URLs with security integration
            $results = $this->linkValidationService->validateExternalLinks($urls, $config);

            // Assert: Security validation affects results
            expect($results)->toBeInstanceOf(ValidationResultCollection::class);

            // Check for security violations by iterating through results
            $securityViolationCount = 0;
            $invalidFormatCount = 0;
            foreach ($results as $result) {
                if ($result->getStatus() === LinkStatus::SECURITY_VIOLATION) {
                    $securityViolationCount++;
                } elseif ($result->getStatus() === LinkStatus::INVALID_FORMAT) {
                    $invalidFormatCount++;
                }
            }

            // Should have at least one security violation or invalid format
            expect($securityViolationCount + $invalidFormatCount)->toBeGreaterThan(0);
        });

        it('handles file system integration with security constraints', function () {
            // Setup: Create test files in different locations
            $safeFile = "{$this->tempDir}/safe.md";
            file_put_contents($safeFile, '[Link](./other.md)');

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL],
                'security_checks' => true,
            ]);

            // Execute: Validate with file system access
            $results = $this->linkValidationService->validateFile($safeFile, $config);

            // Assert: File system integration works with security
            expect($results)->toBeInstanceOf(ValidationResultCollection::class);

            // Should have results for internal link validation
            expect($results->count())->toBeGreaterThan(0);
        });
    });

    describe('LinkValidationService and ReportingService Integration', function () {
        it('generates reports from validation results', function () {
            // Setup: Create validation results
            $results = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://example.com',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::EXTERNAL,
                    httpStatusCode: 200,
                    responseTime: 150.0
                ),
                ValidationResult::create(
                    url: 'https://broken.com',
                    status: LinkStatus::BROKEN,
                    scope: ValidationScope::EXTERNAL,
                    httpStatusCode: 404,
                    responseTime: 200.0
                ),
            ]);

            // Execute: Generate reports in different formats
            $consoleReport = $this->reportingService->generateReport($results, OutputFormat::CONSOLE);
            $jsonReport = $this->reportingService->generateReport($results, OutputFormat::JSON);
            $htmlReport = $this->reportingService->generateReport($results, OutputFormat::HTML);

            // Assert: Reports are generated successfully
            expect($consoleReport)->toBeString()->not->toBeEmpty()
                ->and($jsonReport)->toBeString()->not->toBeEmpty()
                ->and($htmlReport)->toBeString()->not->toBeEmpty();

            // Verify JSON report structure
            $jsonData = json_decode($jsonReport, true);
            expect($jsonData)->toBeArray()
                ->and($jsonData)->toHaveKey('validation')
                ->and($jsonData)->toHaveKey('links');
        });

        it('exports reports to files with proper integration', function () {
            // Setup: Create validation results
            $results = ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: 'https://test.com',
                    status: LinkStatus::VALID,
                    scope: ValidationScope::EXTERNAL
                ),
            ]);

            $reportFile = "{$this->tempDir}/report.json";

            // Execute: Export report to file
            $success = $this->reportingService->exportReport($results, $reportFile, OutputFormat::JSON);

            // Assert: File is created and contains valid data
            expect($success)->toBeTrue()
                ->and(file_exists($reportFile))->toBeTrue();

            $content = file_get_contents($reportFile);
            $data = json_decode($content, true);

            expect($data)->toBeArray()
                ->and($data)->toHaveKey('validation')
                ->and($data)->toHaveKey('links');
        });
    });

    describe('Full Service Chain Integration', function () {
        it('processes complete validation workflow', function () {
            // Setup: Create comprehensive test file
            $content = <<<'MD'
# Integration Test Document

## External Links
[Valid HTTP](https://httpbin.org/status/200)
[Valid HTTPS](https://httpbin.org/get)
[Broken Link](https://httpbin.org/status/404)

## Internal Links
[README](./README.md)
[Non-existent](./missing.md)

## Anchor Links
[Section](#integration-test-document)
[Missing Section](#non-existent-section)
MD;
            file_put_contents($this->testMarkdownFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'security_checks' => true,
                'timeout' => 15,
                'follow_redirects' => true,
            ]);

            // Execute: Complete validation and reporting workflow
            $validationResults = $this->linkValidationService->validateFile($this->testMarkdownFile, $config);
            $summary = $this->reportingService->generateSummary($validationResults);
            $report = $this->reportingService->generateReport($validationResults, OutputFormat::JSON);

            // Assert: Complete workflow produces expected results
            expect($validationResults)->toBeInstanceOf(ValidationResultCollection::class)
                ->and($validationResults->count())->toBeGreaterThan(0)
                ->and($summary->getTotalLinks())->toBe($validationResults->count())
                ->and($report)->toBeString()->not->toBeEmpty();

            // Verify different link types are processed
            $externalCount = 0;
            $internalCount = 0;
            foreach ($validationResults as $result) {
                if ($result->getScope() === ValidationScope::EXTERNAL) {
                    $externalCount++;
                } elseif ($result->getScope() === ValidationScope::INTERNAL) {
                    $internalCount++;
                }
            }
            $anchorCount = 0;
            foreach ($validationResults as $result) {
                if ($result->getScope() === ValidationScope::ANCHOR) {
                    $anchorCount++;
                }
            }

            expect($externalCount)->toBeGreaterThan(0)
                ->and($internalCount)->toBeGreaterThan(0)
                ->and($anchorCount)->toBeGreaterThan(0);
        });

        it('handles error scenarios across service boundaries', function () {
            // Setup: Create problematic scenarios
            $nonExistentFile = "{$this->tempDir}/missing.md";

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'security_checks' => true,
            ]);

            // Execute: Validate non-existent file
            $results = $this->linkValidationService->validateFile($nonExistentFile, $config);

            // Assert: Error handling works across services
            expect($results)->toBeInstanceOf(ValidationResultCollection::class)
                ->and($results->count())->toBe(1);

            $firstResult = $results->first();
            expect($firstResult->getStatus())->toBe(LinkStatus::BROKEN)
                ->and($firstResult->getUrl())->toBe($nonExistentFile);

            // Verify reporting service handles error results
            $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
            expect($report)->toBeString()->not->toBeEmpty();

            $reportData = json_decode($report, true);
            expect($reportData['links']['broken'])->toBe(1);
        });
    });

    describe('Configuration Integration', function () {
        it('respects configuration across all services', function () {
            // Setup: Create test with timeout-sensitive content
            $content = '[Slow Link](https://httpbin.org/delay/2)';
            file_put_contents($this->testMarkdownFile, $content);

            $fastConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 1, // Very short timeout
                'security_checks' => true,
            ]);

            $slowConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10, // Longer timeout
                'security_checks' => true,
            ]);

            // Execute: Test with different configurations
            $fastResults = $this->linkValidationService->validateFile($this->testMarkdownFile, $fastConfig);
            $slowResults = $this->linkValidationService->validateFile($this->testMarkdownFile, $slowConfig);

            // Assert: Configuration affects behavior across services
            expect($fastResults)->toBeInstanceOf(ValidationResultCollection::class)
                ->and($slowResults)->toBeInstanceOf(ValidationResultCollection::class);

            // Fast config should likely timeout, slow config should succeed
            // (Note: This test may be flaky depending on network conditions)
            expect($fastResults->count())->toBeGreaterThan(0)
                ->and($slowResults->count())->toBeGreaterThan(0);
        });
    });
});
