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

// All tests use Laravel TestCase for full framework functionality
uses(Tests\TestCase::class)->in('Unit');
uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Integration');
uses(Tests\TestCase::class)->in('E2E');

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
| Test Helper Functions
|--------------------------------------------------------------------------
|
| Global helper functions for common testing patterns and data generation.
|
*/

/**
 * Create a temporary test file with specified content.
 */
function createTestFile(string $filename, string $content = ''): string
{
    $tempDir = sys_get_temp_dir().'/validate-links-tests';
    if (! is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    $filepath = "{$tempDir}/{$filename}";
    file_put_contents($filepath, $content);

    return $filepath;
}

/**
 * Clean up test files and directories.
 */
function cleanupTestFiles(): void
{
    $tempDir = sys_get_temp_dir().'/validate-links-tests';
    if (is_dir($tempDir)) {
        array_map('unlink', glob("{$tempDir}/*"));
        rmdir($tempDir);
    }
}

/**
 * Generate test URLs for validation testing.
 *
 * @return array<string, string>
 */
function getTestUrls(): array
{
    return [
        'valid_http' => 'http://example.com',
        'valid_https' => 'https://example.com',
        'invalid_format' => 'not-a-url',
        'missing_protocol' => 'example.com',
        'localhost' => 'http://localhost:8080',
        'with_path' => 'https://example.com/path/to/resource',
        'with_query' => 'https://example.com?param=value',
        'with_fragment' => 'https://example.com#section',
    ];
}

/**
 * Create test validation configuration with default values.
 */
function createTestValidationConfig(array $overrides = []): App\Services\ValueObjects\ValidationConfig
{
    $defaults = [
        'scopes' => [App\Enums\ValidationScope::ALL],
        'timeout' => 30,
        'max_redirects' => 5,
        'concurrent_requests' => 10,
        'cache_enabled' => false,
        'follow_redirects' => true,
        'user_agent' => 'validate-links-test/1.0',
        'excluded_domains' => [],
        'included_domains' => [],
        'security_checks' => true,
    ];

    return App\Services\ValueObjects\ValidationConfig::create(array_merge($defaults, $overrides));
}

/**
 * Create test validation result with specified status.
 */
function createTestValidationResult(
    string $url = 'https://example.com',
    App\Enums\LinkStatus $status = App\Enums\LinkStatus::VALID,
    array $metadata = []
): App\Services\ValueObjects\ValidationResult {
    return App\Services\ValueObjects\ValidationResult::create(
        url: $url,
        status: $status,
        message: '',
        scope: App\Enums\ValidationScope::ALL,
        httpStatusCode: $status->getHttpStatusCode(),
        responseTime: 150.0,
        metadata: $metadata
    );
}
