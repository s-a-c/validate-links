<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum for HTTP request methods.
 *
 * This enum provides type-safe constants for HTTP methods
 * used in link validation and external requests.
 */
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
    case TRACE = 'TRACE';
    case CONNECT = 'CONNECT';

    /**
     * Get all HTTP method values as array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if method is safe (doesn't modify server state).
     */
    public function isSafe(): bool
    {
        return match ($this) {
            self::GET, self::HEAD, self::OPTIONS, self::TRACE => true,
            default => false,
        };
    }

    /**
     * Check if method is idempotent.
     */
    public function isIdempotent(): bool
    {
        return match ($this) {
            self::GET, self::HEAD, self::PUT, self::DELETE, self::OPTIONS, self::TRACE => true,
            default => false,
        };
    }

    /**
     * Check if method typically has a request body.
     */
    public function hasRequestBody(): bool
    {
        return match ($this) {
            self::POST, self::PUT, self::PATCH => true,
            default => false,
        };
    }

    /**
     * Get display name for the HTTP method.
     */
    public function getDisplayName(): string
    {
        return $this->value;
    }

    /**
     * Get description of the HTTP method.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::GET => 'Retrieve data from server',
            self::POST => 'Submit data to server',
            self::PUT => 'Update or create resource',
            self::PATCH => 'Partially update resource',
            self::DELETE => 'Delete resource',
            self::HEAD => 'Retrieve headers only',
            self::OPTIONS => 'Get allowed methods',
            self::TRACE => 'Perform message loop-back test',
            self::CONNECT => 'Establish tunnel to server',
        };
    }
}
