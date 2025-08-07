<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing custom formatter options with type safety.
 *
 * This class provides type-safe access to custom formatting options
 * that can be used by different output formatters.
 */
final readonly class CustomFormatterOptions
{
    /**
     * @param int|null $indentSize Number of spaces for indentation (JSON, XML)
     * @param bool $prettyPrint Whether to format output for readability
     * @param string|null $dateFormat Custom date format string
     * @param bool $includeMetadata Whether to include metadata in output
     * @param int|null $maxDepth Maximum nesting depth for structured formats
     * @param string|null $encoding Character encoding for output
     * @param bool $escapeHtml Whether to escape HTML characters
     * @param string|null $lineEnding Line ending style (\n, \r\n, etc.)
     * @param bool $sortKeys Whether to sort object keys in structured formats
     * @param array<string, string> $customHeaders Additional headers for structured formats
     */
    public function __construct(
        public ?int $indentSize = null,
        public bool $prettyPrint = true,
        public ?string $dateFormat = null,
        public bool $includeMetadata = true,
        public ?int $maxDepth = null,
        public ?string $encoding = null,
        public bool $escapeHtml = true,
        public ?string $lineEnding = null,
        public bool $sortKeys = false,
        public array $customHeaders = [],
    ) {
        $this->validate();
    }

    /**
     * Create instance from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): self
    {
        $customHeaders = $data['custom_headers'] ?? [];
        if (! is_array($customHeaders)) {
            $customHeaders = [];
        }

        // Ensure custom headers are string => string
        $validHeaders = [];
        foreach ($customHeaders as $key => $value) {
            if (is_string($key) && (is_string($value) || is_numeric($value))) {
                $validHeaders[$key] = (string) $value;
            }
        }

        return new self(
            indentSize: isset($data['indent_size']) && is_numeric($data['indent_size']) ? (int) $data['indent_size'] : null,
            prettyPrint: (bool) ($data['pretty_print'] ?? true),
            dateFormat: isset($data['date_format']) && is_string($data['date_format']) ? $data['date_format'] : null,
            includeMetadata: (bool) ($data['include_metadata'] ?? true),
            maxDepth: isset($data['max_depth']) && is_numeric($data['max_depth']) ? (int) $data['max_depth'] : null,
            encoding: isset($data['encoding']) && is_string($data['encoding']) ? $data['encoding'] : null,
            escapeHtml: (bool) ($data['escape_html'] ?? true),
            lineEnding: isset($data['line_ending']) && is_string($data['line_ending']) ? $data['line_ending'] : null,
            sortKeys: (bool) ($data['sort_keys'] ?? false),
            customHeaders: $validHeaders,
        );
    }

    /**
     * Create default options for console output.
     */
    public static function forConsole(): self
    {
        return new self(
            prettyPrint: true,
            includeMetadata: false,
            escapeHtml: false,
        );
    }

    /**
     * Create default options for JSON output.
     */
    public static function forJson(): self
    {
        return new self(
            indentSize: 2,
            prettyPrint: true,
            sortKeys: true,
            escapeHtml: false,
        );
    }

    /**
     * Create default options for XML output.
     */
    public static function forXml(): self
    {
        return new self(
            indentSize: 2,
            prettyPrint: true,
            escapeHtml: true,
            encoding: 'UTF-8',
        );
    }

    /**
     * Create default options for HTML output.
     */
    public static function forHtml(): self
    {
        return new self(
            prettyPrint: true,
            escapeHtml: true,
            includeMetadata: true,
            encoding: 'UTF-8',
        );
    }

    /**
     * Create a new instance with different indent size.
     */
    public function withIndentSize(int $indentSize): self
    {
        return new self(
            indentSize: $indentSize,
            prettyPrint: $this->prettyPrint,
            dateFormat: $this->dateFormat,
            includeMetadata: $this->includeMetadata,
            maxDepth: $this->maxDepth,
            encoding: $this->encoding,
            escapeHtml: $this->escapeHtml,
            lineEnding: $this->lineEnding,
            sortKeys: $this->sortKeys,
            customHeaders: $this->customHeaders,
        );
    }

    /**
     * Create a new instance with additional custom header.
     */
    public function withCustomHeader(string $key, string $value): self
    {
        $headers = $this->customHeaders;
        $headers[$key] = $value;

        return new self(
            indentSize: $this->indentSize,
            prettyPrint: $this->prettyPrint,
            dateFormat: $this->dateFormat,
            includeMetadata: $this->includeMetadata,
            maxDepth: $this->maxDepth,
            encoding: $this->encoding,
            escapeHtml: $this->escapeHtml,
            lineEnding: $this->lineEnding,
            sortKeys: $this->sortKeys,
            customHeaders: $headers,
        );
    }

    /**
     * Get effective indent size (with fallback).
     */
    public function getIndentSize(): int
    {
        return $this->indentSize ?? 2;
    }

    /**
     * Get effective encoding (with fallback).
     */
    public function getEncoding(): string
    {
        return $this->encoding ?? 'UTF-8';
    }

    /**
     * Get effective line ending (with fallback).
     */
    public function getLineEnding(): string
    {
        return $this->lineEnding ?? "\n";
    }

    /**
     * Check if a custom header exists.
     */
    public function hasCustomHeader(string $key): bool
    {
        return array_key_exists($key, $this->customHeaders);
    }

    /**
     * Get a custom header value.
     */
    public function getCustomHeader(string $key): ?string
    {
        return $this->customHeaders[$key] ?? null;
    }

    /**
     * Convert to array representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'indent_size' => $this->indentSize,
            'pretty_print' => $this->prettyPrint,
            'date_format' => $this->dateFormat,
            'include_metadata' => $this->includeMetadata,
            'max_depth' => $this->maxDepth,
            'encoding' => $this->encoding,
            'escape_html' => $this->escapeHtml,
            'line_ending' => $this->lineEnding,
            'sort_keys' => $this->sortKeys,
            'custom_headers' => $this->customHeaders,
        ];
    }

    private function validate(): void
    {
        if ($this->indentSize !== null && $this->indentSize < 0) {
            throw new InvalidArgumentException('Indent size must be non-negative');
        }

        if ($this->maxDepth !== null && $this->maxDepth < 1) {
            throw new InvalidArgumentException('Max depth must be at least 1');
        }

        if ($this->dateFormat !== null && $this->dateFormat === '') {
            throw new InvalidArgumentException('Date format cannot be empty string');
        }

        if ($this->encoding !== null && $this->encoding === '') {
            throw new InvalidArgumentException('Encoding cannot be empty string');
        }
    }
}
