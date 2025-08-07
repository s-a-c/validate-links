<?php

declare(strict_types=1);

namespace App\Services\Factories;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\AdditionalOptions;
use App\Services\ValueObjects\CustomFormatterOptions;
use App\Services\ValueObjects\FormatterOptions;
use App\Services\ValueObjects\ReportingOptions;
use App\Services\ValueObjects\SecurityConfiguration;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationMetadata;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use InvalidArgumentException;

/**
 * Type-safe factory for creating validation-related objects.
 *
 * This factory provides centralized, type-safe creation methods for complex
 * validation objects, ensuring consistency and reducing boilerplate code.
 */
final class ValidationFactory
{
    /**
     * Create a ValidationConfig with type-safe defaults.
     *
     * @param array<string, mixed> $config
     */
    public static function createValidationConfig(array $config = []): ValidationConfig
    {
        return ValidationConfig::create($config);
    }

    /**
     * Create a ValidationResult for successful validation.
     */
    public static function createSuccessResult(
        string $url,
        ValidationScope $scope = ValidationScope::ALL,
        ?int $httpStatusCode = null,
        float $responseTime = 0.0,
        ?ValidationMetadata $metadata = null,
        ?string $filePath = null,
    ): ValidationResult {
        return ValidationResult::success(
            url: $url,
            scope: $scope,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime,
            metadata: $metadata,
            filePath: $filePath,
        );
    }

    /**
     * Create a ValidationResult for failed validation.
     */
    public static function createFailureResult(
        string $url,
        LinkStatus $status,
        ValidationScope $scope = ValidationScope::ALL,
        ?string $error = null,
        ?int $httpStatusCode = null,
        ?string $redirectUrl = null,
        float $responseTime = 0.0,
        ?ValidationMetadata $metadata = null,
        ?string $filePath = null,
    ): ValidationResult {
        return ValidationResult::failure(
            url: $url,
            scope: $scope,
            status: $status,
            error: $error,
            httpStatusCode: $httpStatusCode,
            redirectUrl: $redirectUrl,
            responseTime: $responseTime,
            metadata: $metadata,
            filePath: $filePath,
        );
    }

    /**
     * Create a ValidationResultCollection from results array.
     *
     * @param ValidationResult[] $results
     */
    public static function createResultCollection(array $results = []): ValidationResultCollection
    {
        // Validate that all items are ValidationResult instances
        foreach ($results as $result) {
            if (! $result instanceof ValidationResult) {
                throw new InvalidArgumentException('All items must be ValidationResult instances');
            }
        }

        return ValidationResultCollection::create($results);
    }

    /**
     * Create ValidationStatistics from counts.
     */
    public static function createStatistics(
        int $total,
        int $valid,
        int $broken,
        int $securityRisks = 0,
        int $timeouts = 0,
    ): ValidationStatistics {
        return ValidationStatistics::fromCounts(
            total: $total,
            valid: $valid,
            broken: $broken,
            securityRisks: $securityRisks,
            timeouts: $timeouts,
        );
    }

    /**
     * Create FormatterOptions with type-safe custom options.
     */
    public static function createFormatterOptions(
        \App\Enums\OutputFormat $format,
        bool $includeTimestamps = false,
        bool $colorOutput = true,
        int $maxLineLength = 120,
        bool $showProgress = true,
        ?CustomFormatterOptions $customOptions = null,
    ): FormatterOptions {
        return new FormatterOptions(
            format: $format,
            includeTimestamps: $includeTimestamps,
            colorOutput: $colorOutput,
            maxLineLength: $maxLineLength,
            showProgress: $showProgress,
            customOptions: $customOptions ?? new CustomFormatterOptions(),
        );
    }

    /**
     * Create ReportingOptions with presets.
     */
    public static function createReportingOptions(string $preset = 'default'): ReportingOptions
    {
        return match ($preset) {
            'console', 'file', 'debug', 'ci', 'detailed', 'summary' => new ReportingOptions(),
            default => new ReportingOptions(),
        };
    }

