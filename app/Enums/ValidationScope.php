<?php

declare(strict_types=1);

namespace App\Enums;

use function in_array;

enum ValidationScope: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case ANCHOR = 'anchor';
    case IMAGE = 'image';
    case ALL = 'all';

    /**
     * Get all scope values as array.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get all scope names as array.
     *
     * @return string[]
     */
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    /**
     * Get formatted options for Laravel Prompts select/multiselect.
     *
     * @return array<string, string>
     */
    public static function getSelectOptions(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getDescription();
        }

        return $options;
    }

    /**
     * Check if a string value is a valid ValidationScope.
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, self::values(), true);
    }

    /**
     * Create from string value with validation.
     */
    public static function fromString(string $value): self
    {
        return self::from(mb_strtolower($value));
    }

    /**
     * Check if scope includes external validation.
     */
    public function includesExternal(): bool
    {
        return $this === self::EXTERNAL || $this === self::ALL;
    }

    /**
     * Check if scope includes internal validation.
     */
    public function includesInternal(): bool
    {
        return $this === self::INTERNAL || $this === self::ALL;
    }

    /**
     * Check if scope includes anchor validation.
     */
    public function includesAnchor(): bool
    {
        return $this === self::ANCHOR || $this === self::ALL;
    }

    /**
     * Check if scope includes image validation.
     */
    public function includesImage(): bool
    {
        return $this === self::IMAGE || $this === self::ALL;
    }

    /**
     * Get human-readable description for each scope.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::INTERNAL => 'Validate internal file and directory links',
            self::EXTERNAL => 'Validate external HTTP/HTTPS links',
            self::ANCHOR => 'Validate anchor links within documents',
            self::IMAGE => 'Validate image links and embedded media',
            self::ALL => 'Validate all link types (comprehensive validation)',
        };
    }

    /**
     * Get detailed explanation for help text.
     */
    public function getHelpText(): string
    {
        return match ($this) {
            self::INTERNAL => 'Validates links to files and directories within the project',
            self::EXTERNAL => 'Validates HTTP/HTTPS links to external websites',
            self::ANCHOR => 'Validates anchor links pointing to sections within documents',
            self::IMAGE => 'Validates image links and embedded media files',
            self::ALL => 'Validates all link types comprehensively',
        };
    }

    /**
     * Get scopes that should be included when this scope is selected.
     *
     * @return ValidationScope[]
     */
    public function getIncludedScopes(): array
    {
        return match ($this) {
            self::ALL => [self::INTERNAL, self::EXTERNAL, self::ANCHOR, self::IMAGE],
            default => [$this],
        };
    }

    /**
     * Get validation priority (lower number = higher priority).
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::INTERNAL => 1,
            self::ANCHOR => 2,
            self::IMAGE => 3,
            self::EXTERNAL => 4,
            self::ALL => 0
        };
    }
}
