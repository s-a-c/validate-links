<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Exception thrown when security violations are detected.
 */
final class SecurityException extends ValidateLinksException
{
    /**
     * Create a security exception for a detected threat.
     *
     * @param  string  $url  The URL where the threat was detected
     * @param  string  $threatType  The type of threat detected
     * @param  array<string, mixed>  $context  Additional context data
     */
    public static function threatDetected(string $url, string $threatType, array $context): static
    {
        $message = "Security threat detected at {$url}: {$threatType}";

        return new self($message, 0, null, $context);
    }

    /**
     * Create a security exception for a blacklisted domain.
     *
     * @param  string  $domain  The blacklisted domain
     */
    public static function blacklistedDomain(string $domain): static
    {
        $message = "Access denied: {$domain} is on the security blacklist";
        $context = [
            'domain' => $domain,
            'threat_type' => 'blacklisted_domain',
            'risk_level' => 'high',
        ];

        return new self($message, 0, null, $context);
    }

    /**
     * Create a security exception for SSL violations.
     *
     * @param  string  $url  The URL with SSL issues
     * @param  string  $sslError  The SSL error description
     */
    public static function sslViolation(string $url, string $sslError): static
    {
        $message = "SSL security violation for {$url}: {$sslError}";
        $context = [
            'url' => $url,
            'ssl_error' => $sslError,
            'threat_type' => 'ssl_violation',
            'risk_level' => 'medium',
        ];

        return new self($message, 0, null, $context);
    }

    public function getErrorCode(): string
    {
        return 'SECURITY_VIOLATION';
    }

    public function getSeverity(): string
    {
        return 'critical';
    }
}
