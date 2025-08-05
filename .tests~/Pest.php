<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Case Configuration
|--------------------------------------------------------------------------
|
| Pure Pest framework configuration with comprehensive test suite setup.
| This provides a clean, modern testing setup using only Pest's native features
| while maintaining Laravel functionality through a minimal TestCase.
|
*/

// Import mock support classes
use Tests\Support\MockFactory;
use Tests\Support\HttpMockFactory;
use Tests\Support\MockDataBuilder;
use Tests\Support\MockLifecycleManager;

// Import specialized data builders
use Tests\Support\DataBuilders\UrlDataBuilder;
use Tests\Support\DataBuilders\ConfigurationDataBuilder;
use Tests\Support\DataBuilders\ResponseDataBuilder;

// Import exception testing helpers
use Tests\Support\ExceptionTestingHelpers;

// All tests use Laravel TestCase for full framework functionality
uses(Tests\TestCase::class)->in('Unit');
uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Integration');
uses(Tests\TestCase::class)->in('E2E');
uses(Tests\TestCase::class)->in('Architecture');

/*
|--------------------------------------------------------------------------
| Test Groups and Organization
|--------------------------------------------------------------------------
|
| Organize tests into logical groups for selective execution and reporting.
|
*/

uses()->group('unit')->in('Unit');
uses()->group('feature')->in('Feature');
uses()->group('integration')->in('Integration');
uses()->group('e2e')->in('E2E');
uses()->group('arch')->in('Architecture');

/*
|--------------------------------------------------------------------------
| Mock Lifecycle Management
|--------------------------------------------------------------------------
|
| Automatic mock setup and cleanup for all test types.
|
*/

// Setup mock lifecycle management for all tests
beforeEach(function () {
    // Clear any previous mock state
    MockLifecycleManager::clearVerificationErrors();
    
    // Store original HTTP client if bound
    if (app()->bound('http.client')) {
        HttpMockFactory::storeOriginalClient();
    }
})->in('Unit', 'Feature', 'Integration', 'E2E');

// Cleanup mocks after each test
afterEach(function () {
    // Verify and cleanup all mocks
    $errors = MockLifecycleManager::verifyAndCleanup();
    
    // Reset HTTP mock factory
    HttpMockFactory::reset();
    
    // Log verification errors if any (but don't fail the test)
    if (!empty($errors)) {
        error_log('Mock verification errors: ' . json_encode($errors));
    }
})->in('Unit', 'Feature', 'Integration', 'E2E');

/*
|--------------------------------------------------------------------------
| Custom Expectations
|--------------------------------------------------------------------------
|
| Extended expectations for domain-specific assertions and improved test readability.
|
*/

expect()->extend('toBeValidLinkStatus', function () {
    /** @var Pest\Expectation $this */
    $validStatuses = App\Enums\LinkStatus::values();

    return $this->toBeIn($validStatuses);
});

expect()->extend('toBeValidEnum', function (string $enumClass) {
    /** @var Pest\Expectation $this */
    if (! enum_exists($enumClass)) {
        throw new InvalidArgumentException("Class {$enumClass} is not an enum");
    }
    $validValues = array_column($enumClass::cases(), 'value');

    return $this->toBeIn($validValues);
});

expect()->extend('toBeValidationResult', function () {
    /** @var Pest\Expectation $this */
    return $this->toBeInstanceOf(App\Services\ValueObjects\ValidationResult::class);
});

expect()->extend('toBeValidationConfig', function () {
    /** @var Pest\Expectation $this */
    return $this->toBeInstanceOf(App\Services\ValueObjects\ValidationConfig::class);
});

expect()->extend('toHaveValidUrl', function () {
    /** @var Pest\Expectation $this */
    return $this->toMatch('/^https?:\/\/[^\s]+$/');
});

/*
|--------------------------------------------------------------------------
| Enhanced Mock Helper Functions
|--------------------------------------------------------------------------
|
| Optimized helper functions for mock creation and management with
| improved lifecycle handling and realistic behavior simulation.
|
*/

/**
 * Create a managed security validation service mock with realistic behavior.
 */
function createSecurityValidationMock(array $config = []): Mockery\MockInterface
{
    $mock = MockFactory::createSecurityValidationService($config);
    MockLifecycleManager::registerMock('security_validation', $mock);
    return $mock;
}

/**
 * Create a managed link validation service mock with comprehensive behavior.
 */
function createLinkValidationMock(array $config = []): Mockery\MockInterface
{
    $mock = MockFactory::createLinkValidationService($config);
    MockLifecycleManager::registerMock('link_validation', $mock);
    return $mock;
}

/**
 * Create a managed reporting service mock with realistic behavior.
 */
function createReportingMock(array $config = []): Mockery\MockInterface
{
    $mock = MockFactory::createReportingService($config);
    MockLifecycleManager::registerMock('reporting', $mock);
    return $mock;
}

/**
 * Create and bind a mock HTTP client with predefined responses.
 */
function createMockHttpClient(array $responses): GuzzleHttp\Client
{
    $client = HttpMockFactory::createMockClient($responses);
    HttpMockFactory::bindToContainer($client);
    return $client;
}

/**
 * Create a mock HTTP client for successful validation scenarios.
 */
function createSuccessfulHttpClient(): GuzzleHttp\Client
{
    $client = HttpMockFactory::createSuccessfulValidationClient();
    HttpMockFactory::bindToContainer($client);
    return $client;
}

/**
 * Create a mock HTTP client for mixed validation results.
 */
function createMixedHttpClient(): GuzzleHttp\Client
{
    $client = HttpMockFactory::createMixedValidationClient();
    HttpMockFactory::bindToContainer($client);
    return $client;
}

/**
 * Create a mock HTTP client for redirect testing.
 */
function createRedirectHttpClient(): GuzzleHttp\Client
{
    $client = HttpMockFactory::createRedirectClient();
    HttpMockFactory::bindToContainer($client);
    return $client;
}

/**
 * Create a mock HTTP client for timeout testing.
 */
