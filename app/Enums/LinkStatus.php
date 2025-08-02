<?php

declare(strict_types=1);

namespace App\Enums;

enum LinkStatus: string
{
    case VALID = 'valid';
    case BROKEN = 'broken';
    case TIMEOUT = 'timeout';
    case FORBIDDEN = 'forbidden';
    case NOT_FOUND = 'not_found';
    case INVALID_FORMAT = 'invalid_format';
    case SECURITY_VIOLATION = 'security_violation';
    case REDIRECT_LOOP = 'redirect_loop';
    case SSL_ERROR = 'ssl_error';
    case DNS_ERROR = 'dns_error';

    /**
     * Get all status values as array.
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if status indicates a problem.
     */
    public function isBroken(): bool
    {
        return $this !== self::VALID;
    }

    /**
     * Check if status indicates a temporary issue.
     */
    public function isTemporary(): bool
    {
        return match ($this) {
            self::TIMEOUT, self::DNS_ERROR => true,
            default => false
        };
    }

    /**
     * Check if status indicates a security issue.
     */
    public function isSecurityIssue(): bool
    {
        return match ($this) {
            self::SECURITY_VIOLATION, self::SSL_ERROR => true,
            default => false
        };
    }

    /**
     * Get display name for status.
     */
    public function getDisplayName(): string
    {
        return match ($this) {
            self::VALID => 'Valid',
            self::BROKEN => 'Broken',
            self::TIMEOUT => 'Timeout',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::INVALID_FORMAT => 'Invalid Format',
            self::SECURITY_VIOLATION => 'Security Violation',
            self::REDIRECT_LOOP => 'Redirect Loop',
            self::SSL_ERROR => 'SSL Error',
            self::DNS_ERROR => 'DNS Error'
        };
    }

    /**
     * Get human-readable description.
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::VALID => 'Link is valid and accessible',
            self::BROKEN => 'Link is broken or inaccessible',
            self::TIMEOUT => 'Request timed out',
            self::FORBIDDEN => 'Access forbidden (403)',
            self::NOT_FOUND => 'Resource not found (404)',
            self::INVALID_FORMAT => 'Invalid URL format',
            self::SECURITY_VIOLATION => 'Security policy violation',
            self::REDIRECT_LOOP => 'Redirect loop detected',
            self::SSL_ERROR => 'SSL/TLS certificate error',
            self::DNS_ERROR => 'DNS resolution failed'
        };
    }

    /**
     * Get severity level (1 = low, 5 = critical).
     */
    public function getSeverity(): int
    {
        return match ($this) {
            self::VALID => 0,
            self::TIMEOUT, self::DNS_ERROR => 2,
            self::NOT_FOUND, self::INVALID_FORMAT => 3,
            self::FORBIDDEN, self::REDIRECT_LOOP => 4,
            self::BROKEN, self::SECURITY_VIOLATION, self::SSL_ERROR => 5
        };
    }

    /**
     * Get recommended action.
     */
    public function getRecommendedAction(): string
    {
        return match ($this) {
            self::VALID => 'No action required',
            self::TIMEOUT => 'Retry validation or increase timeout',
            self::FORBIDDEN => 'Check access permissions or authentication',
            self::NOT_FOUND => 'Update link URL or remove broken link',
            self::INVALID_FORMAT => 'Fix URL syntax and format',
            self::SECURITY_VIOLATION => 'Review security policies and URL safety',
            self::REDIRECT_LOOP => 'Check redirect configuration',
            self::SSL_ERROR => 'Verify SSL certificate validity',
            self::DNS_ERROR => 'Check domain name and DNS configuration',
            self::BROKEN => 'Investigate and fix underlying issue'
        };
    }

    /**
     * Get console color for status display.
     */
    public function getConsoleColor(): string
    {
        return match ($this) {
            self::VALID => 'green',
            self::TIMEOUT, self::DNS_ERROR => 'yellow',
            self::NOT_FOUND, self::INVALID_FORMAT => 'magenta',
            self::FORBIDDEN, self::REDIRECT_LOOP => 'cyan',
            default => 'red'
        };
    }

    /**
     * Get status icon for console display.
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::VALID => '✅',
            self::TIMEOUT => '⏱️',
            self::FORBIDDEN => '🚫',
            self::NOT_FOUND => '❌',
            self::INVALID_FORMAT => '⚠️',
            self::SECURITY_VIOLATION => '🔒',
            self::REDIRECT_LOOP => '🔄',
            self::SSL_ERROR => '🔐',
            self::DNS_ERROR => '🌐',
            self::BROKEN => '💥'
        };
    }

    /**
     * Get status group for result organization.
     */
    public function getGroup(): string
    {
        return match ($this) {
            self::VALID => 'success',
            self::TIMEOUT, self::DNS_ERROR => 'temporary',
            self::FORBIDDEN, self::NOT_FOUND => 'client_error',
            self::INVALID_FORMAT, self::SECURITY_VIOLATION => 'format_error',
            self::REDIRECT_LOOP, self::SSL_ERROR => 'protocol_error',
            self::BROKEN => 'unknown_error'
        };
    }

    /**
     * Get formatted display text with icon and color.
     */
    public function getFormattedDisplay(): string
    {
        $color = $this->getConsoleColor();
        $icon = $this->getIcon();
        $description = $this->getDescription();

        return "<fg={$color}>{$icon} {$description}</fg>";
    }

    /**
     * Check if status should be retried automatically.
     */
    public function shouldRetry(): bool
    {
        return match ($this) {
            self::TIMEOUT, self::DNS_ERROR => true,
            default => false
        };
    }

    /**
     * Get HTTP status code equivalent (if applicable).
     */
    public function getHttpStatusCode(): ?int
    {
        return match ($this) {
            self::VALID => 200,
            self::FORBIDDEN => 403,
            self::NOT_FOUND => 404,
            self::TIMEOUT => 408,
            self::REDIRECT_LOOP => 310,
            self::SSL_ERROR => 526,
            default => null
        };
    }
}
