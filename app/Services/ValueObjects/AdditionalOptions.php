<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing additional configuration options.
 *
 * This class replaces generic array additional options with strongly typed properties
 * for better type safety and validation.
 */
final readonly class AdditionalOptions
{
    /**
     * @param int|null $maxRetries Maximum number of retry attempts for failed validations
     * @param int|null $batchSize Number of links to process in each batch
     * @param string|null $userAgent Custom User-Agent string for HTTP requests
     * @param bool $enableLogging Whether to enable detailed logging
     * @param int|null $connectionTimeout Connection timeout in seconds
     * @param int|null $readTimeout Read timeout in seconds
     * @param bool $verifySSL Whether to verify SSL certificates
     * @param array<string> $allowedMimeTypes Allowed MIME types for file validation
     * @param array<string> $excludePatterns URL patterns to exclude from validation
     * @param bool $followSymlinks Whether to follow symbolic links
     */
    public function __construct(
        public ?int $maxRetries = null,
        public ?int $batchSize = null,
        public ?string $userAgent = null,
        public bool $enableLogging = false,
        public ?int $connectionTimeout = null,
        public ?int $readTimeout = null,
        public bool $verifySSL = true,
        public array $allowedMimeTypes = [],
        public array $excludePatterns = [],
        public bool $followSymlinks = false,
    ) {
        $this->validate();
    }

    /**
     * Create AdditionalOptions from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $maxRetries = isset($data['max_retries']) && is_numeric($data['max_retries'])
            ? (int) $data['max_retries']
            : null;

        $batchSize = isset($data['batch_size']) && is_numeric($data['batch_size'])
            ? (int) $data['batch_size']
            : null;

        $userAgent = isset($data['user_agent']) && is_string($data['user_agent'])
            ? $data['user_agent']
            : null;

        $enableLogging = isset($data['enable_logging'])
            ? (bool) $data['enable_logging']
            : false;

        $connectionTimeout = isset($data['connection_timeout']) && is_numeric($data['connection_timeout'])
            ? (int) $data['connection_timeout']
            : null;

        $readTimeout = isset($data['read_timeout']) && is_numeric($data['read_timeout'])
            ? (int) $data['read_timeout']
            : null;

        $verifySSL = isset($data['verify_ssl'])
            ? (bool) $data['verify_ssl']
            : true;

        $allowedMimeTypes = isset($data['allowed_mime_types']) && is_array($data['allowed_mime_types'])
            ? array_filter($data['allowed_mime_types'], 'is_string')
            : [];

        $excludePatterns = isset($data['exclude_patterns']) && is_array($data['exclude_patterns'])
            ? array_filter($data['exclude_patterns'], 'is_string')
            : [];

        $followSymlinks = isset($data['follow_symlinks'])
            ? (bool) $data['follow_symlinks']
            : false;

        return new self(
            maxRetries: $maxRetries,
            batchSize: $batchSize,
            userAgent: $userAgent,
            enableLogging: $enableLogging,
            connectionTimeout: $connectionTimeout,
            readTimeout: $readTimeout,
            verifySSL: $verifySSL,
            allowedMimeTypes: $allowedMimeTypes,
            excludePatterns: $excludePatterns,
            followSymlinks: $followSymlinks,
        );
    }

    /**
     * Create default options.
     */
    public static function defaults(): self
    {
        return new self(
            maxRetries: 3,
            batchSize: 10,
            userAgent: 'validate-links/1.0',
            enableLogging: false,
            connectionTimeout: 30,
            readTimeout: 30,
            verifySSL: true,
            allowedMimeTypes: ['text/html', 'text/plain', 'application/json'],
            excludePatterns: [],
            followSymlinks: false,
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
     * Get effective max retries (with fallback).
     */
    public function getEffectiveMaxRetries(): int
    {
        return $this->maxRetries ?? 3;
    }

    /**
     * Get effective batch size (with fallback).
     */
    public function getEffectiveBatchSize(): int
    {
        return $this->batchSize ?? 10;
    }

    /**
     * Get effective user agent (with fallback).
     */
    public function getEffectiveUserAgent(): string
    {
        return $this->userAgent ?? 'validate-links/1.0';
    }

    /**
     * Get effective connection timeout (with fallback).
     */
    public function getEffectiveConnectionTimeout(): int
    {
        return $this->connectionTimeout ?? 30;
    }

    /**
     * Get effective read timeout (with fallback).
     */
    public function getEffectiveReadTimeout(): int
    {
        return $this->readTimeout ?? 30;
    }

    /**
     * Check if a MIME type is allowed.
     */
    public function isMimeTypeAllowed(string $mimeType): bool
    {
        if ($this->allowedMimeTypes === []) {
            return true; // No restrictions
        }

        return in_array($mimeType, $this->allowedMimeTypes, true);
    }

    /**
     * Check if a URL matches any exclude pattern.
     */
    public function isUrlExcluded(string $url): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'max_retries' => $this->maxRetries,
            'batch_size' => $this->batchSize,
            'user_agent' => $this->userAgent,
            'enable_logging' => $this->enableLogging,
            'connection_timeout' => $this->connectionTimeout,
            'read_timeout' => $this->readTimeout,
            'verify_ssl' => $this->verifySSL,
            'allowed_mime_types' => $this->allowedMimeTypes,
            'exclude_patterns' => $this->excludePatterns,
            'follow_symlinks' => $this->followSymlinks,
        ];
    }

    /**
     * Validate the options values.
     */
    private function validate(): void
    {
        if ($this->maxRetries !== null && $this->maxRetries < 0) {
            throw new InvalidArgumentException('Max retries must be non-negative');
        }

        if ($this->batchSize !== null && $this->batchSize < 1) {
            throw new InvalidArgumentException('Batch size must be positive');
        }

        if ($this->connectionTimeout !== null && $this->connectionTimeout < 1) {
            throw new InvalidArgumentException('Connection timeout must be positive');
        }

        if ($this->readTimeout !== null && $this->readTimeout < 1) {
            throw new InvalidArgumentException('Read timeout must be positive');
        }

        // Validate exclude patterns are valid regex
        foreach ($this->excludePatterns as $pattern) {
            if (@preg_match($pattern, '') === false) {
                throw new InvalidArgumentException("Invalid regex pattern: {$pattern}");
            }
        }
    }
}