function createTimeoutHttpClient(): GuzzleHttp\Client
{
    $client = HttpMockFactory::createTimeoutClient();
    HttpMockFactory::bindToContainer($client);
    return $client;
}

/*
|--------------------------------------------------------------------------
| Enhanced Test Data Builders
|--------------------------------------------------------------------------
|
| Improved data builders with realistic patterns and proper relationships.
|
*/

/**
 * Create test validation configuration with enhanced defaults.
 */
function createTestValidationConfig(array $overrides = []): App\Services\ValueObjects\ValidationConfig
{
    return MockDataBuilder::createValidationConfig($overrides);
}

/**
 * Create test validation result with realistic data patterns.
 */
function createTestValidationResult(
    string $url = 'https://example.com',
    App\Enums\LinkStatus $status = App\Enums\LinkStatus::VALID,
    array $metadata = []
): App\Services\ValueObjects\ValidationResult {
    return MockDataBuilder::createValidationResult([
        'url' => $url,
        'status' => $status,
        'metadata' => $metadata,
    ]);
}

/**
 * Create a collection of test validation results with mixed statuses.
 */
function createTestValidationResultCollection(array $config = []): App\Services\ValueObjects\ValidationResultCollection
{
    return MockDataBuilder::createValidationResultCollection($config);
}

/**
 * Create test report summary with realistic statistics.
 */
function createTestReportSummary(array $config = []): App\Services\ValueObjects\ReportSummary
{
    return MockDataBuilder::createReportSummary($config);
}

/**
 * Create test validation statistics with realistic data.
 */
function createTestValidationStatistics(array $config = []): App\Services\ValueObjects\ValidationStatistics
{
    return MockDataBuilder::createValidationStatistics($config);
}

/*
|--------------------------------------------------------------------------
| Enhanced File System Helpers
|--------------------------------------------------------------------------
|
| Improved file system utilities with better isolation and cleanup.
|
*/

/**
 * Create a temporary test file with specified content and automatic cleanup.
 */
function createTestFile(string $filename, string $content = ''): string
{
    // Create unique temporary directory to prevent test isolation issues
    $tempDir = createIsolatedTempDirectory('validate-links-tests');
    $filepath = "{$tempDir}/{$filename}";
    file_put_contents($filepath, $content);

    // Register cleanup callback
    MockLifecycleManager::registerCleanupCallback(
        "file_{$filename}",
        fn() => safeCleanupDirectory($tempDir)
    );

    return $filepath;
}

/**
 * Create a unique temporary directory for test isolation with automatic cleanup.
 */
