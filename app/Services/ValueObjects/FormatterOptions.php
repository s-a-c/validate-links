<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\OutputFormat;
use InvalidArgumentException;
use ValueError;

final readonly class FormatterOptions
{
    /**
     * @param  array<string, mixed>  $customOptions
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

    public function getCustomOption(string $key, mixed $default = null): mixed
    {
        return $this->customOptions[$key] ?? $default;
    }

    public function hasCustomOption(string $key): bool
    {
        return array_key_exists($key, $this->customOptions);
    }

    public function supportsColor(): bool
    {
        return $this->colorOutput && $this->format->supportsFormatting();
    }

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
