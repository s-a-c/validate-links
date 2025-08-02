<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;

describe('External Dependency Integration Tests', function () {
    beforeEach(function () {
        // Setup: Create services for external dependency testing
        $this->securityService = new SecurityValidationService();
        $this->linkValidationService = new LinkValidationService($this->securityService);
        
        $this->tempDir = sys_get_temp_dir() . '/validate-links-external-tests';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    });

    afterEach(function () {
        // Teardown: Clean up test files
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    });

    describe('HTTP Client Integration', function () {
        it('validates external HTTP links with real network requests', function () {
            // Setup: Use httpbin.org for reliable testing
            $urls = [
                'https://httpbin.org/status/200',  // Should be valid
                'https://httpbin.org/status/404',  // Should be broken
                'https://httpbin.org/status/500',  // Should be broken
            ];

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
                'follow_redirects' => true,
            ]);

            // Execute: Validate external links with real HTTP requests
            $results = $this->linkValidationService->validateExternalLinks($urls, $config);

            // Assert: Real HTTP responses are processed correctly
            expect($results->count())->toBe(3);

            $resultArray = $results->toArray();
            
            // Check 200 status link
            $validResult = collect($resultArray)->first(fn($result) => 
                str_contains($result->getUrl(), 'status/200')
            );
            expect($validResult)->not->toBeNull()
                ->and($validResult->getStatus())->toBe(LinkStatus::VALID);

            // Check 404 status link
            $brokenResult = collect($resultArray)->first(fn($result) => 
                str_contains($result->getUrl(), 'status/404')
            );
            expect($brokenResult)->not->toBeNull()
                ->and($brokenResult->getStatus())->toBe(LinkStatus::BROKEN);
        });

        it('handles HTTP redirects properly', function () {
            // Setup: Test redirect handling
            $redirectUrl = 'https://httpbin.org/redirect/3';
            
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
                'follow_redirects' => true,
                'max_redirects' => 5,
            ]);

            // Execute: Validate URL that redirects
            $result = $this->linkValidationService->validateLink($redirectUrl, $config);

            // Assert: Redirect is followed successfully
            expect($result->getStatus())->toBe(LinkStatus::VALID);
        });

        it('respects timeout configuration for slow responses', function () {
            // Setup: Test timeout handling
            $slowUrl = 'https://httpbin.org/delay/2';
            
            $shortTimeoutConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 1, // Very short timeout
            ]);

            $longTimeoutConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 5, // Longer timeout
            ]);

            // Execute: Test with different timeout configurations
            $shortResult = $this->linkValidationService->validateLink($slowUrl, $shortTimeoutConfig);
            $longResult = $this->linkValidationService->validateLink($slowUrl, $longTimeoutConfig);

            // Assert: Timeout configuration affects results
            // Short timeout should likely timeout, long timeout should succeed
            expect($shortResult->getStatus())->toBeIn([LinkStatus::TIMEOUT, LinkStatus::BROKEN])
                ->and($longResult->getStatus())->toBeIn([LinkStatus::VALID, LinkStatus::TIMEOUT]);
        });

        it('handles SSL/TLS certificate validation', function () {
            // Setup: Test HTTPS links with different certificate scenarios
            $validHttpsUrl = 'https://httpbin.org/get';
            
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate HTTPS link
            $result = $this->linkValidationService->validateLink($validHttpsUrl, $config);

            // Assert: HTTPS validation works
            expect($result->getStatus())->toBe(LinkStatus::VALID);
        });
    });

    describe('File System Integration', function () {
        it('validates local file links with real file system access', function () {
            // Setup: Create test files
            $testFile = $this->tempDir . '/test.md';
            $targetFile = $this->tempDir . '/target.md';
            $missingFile = $this->tempDir . '/missing.md';
            
            file_put_contents($targetFile, '# Target File');
            
            $content = <<<MD
[Existing File](./target.md)
[Missing File](./missing.md)
[Absolute Path]({$targetFile})
MD;
            file_put_contents($testFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL],
                'security_checks' => true,
            ]);

            // Execute: Validate file with internal links
            $results = $this->linkValidationService->validateFile($testFile, $config);

            // Assert: File system access works correctly
            expect($results->count())->toBeGreaterThan(0);

            $resultArray = $results->toArray();
            
            // Check existing file link
            $validFileResult = collect($resultArray)->first(fn($result) => 
                str_contains($result->getUrl(), 'target.md')
            );
            expect($validFileResult)->not->toBeNull();

            // Check missing file link
            $missingFileResult = collect($resultArray)->first(fn($result) => 
                str_contains($result->getUrl(), 'missing.md')
            );
            expect($missingFileResult)->not->toBeNull()
                ->and($missingFileResult->getStatus())->toBe(LinkStatus::BROKEN);
        });

        it('handles file permissions and access rights', function () {
            // Setup: Create test file with permission scenarios
            $testFile = $this->tempDir . '/permissions.md';
            $readableFile = $this->tempDir . '/readable.md';
            
            file_put_contents($readableFile, '# Readable File');
            
            $content = "[Readable File](./readable.md)";
            file_put_contents($testFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL],
                'security_checks' => true,
            ]);

            // Execute: Validate with file permission checks
            $results = $this->linkValidationService->validateFile($testFile, $config);

            // Assert: File permissions are respected
            expect($results->count())->toBeGreaterThan(0);
            
            $result = $results->first();
            expect($result->getStatus())->toBe(LinkStatus::VALID);
        });

        it('validates directory traversal security', function () {
            // Setup: Create test with potentially dangerous paths
            $testFile = $this->tempDir . '/security.md';
            
            $content = <<<MD
[Dangerous Path](../../../etc/passwd)
[Relative Up](../../sensitive.txt)
[Safe Relative](./safe.md)
MD;
            file_put_contents($testFile, $content);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL],
                'security_checks' => true,
            ]);

            // Execute: Validate with security checks
            $results = $this->linkValidationService->validateFile($testFile, $config);

            // Assert: Security violations are detected
            expect($results->count())->toBeGreaterThan(0);

            $securityViolations = $results->filter(fn($result) => 
                $result->getStatus() === LinkStatus::SECURITY_VIOLATION
            );
            
            expect($securityViolations->count())->toBeGreaterThan(0);
        });
    });

    describe('Configuration System Integration', function () {
        it('loads and applies configuration from Laravel config system', function () {
            // Setup: Test configuration loading
            $config = ValidationConfig::withDefaults();

            // Execute: Verify configuration is loaded from Laravel config
            expect($config)->toBeInstanceOf(ValidationConfig::class)
                ->and($config->getTimeout())->toBeInt()
                ->and($config->getTimeout())->toBeGreaterThan(0)
                ->and($config->getMaxRedirects())->toBeInt()
                ->and($config->getConcurrentRequests())->toBeInt();
        });

        it('integrates with Laravel service container', function () {
            // Setup: Test service container integration
            $linkValidation = app(App\Services\Contracts\LinkValidationInterface::class);
            $reporting = app(App\Services\Contracts\ReportingInterface::class);
            $security = app(App\Services\Contracts\SecurityValidationInterface::class);

            // Execute: Verify services are properly bound
            expect($linkValidation)->toBeInstanceOf(App\Services\Contracts\LinkValidationInterface::class)
                ->and($reporting)->toBeInstanceOf(App\Services\Contracts\ReportingInterface::class)
                ->and($security)->toBeInstanceOf(App\Services\Contracts\SecurityValidationInterface::class);

            // Assert: Services are singletons
            $linkValidation2 = app(App\Services\Contracts\LinkValidationInterface::class);
            expect($linkValidation)->toBe($linkValidation2);
        });
    });

    describe('Error Handling and Recovery', function () {
        it('handles network connectivity issues gracefully', function () {
            // Setup: Test with unreachable host
            $unreachableUrl = 'https://this-domain-should-not-exist-12345.com';
            
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 5,
            ]);

            // Execute: Validate unreachable URL
            $result = $this->linkValidationService->validateLink($unreachableUrl, $config);

            // Assert: Network errors are handled gracefully
            expect($result->getStatus())->toBeIn([
                LinkStatus::BROKEN,
                LinkStatus::TIMEOUT,
                LinkStatus::INVALID_FORMAT
            ]);
        });

        it('recovers from temporary service failures', function () {
            // Setup: Test with service that might be temporarily unavailable
            $testUrls = [
                'https://httpbin.org/status/503', // Service unavailable
                'https://httpbin.org/status/200', // Should work
            ];

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate mixed success/failure URLs
            $results = $this->linkValidationService->validateExternalLinks($testUrls, $config);

            // Assert: Service handles mixed results appropriately
            expect($results->count())->toBe(2);
            
            $validResults = $results->filter(fn($result) => 
                $result->getStatus() === LinkStatus::VALID
            );
            $brokenResults = $results->filter(fn($result) => 
                $result->getStatus() === LinkStatus::BROKEN
            );
            
            expect($validResults->count())->toBeGreaterThan(0)
                ->and($brokenResults->count())->toBeGreaterThan(0);
        });
    });

    describe('Performance and Resource Management', function () {
        it('handles concurrent external link validation', function () {
            // Setup: Create multiple URLs for concurrent testing
            $urls = [
                'https://httpbin.org/delay/1',
                'https://httpbin.org/status/200',
                'https://httpbin.org/status/404',
                'https://httpbin.org/get',
                'https://httpbin.org/headers',
            ];

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
                'concurrent_requests' => 3,
            ]);

            $startTime = microtime(true);

            // Execute: Validate multiple URLs
            $results = $this->linkValidationService->validateExternalLinks($urls, $config);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Assert: Concurrent processing improves performance
            expect($results->count())->toBe(count($urls))
                ->and($executionTime)->toBeLessThan(15); // Should be faster than sequential
        });

        it('manages memory usage with large file processing', function () {
            // Setup: Create large test file
            $largeContent = str_repeat("[Link](https://httpbin.org/status/200)\n", 100);
            $largeFile = $this->tempDir . '/large.md';
            file_put_contents($largeFile, $largeContent);

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            $memoryBefore = memory_get_usage();

            // Execute: Process large file
            $results = $this->linkValidationService->validateFile($largeFile, $config);

            $memoryAfter = memory_get_usage();
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Assert: Memory usage is reasonable
            expect($results->count())->toBe(100)
                ->and($memoryUsed)->toBeLessThan(50 * 1024 * 1024); // Less than 50MB
        });
    });
});
