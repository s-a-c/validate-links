<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;
use Throwable;

/**
 * Value object representing reporting and formatting options.
 *
 * This class replaces generic array options with strongly typed properties
 * for better type safety and validation in reporting operations.
 */
final readonly class ReportingOptions
{
    /**
     * @param bool $colors Whether to include ANSI color codes in console output
     * @param bool $verbose Whether to include verbose/detailed information
     * @param int|null $maxLineLength Maximum line length for text wrapping
     * @param bool $includeTimestamps Whether to include timestamps in output
     * @param bool $showProgress Whether to show progress indicators
     * @param bool $includeStatistics Whether to include detailed statistics
     * @param bool $includeMetadata Whether to include metadata information
     * @param string $dateFormat Format string for date/time display
     * @param int $indentSize Number of spaces for indentation
     * @param bool $sortResults Whether to sort results alphabetically
     * @param bool $groupByType Whether to group results by type/category
     * @param bool $showSuccessful Whether to show successful validations
     * @param bool $showOnlyErrors Whether to show only errors (overrides showSuccessful)
     */
    public function __construct(
        public bool $colors = true,
        public bool $verbose = false,
        public ?int $maxLineLength = null,
        public bool $includeTimestamps = false,
        public bool $showProgress = true,
        public bool $includeStatistics = true,
        public bool $includeMetadata = false,
        public string $dateFormat = 'Y-m-d H:i:s',
        public int $indentSize = 2,
        public bool $sortResults = false,
        public bool $groupByType = false,
        public bool $showSuccessful = true,
        public bool $showOnlyErrors = false,
    ) {
        $this->validate();
    }

    /**
     * Create ReportingOptions from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $colors = isset($data['colors']) ? (bool) $data['colors'] : true;
        $verbose = isset($data['verbose']) ? (bool) $data['verbose'] : false;

        $maxLineLength = isset($data['max_line_length']) && is_numeric($data['max_line_length'])
            ? (int) $data['max_line_length']
            : null;

        $includeTimestamps = isset($data['include_timestamps']) ? (bool) $data['include_timestamps'] : false;
        $showProgress = isset($data['show_progress']) ? (bool) $data['show_progress'] : true;
        $includeStatistics = isset($data['include_statistics']) ? (bool) $data['include_statistics'] : true;
        $includeMetadata = isset($data['include_metadata']) ? (bool) $data['include_metadata'] : false;

        $dateFormat = isset($data['date_format']) && is_string($data['date_format'])
            ? $data['date_format']
            : 'Y-m-d H:i:s';

        $indentSize = isset($data['indent_size']) && is_numeric($data['indent_size'])
            ? (int) $data['indent_size']
            : 2;

        $sortResults = isset($data['sort_results']) ? (bool) $data['sort_results'] : false;
        $groupByType = isset($data['group_by_type']) ? (bool) $data['group_by_type'] : false;
        $showSuccessful = isset($data['show_successful']) ? (bool) $data['show_successful'] : true;
        $showOnlyErrors = isset($data['show_only_errors']) ? (bool) $data['show_only_errors'] : false;

        return new self(
            colors: $colors,
            verbose: $verbose,
            maxLineLength: $maxLineLength,
            includeTimestamps: $includeTimestamps,
            showProgress: $showProgress,
            includeStatistics: $includeStatistics,
            includeMetadata: $includeMetadata,
            dateFormat: $dateFormat,
            indentSize: $indentSize,
            sortResults: $sortResults,
            groupByType: $groupByType,
            showSuccessful: $showSuccessful,
            showOnlyErrors: $showOnlyErrors,
        );
    }

    /**
     * Create default console options.
     */
    public static function console(): self
    {
        return new self(
            colors: true,
            verbose: false,
            maxLineLength: 120,
            includeTimestamps: false,
            showProgress: true,
            includeStatistics: true,
            includeMetadata: false,
            sortResults: false,
            groupByType: true,
            showSuccessful: false, // Console typically shows only errors
            showOnlyErrors: false,
        );
    }

    /**
     * Create default file output options.
     */
    public static function file(): self
    {
        return new self(
            colors: false, // No ANSI colors in files
            verbose: true,
            maxLineLength: null, // No line length limit for files
            includeTimestamps: true,
            showProgress: false, // No progress in files
            includeStatistics: true,
            includeMetadata: true,
            sortResults: true,
            groupByType: true,
            showSuccessful: true, // Files include all results
            showOnlyErrors: false,
        );
    }

    /**
     * Create verbose debugging options.
     */
    public static function debug(): self
    {
        return new self(
            colors: true,
            verbose: true,
            maxLineLength: null,
            includeTimestamps: true,
            showProgress: true,
            includeStatistics: true,
            includeMetadata: true,
            sortResults: false, // Keep original order for debugging
            groupByType: false,
            showSuccessful: true,
            showOnlyErrors: false,
        );
    }

    /**
     * Create minimal error-only options.
     */
    public static function errorsOnly(): self
    {
        return new self(
            colors: true,
            verbose: false,
            maxLineLength: 120,
            includeTimestamps: false,
            showProgress: false,
            includeStatistics: false,
            includeMetadata: false,
            sortResults: false,
            groupByType: false,
            showSuccessful: false,
            showOnlyErrors: true,
        );
    }

    /**
     * Create options with overrides.
     *
     * @param array<string, mixed> $overrides
     */
    public function withOverrides(array $overrides): self
    {
        $data = $this->toArray();
        $mergedData = array_merge($data, $overrides);

        return self::fromArray($mergedData);
    }

    /**
     * Get effective max line length (with fallback).
     */
    public function getEffectiveMaxLineLength(): int
    {
        return $this->maxLineLength ?? 120;
    }

    /**
     * Get indentation string.
     */
    public function getIndentString(): string
    {
        return str_repeat(' ', $this->indentSize);
    }

    /**
     * Check if results should be filtered.
     */
    public function shouldShowResult(bool $isSuccessful): bool
    {
        if ($this->showOnlyErrors) {
            return ! $isSuccessful;
        }

        if (! $this->showSuccessful) {
            return ! $isSuccessful;
        }

        return true; // Show all results
    }

    /**
     * Format a timestamp according to the configured format.
     */
    public function formatTimestamp(int $timestamp): string
    {
        return date($this->dateFormat, $timestamp);
    }

    /**
     * Format current timestamp.
     */
    public function formatCurrentTimestamp(): string
    {
        return $this->formatTimestamp(time());
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'colors' => $this->colors,
            'verbose' => $this->verbose,
            'max_line_length' => $this->maxLineLength,
            'include_timestamps' => $this->includeTimestamps,
            'show_progress' => $this->showProgress,
            'include_statistics' => $this->includeStatistics,
            'include_metadata' => $this->includeMetadata,
            'date_format' => $this->dateFormat,
            'indent_size' => $this->indentSize,
            'sort_results' => $this->sortResults,
            'group_by_type' => $this->groupByType,
            'show_successful' => $this->showSuccessful,
            'show_only_errors' => $this->showOnlyErrors,
        ];
    }

    /**
     * Validate the options values.
     */
    private function validate(): void
    {
        if ($this->maxLineLength !== null && $this->maxLineLength < 20) {
            throw new InvalidArgumentException('Max line length must be at least 20 characters');
        }

        if ($this->indentSize < 0 || $this->indentSize > 20) {
            throw new InvalidArgumentException('Indent size must be between 0 and 20');
        }

        // Validate date format by attempting to format current time
        try {
            $testDate = date($this->dateFormat);
            if ($testDate === '') {
                throw new InvalidArgumentException("Invalid date format: {$this->dateFormat}");
            }
        } catch (Throwable) {
            throw new InvalidArgumentException("Invalid date format: {$this->dateFormat}");
        }
    }
}
