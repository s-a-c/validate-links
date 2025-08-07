<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\ValueObjects\SecurityConfiguration;

interface SecurityValidationInterface
{
    /**
     * Validate if a URL is safe to access.
     */
    public function validateUrl(string $url): bool;

    /**
     * Validate if a file path is safe to access.
     */
    public function validatePath(string $path): bool;

    /**
     * Check if domain is blocked.
     */
    public function isDomainBlocked(string $domain): bool;

    /**
     * Check if protocol is allowed.
     */
    public function isProtocolAllowed(string $protocol): bool;

    /**
     * Validate file size limits.
     */
    public function validateFileSize(string $filePath): bool;

    /**
     * Get security configuration.
     */
    public function getSecurityConfig(): SecurityConfiguration;
}
