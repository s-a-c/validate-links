<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing security configuration settings.
 *
 * This class replaces generic array security configuration with strongly typed properties
 * for better type safety and validation in security operations.
 */
final readonly class SecurityConfiguration
{
    /**
     * @param array<string> $blockedDomains List of domains to block
     * @param array<string> $allowedProtocols List of allowed URL protocols
     * @param int $maxFileSize Maximum file size in bytes
     * @param array<string> $blockedPaths List of path patterns to block
     * @param bool $allowPrivateIps Whether to allow private IP addresses
     * @param bool $allowLocalhostUrls Whether to allow localhost URLs
     * @param bool $strictSSLVerification Whether to enforce strict SSL verification
     * @param array<string> $trustedCertificates List of trusted certificate fingerprints
     * @param int $maxRedirectDepth Maximum number of redirects to follow
     * @param bool $blockSuspiciousExtensions Whether to block suspicious file extensions
     * @param array<string> $allowedFileExtensions List of allowed file extensions
     * @param bool $enableRateLimiting Whether to enable rate limiting
     * @param int $requestsPerMinute Maximum requests per minute per domain
     */
    public function __construct(
        public array $blockedDomains = [],
        public array $allowedProtocols = ['http', 'https'],
        public int $maxFileSize = 52428800, // 50MB
        public array $blockedPaths = [],
        public bool $allowPrivateIps = false,
        public bool $allowLocalhostUrls = true,
        public bool $strictSSLVerification = true,
        public array $trustedCertificates = [],
        public int $maxRedirectDepth = 5,
        public bool $blockSuspiciousExtensions = true,
        public array $allowedFileExtensions = [],
        public bool $enableRateLimiting = false,
        public int $requestsPerMinute = 60,
    ) {
        $this->validate();
    }

    /**
     * Create SecurityConfiguration from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $blockedDomains = isset($data['blocked_domains']) && is_array($data['blocked_domains'])
            ? array_filter($data['blocked_domains'], 'is_string')
            : [];

        $allowedProtocols = isset($data['allowed_protocols']) && is_array($data['allowed_protocols'])
            ? array_filter($data['allowed_protocols'], 'is_string')
            : ['http', 'https'];

        $maxFileSize = isset($data['max_file_size']) && is_numeric($data['max_file_size'])
            ? (int) $data['max_file_size']
            : 52428800;

        $blockedPaths = isset($data['blocked_paths']) && is_array($data['blocked_paths'])
            ? array_filter($data['blocked_paths'], 'is_string')
            : [];

        $allowPrivateIps = isset($data['allow_private_ips'])
            ? (bool) $data['allow_private_ips']
            : false;

        $allowLocalhostUrls = isset($data['allow_localhost_urls'])
            ? (bool) $data['allow_localhost_urls']
            : true;

        $strictSSLVerification = isset($data['strict_ssl_verification'])
            ? (bool) $data['strict_ssl_verification']
            : true;

        $trustedCertificates = isset($data['trusted_certificates']) && is_array($data['trusted_certificates'])
            ? array_filter($data['trusted_certificates'], 'is_string')
            : [];

        $maxRedirectDepth = isset($data['max_redirect_depth']) && is_numeric($data['max_redirect_depth'])
            ? (int) $data['max_redirect_depth']
            : 5;

        $blockSuspiciousExtensions = isset($data['block_suspicious_extensions'])
            ? (bool) $data['block_suspicious_extensions']
            : true;

        $allowedFileExtensions = isset($data['allowed_file_extensions']) && is_array($data['allowed_file_extensions'])
            ? array_filter($data['allowed_file_extensions'], 'is_string')
            : [];

        $enableRateLimiting = isset($data['enable_rate_limiting'])
            ? (bool) $data['enable_rate_limiting']
            : false;

        $requestsPerMinute = isset($data['requests_per_minute']) && is_numeric($data['requests_per_minute'])
            ? (int) $data['requests_per_minute']
            : 60;

        return new self(
            blockedDomains: $blockedDomains,
            allowedProtocols: $allowedProtocols,
            maxFileSize: $maxFileSize,
            blockedPaths: $blockedPaths,
            allowPrivateIps: $allowPrivateIps,
            allowLocalhostUrls: $allowLocalhostUrls,
            strictSSLVerification: $strictSSLVerification,
            trustedCertificates: $trustedCertificates,
            maxRedirectDepth: $maxRedirectDepth,
            blockSuspiciousExtensions: $blockSuspiciousExtensions,
            allowedFileExtensions: $allowedFileExtensions,
            enableRateLimiting: $enableRateLimiting,
            requestsPerMinute: $requestsPerMinute,
        );
    }

    /**
     * Create default security configuration.
     */
    public static function defaults(): self
    {
        return new self(
            blockedDomains: [
                'malware.example.com',
                'phishing.example.com',
            ],
            allowedProtocols: ['http', 'https'],
            maxFileSize: 52428800, // 50MB
            blockedPaths: [
                '/\.git/',
                '/\.env',
                '/config/',
                '/admin/',
            ],
            allowPrivateIps: false,
            allowLocalhostUrls: true,
            strictSSLVerification: true,
            maxRedirectDepth: 5,
            blockSuspiciousExtensions: true,
            allowedFileExtensions: [
                'html', 'htm', 'md', 'txt', 'json', 'xml',
                'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif',
            ],
            enableRateLimiting: true,
            requestsPerMinute: 60,
        );
    }

    /**
     * Create permissive configuration for development.
     */
    public static function development(): self
    {
        return new self(
            blockedDomains: [],
            allowedProtocols: ['http', 'https', 'file'],
            maxFileSize: 104857600, // 100MB
            blockedPaths: [],
            allowPrivateIps: true,
            allowLocalhostUrls: true,
            strictSSLVerification: false,
            maxRedirectDepth: 10,
            blockSuspiciousExtensions: false,
            allowedFileExtensions: [], // Allow all
            enableRateLimiting: false,
            requestsPerMinute: 1000,
        );
    }

    /**
     * Create strict configuration for production.
     */
    public static function production(): self
    {
        return new self(
            blockedDomains: [
                'malware.example.com',
                'phishing.example.com',
                'suspicious.example.com',
            ],
            allowedProtocols: ['https'], // HTTPS only
            maxFileSize: 10485760, // 10MB
            blockedPaths: [
                '/\.git/',
                '/\.env',
                '/config/',
                '/admin/',
                '/private/',
                '/internal/',
            ],
            allowPrivateIps: false,
            allowLocalhostUrls: false,
            strictSSLVerification: true,
            maxRedirectDepth: 3,
            blockSuspiciousExtensions: true,
            allowedFileExtensions: [
                'html', 'htm', 'md', 'txt', 'json',
                'pdf', 'jpg', 'jpeg', 'png',
            ],
            enableRateLimiting: true,
            requestsPerMinute: 30,
        );
    }

    /**
     * Check if a domain is blocked.
     */
    public function isDomainBlocked(string $domain): bool
    {
        $normalizedDomain = mb_strtolower($domain);

        foreach ($this->blockedDomains as $blockedDomain) {
            if (mb_strtolower($blockedDomain) === $normalizedDomain) {
                return true;
            }

            // Check for wildcard matches
            if (str_starts_with($blockedDomain, '*.')) {
                $pattern = mb_substr($blockedDomain, 2);
                if (str_ends_with($normalizedDomain, mb_strtolower($pattern))) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a protocol is allowed.
     */
    public function isProtocolAllowed(string $protocol): bool
    {
        return in_array(mb_strtolower($protocol), array_map('strtolower', $this->allowedProtocols), true);
    }

    /**
     * Check if a path is blocked.
     */
    public function isPathBlocked(string $path): bool
    {
        foreach ($this->blockedPaths as $blockedPath) {
            if (preg_match($blockedPath, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a file extension is allowed.
     */
    public function isFileExtensionAllowed(string $extension): bool
    {
        if ($this->allowedFileExtensions === []) {
            return true; // No restrictions
        }

        $normalizedExtension = mb_strtolower(mb_ltrim($extension, '.'));
        $normalizedAllowed = array_map('strtolower', $this->allowedFileExtensions);

        return in_array($normalizedExtension, $normalizedAllowed, true);
    }

    /**
     * Check if a file extension is suspicious.
     */
    public function isSuspiciousExtension(string $extension): bool
    {
        if (! $this->blockSuspiciousExtensions) {
            return false;
        }

        $suspiciousExtensions = [
            'exe', 'bat', 'cmd', 'com', 'pif', 'scr', 'vbs', 'js',
            'jar', 'app', 'deb', 'pkg', 'dmg', 'rpm', 'msi',
            'php', 'asp', 'jsp', 'cgi', 'pl', 'py', 'rb',
        ];

        $normalizedExtension = mb_strtolower(mb_ltrim($extension, '.'));

        return in_array($normalizedExtension, $suspiciousExtensions, true);
    }

    /**
     * Get maximum file size in human-readable format.
     */
    public function getMaxFileSizeFormatted(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->maxFileSize;
        $unitIndex = 0;

        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }

        return round($size, 2) . ' ' . $units[$unitIndex];
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'blocked_domains' => $this->blockedDomains,
            'allowed_protocols' => $this->allowedProtocols,
            'max_file_size' => $this->maxFileSize,
            'blocked_paths' => $this->blockedPaths,
            'allow_private_ips' => $this->allowPrivateIps,
            'allow_localhost_urls' => $this->allowLocalhostUrls,
            'strict_ssl_verification' => $this->strictSSLVerification,
            'trusted_certificates' => $this->trustedCertificates,
            'max_redirect_depth' => $this->maxRedirectDepth,
            'block_suspicious_extensions' => $this->blockSuspiciousExtensions,
            'allowed_file_extensions' => $this->allowedFileExtensions,
            'enable_rate_limiting' => $this->enableRateLimiting,
            'requests_per_minute' => $this->requestsPerMinute,
        ];
    }

    /**
     * Validate the security configuration values.
     */
    private function validate(): void
    {
        if ($this->maxFileSize < 0) {
            throw new InvalidArgumentException('Max file size must be non-negative');
        }

        if ($this->maxRedirectDepth < 0) {
            throw new InvalidArgumentException('Max redirect depth must be non-negative');
        }

        if ($this->requestsPerMinute < 1) {
            throw new InvalidArgumentException('Requests per minute must be positive');
        }

        // Validate that blocked paths are valid regex patterns
        foreach ($this->blockedPaths as $path) {
            if (@preg_match($path, '') === false) {
                throw new InvalidArgumentException("Invalid regex pattern in blocked paths: {$path}");
            }
        }

        // Validate protocols
        foreach ($this->allowedProtocols as $protocol) {
            if (! preg_match('/^[a-z][a-z0-9+.-]*$/', $protocol)) {
                throw new InvalidArgumentException("Invalid protocol format: {$protocol}");
            }
        }
    }
}