function createIsolatedTempDirectory(string $prefix = 'validate-links-test'): string
{
    $tempDir = sys_get_temp_dir() . '/' . $prefix . '-' . uniqid();
    if (! is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    // Register cleanup callback
    MockLifecycleManager::registerCleanupCallback(
        "dir_{$prefix}",
        fn() => safeCleanupDirectory($tempDir)
    );
    
    return $tempDir;
}

/**
 * Safely cleanup a temporary directory with proper error handling.
 */
function safeCleanupDirectory(string $tempDir): void
{
    try {
        if (is_dir($tempDir)) {
            // Use recursive directory removal
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($tempDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );
            
            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($tempDir);
        }
    } catch (Exception $e) {
        // Log cleanup failure but don't fail the test
        error_log("Failed to cleanup directory {$tempDir}: " . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| Enhanced URL Test Data
|--------------------------------------------------------------------------
|
| Comprehensive URL test data with realistic patterns and edge cases.
|
*/

/**
 * Generate comprehensive test URLs for validation testing.
 *
 * @return array<string, string>
 */
function getTestUrls(): array
{
    return [
        // Valid URLs
        'valid_http' => 'http://example.com',
        'valid_https' => 'https://example.com',
        'localhost' => 'http://localhost:8080',
        'with_path' => 'https://example.com/path/to/resource',
        'with_query' => 'https://example.com?param=value',
        'with_fragment' => 'https://example.com#section',
        'with_port' => 'https://example.com:8443/secure',
        'subdomain' => 'https://api.example.com/v1/endpoint',
        
        // Invalid URLs
        'invalid_format' => 'not-a-url',
        'missing_protocol' => 'example.com',
        'malformed' => '://invalid',
        'empty' => '',
        'spaces' => 'invalid url with spaces',
        'special_chars' => 'invalid<url>',
        
        // Security test URLs
        'javascript' => 'javascript:alert("xss")',
        'data_uri' => 'data:text/html,<script>alert(1)</script>',
        'ftp' => 'ftp://example.com/file.txt',
        
        // Internal links
        'relative' => './relative/path.md',
        'absolute' => '/absolute/path.md',
        'anchor' => '#section-anchor',
        'parent_dir' => '../parent/file.md',
        
        // Edge cases
        'very_long' => 'https://example.com/' . str_repeat('a', 2000),
        'unicode' => 'https://example.com/测试',
        'encoded' => 'https://example.com/path%20with%20spaces',
    ];
}

/**
 * Get URLs categorized by expected validation status.
 */
function getCategorizedTestUrls(): array
{
    return [
        'anchor' => [
            'simple' => '#section',
            'complex' => '#complex-section-with-special-chars',
        ],
        'external' => [
            'https' => 'https://example.com',
            'http' => 'http://example.com',
        ],
        'invalid' => [
            'with_spaces' => 'http://invalid url with spaces',
            'empty' => '',
            'malformed' => 'not-a-url',
            'special_chars' => 'http://invalid<url>',
        ],
        'security_risks' => [
            'malicious_url' => 'https://malicious.com',
        ],
        'valid' => [
            'https://example.com',
            'http://test.org',
            './valid/file.md',
            '#valid-anchor',
        ],
        'broken' => [
            'https://broken.example.com',
            'http://timeout.example.com',
            './missing/file.md',
        ],
        'security_risk' => [
            'javascript:alert("xss")',
            'data:text/html,<script>',
            '../../../etc/passwd',
            'ftp://malicious.com',
        ],
    ];
}

/*
|--------------------------------------------------------------------------
| Enhanced Fixture Management Utilities
|--------------------------------------------------------------------------
|
| Comprehensive fixture loading, caching, and validation utilities with
| deterministic data generation and proper versioning support.
|
*/

/**
 * Global fixture cache for performance optimization.
 *
 * @var array<string, mixed>
 */
$GLOBALS['fixture_cache'] = [];

/**
 * Load fixture data from JSON files with comprehensive caching and validation.
 *
 * @param string $fixturePath Path to fixture file relative to tests/Fixtures/
 * @param bool $useCache Whether to use caching (default: true)
 * @return array<string, mixed> Fixture data
 * @throws InvalidArgumentException If fixture file not found
 * @throws RuntimeException If fixture file cannot be read or parsed
 */
function loadFixture(string $fixturePath, bool $useCache = true): array
{
    if ($useCache && isset($GLOBALS['fixture_cache'][$fixturePath])) {
        return $GLOBALS['fixture_cache'][$fixturePath];
    }
    
    $fullPath = __DIR__ . '/Fixtures/' . $fixturePath;
    
    if (!file_exists($fullPath)) {
        throw new InvalidArgumentException("Fixture file not found: {$fullPath}");
    }
    
    $content = file_get_contents($fullPath);
    if ($content === false) {
        throw new RuntimeException("Failed to read fixture file: {$fullPath}");
    }
    
    $data = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new RuntimeException("Invalid JSON in fixture file: {$fullPath} - " . json_last_error_msg());
    }
    
    // Validate fixture structure
    validateFixtureStructure($data, $fixturePath);
    
    if ($useCache) {
        $GLOBALS['fixture_cache'][$fixturePath] = $data;
    }
    
    return $data;
}

/**
 * Validate fixture data structure and version compatibility.
 *
 * @param array<string, mixed> $data Fixture data
 * @param string $fixturePath Fixture file path for error reporting
 * @return void
 * @throws RuntimeException If fixture structure is invalid
 */
function validateFixtureStructure(array $data, string $fixturePath): void
{
    // Check for required version field
    if (!isset($data['version'])) {
        throw new RuntimeException("Fixture missing version field: {$fixturePath}");
    }
    
    // Validate version format (semantic versioning)
    if (!preg_match('/^\d+\.\d+\.\d+$/', $data['version'])) {
        throw new RuntimeException("Invalid version format in fixture: {$fixturePath}");
    }
    
    // Check for required metadata
    if (!isset($data['metadata'])) {
        throw new RuntimeException("Fixture missing metadata field: {$fixturePath}");
    }
    
    $metadata = $data['metadata'];
    $requiredMetadataFields = ['created_at', 'updated_at', 'tags'];
    
    foreach ($requiredMetadataFields as $field) {
        if (!isset($metadata[$field])) {
            throw new RuntimeException("Fixture missing required metadata field '{$field}': {$fixturePath}");
        }
    }
    
    // Validate timestamp formats
    foreach (['created_at', 'updated_at'] as $timestampField) {
        if (isset($metadata[$timestampField])) {
            $timestamp = $metadata[$timestampField];
            if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $timestamp)) {
                throw new RuntimeException("Invalid timestamp format for '{$timestampField}' in fixture: {$fixturePath}");
            }
        }
    }
}

/**
 * Clear the fixture cache.
 *
 * @return void
 */
function clearFixtureCache(): void
{
    $GLOBALS['fixture_cache'] = [];
}

/**
 * Get fixture cache statistics.
 *
 * @return array<string, mixed> Cache statistics
 */
function getFixtureCacheStats(): array
{
    return [
        'cached_fixtures' => count($GLOBALS['fixture_cache']),
        'cache_size_bytes' => strlen(serialize($GLOBALS['fixture_cache'])),
        'cached_files' => array_keys($GLOBALS['fixture_cache']),
    ];
}

/**
 * Load URL fixtures with category filtering.
 *
 * @param string $category Optional category filter ('valid', 'invalid', 'security')
 * @param string|null $subcategory Optional subcategory filter
 * @return array<string> URLs from specified category
 */
function loadUrlFixtures(string $category = 'valid', ?string $subcategory = null): array
{
    $fixturePath = match ($category) {
        'invalid' => 'urls/invalid-urls.json',
        'security' => 'urls/security-test-urls.json',
        'redirects' => 'urls/redirect-chains.json',
        default => 'urls/valid-urls.json',
    };
    
    $fixture = loadFixture($fixturePath);
    
    if ($category === 'redirects') {
        // Special handling for redirect fixtures
        $urls = [];
        foreach ($fixture['scenarios'] as $scenarioType => $scenarios) {
            if ($subcategory && $scenarioType !== $subcategory) {
                continue;
            }
            foreach ($scenarios as $scenario) {
                $urls[] = $scenario['initial_url'];
            }
        }
        return $urls;
    }
    
    if ($subcategory && isset($fixture['categories'][$subcategory])) {
        return $fixture['categories'][$subcategory];
    }
    
    // Return all URLs from all categories
    $allUrls = [];
    foreach ($fixture['categories'] as $categoryUrls) {
        $allUrls = array_merge($allUrls, $categoryUrls);
    }
    
    return $allUrls;
}

/**
 * Load configuration fixtures with type filtering.
 *
 * @param string $type Configuration type ('strict', 'permissive', 'custom')
 * @return array<string, mixed> Configuration data
 */
function loadConfigurationFixtures(string $type = 'strict'): array
{
    $fixturePath = match ($type) {
        'permissive' => 'configurations/permissive-validation.json',
        'custom' => 'configurations/custom-rules.json',
        default => 'configurations/strict-validation.json',
    };
    
    $fixture = loadFixture($fixturePath);
    return $fixture['configuration'];
}

/**
 * Load HTTP response fixtures with category filtering.
 *
 * @param string $category Response category ('success', 'client_error', 'server_error', etc.)
 * @param string|null $responseType Specific response type
 * @return array<string, mixed> Response data
 */
function loadResponseFixtures(string $category = 'success_responses', ?string $responseType = null): array
{
    $fixture = loadFixture('responses/http-responses.json');
    
    if (!isset($fixture['response_categories'][$category])) {
        throw new InvalidArgumentException("Unknown response category: {$category}");
    }
    
    $categoryData = $fixture['response_categories'][$category];
    
    if ($responseType) {
        if (!isset($categoryData[$responseType])) {
            throw new InvalidArgumentException("Unknown response type '{$responseType}' in category '{$category}'");
        }
        return $categoryData[$responseType];
    }
    
    return $categoryData;
}

/**
 * Load error scenario fixtures with category filtering.
 *
 * @param string $category Error category ('network_errors', 'ssl_errors', etc.)
 * @param string|null $errorType Specific error type
 * @return array<string, mixed> Error scenario data
 */
function loadErrorFixtures(string $category = 'network_errors', ?string $errorType = null): array
{
    $fixture = loadFixture('responses/error-scenarios.json');
    
    if (!isset($fixture['error_categories'][$category])) {
        throw new InvalidArgumentException("Unknown error category: {$category}");
    }
    
    $categoryData = $fixture['error_categories'][$category];
    
    if ($errorType) {
        if (!isset($categoryData[$errorType])) {
            throw new InvalidArgumentException("Unknown error type '{$errorType}' in category '{$category}'");
        }
        return $categoryData[$errorType];
    }
    
    return $categoryData;
}

/**
 * Load file content fixtures.
 *
 * @param string $filename Fixture filename
 * @return string File content
 */
function loadFileFixture(string $filename): string
{
    $fullPath = __DIR__ . '/Fixtures/files/' . $filename;
    
    if (!file_exists($fullPath)) {
        throw new InvalidArgumentException("File fixture not found: {$fullPath}");
    }
    
    $content = file_get_contents($fullPath);
    if ($content === false) {
        throw new RuntimeException("Failed to read file fixture: {$fullPath}");
    }
    
    return $content;
}

/**
 * Create a temporary file with fixture content.
 *
 * @param string $fixtureFilename Fixture filename
 * @param string|null $tempFilename Optional temporary filename
 * @return string Path to temporary file
 */
function createTempFileFromFixture(string $fixtureFilename, ?string $tempFilename = null): string
{
    $content = loadFileFixture($fixtureFilename);
    $tempDir = createIsolatedTempDirectory('fixture-temp');
    
    $filename = $tempFilename ?? basename($fixtureFilename);
    $tempPath = "{$tempDir}/{$filename}";
    
    file_put_contents($tempPath, $content);
    
    return $tempPath;
}

/**
 * Get available fixture categories for a given fixture type.
 *
 * @param string $fixtureType Fixture type ('urls', 'configurations', 'responses', 'errors')
 * @return array<string> Available categories
 */
function getAvailableFixtureCategories(string $fixtureType): array
{
    return match ($fixtureType) {
        'urls' => ['valid', 'invalid', 'security', 'redirects'],
        'configurations' => ['strict', 'permissive', 'custom'],
        'responses' => ['success_responses', 'client_error_responses', 'server_error_responses', 'redirect_responses', 'special_responses'],
        'errors' => ['network_errors', 'ssl_tls_errors', 'http_protocol_errors', 'redirect_errors', 'content_errors', 'authentication_errors', 'rate_limiting_errors', 'server_errors', 'security_errors'],
        default => throw new InvalidArgumentException("Unknown fixture type: {$fixtureType}"),
    };
}

/**
 * Validate fixture version compatibility.
 *
 * @param string $fixturePath Fixture file path
 * @param string $requiredVersion Required version (semantic versioning)
 * @return bool Whether fixture version is compatible
 */
function isFixtureVersionCompatible(string $fixturePath, string $requiredVersion): bool
{
    $fixture = loadFixture($fixturePath);
    $fixtureVersion = $fixture['version'];
    
    // Simple semantic version comparison (major.minor.patch)
    $fixtureVersionParts = explode('.', $fixtureVersion);
    $requiredVersionParts = explode('.', $requiredVersion);
    
    // Major version must match
    if ($fixtureVersionParts[0] !== $requiredVersionParts[0]) {
        return false;
    }
    
    // Minor version must be >= required
    if ((int)$fixtureVersionParts[1] < (int)$requiredVersionParts[1]) {
        return false;
    }
    
    // If minor versions match, patch version must be >= required
    if ($fixtureVersionParts[1] === $requiredVersionParts[1]) {
        return (int)$fixtureVersionParts[2] >= (int)$requiredVersionParts[2];
    }
    
    return true;
}

/*
|--------------------------------------------------------------------------
| Enhanced Data Builder Helper Functions
|--------------------------------------------------------------------------
|
| Convenient helper functions for using the specialized data builders.
|
*/

/**
 * Create test URLs using the UrlDataBuilder.
 *
 * @param string $type URL type ('valid', 'invalid', 'security', 'status', 'scope')
 * @param array<string, mixed> $options URL generation options
 * @return string|array<string> Generated URL(s)
 */
function createTestUrls(string $type = 'valid', array $options = []): string|array
{
    return match ($type) {
        'valid' => UrlDataBuilder::createValidUrl($options),
        'invalid' => UrlDataBuilder::createInvalidUrl($options),
        'security' => UrlDataBuilder::createSecurityTestUrl($options),
        'collection' => UrlDataBuilder::createUrlCollection($options['count'] ?? 10, $options),
        'redirects' => UrlDataBuilder::createRedirectUrls($options),
        'performance' => UrlDataBuilder::createPerformanceTestUrls($options),
        default => UrlDataBuilder::createValidUrl($options),
    };
}

/**
 * Create test configurations using the ConfigurationDataBuilder.
 *
 * @param string $type Configuration type
 * @param array<string, mixed> $options Configuration options
 * @return ValidationConfig Generated configuration
 */
function createTestConfiguration(string $type = 'strict', array $options = []): ValidationConfig
{
    return ConfigurationDataBuilder::createByType($type, $options);
}

/**
 * Create test HTTP responses using the ResponseDataBuilder.
 *
 * @param string $type Response type
 * @param array<string, mixed> $options Response options
 * @return mixed Generated response(s)
 */
function createTestResponses(string $type = 'success', array $options = [])
{
    return match ($type) {
        'success' => ResponseDataBuilder::createSuccessResponse($options),
        'client_error' => ResponseDataBuilder::createClientErrorResponse($options),
        'server_error' => ResponseDataBuilder::createServerErrorResponse($options),
        'redirect' => ResponseDataBuilder::createRedirectResponse($options),
        'special' => ResponseDataBuilder::createSpecialResponse($options),
        'exception' => ResponseDataBuilder::createHttpException($options),
        'collection' => ResponseDataBuilder::createResponseCollection($options['count'] ?? 10, $options),
        'performance' => ResponseDataBuilder::createPerformanceResponses($options),
        'redirect_chain' => ResponseDataBuilder::createRedirectChain($options),
        'content_types' => ResponseDataBuilder::createContentTypeResponses($options),
        default => ResponseDataBuilder::createSuccessResponse($options),
    };
}

/*
|--------------------------------------------------------------------------
| Exception Testing Helper Functions
|--------------------------------------------------------------------------
|
| Comprehensive exception testing utilities for validating error paths,
| exception propagation, and recovery mechanisms.
|
*/

/**
 * Assert that a callable throws an exception with exact message matching.
 */
function expectExceptionWithMessage(callable $callback, string $exceptionClass, string $expectedMessage): void
{
    ExceptionTestingHelpers::expectExceptionWithMessage($callback, $exceptionClass, $expectedMessage);
}

/**
 * Assert that a callable throws an exception with expected error code.
 */
function expectExceptionWithCode(callable $callback, string $exceptionClass, int $expectedCode): void
{
    ExceptionTestingHelpers::expectExceptionWithCode($callback, $exceptionClass, $expectedCode);
}

/**
 * Assert that an exception chain contains the expected previous exception.
 */
function expectExceptionChain(callable $callback, string $exceptionClass, string $previousExceptionClass): void
{
    ExceptionTestingHelpers::expectExceptionChain($callback, $exceptionClass, $previousExceptionClass);
}

/**
 * Assert that an exception contains expected context data.
 */
function expectExceptionContext(callable $callback, string $exceptionClass, array $expectedContext): void
{
    ExceptionTestingHelpers::expectExceptionContext($callback, $exceptionClass, $expectedContext);
}

/**
 * Simulate network failure scenarios for testing.
 */
function simulateNetworkFailure(string $type = 'connection_timeout'): Exception
{
    return ExceptionTestingHelpers::simulateNetworkFailure($type);
}

/**
 * Simulate file system error scenarios for testing.
 */
function simulateFileSystemError(string $type = 'file_not_found'): Exception
{
    return ExceptionTestingHelpers::simulateFileSystemError($type);
}

/**
 * Simulate resource exhaustion scenarios for testing.
 */
function simulateResourceExhaustion(string $type = 'memory_limit'): Exception
{
    return ExceptionTestingHelpers::simulateResourceExhaustion($type);
}

/**
 * Simulate timeout error scenarios for testing.
 */
function simulateTimeoutError(string $type = 'request_timeout'): Exception
{
    return ExceptionTestingHelpers::simulateTimeoutError($type);
}

/**
 * Test error recovery mechanisms.
 */
function testErrorRecovery(callable $operation, callable $recovery, string $expectedRecoveryResult): void
{
    ExceptionTestingHelpers::testErrorRecovery($operation, $recovery, $expectedRecoveryResult);
}

/**
 * Test graceful degradation behavior.
 */
function testGracefulDegradation(callable $operation, mixed $expectedFallbackValue): mixed
{
    return ExceptionTestingHelpers::testGracefulDegradation($operation, $expectedFallbackValue);
}

/**
 * Test error cleanup procedures.
 */
function testErrorCleanup(callable $operation, callable $cleanupValidator): void
{
    ExceptionTestingHelpers::testErrorCleanup($operation, $cleanupValidator);
}

/**
 * Test error reporting mechanisms.
 */
function testErrorReporting(callable $operation, callable $errorReporter): array
{
    return ExceptionTestingHelpers::testErrorReporting($operation, $errorReporter);
}

/**
 * Create a validation exception with specific context for testing.
 */
function createValidationException(
    string $url = 'https://example.com',
    int $statusCode = 404,
    int $responseTime = 1000,
    ?Throwable $previous = null
): App\Exceptions\ValidationException {
    return ExceptionTestingHelpers::createValidationException($url, $statusCode, $responseTime, $previous);
}

/**
 * Create a security exception with specific threat context for testing.
 */
function createSecurityException(
    string $url = 'https://malicious.com',
    string $threatType = 'malware',
    array $context = [],
    ?Throwable $previous = null
): App\Exceptions\SecurityException {
    return ExceptionTestingHelpers::createSecurityException($url, $threatType, $context, $previous);
}

/**
 * Create a configuration exception with specific error context for testing.
 */
function createConfigurationException(
    string $type = 'missing_file',
    string $filePath = '/config/missing.json',
    ?Throwable $previous = null
): App\Exceptions\ConfigurationException {
    return ExceptionTestingHelpers::createConfigurationException($type, $filePath, $previous);
}

/**
 * Create HTTP error responses for testing.
 */
function createHttpErrorResponse(int $statusCode, string $reasonPhrase = ''): GuzzleHttp\Psr7\Response
{
    return ExceptionTestingHelpers::createHttpErrorResponse($statusCode, $reasonPhrase);
}

/**
 * Create HTTP exception for testing.
 */
function createHttpException(int $statusCode, string $message = ''): GuzzleHttp\Exception\RequestException
{
    return ExceptionTestingHelpers::createHttpException($statusCode, $message);
}

/**
 * Create a mock that throws exceptions on specific method calls.
 */
function createFailingMock(string $className, array $methodExceptions): Mockery\MockInterface
{
    return ExceptionTestingHelpers::createFailingMock($className, $methodExceptions);
}

/**
 * Create a mock that fails after a certain number of calls.
 */
function createIntermittentFailureMock(
    string $className,
    string $method,
    int $failAfterCalls,
    Exception $exception
): Mockery\MockInterface {
    return ExceptionTestingHelpers::createIntermittentFailureMock($className, $method, $failAfterCalls, $exception);
}

/**
 * Test that exceptions propagate correctly through the call stack.
 */
function testExceptionPropagation(callable $operation, array $expectedExceptionChain): void
{
    ExceptionTestingHelpers::testExceptionPropagation($operation, $expectedExceptionChain);
}

/**
 * Validate exception serialization and deserialization.
 */
function testExceptionSerialization(App\Exceptions\ValidateLinksException $exception): void
{
    ExceptionTestingHelpers::testExceptionSerialization($exception);
}

/**
 * Test exception handling under load.
 */
function testExceptionHandlingUnderLoad(
    callable $operation,
    int $iterations,
    float $maxAverageTime
): array {
    return ExceptionTestingHelpers::testExceptionHandlingUnderLoad($operation, $iterations, $maxAverageTime);
}

/*
|--------------------------------------------------------------------------
| Dependency Injection Helper Functions
|--------------------------------------------------------------------------
|
| Helper functions for dependency injection testing, container management,
| service resolution validation, and test isolation.
|
*/

/**
 * Create an isolated container for dependency injection testing.
 */
function createIsolatedContainer(): \Illuminate\Contracts\Foundation\Application
{
    return \Tests\Support\ContainerTestHelper::createIsolatedContainer();
}

/**
 * Reset container state to clean baseline.
 */
function resetContainerState(\Illuminate\Contracts\Foundation\Application $app): void
{
    \Tests\Support\ContainerTestHelper::resetContainerState($app);
}

/**
 * Validate all container bindings and dependencies.
 */
function validateContainerBindings(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\ContainerTestHelper::validateContainerBindings($app);
}

/**
 * Detect circular dependencies in service configuration.
 */
function detectCircularDependencies(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\DependencyGraphValidator::detectCircularDependencies($app);
}

/**
 * Test service resolution for all registered services.
 */
function testServiceResolution(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\ContainerTestHelper::testServiceResolution($app);
}

/**
 * Validate interface bindings and implementations.
 */
function validateInterfaceBindings(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\ContainerTestHelper::validateInterfaceBindings($app);
}

/**
 * Test service lifecycle (singleton vs transient behavior).
 */
function testServiceLifecycle(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\ContainerTestHelper::testServiceLifecycle($app);
}

/**
 * Validate dependency graph integrity and detect issues.
 */
function validateDependencyGraph(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\DependencyGraphValidator::validateDependencyGraph($app);
}

/**
 * Create mock service bindings for testing.
 */
function createMockServiceBindings(\Illuminate\Contracts\Foundation\Application $app, array $mockConfig = []): array
{
    return \Tests\Support\ContainerTestHelper::createMockServiceBindings($app, $mockConfig);
}

/**
 * Test container performance under load.
 */
function testContainerPerformance(\Illuminate\Contracts\Foundation\App $app, int $iterations = 1000): array
{
    return \Tests\Support\ContainerTestHelper::testContainerPerformance($app, $iterations);
}

/**
 * Initialize test isolation for dependency injection testing.
 */
function initializeTestIsolation(\Tests\TestCase $testCase): void
{
    \Tests\Support\TestIsolationManager::initializeIsolation($testCase);
}

/**
 * Isolate test environment completely.
 */
function isolateTestEnvironment(\Tests\TestCase $testCase): void
{
    \Tests\Support\TestIsolationManager::isolateTestEnvironment($testCase);
}

/**
 * Comprehensive cleanup of all test resources.
 */
function cleanupTestResources(): void
{
    \Tests\Support\TestIsolationManager::cleanupTestResources();
}

/**
 * Validate test isolation effectiveness.
 */
function validateTestIsolation(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\TestIsolationManager::validateTestIsolation($app);
}

/**
 * Prevent cross-test contamination.
 */
function preventCrossTestContamination(): void
{
    \Tests\Support\TestIsolationManager::preventCrossTestContamination();
}

/**
 * Build dependency graph from container bindings.
 */
function buildDependencyGraph(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\DependencyGraphValidator::buildDependencyGraph($app);
}

/**
 * Calculate dependency depth for services.
 */
function calculateDependencyDepth(): array
{
    return \Tests\Support\DependencyGraphValidator::calculateDependencyDepth();
}

/**
 * Generate optimal service resolution order.
 */
function generateResolutionOrder(): array
{
    return \Tests\Support\DependencyGraphValidator::generateResolutionOrder();
}

/**
 * Analyze dependency complexity and bottlenecks.
 */
function analyzeDependencyComplexity(): array
{
    return \Tests\Support\DependencyGraphValidator::analyzeDependencyComplexity();
}

/**
 * Find dependency bottlenecks in the service graph.
 */
function findDependencyBottlenecks(): array
{
    return \Tests\Support\DependencyGraphValidator::findDependencyBottlenecks();
}

/**
 * Validate service resolution order.
 */
function validateResolutionOrder(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\DependencyGraphValidator::validateResolutionOrder($app);
}

/**
 * Get container statistics and monitoring data.
 */
function getContainerStatistics(\Illuminate\Contracts\Foundation\Application $app): array
{
    return \Tests\Support\ContainerTestHelper::getContainerStatistics($app);
}

/**
 * Get dependency graph statistics.
 */
function getDependencyGraphStatistics(): array
{
    return \Tests\Support\DependencyGraphValidator::getDependencyGraphStatistics();
}

/**
 * Clear dependency graph cache.
 */
function clearDependencyGraphCache(): void
{
    \Tests\Support\DependencyGraphValidator::clearCache();
}

/**
 * Add test-specific binding to container helper.
 */
function addTestBinding(string $abstract, mixed $concrete): void
{
    \Tests\Support\ContainerTestHelper::addTestBinding($abstract, $concrete);
}

/**
 * Clear test-specific bindings.
 */
function clearTestBindings(): void
{
    \Tests\Support\ContainerTestHelper::clearTestBindings();
}

/**
 * Get container errors from helper.
 */
function getContainerErrors(): array
{
    return \Tests\Support\ContainerTestHelper::getContainerErrors();
}

/**
 * Clear container errors.
 */
function clearContainerErrors(): void
{
    \Tests\Support\ContainerTestHelper::clearContainerErrors();
}

/**
 * Register isolation callback for cleanup.
 */
function registerIsolationCallback(string $name, callable $callback): void
{
    \Tests\Support\TestIsolationManager::registerIsolationCallback($name, $callback);
}

/**
 * Get isolation statistics.
 */
function getIsolationStatistics(): array
{
    return \Tests\Support\TestIsolationManager::getIsolationStatistics();
}

/**
 * Force cleanup in case of emergency.
 */
function forceCleanup(): void
{
    \Tests\Support\TestIsolationManager::forceCleanup();
}

/*
|--------------------------------------------------------------------------
| Error Message and Compatibility Testing Helper Functions
|--------------------------------------------------------------------------
|
| Enhanced helper functions for error message validation and backward
| compatibility testing with comprehensive validation utilities.
|
*/

/**
 * Validate error message with exact text matching.
 */
function validateErrorMessage(string $actualMessage, string $expectedMessage, bool $caseSensitive = true): bool
{
    return \Tests\Support\ErrorMessageTestHelper::validateExactText($actualMessage, $expectedMessage, $caseSensitive);
}

/**
 * Validate error message against standard pattern.
 */
function validateErrorPattern(string $message, string $messageType): bool
{
    return \Tests\Support\ErrorMessageTestHelper::validateStandardPattern($message, $messageType);
}

/**
 * Validate error message template compliance.
 */
function validateErrorTemplate(string $message, string $templateType, array $placeholders = []): array
{
    return \Tests\Support\ErrorMessageTestHelper::validateTemplate($message, $templateType, $placeholders);
}

/**
 * Validate error message parameter substitution.
 */
function validateParameterSubstitution(string $template, array $parameters, string $expectedResult): array
{
    return \Tests\Support\ErrorMessageTestHelper::validateParameterSubstitution($template, $parameters, $expectedResult);
}

/**
 * Validate error message localization.
 */
function validateErrorLocalization(string $messageKey, string $locale, array $parameters = []): array
{
    return \Tests\Support\LocalizationTestUtilities::testErrorMessageLocalization($messageKey, $locale, $parameters);
}

/**
 * Validate error message contextual information.
 */
function validateErrorContext(\App\Exceptions\ValidateLinksException $exception, array $expectedContext = []): array
{
    return \Tests\Support\ErrorMessageTestHelper::validateContextualInformation($exception, $expectedContext);
}

/**
 * Validate error message user experience compliance.
 */
function validateErrorUX(string $message, array $customGuidelines = []): array
{
    return \Tests\Support\ErrorMessageTestHelper::validateUserExperience($message, $customGuidelines);
}

/**
 * Validate error message accessibility compliance.
 */
function validateErrorAccessibility(string $message): array
{
    return \Tests\Support\ErrorMessageTestHelper::validateAccessibility($message);
}

/**
 * Generate comprehensive error message validation report.
 */
function generateErrorValidationReport(\App\Exceptions\ValidateLinksException $exception, array $config = []): array
{
    return \Tests\Support\ErrorMessageTestHelper::generateValidationReport($exception, $config);
}

/**
 * Test version compatibility for a component.
 */
function testVersionCompatibility(string $component, string $version): array
{
    return \Tests\Support\CompatibilityTestHelper::testVersionCompatibility($component, $version);
}

/**
 * Test API compatibility between versions.
 */
function testApiCompatibility(string $className, string $oldVersion, string $newVersion): array
{
    return \Tests\Support\CompatibilityTestHelper::testApiCompatibility($className, $oldVersion, $newVersion);
}

/**
 * Validate legacy test patterns in a file.
 */
function validateLegacyPatterns(string $testFilePath): array
{
    return \Tests\Support\CompatibilityTestHelper::validateLegacyTestPatterns($testFilePath);
}

/**
 * Test migration path between versions.
 */
function testMigrationPath(string $fromVersion, string $toVersion, string $component = 'pest'): array
{
    return \Tests\Support\CompatibilityTestHelper::testMigrationPath($fromVersion, $toVersion, $component);
}

/**
 * Validate compatibility matrix for version combinations.
 */
function validateCompatibilityMatrix(array $versions): array
{
    return \Tests\Support\CompatibilityTestHelper::validateCompatibilityMatrix($versions);
}

/**
 * Test deprecation handling for a feature.
 */
function testDeprecationHandling(string $feature, string $version): array
{
    return \Tests\Support\CompatibilityTestHelper::testDeprecationHandling($feature, $version);
}

/**
 * Generate comprehensive compatibility report.
 */
function generateCompatibilityReport(array $currentVersions, array $targetVersions): array
{
    return \Tests\Support\CompatibilityTestHelper::generateCompatibilityReport($currentVersions, $targetVersions);
}

/**
 * Test locale support and validation.
 */
function testLocaleSupport(string $locale): array
{
    return \Tests\Support\LocalizationTestUtilities::testLocaleSupport($locale);
}

/**
 * Test character encoding for text.
 */
function testCharacterEncoding(string $text, string $encoding = 'UTF-8'): array
{
    return \Tests\Support\LocalizationTestUtilities::testCharacterEncoding($text, $encoding);
}

/**
 * Test RTL language support.
 */
function testRtlSupport(string $text, string $locale): array
{
    return \Tests\Support\LocalizationTestUtilities::testRtlSupport($text, $locale);
}

/**
 * Test pluralization rules for a locale.
 */
function testPluralization(string $locale, int $count, string $messageKey = 'simple'): array
{
    return \Tests\Support\LocalizationTestUtilities::testPluralization($locale, $count, $messageKey);
}

/**
 * Test fallback mechanism for missing translations.
 */
function testFallbackMechanism(string $messageKey, string $primaryLocale, string $fallbackLocale = 'en'): array
{
    return \Tests\Support\LocalizationTestUtilities::testFallbackMechanism($messageKey, $primaryLocale, $fallbackLocale);
}

/**
 * Test date and time formatting for a locale.
 */
function testDateTimeFormatting(string $locale, ?int $timestamp = null): array
{
    return \Tests\Support\LocalizationTestUtilities::testDateTimeFormatting($locale, $timestamp);
}

/**
 * Test number formatting for a locale.
 */
function testNumberFormatting(float $number, string $locale): array
{
    return \Tests\Support\LocalizationTestUtilities::testNumberFormatting($number, $locale);
}

/**
 * Generate comprehensive localization report.
 */
function generateLocalizationReport(string $locale, array $messageKeys = []): array
{
    return \Tests\Support\LocalizationTestUtilities::generateLocalizationReport($locale, $messageKeys);
}

/*
|--------------------------------------------------------------------------
| Enhanced Custom Expectations for Error Messages and Compatibility
|--------------------------------------------------------------------------
|
| Extended expectations for comprehensive error message and compatibility validation.
|
*/

expect()->extend('toHaveValidErrorMessage', function () {
    /** @var Pest\Expectation $this */
    $exception = $this->value;
    
    if (!$exception instanceof \App\Exceptions\ValidateLinksException) {
        throw new InvalidArgumentException('Expected ValidateLinksException instance');
    }
    
    $message = $exception->getMessage();
    
    return $this->not->toBeEmpty()
        ->and(strlen($message))->toBeGreaterThan(10)
        ->and(strlen($message))->toBeLessThan(500)
        ->and(mb_check_encoding($message, 'UTF-8'))->toBeTrue();
});

expect()->extend('toHaveValidErrorContext', function (array $expectedKeys = []) {
    /** @var Pest\Expectation $this */
    $exception = $this->value;
    
    if (!$exception instanceof \App\Exceptions\ValidateLinksException) {
        throw new InvalidArgumentException('Expected ValidateLinksException instance');
    }
    
    $context = $exception->getContext();
    
    $expectation = $this->toBeArray();
    
    foreach ($expectedKeys as $key) {
        $expectation = $expectation->and($context)->toHaveKey($key);
    }
    
    return $expectation;
});

expect()->extend('toMatchErrorPattern', function (string $patternType) {
    /** @var Pest\Expectation $this */
    $message = $this->value;
    
    if (!is_string($message)) {
        throw new InvalidArgumentException('Expected string message');
    }
    
    $isValid = \Tests\Support\ErrorMessageTestHelper::validateStandardPattern($message, $patternType);
    
    return expect($isValid)->toBeTrue();
});

expect()->extend('toBeCompatibleVersion', function (string $component) {
    /** @var Pest\Expectation $this */
    $version = $this->value;
    
    if (!is_string($version)) {
        throw new InvalidArgumentException('Expected string version');
    }
    
    $results = \Tests\Support\CompatibilityTestHelper::testVersionCompatibility($component, $version);
    
    return expect($results['compatible'])->toBeTrue();
});

expect()->extend('toSupportLocale', function () {
    /** @var Pest\Expectation $this */
    $locale = $this->value;
    
    if (!is_string($locale)) {
        throw new InvalidArgumentException('Expected string locale');
    }
    
    $results = \Tests\Support\LocalizationTestUtilities::testLocaleSupport($locale);
    
    return expect($results['supported'])->toBeTrue();
});

expect()->extend('toHaveValidEncoding', function (string $encoding = 'UTF-8') {
    /** @var Pest\Expectation $this */
    $text = $this->value;
    
    if (!is_string($text)) {
        throw new InvalidArgumentException('Expected string text');
    }
    
    $results = \Tests\Support\LocalizationTestUtilities::testCharacterEncoding($text, $encoding);
    
    return expect($results['valid_encoding'])->toBeTrue();
});

expect()->extend('toBeAccessibleErrorMessage', function () {
    /** @var Pest\Expectation $this */
    $message = $this->value;
    
    if (!is_string($message)) {
        throw new InvalidArgumentException('Expected string message');
    }
    
    $results = \Tests\Support\ErrorMessageTestHelper::validateAccessibility($message);
    
    return expect($results['screen_reader_friendly'])->toBeTrue()
        ->and($results['color_independent'])->toBeTrue()
        ->and($results['plain_language'])->toBeTrue();
});

expect()->extend('toHaveGoodUX', function (float $minScore = 7.0) {
    /** @var Pest\Expectation $this */
    $message = $this->value;
    
    if (!is_string($message)) {
        throw new InvalidArgumentException('Expected string message');
    }
    
    $results = \Tests\Support\ErrorMessageTestHelper::validateUserExperience($message);
    
    return expect($results['ux_score'])->toBeGreaterThanOrEqual($minScore)
        ->and($results['length_appropriate'])->toBeTrue()
        ->and($results['tone_appropriate'])->toBeTrue();
});

/*
|--------------------------------------------------------------------------
| Legacy Helper Functions (Maintained for Backward Compatibility)
|--------------------------------------------------------------------------
|
| Original helper functions maintained for existing tests.
|
*/

/**
 * Clean up test files and directories with proper error handling.
 * @deprecated Use safeCleanupDirectory() instead
 */
function cleanupTestFiles(?string $tempDir = null): void
{
    if ($tempDir === null) {
        $tempDir = sys_get_temp_dir().'/validate-links-tests';
    }
    
    safeCleanupDirectory($tempDir);
}
