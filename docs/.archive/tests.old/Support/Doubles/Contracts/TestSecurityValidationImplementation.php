<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Contracts;

use App\Services\Contracts\SecurityValidationInterface;

/**
 * Test implementation of SecurityValidationInterface for testing purposes.
 *
 * Provides predictable responses for testing security validation functionality.
 */
final class TestSecurityValidationImplementation implements SecurityValidationInterface
{
    public function validateUrl(string $url): bool
    {
        return true; // Always safe for testing
    }

    public function validatePath(string $path): bool
    {
        return true; // Always safe for testing
    }

    public function isDomainBlocked(string $domain): bool
    {
        return false; // Never blocked for testing
    }

    public function isProtocolAllowed(string $protocol): bool
    {
        return true; // Always allowed for testing
    }

    public function validateFileSize(string $filePath): bool
    {
        return true; // Always valid size for testing
    }

    public function getSecurityConfig(): array
    {
        return [
            'max_file_size' => 1024 * 1024,
            'allowed_protocols' => ['http', 'https'],
            'blocked_domains' => [],
        ];
    }
}
