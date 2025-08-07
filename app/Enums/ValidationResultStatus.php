<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for validation result status values.
 *
 * This enum provides type-safe constants for validation result statuses
 * used throughout the application for consistent status handling.
 */
enum ValidationResultStatus: string
{
    case PASSED = 'passed';
    case FAILED = 'failed';
    case SUCCESS = 'success';
    case ERROR = 'error';
    case PENDING = 'pending';
    case SKIPPED = 'skipped';

    /**
     * Get all status values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status represents a successful validation.
     */
    public function isSuccessful(): bool
    {
        return match ($this) {
            self::PASSED, self::SUCCESS => true,
            default => false,
        };
    }

    /**
     * Check if status represents a failed validation.
     */
    public function isFailed(): bool
    {
        return match ($this) {
            self::FAILED, self::ERROR => true,
            default => false,
        };
    }

    /**
     * Get display name for the status.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::PASSED => 'Passed',
            self::FAILED => 'Failed',
            self::SUCCESS => 'Success',
            self::ERROR => 'Error',
            self::PENDING => 'Pending',
            self::SKIPPED => 'Skipped',
        };
    }

    /**
     * Get console color for status display.
     */
    public function getConsoleColor(): string
    {
        return match ($this) {
            self::PASSED, self::SUCCESS => 'green',
            self::FAILED, self::ERROR => 'red',
            self::PENDING => 'yellow',
            self::SKIPPED => 'cyan',
        };
    }

    /**
     * Get status icon for console display.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::PASSED, self::SUCCESS => '✅',
            self::FAILED, self::ERROR => '❌',
            self::PENDING => '⏳',
            self::SKIPPED => '⏭️',
        };
    }
}
