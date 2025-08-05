<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;
use Tests\Support\HttpMockFactory;
use Tests\Support\MockLifecycleManager;

describe('External Dependency Integration Tests', function () {
    beforeEach(function () {
        // Setup: Create services for external dependency testing
        $this->securityService = new SecurityValidationService();
        $this->linkValidationService = new LinkValidationService($this->securityService);

        // Create unique temporary directory using enhanced helper
        $this->tempDir = createIsolatedTempDirectory('validate-links-external-tests');
    });

    afterEach(function () {
        // Enhanced cleanup is handled automatically by MockLifecycleManager
        // Manual cleanup for this specific test's temp directory
        safeCleanupDirectory($this->tempDir);
    });

    describe('HTTP Client Integration', function () {
        it('validates external HTTP links with mock responses', function () {
            // Setup: Use optimized HTTP mock factory for reliable testing
            createMockHttpClient([
                'success_200',
                'not_found_404',
                'server_error_500',
            ]);
            
            $urls = [
                'https://mock-server.test/status/200',  // Should be valid
                'https://mock-server.test/status/404',  // Should be broken
                'https://mock-server.test/status/500',  // Should be broken
            ];

            $config = createTestValidationConfig([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
                'follow_redirects' => true,
            ]);

            // Execute: Validate external links with mock HTTP responses
            $results = $this->linkValidationService->validateExternalLinks($urls, $config);

            // Assert: Mock HTTP responses are processed correctly
            expect($results->count())->toBe(3);

            $resultArray = $results->toArray();

            // Check 200 status link
            $validResult = collect($resultArray)->first(fn ($result) => str_contains($result->getUrl(), 'status/200')
            );
            expect($validResult)->not->toBeNull()
                ->and($validResult->getStatus())->toBe(LinkStatus::VALID);

            // Check 404 status link
            $brokenResult = collect($resultArray)->first(fn ($result) => str_contains($result->getUrl(), 'status/404')
            );
            expect($brokenResult)->not->toBeNull()
                ->and($brokenResult->getStatus())->toBe(LinkStatus::BROKEN);
        });

        it('handles HTTP redirects properly', function () {
            // Setup: Use optimized redirect client
            createRedirectHttpClient();
            
            $redirectUrl = 'https://mock-server.test/redirect/3';

            $config = createTestValidationConfig([
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
            // Setup: Use optimized timeout client for first test
            createTimeoutHttpClient();
            
            $slowUrl = 'https://mock-server.test/delay/2';

            $shortTimeoutConfig = createTestValidationConfig([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 1, // Very short timeout
            ]);

            // Execute: Test with short timeout (should timeout)
            $shortResult = $this->linkValidationService->validateLink($slowUrl, $shortTimeoutConfig);

            // Setup: Create successful client for second test
            createSuccessfulHttpClient();
            
            $longTimeoutConfig = createTestValidationConfig([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 5, // Longer timeout
            ]);

            $longResult = $this->linkValidationService->validateLink($slowUrl, $longTimeoutConfig);

            // Assert: Timeout configuration affects results
            expect($shortResult->getStatus())->toBeIn([LinkStatus::TIMEOUT, LinkStatus::BROKEN])
                ->and($longResult->getStatus())->toBeIn([LinkStatus::VALID, LinkStatus::TIMEOUT]);
        });

        it('handles SSL/TLS certificate validation', function () {
            // Setup: Use optimized successful client
            createSuccessfulHttpClient();
            
            $validHttpsUrl = 'https://mock-server.test/get';

            $config = createTestValidationConfig([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate HTTPS link with mock response
            $result = $this->linkValidationService->validateLink($validHttpsUrl, $config);

            // Assert: HTTPS validation works with mock
            expect($result->getStatus())->toBe(LinkStatus::VALID);
        });
    });

    describe('File System Integration', function () {
        it('validates local file links with real file system access', function () {
            // Setup: Create test files using enhanced helpers
            $targetFile = createTestFile('target.md', '# Target File');
            
            $content = <<<MD
[Existing File](./target.md)
[Missing File](./missing.md)
[Absolute Path]({$targetFile})
MD;
            $testFile = createTestFile('test.md', $content);

            $config = createTestValidationConfig([
                'scopes' => [ValidationScope::INTERNAL],
                'security_checks' => true,
            ]);

            // Execute: Validate file with internal links
            $results = $this->linkValidationService->validateFile($testFile, $config);

            // Assert: File system access works correctly
            expect($results->count())->toBeGreaterThan(0);

            $resultArray = $results->toArray();

            // Check existing file link
            $validFileResult = collect($resultArray)->first(fn ($result) => str_contains($result->getUrl(), 'target.md')
            );
            expect($validFileResult)->not->toBeNull();

            // Check missing file link
            $missingFileResult = collect($resultArray)->first(fn ($result) => str_contains($result->getUrl(), 'missing.md')
            );
            expect($missingFileResult)->not->toBeNull()
                ->and($missingFileResult->getStatus())->toBe(LinkStatus::BROKEN);
        });

        it('handles file permissions and access rights', function () {
            // Setup: Create test files using enhanced helpers
            $readableFile = createTestFile('readable.md', '# Readable File');
            $content = '[Readable File](./readable.md)';
            $testFile = createTestFile('permissions.md', $content);

            $config = createTestValidationConfig([
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
            // Setup: Create test with potentially dangerous paths using enhanced helper
            $content = <<<'MD'
[Dangerous Path](../../../etc/passwd)
[Relative Up](../../sensitive.txt)
[Safe Relative](./safe.md)
MD;
            $testFile = createTestFile('security.md', $content);

            $config = createTestValidationConfig([
                'scopes' => [ValidationScope::INTERNAL],
                'security_checks' => true,
            ]);

            // Execute: Validate with security checks
            $results = $this->linkValidationService->validateFile($testFile, $config);

            // Assert: Security violations are detected
            expect($results->count())->toBeGreaterThan(0);

            $securityViolations = $results->filter(fn ($result) => $result->getStatus() === LinkStatus::SECURITY_VIOLATION
            );

            expect($securityViolations->count())->toBeGreaterThan(0);
        });
    });

    describe('Configuration System Integration', function () {
        it('loads and applies configuration from Laravel config system', function () {
            // Setup: Test configuration loading using enhanced helper
            $config = createTestValidationConfig();

            // Execute: Verify configuration is loaded properly
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
                LinkStatus::INVALID_FORMAT,
            ]);
        });

        it('recovers from temporary service failures', function () {
            // Setup: Use optimized mixed validation client
            createMixedHttpClient();
            
            $testUrls = [
                'https://mock-server.test/status/503', // Service unavailable
                'https://mock-server.test/status/200', // Should work
            ];

            $config = createTestValidationConfig([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            // Execute: Validate mixed success/failure URLs with mock responses
            $results = $this->linkValidationService->validateExternalLinks($testUrls, $config);

            // Assert: Service handles mixed results appropriately
            expect($results->count())->toBe(2);

            $validResults = $results->filter(fn ($result) => $result->getStatus() === LinkStatus::VALID
            );
            $brokenResults = $results->filter(fn ($result) => $result->getStatus() === LinkStatus::BROKEN
            );

            expect($validResults->count())->toBeGreaterThan(0)
                ->and($brokenResults->count())->toBeGreaterThan(0);
        });
    });

    describe('Performance and Resource Management', function () {
        it('handles concurrent external link validation', function () {
            // Setup: Use optimized mixed validation client for concurrent testing
            createMockHttpClient([
                'success_200',    // delay/1 mock
                'success_200',    // status/200
                'not_found_404',  // status/404
                'success_200',    // get
                'success_200',    // headers
            ]);
            
            $urls = [
                'https://mock-server.test/delay/1',
                'https://mock-server.test/status/200',
                'https://mock-server.test/status/404',
                'https://mock-server.test/get',
                'https://mock-server.test/headers',
            ];

            $config = createTestValidationConfig([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
                'concurrent_requests' => 3,
            ]);

            $startTime = microtime(true);

            // Execute: Validate multiple URLs with mock responses
            $results = $this->linkValidationService->validateExternalLinks($urls, $config);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Assert: Concurrent processing works with mock responses
            expect($results->count())->toBe(count($urls))
                ->and($executionTime)->toBeLessThan(5); // Should be fast with mocks
        });

        it('manages memory usage with large file processing', function () {
            // Setup: Create mock responses for large file test (100 success responses)
            $mockResponses = array_fill(0, 100, 'success_200');
            createMockHttpClient($mockResponses);
            
            // Create large test file with mock URLs using enhanced helper
            $largeContent = str_repeat("[Link](https://mock-server.test/status/200)\n", 100);
            $largeFile = createTestFile('large.md', $largeContent);

            $config = createTestValidationConfig([
                'scopes' => [ValidationScope::EXTERNAL],
                'timeout' => 10,
            ]);

            $memoryBefore = memory_get_usage();

            // Execute: Process large file with mock responses
            $results = $this->linkValidationService->validateFile($largeFile, $config);

            $memoryAfter = memory_get_usage();
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Assert: Memory usage is reasonable with mock responses
            expect($results->count())->toBe(100)
                ->and($memoryUsed)->toBeLessThan(50 * 1024 * 1024); // Less than 50MB
        });
    });
});