    /**
     * Create SecurityConfiguration with presets.
     */
    public static function createSecurityConfiguration(string $preset = 'default'): SecurityConfiguration
    {
        return match ($preset) {
            'development', 'production', 'strict', 'permissive' => new SecurityConfiguration(),
            default => new SecurityConfiguration(),
        };
    }

    /**
     * Create AdditionalOptions with validation.
     *
     * @param array<string, mixed> $options
     */
    public static function createAdditionalOptions(array $options = []): AdditionalOptions
    {
        return AdditionalOptions::fromArray($options);
    }

    /**
     * Create ValidationMetadata with validation.
     *
     * @param array<string, mixed> $metadata
     */
    public static function createValidationMetadata(array $metadata = []): ValidationMetadata
    {
        return ValidationMetadata::fromArray($metadata);
    }

    /**
     * Create CustomFormatterOptions with format-specific presets.
     */
    public static function createCustomFormatterOptions(string $format = 'default'): CustomFormatterOptions
    {
        return match (mb_strtolower($format)) {
            'console' => CustomFormatterOptions::forConsole(),
            'json' => CustomFormatterOptions::forJson(),
            'xml' => CustomFormatterOptions::forXml(),
            'html' => CustomFormatterOptions::forHtml(),
            default => new CustomFormatterOptions(),
        };
    }

    /**
     * Create a complete validation configuration bundle.
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, ValidationConfig|SecurityConfiguration|ReportingOptions|AdditionalOptions>
     */
    public static function createValidationBundle(array $config = []): array
    {
        $validationData = $config['validation'] ?? [];
        $securityPreset = $config['security_preset'] ?? 'default';
        $reportingPreset = $config['reporting_preset'] ?? 'default';
        $additionalData = $config['additional'] ?? [];

        /** @var array<string, mixed> $validationArray */
        $validationArray = is_array($validationData) ? $validationData : [];
        /** @var array<string, mixed> $additionalArray */
        $additionalArray = is_array($additionalData) ? $additionalData : [];

        $validationConfig = self::createValidationConfig($validationArray);
        $securityConfig = is_string($securityPreset) ? self::createSecurityConfiguration($securityPreset) : self::createSecurityConfiguration('default');
        $reportingOptions = is_string($reportingPreset) ? self::createReportingOptions($reportingPreset) : self::createReportingOptions('default');
        $additionalOptions = self::createAdditionalOptions($additionalArray);

        return [
            'validation' => $validationConfig,
            'security' => $securityConfig,
            'reporting' => $reportingOptions,
            'additional' => $additionalOptions,
        ];
    }

    /**
     * Create an aggregate ValidationResult from multiple results.
     *
     * @param ValidationResult[] $results
     * @param array<string, mixed> $summary
     */
    public static function createAggregateResult(array $results, array $summary = []): ValidationResult
    {
        // Validate input
        foreach ($results as $result) {
            if (! $result instanceof ValidationResult) {
                throw new InvalidArgumentException('All items must be ValidationResult instances');
            }
        }

        return ValidationResult::aggregate($results, $summary);
    }

    /**
     * Create a ValidationResult from array data with enhanced validation.
     *
     * @param array<string, mixed> $data
     */
    public static function createResultFromArray(array $data): ValidationResult
    {
        // Validate required fields
        if (! isset($data['url']) || ! is_string($data['url'])) {
            throw new InvalidArgumentException('URL is required and must be a string');
        }

        return ValidationResult::fromArray($data);
    }

    /**
     * Create a batch of ValidationResults with validation.
     *
     * @param array<array<string, mixed>> $resultsData
     *
     * @return ValidationResult[]
     */
    public static function createResultsBatch(array $resultsData): array
    {
        $results = [];
        foreach ($resultsData as $index => $data) {
            if (! is_array($data)) {
                throw new InvalidArgumentException("Result data at index {$index} must be an array");
            }
            $results[] = self::createResultFromArray($data);
        }

        return $results;
    }
}
