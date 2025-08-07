<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\ValidationScope;
use InvalidArgumentException;
use ValueError;

/**
 * Value object representing a link extracted from a document for validation.
 *
 * This class encapsulates all information about a discovered link including
 * its location, scope, and associated metadata for validation processing.
 */
final readonly class LinkExtractionResult
{
    /**
     * @param string $url The extracted URL to be validated
     * @param string $text The link text or description
     * @param int $lineNumber Line number where the link was found
     * @param ValidationScope $scope Validation scope (internal, external, etc.)
     * @param string|null $anchor Optional anchor/fragment identifier
     * @param string|null $filePath Optional path to the source file
     */
    public function __construct(
        public string $url,
        public string $text,
        public int $lineNumber,
        public ValidationScope $scope,
        public ?string $anchor = null,
        public ?string $filePath = null,
    ) {
        $this->validate();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): self
    {
        $url = $data['url'] ?? '';
        if (! is_string($url) || empty($url)) {
            throw new InvalidArgumentException('URL must be a non-empty string');
        }

        $text = $data['text'] ?? '';
        if (! is_string($text)) {
            throw new InvalidArgumentException('Text must be a string');
        }

        $lineNumber = is_numeric($data['line'] ?? null) ? (int) $data['line'] : 0;

        $scope = $data['scope'] ?? ValidationScope::ALL;
        if (! $scope instanceof ValidationScope) {
            try {
                $scope = ValidationScope::from(is_string($scope) ? $scope : '');
            } catch (ValueError) {
                $scope = ValidationScope::ALL;
            }
        }

        return new self(
            url: $url,
            text: $text,
            lineNumber: $lineNumber,
            scope: $scope,
            anchor: isset($data['anchor']) ? (is_string($data['anchor']) ? $data['anchor'] : (is_scalar($data['anchor']) ? (string) $data['anchor'] : null)) : null,
            filePath: isset($data['file_path']) ? (is_string($data['file_path']) ? $data['file_path'] : (is_scalar($data['file_path']) ? (string) $data['file_path'] : null)) : null,
        );
    }

    /**
     * Check if this is an internal link.
     *
     * @return bool True if the link scope is internal
     */
    public function isInternal(): bool
    {
        return $this->scope === ValidationScope::INTERNAL;
    }

    /**
     * Check if this is an external link.
     *
     * @return bool True if the link scope is external
     */
    public function isExternal(): bool
    {
        return $this->scope === ValidationScope::EXTERNAL;
    }

    /**
     * Check if this link has an anchor/fragment identifier.
     *
     * @return bool True if an anchor is present
     */
    public function hasAnchor(): bool
    {
        return $this->anchor !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'text' => $this->text,
            'line' => $this->lineNumber,
            'scope' => $this->scope->value,
            'anchor' => $this->anchor,
            'file_path' => $this->filePath,
        ];
    }

    /**
     * Validate the link extraction result data.
     *
     * @throws InvalidArgumentException If URL is empty or line number is negative
     */
    private function validate(): void
    {
        if ($this->url === '' || $this->url === '0') {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if ($this->lineNumber < 0) {
            throw new InvalidArgumentException('Line number must be non-negative');
        }
    }
}
