<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for cross-reference validation status values.
 *
 * This enum provides type-safe constants for cross-reference validation results
 * when validating internal links that reference other files and anchors.
 */
enum CrossReferenceStatus: string
{
    case VALID = 'valid';
    case BROKEN = 'broken';
    case FILE_NOT_FOUND = 'file_not_found';
    case ANCHOR_NOT_FOUND = 'anchor_not_found';
    case INVALID_FORMAT = 'invalid_format';
    case CIRCULAR_REFERENCE = 'circular_reference';

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
    public function isValid(): bool
    {
        return $this === self::VALID;
    }

    /**
     * Check if status represents a failed validation.
     */
    public function isBroken(): bool
    {
        return ! $this->isValid();
    }

    /**
     * Check if status is recoverable (can be fixed).
     */
    public function isRecoverable(): bool
    {
        return match ($this) {
            self::FILE_NOT_FOUND, self::ANCHOR_NOT_FOUND => true,
            self::INVALID_FORMAT, self::CIRCULAR_REFERENCE => false,
            default => false,
        };
    }

    /**
     * Get display name for the status.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::VALID => 'Valid',
            self::BROKEN => 'Broken',
            self::FILE_NOT_FOUND => 'File Not Found',
            self::ANCHOR_NOT_FOUND => 'Anchor Not Found',
            self::INVALID_FORMAT => 'Invalid Format',
            self::CIRCULAR_REFERENCE => 'Circular Reference',
        };
    }

    /**
     * Get console color for status display.
     */
    public function getConsoleColor(): string
    {
        return match ($this) {
            self::VALID => 'green',
            self::FILE_NOT_FOUND, self::ANCHOR_NOT_FOUND => 'yellow',
            self::INVALID_FORMAT => 'magenta',
            self::CIRCULAR_REFERENCE => 'red',
            self::BROKEN => 'red',
        };
    }

    /**
     * Get status icon for console display.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::VALID => '✅',
            self::FILE_NOT_FOUND => '📄',
            self::ANCHOR_NOT_FOUND => '⚓',
            self::INVALID_FORMAT => '❓',
            self::CIRCULAR_REFERENCE => '🔄',
            self::BROKEN => '❌',
        };
    }

    /**
     * Get recommended action for the status.
     */
    public function getRecommendedAction(): string
    {
        return match ($this) {
            self::VALID => 'No action required',
            self::FILE_NOT_FOUND => 'Create the target file or update the link',
            self::ANCHOR_NOT_FOUND => 'Add the target anchor or update the link',
            self::INVALID_FORMAT => 'Fix the link format',
            self::CIRCULAR_REFERENCE => 'Remove circular reference',
            self::BROKEN => 'Fix the broken reference',
        };
    }

    /**
     * Get severity level for the status.
     */
    public function getSeverity(): string
    {
        return match ($this) {
            self::VALID => 'info',
            self::FILE_NOT_FOUND, self::ANCHOR_NOT_FOUND => 'warning',
            self::INVALID_FORMAT => 'error',
            self::CIRCULAR_REFERENCE => 'critical',
            self::BROKEN => 'error',
        };
    }
}
