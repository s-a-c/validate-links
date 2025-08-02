<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\ValidationScope;
use InvalidArgumentException;
use ValueError;

final readonly class LinkExtractionResult
{
    public function __construct(
        public string $url,
        public string $text,
        public int $lineNumber,
        public ValidationScope $scope,
        public ?string $anchor = null,
        public ?string $filePath = null
    ) {
        $this->validate();
    }

    /**
     * @param  array<string, mixed>  $data
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
            filePath: isset($data['file_path']) ? (is_string($data['file_path']) ? $data['file_path'] : (is_scalar($data['file_path']) ? (string) $data['file_path'] : null)) : null
        );
    }

    public function isInternal(): bool
    {
        return $this->scope === ValidationScope::INTERNAL;
    }

    public function isExternal(): bool
    {
        return $this->scope === ValidationScope::EXTERNAL;
    }

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

    private function validate(): void
    {
        if (empty($this->url)) {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if ($this->lineNumber < 0) {
            throw new InvalidArgumentException('Line number must be non-negative');
        }
    }
}
