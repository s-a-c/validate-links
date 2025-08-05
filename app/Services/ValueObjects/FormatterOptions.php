<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\OutputFormat;
use InvalidArgumentException;
use ValueError;

/**
 * Value object representing formatting options for output generation.
 *
 * This class encapsulates all configuration options for formatting validation
 * results in different output formats with customizable display preferences.
 */
final readonly class FormatterOptions
{
    /**
     * @param OutputFormat $format The output format to use
     * @param bool $includeTimestamps Whether to include timestamps in output
     * @param bool $colorOutput Whether to use colored output (where supported)
     * @param int $maxLineLength Maximum line length for text-based formats
     * @param bool $showProgress Whether to show progress indicators
     * @param array<string, mixed> $customOptions Additional format-specific options
     */
    public function __construct(
        public OutputFormat $format,
        public bool $includeTimestamps = false,
        public bool $colorOutput = true,
        public int $maxLineLength = 120,
        public bool $showProgress = true,
        public array $customOptions = [],
    ) {
        $this->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): self
    {
        $format = $data['format'] ?? OutputFormat::CONSOLE;
        if (! $format instanceof OutputFormat) {
            try {
                $format = OutputFormat::from(is_string($format) ? $format : '');
            } catch (ValueError) {
                $format = OutputFormat::CONSOLE;
            }
        }

        $customOptions = $data['custom_options'] ?? [];
        /** @var array<string, mixed> $customOptions */

        return new self(
            format           : $format,
            includeTimestamps: (bool) ($data['include_timestamps'] ?? false),
            colorOutput      : (bool) ($data['color_output'] ?? true),
            maxLineLength    : is_numeric($data['max_line_length'] ?? null) ? (int) $data['max_line_length'] : 120,
            showProgress     : (bool) ($data['show_progress'] ?? true),
            customOptions    : is_array($customOptions) ? $customOptions : [],
        );
    }

    /**
     * Create a new instance with a different output format.
     *
     * @param OutputFormat $format The new output format
     * @return self New FormatterOptions instance with updated format
     */
    public function withFormat(OutputFormat $format): self
    {
        return new self(
            format           : $format,
            includeTimestamps: $this->includeTimestamps,
            colorOutput      : $this->colorOutput,
            maxLineLength    : $this->maxLineLength,
            showProgress     : $this->showProgress,
            customOptions    : $this->customOptions,
        );
    }

    /**
     * Create a new instance with different color output setting.
     *
     * @param bool $colorOutput Whether to enable colored output
     * @return self New FormatterOptions instance with updated color setting
     */
    public function withColorOutput(bool $colorOutput): self
    {
        return new self(
            format           : $this->format,
            includeTimestamps: $this->includeTimestamps,
            colorOutput      : $colorOutput,
            maxLineLength    : $this->maxLineLength,
            showProgress     : $this->showProgress,
            customOptions    : $this->customOptions,
        );
    }

    /**
     * Create a new instance with an additional custom option.
     *
     * @param string $key The option key
     * @param mixed $value The option value
     * @return self New FormatterOptions instance with added custom option
     */
    public function withCustomOption(string $key, mixed $value): self
    {
        $customOptions = $this->customOptions;
        $customOptions[$key] = $value;

        return new self(
            format           : $this->format,
            includeTimestamps: $this->includeTimestamps,
            colorOutput      : $this->colorOutput,
            maxLineLength    : $this->maxLineLength,
            showProgress     : $this->showProgress,
            customOptions    : $customOptions,
        );
    }

    /**
     * Get a custom option value with optional default.
     *
     * @param string $key The option key
     * @param mixed $default Default value if option is not set
     * @return mixed The option value or default
     */
    public function getCustomOption(string $key, mixed $default = null): mixed
    {
        return $this->customOptions[$key] ?? $default;
    }

    /**
     * Check if a custom option is set.
     *
     * @param string $key The option key to check
     * @return bool True if the option exists
     */
    public function hasCustomOption(string $key): bool
    {
        return array_key_exists($key, $this->customOptions);
    }

    /**
     * Check if color output is supported and enabled.
     *
     * @return bool True if color output should be used
     */
    public function supportsColor(): bool
    {
        return $this->colorOutput && $this->format->supportsFormatting();
    }

    /**
     * Check if the format is structured (JSON, XML, etc.).
     *
     * @return bool True if the format is structured
     */
    public function isStructuredFormat(): bool
    {
        return $this->format->isStructured();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'format' => $this->format->value,
            'include_timestamps' => $this->includeTimestamps,
            'color_output' => $this->colorOutput,
            'max_line_length' => $this->maxLineLength,
            'show_progress' => $this->showProgress,
            'custom_options' => $this->customOptions,
        ];
    }

    private function validate(): void
    {
        if ($this->maxLineLength < 40) {
            throw new InvalidArgumentException('Max line length must be at least 40 characters');
        }
    }
}
