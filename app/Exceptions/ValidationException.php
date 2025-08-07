<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ArrayKey;
use App\Enums\SeverityLevel;
use App\Enums\ValidationErrorType;

/**
 * Exception thrown when link validation fails.
 */
final class ValidationException extends ValidateLinksException
{
    /**
     * Create a validation exception for link validation failure.
     *
     * @param string $url The URL that failed validation
     * @param int $statusCode The HTTP status code received
     * @param int $responseTime The response time in milliseconds
     */
    public static function linkValidationFailed(string $url, int $statusCode, int $responseTime): static
    {
        $message = "Link validation failed for {$url} (HTTP {$statusCode}, {$responseTime}ms)";
        $context = [
            ArrayKey::URL->value => $url,
            'status_code' => $statusCode,
            'response_time' => $responseTime,
            'validation_type' => ValidationErrorType::LINK_VALIDATION->value,
        ];

        return new self($message, 0, null, $context);
    }

    /**
     * Create a validation exception for file processing failure.
     *
     * @param string $filePath The path to the file that failed processing
     * @param string $error The error description
     */
    public static function fileProcessingFailed(string $filePath, string $error): static
    {
        $message = "File processing failed for {$filePath}: {$error}";
        $context = [
            ArrayKey::FILE_PATH->value => $filePath,
            ArrayKey::ERROR->value => $error,
            'validation_type' => ValidationErrorType::FILE_PROCESSING->value,
        ];

        return new self($message, 0, null, $context);
    }

    /**
     * Create a validation exception for validation timeout.
     *
     * @param string $url The URL that timed out
     * @param int $timeout The timeout value in seconds
     */
    public static function validationTimeout(string $url, int $timeout): static
    {
        $message = "Validation timeout for {$url} after {$timeout} seconds";
        $context = [
            ArrayKey::URL->value => $url,
            'timeout_seconds' => $timeout,
            'validation_type' => ValidationErrorType::TIMEOUT->value,
        ];

        return new self($message, 0, null, $context);
    }

    public function getErrorCode(): string
    {
        return ValidationErrorType::LINK_VALIDATION->getErrorCode();
    }

    public function getSeverity(): string
    {
        return SeverityLevel::MEDIUM->value;
    }
}
