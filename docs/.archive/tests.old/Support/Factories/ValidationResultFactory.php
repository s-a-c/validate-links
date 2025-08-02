<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Factories;

use App\Enums\LinkStatus;

/**
 * Factory for creating validation result arrays for testing.
 *
 * Provides convenient methods for creating realistic validation results
 * with different scenarios and status combinations.
 */
final class ValidationResultFactory
{
    /**
     * Create a successful validation result.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function success(array $overrides = []): array
    {
        $defaults = [
            'url' => 'https://example.com',
            'status' => LinkStatus::VALID,
            'status_code' => 200,
            'response_time' => 250,
            'final_url' => 'https://example.com',
            'redirects' => [],
            'error' => null,
            'checked_at' => now()->toISOString(),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create a failed validation result.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function failure(array $overrides = []): array
    {
        $defaults = [
            'url' => 'https://broken.example.com',
            'status' => LinkStatus::BROKEN,
            'status_code' => 404,
            'response_time' => 1000,
            'final_url' => null,
            'redirects' => [],
            'error' => 'Not Found',
            'checked_at' => now()->toISOString(),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create a timeout validation result.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function timeout(array $overrides = []): array
    {
        return self::failure(array_merge([
            'status' => LinkStatus::TIMEOUT,
            'status_code' => null,
            'response_time' => 30000,
            'error' => 'Request timeout',
        ], $overrides));
    }

    /**
     * Create a redirect validation result.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function redirect(array $overrides = []): array
    {
        $defaults = [
            'url' => 'https://example.com/old',
            'status' => LinkStatus::VALID,
            'status_code' => 200,
            'response_time' => 350,
            'final_url' => 'https://example.com/new',
            'redirects' => [
                [
                    'from' => 'https://example.com/old',
                    'to' => 'https://example.com/new',
                    'status_code' => 301,
                ],
            ],
            'error' => null,
            'checked_at' => now()->toISOString(),
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create a validation result with specific status.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function withStatus(LinkStatus $status, array $overrides = []): array
    {
        $base = match ($status) {
            LinkStatus::VALID => self::success(),
            LinkStatus::BROKEN => self::failure(),
            LinkStatus::TIMEOUT => self::timeout(),
            LinkStatus::REDIRECT_LOOP => self::redirect(),
            default => self::success(),
        };

        return array_merge($base, ['status' => $status], $overrides);
    }

    /**
     * Create a batch of validation results.
     *
     * @param  array<string, mixed>  $overrides
     * @return list<array<string, mixed>>
     */
    public static function batch(int $count, array $overrides = []): array
    {
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            $results[] = self::success(array_merge([
                'url' => "https://example.com/page-{$i}",
            ], $overrides));
        }

        return $results;
    }

    /**
     * Create mixed validation results (some success, some failure).
     *
     * @return list<array<string, mixed>>
     */
    public static function mixed(int $successCount = 3, int $failureCount = 2): array
    {
        $results = [];

        // Add successful results
        for ($i = 0; $i < $successCount; $i++) {
            $results[] = self::success([
                'url' => "https://example.com/success-{$i}",
            ]);
        }

        // Add failed results
        for ($i = 0; $i < $failureCount; $i++) {
            $results[] = self::failure([
                'url' => "https://broken.example.com/fail-{$i}",
            ]);
        }

        return $results;
    }

    /**
     * Create a complete validation summary.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function summary(array $overrides = []): array
    {
        $defaults = [
            'total_links' => 10,
            'valid_links' => 8,
            'broken_links' => 2,
            'timeout_links' => 0,
            'redirect_links' => 1,
            'files_processed' => 5,
            'total_time' => 2.5,
            'average_response_time' => 300,
            'success_rate' => 0.8,
        ];

        return array_merge($defaults, $overrides);
    }
}
