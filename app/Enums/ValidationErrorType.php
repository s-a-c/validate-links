<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for validation error types.
 *
 * This enum provides type-safe constants for different types of validation errors
 * that can occur during link validation processes.
 */
enum ValidationErrorType: string
{
    case LINK_VALIDATION = 'link_validation';
    case FILE_PROCESSING = 'file_processing';
    case TIMEOUT = 'timeout';
    case SECURITY_VIOLATION = 'security_violation';
    case CONFIGURATION_ERROR = 'configuration_error';
    case NETWORK_ERROR = 'network_error';
    case FORMAT_ERROR = 'format_error';
    case PERMISSION_ERROR = 'permission_error';

    /**
     * Get all error type values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get display name for the error type.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::LINK_VALIDATION => 'Link Validation Error',
            self::FILE_PROCESSING => 'File Processing Error',
            self::TIMEOUT => 'Timeout Error',
            self::SECURITY_VIOLATION => 'Security Violation',
            self::CONFIGURATION_ERROR => 'Configuration Error',
            self::NETWORK_ERROR => 'Network Error',
            self::FORMAT_ERROR => 'Format Error',
            self::PERMISSION_ERROR => 'Permission Error',
        };
    }

    /**
     * Get severity level for the error type.
     */
    public function getSeverity(): string
    {
        return match ($this) {
            self::SECURITY_VIOLATION => 'critical',
            self::CONFIGURATION_ERROR => 'high',
            self::LINK_VALIDATION, self::FILE_PROCESSING => 'medium',
            self::TIMEOUT, self::NETWORK_ERROR => 'low',
            self::FORMAT_ERROR, self::PERMISSION_ERROR => 'medium',
        };
    }

    /**
     * Check if error type is recoverable.
     */
    public function isRecoverable(): bool
    {
        return match ($this) {
            self::TIMEOUT, self::NETWORK_ERROR => true,
            self::SECURITY_VIOLATION, self::CONFIGURATION_ERROR => false,
            default => true,
        };
    }

    /**
     * Get recommended action for the error type.
     */
    public function getRecommendedAction(): string
    {
        return match ($this) {
            self::LINK_VALIDATION => 'Check link URL and target availability',
            self::FILE_PROCESSING => 'Verify file exists and is readable',
            self::TIMEOUT => 'Retry with longer timeout or check network',
            self::SECURITY_VIOLATION => 'Review security settings and URL safety',
            self::CONFIGURATION_ERROR => 'Check application configuration',
            self::NETWORK_ERROR => 'Check network connectivity and retry',
            self::FORMAT_ERROR => 'Verify URL format and encoding',
            self::PERMISSION_ERROR => 'Check file and directory permissions',
        };
    }

    /**
     * Get error code for the error type.
     */
    public function getErrorCode(): string
    {
        return match ($this) {
            self::LINK_VALIDATION => 'VALIDATION_ERROR',
            self::FILE_PROCESSING => 'FILE_ERROR',
            self::TIMEOUT => 'TIMEOUT_ERROR',
            self::SECURITY_VIOLATION => 'SECURITY_ERROR',
            self::CONFIGURATION_ERROR => 'CONFIG_ERROR',
            self::NETWORK_ERROR => 'NETWORK_ERROR',
            self::FORMAT_ERROR => 'FORMAT_ERROR',
            self::PERMISSION_ERROR => 'PERMISSION_ERROR',
        };
    }
}
