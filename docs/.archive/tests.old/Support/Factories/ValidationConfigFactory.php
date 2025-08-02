<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Factories;

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;

/**
 * Factory for creating ValidationConfig instances for testing.
 *
 * Provides convenient methods for creating ValidationConfig objects with
 * predefined or customizable properties for different test scenarios.
 */
final class ValidationConfigFactory
{
    /**
     * Create a ValidationConfig with default test values.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function create(array $overrides = []): ValidationConfig
    {
        $defaults = [
            'timeout' => 30,
            'concurrent_requests' => 5,
            'follow_redirects' => true,
            'check_external' => true,
            'output_format' => OutputFormat::JSON,
            'scope' => ValidationScope::ALL,
            'max_file_size' => 1024 * 1024, // 1MB
            'user_agent' => 'ValidateLinks/1.0 (Test)',
            'retry_attempts' => 3,
            'retry_delay' => 1000, // 1 second
        ];

        $config = array_merge($defaults, $overrides);

        return ValidationConfig::create($config);
    }

    /**
     * Create a ValidationConfig for fast testing (minimal timeouts).
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function fast(array $overrides = []): ValidationConfig
    {
        return self::create(array_merge([
            'timeout' => 5,
            'concurrent_requests' => 10,
            'retry_attempts' => 1,
            'retry_delay' => 100,
        ], $overrides));
    }

    /**
     * Create a ValidationConfig for slow/thorough testing.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function thorough(array $overrides = []): ValidationConfig
    {
        return self::create(array_merge([
            'timeout' => 60,
            'concurrent_requests' => 2,
            'retry_attempts' => 5,
            'retry_delay' => 2000,
        ], $overrides));
    }

    /**
     * Create a ValidationConfig for internal links only.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function internalOnly(array $overrides = []): ValidationConfig
    {
        return self::create(array_merge([
            'check_external' => false,
            'scope' => ValidationScope::INTERNAL,
        ], $overrides));
    }

    /**
     * Create a ValidationConfig for external links only.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function externalOnly(array $overrides = []): ValidationConfig
    {
        return self::create(array_merge([
            'check_external' => true,
            'scope' => ValidationScope::EXTERNAL,
        ], $overrides));
    }

    /**
     * Create a ValidationConfig with specific timeout.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function withTimeout(int $timeout, array $overrides = []): ValidationConfig
    {
        return self::create(array_merge(['timeout' => $timeout], $overrides));
    }

    /**
     * Create a ValidationConfig with specific output format.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function withFormat(OutputFormat $format, array $overrides = []): ValidationConfig
    {
        return self::create(array_merge(['output_format' => $format], $overrides));
    }

    /**
     * Create a ValidationConfig with specific concurrency level.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function withConcurrency(int $concurrent, array $overrides = []): ValidationConfig
    {
        return self::create(array_merge(['concurrent_requests' => $concurrent], $overrides));
    }

    /**
     * Create a ValidationConfig for testing error scenarios.
     *
     * @param  array<string, mixed>  $overrides
     */
    public static function forErrors(array $overrides = []): ValidationConfig
    {
        return self::create(array_merge([
            'timeout' => 1, // Very short timeout to trigger timeouts
            'retry_attempts' => 0, // No retries
            'follow_redirects' => false,
        ], $overrides));
    }
}
