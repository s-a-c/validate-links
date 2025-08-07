<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when configuration errors occur.
 */
final class ConfigurationException extends ValidateLinksException
{
    /**
     * Create a configuration exception with custom context.
     *
     * @param string $message The exception message
     * @param array<string, mixed> $context Additional context data
     */
    public static function withContext(string $message, array $context): static
    {
        return new self($message, 0, null, $context);
    }

    /**
     * Create a configuration exception for a missing file.
     *
     * @param string $filePath The path to the missing file
     */
    public static function missingFile(string $filePath): static
    {
        $message = "Configuration file not found: {$filePath}";
        $context = [
            'file_path' => $filePath,
            'error_type' => 'missing_file',
        ];

        return new self($message, 0, null, $context);
    }

    /**
     * Create a configuration exception for invalid file format.
     *
     * @param string $filePath The path to the file
     * @param string $expectedFormat The expected format
     * @param string $actualContent The actual content that failed parsing
     */
    public static function invalidFormat(string $filePath, string $expectedFormat, string $actualContent): static
    {
        $message = "Invalid configuration file format in {$filePath}. Expected {$expectedFormat} format.";
        $context = [
            'file_path' => $filePath,
            'expected_format' => $expectedFormat,
            'actual_content' => $actualContent,
            'error_type' => 'invalid_format',
        ];

        return new self($message, 0, null, $context);
    }

    /**
     * Create a configuration exception for a missing required key.
     *
     * @param string $key The missing required key
     * @param string $filePath The path to the configuration file
     */
    public static function missingRequiredKey(string $key, string $filePath): static
    {
        $message = "Missing required configuration key '{$key}' in {$filePath}";
        $context = [
            'required_key' => $key,
            'file_path' => $filePath,
            'error_type' => 'missing_required_key',
        ];

        return new self($message, 0, null, $context);
    }

    /**
     * Create a configuration exception for an invalid value.
     *
     * @param string $key The configuration key
     * @param mixed $value The invalid value
     * @param string $expectedType The expected type
     */
    public static function invalidValue(string $key, mixed $value, string $expectedType): static
    {
        $message = "Invalid value for configuration key '{$key}'. Expected {$expectedType}.";
        $context = [
            'key' => $key,
            'value' => $value,
            'expected_type' => $expectedType,
            'actual_type' => gettype($value),
            'error_type' => 'invalid_value',
        ];

        return new self($message, 0, null, $context);
    }

    /**
     * Create a configuration exception for an invalid file.
     *
     * @param string $filePath The path to the invalid file
     */
    public static function invalidFile(string $filePath): never
    {
        $message = "Configuration error: Invalid configuration file: {$filePath}";
        $context = [
            'file_path' => $filePath,
            'error_type' => 'invalid_file',
        ];

        throw new self($message, 0, null, $context);
    }

    /**
     * Create a configuration exception for an invalid key.
     *
     * @param string $key The invalid key
     */
    public static function invalidKey(string $key): never
    {
        $message = "Configuration error: Invalid configuration key: {$key}";
        $context = [
            'key' => $key,
            'error_type' => 'invalid_key',
        ];

        throw new self($message, 0, null, $context);
    }

    public function getErrorCode(): string
    {
        return 'CONFIGURATION_ERROR';
    }

    public function getSeverity(): string
    {
        return 'high';
    }
}
