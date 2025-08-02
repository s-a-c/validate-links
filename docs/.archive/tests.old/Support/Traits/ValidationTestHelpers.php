<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;

/**
 * Trait providing validation testing utilities for validate-links testing.
 *
 * This trait follows the established preference for trait-based composition
 * over base class inheritance, providing reusable validation utilities for
 * all test classes that need to create validation configurations and results.
 */
trait ValidationTestHelpers
{
    protected function createValidationConfig(array $overrides = []): ValidationConfig
    {
        return ValidationConfig::withDefaults(array_merge([
            'scopes' => ['internal'],
            'timeout' => 5,
            'max_redirects' => 3,
            'concurrent_requests' => 1,
            'cache_enabled' => false,
        ], $overrides));
    }

    /**
     * Create a test validation result with success status.
     *
     * @param  array<string, mixed>  $overrides  Override default values
     */
    protected function createTestValidationResult(array $overrides = []): ValidationResult
    {
        return ValidationResult::success(
            url: $overrides['url'] ?? 'https://example.com/test-link',
            scope: $overrides['scope'] ?? ValidationScope::INTERNAL,
            httpStatusCode: $overrides['httpStatusCode'] ?? 200,
            responseTime: $overrides['responseTime'] ?? 1.0,
            metadata: $overrides['metadata'] ?? []
        );
    }

    /**
     * Create a failed validation result.
     *
     * @param  array<string, mixed>  $overrides  Override default values
     */
    protected function createFailedValidationResult(array $overrides = []): ValidationResult
    {
        return ValidationResult::failure(
            url: $overrides['url'] ?? 'https://broken.example.com/missing',
            scope: $overrides['scope'] ?? ValidationScope::EXTERNAL,
            status: $overrides['status'] ?? LinkStatus::NOT_FOUND,
            error: $overrides['error'] ?? 'File not found',
            httpStatusCode: $overrides['httpStatusCode'] ?? 404,
            redirectUrl: $overrides['redirectUrl'] ?? null,
            responseTime: $overrides['responseTime'] ?? 1.0,
            metadata: $overrides['metadata'] ?? []
        );
    }

    /**
     * Assert that a validation result represents success.
     *
     * @param  ValidationResult  $result  The validation result to check
     */
    protected function assertValidationSuccess(ValidationResult $result): void
    {
        expect($result->isValid())->toBeTrue();
        expect($result->getBrokenCount())->toBe(0);
    }

    /**
     * Assert that a validation result represents failure.
     *
     * @param  ValidationResult  $result  The validation result to check
     * @param  int|null  $expectedBrokenCount  Expected number of broken links
     */
    protected function assertValidationFailure(ValidationResult $result, ?int $expectedBrokenCount = null): void
    {
        expect($result->isValid())->toBeFalse();

        if ($expectedBrokenCount !== null) {
            expect($result->getBrokenCount())->toBe($expectedBrokenCount);
        } else {
            expect($result->getBrokenCount())->toBeGreaterThan(0);
        }
    }
}
