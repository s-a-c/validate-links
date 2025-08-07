<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for severity levels used throughout the application.
 *
 * This enum provides type-safe constants for severity levels
 * used in error handling, logging, and validation reporting.
 */
enum SeverityLevel: string
{
    case CRITICAL = 'critical';
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';
    case INFO = 'info';
    case DEBUG = 'debug';

    /**
     * Get all severity level values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get numeric priority for the severity level (higher = more severe).
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::CRITICAL => 100,
            self::HIGH => 80,
            self::MEDIUM => 60,
            self::LOW => 40,
            self::INFO => 20,
            self::DEBUG => 10,
        };
    }

    /**
     * Get display name for the severity level.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::CRITICAL => 'Critical',
            self::HIGH => 'High',
            self::MEDIUM => 'Medium',
            self::LOW => 'Low',
            self::INFO => 'Info',
            self::DEBUG => 'Debug',
        };
    }

    /**
     * Get console color for severity display.
     */
    public function getConsoleColor(): string
    {
        return match ($this) {
            self::CRITICAL => 'red',
            self::HIGH => 'magenta',
            self::MEDIUM => 'yellow',
            self::LOW => 'cyan',
            self::INFO => 'blue',
            self::DEBUG => 'gray',
        };
    }

    /**
     * Get icon for severity display.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::CRITICAL => '🔴',
            self::HIGH => '🟠',
            self::MEDIUM => '🟡',
            self::LOW => '🔵',
            self::INFO => 'ℹ️',
            self::DEBUG => '🐛',
        };
    }

    /**
     * Check if severity level requires immediate attention.
     */
    public function requiresImmediateAttention(): bool
    {
        return match ($this) {
            self::CRITICAL, self::HIGH => true,
            default => false,
        };
    }

    /**
     * Check if severity level should be logged.
     */
    public function shouldLog(): bool
    {
        return match ($this) {
            self::DEBUG => false, // Only log debug in development
            default => true,
        };
    }

    /**
     * Get description for the severity level.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::CRITICAL => 'System failure or security breach requiring immediate action',
            self::HIGH => 'Significant issue that affects functionality',
            self::MEDIUM => 'Moderate issue that should be addressed',
            self::LOW => 'Minor issue or warning',
            self::INFO => 'Informational message',
            self::DEBUG => 'Debug information for development',
        };
    }

    /**
     * Compare severity levels.
     */
    public function isMoreSevereThan(self $other): bool
    {
        return $this->getPriority() > $other->getPriority();
    }

    /**
     * Compare severity levels.
     */
    public function isLessSevereThan(self $other): bool
    {
        return $this->getPriority() < $other->getPriority();
    }
}
