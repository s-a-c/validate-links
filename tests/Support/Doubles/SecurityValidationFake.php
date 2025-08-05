<?php

declare(strict_types=1);

namespace Tests\Support\Doubles;

use App\Services\Contracts\SecurityValidationInterface;

/**
 * Deterministic fake for SecurityValidationInterface.
 * Defaults to permissive behavior suitable for unit tests.
 */
final class SecurityValidationFake implements SecurityValidationInterface
{
    /** @var array<string, bool> */
    private array $urlAllowlist = [];

    /** @var array<string, bool> */
    private array $pathAllowlist = [];

    public function allowUrl(string $url, bool $allow = true): void
    {
        $this->urlAllowlist[$url] = $allow;
    }

    public function allowPath(string $path, bool $allow = true): void
    {
        $this->pathAllowlist[$path] = $allow;
    }

    public function validateUrl(string $url): bool
    {
        if (array_key_exists($url, $this->urlAllowlist)) {
            return $this->urlAllowlist[$url];
        }

        // Deterministic defaults for tests
        if ($url === '' || str_contains($url, "\0")) {
            return false;
        }

        // Allow http/https and localhost-like hosts in tests
        if (preg_match('#^(https?://|/#)#i', $url) === 1) {
            return true;
        }

        return true;
    }

    public function validatePath(string $path): bool
    {
        if (array_key_exists($path, $this->pathAllowlist)) {
            return $this->pathAllowlist[$path];
        }

        if ($path === '' || str_contains($path, "\0")) {
            return false;
        }

        // For unit tests, allow relative paths and temp dirs; disallow /etc, /usr etc.
        $blocked = ['/etc/', '/usr/', '/bin/', '/sbin/', '/root/'];
        foreach ($blocked as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return false;
            }
        }

        return true;
    }

    public function isDomainBlocked(string $domain): bool
    {
        return false;
    }

    public function isProtocolAllowed(string $protocol): bool
    {
        return in_array(strtolower($protocol), ['http', 'https', ''], true);
    }

    public function validateFileSize(string $filePath): bool
    {
        return true;
    }

    public function getSecurityConfig(): array
    {
        return [
            'blocked_domains' => [],
            'allowed_protocols' => ['http', 'https'],
            'max_file_size' => 50 * 1024 * 1024,
            'blocked_paths' => [],
        ];
    }
}