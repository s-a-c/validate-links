<?php

declare(strict_types=1);

/**
 * Security Validation Custom Matchers for PEST
 * 
 * Provides domain-specific matchers for security testing,
 * including URL validation, protocol checks, and security assertions.
 */

if (!function_exists('toBeSecure')) {
    /**
     * Assert that a URL or validation result is secure
     */
    function toBeSecure(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                // Check if URL uses HTTPS
                return str_starts_with($this->value, 'https://');
            }
            
            if (is_array($this->value) && isset($this->value['secure'])) {
                return $this->value['secure'] === true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toPassSecurityCheck')) {
    /**
     * Assert that a security validation passes
     */
    function toPassSecurityCheck(): Closure
    {
        return function () {
            if (is_bool($this->value)) {
                return $this->value === true;
            }
            
            if (is_array($this->value)) {
                return ($this->value['passed'] ?? false) === true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toFailSecurityCheck')) {
    /**
     * Assert that a security validation fails
     */
    function toFailSecurityCheck(): Closure
    {
        return function () {
            if (is_bool($this->value)) {
                return $this->value === false;
            }
            
            if (is_array($this->value)) {
                return ($this->value['passed'] ?? true) === false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveSecureProtocol')) {
    /**
     * Assert that URL uses a secure protocol
     */
    function toHaveSecureProtocol(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                $secureProtocols = ['https://', 'ftps://', 'sftp://'];
                foreach ($secureProtocols as $protocol) {
                    if (str_starts_with($this->value, $protocol)) {
                        return true;
                    }
                }
                return false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveInsecureProtocol')) {
    /**
     * Assert that URL uses an insecure protocol
     */
    function toHaveInsecureProtocol(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                $insecureProtocols = ['http://', 'ftp://', 'telnet://'];
                foreach ($insecureProtocols as $protocol) {
                    if (str_starts_with($this->value, $protocol)) {
                        return true;
                    }
                }
                return false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeBlockedDomain')) {
    /**
     * Assert that domain is in blocked list
     */
    function toBeBlockedDomain(array $blockedDomains = []): Closure
    {
        return function () use ($blockedDomains) {
            if (is_string($this->value)) {
                $domain = parse_url($this->value, PHP_URL_HOST);
                if ($domain === null) {
                    return false;
                }
                
                return in_array($domain, $blockedDomains, true);
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeAllowedDomain')) {
    /**
     * Assert that domain is in allowed list
     */
    function toBeAllowedDomain(array $allowedDomains = []): Closure
    {
        return function () use ($allowedDomains) {
            if (is_string($this->value)) {
                $domain = parse_url($this->value, PHP_URL_HOST);
                if ($domain === null) {
                    return false;
                }
                
                return in_array($domain, $allowedDomains, true);
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveSuspiciousPath')) {
    /**
     * Assert that URL path contains suspicious patterns
     */
    function toHaveSuspiciousPath(array $suspiciousPatterns = []): Closure
    {
        return function () use ($suspiciousPatterns) {
            if (is_string($this->value)) {
                $path = parse_url($this->value, PHP_URL_PATH) ?? '';
                
                $defaultPatterns = [
                    '/admin',
                    '/private',
                    '/secret',
                    '/internal',
                    '/.env',
                    '/config',
                    '/backup',
                    '/dump',
                ];
                
                $patterns = array_merge($defaultPatterns, $suspiciousPatterns);
                
                foreach ($patterns as $pattern) {
                    if (str_contains($path, $pattern)) {
                        return true;
                    }
                }
                
                return false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveValidCertificate')) {
    /**
     * Assert that URL has a valid SSL certificate
     */
    function toHaveValidCertificate(): Closure
    {
        return function () {
            if (is_string($this->value) && str_starts_with($this->value, 'https://')) {
                $context = stream_context_create([
                    'ssl' => [
                        'verify_peer' => true,
                        'verify_peer_name' => true,
                    ],
                ]);
                
                $headers = @get_headers($this->value, 1, $context);
                return $headers !== false;
            }
            
            if (is_array($this->value)) {
                return ($this->value['valid_certificate'] ?? false) === true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveExpiredCertificate')) {
    /**
     * Assert that URL has an expired SSL certificate
     */
    function toHaveExpiredCertificate(): Closure
    {
        return function () {
            if (is_array($this->value)) {
                return ($this->value['certificate_expired'] ?? false) === true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toContainMaliciousContent')) {
    /**
     * Assert that content contains malicious patterns
     */
    function toContainMaliciousContent(array $maliciousPatterns = []): Closure
    {
        return function () use ($maliciousPatterns) {
            if (is_string($this->value)) {
                $defaultPatterns = [
                    '<script',
                    'javascript:',
                    'eval(',
                    'document.cookie',
                    'window.location',
                    'onclick=',
                    'onerror=',
                    'onload=',
                ];
                
                $patterns = array_merge($defaultPatterns, $maliciousPatterns);
                
                foreach ($patterns as $pattern) {
                    if (stripos($this->value, $pattern) !== false) {
                        return true;
                    }
                }
                
                return false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveSecurityHeaders')) {
    /**
     * Assert that response has required security headers
     */
    function toHaveSecurityHeaders(array $requiredHeaders = []): Closure
    {
        return function () use ($requiredHeaders) {
            if (is_array($this->value)) {
                $defaultHeaders = [
                    'X-Content-Type-Options',
                    'X-Frame-Options',
                    'X-XSS-Protection',
                    'Strict-Transport-Security',
                    'Content-Security-Policy',
                ];
                
                $headers = array_merge($defaultHeaders, $requiredHeaders);
                
                foreach ($headers as $header) {
                    if (!isset($this->value[$header])) {
                        return false;
                    }
                }
                
                return true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveStrictTransportSecurity')) {
    /**
     * Assert that response has HSTS header
     */
    function toHaveStrictTransportSecurity(): Closure
    {
        return function () {
            if (is_array($this->value)) {
                return isset($this->value['Strict-Transport-Security']);
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveContentSecurityPolicy')) {
    /**
     * Assert that response has CSP header
     */
    function toHaveContentSecurityPolicy(): Closure
    {
        return function () {
            if (is_array($this->value)) {
                return isset($this->value['Content-Security-Policy']) || 
                       isset($this->value['Content-Security-Policy-Report-Only']);
            }
            
            return false;
        };
    }
}

if (!function_exists('toPreventClickjacking')) {
    /**
     * Assert that response prevents clickjacking
     */
    function toPreventClickjacking(): Closure
    {
        return function () {
            if (is_array($this->value)) {
                $xFrameOptions = $this->value['X-Frame-Options'] ?? '';
                return in_array(strtoupper($xFrameOptions), ['DENY', 'SAMEORIGIN'], true);
            }
            
            return false;
        };
    }
}

if (!function_exists('toPreventMimeSniffing')) {
    /**
     * Assert that response prevents MIME sniffing
     */
    function toPreventMimeSniffing(): Closure
    {
        return function () {
            if (is_array($this->value)) {
                $contentTypeOptions = $this->value['X-Content-Type-Options'] ?? '';
                return strtolower($contentTypeOptions) === 'nosniff';
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveXSSProtection')) {
    /**
     * Assert that response has XSS protection enabled
     */
    function toHaveXSSProtection(): Closure
    {
        return function () {
            if (is_array($this->value)) {
                $xssProtection = $this->value['X-XSS-Protection'] ?? '';
                return str_starts_with($xssProtection, '1');
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeVulnerableToInjection')) {
    /**
     * Assert that input is vulnerable to injection attacks
     */
    function toBeVulnerableToInjection(array $injectionPatterns = []): Closure
    {
        return function () use ($injectionPatterns) {
            if (is_string($this->value)) {
                $defaultPatterns = [
                    "' OR '1'='1",
                    '" OR "1"="1',
                    'UNION SELECT',
                    'DROP TABLE',
                    'INSERT INTO',
                    'UPDATE SET',
                    'DELETE FROM',
                    '<script>',
                    'javascript:',
                    '${',
                    '#{',
                ];
                
                $patterns = array_merge($defaultPatterns, $injectionPatterns);
                
                foreach ($patterns as $pattern) {
                    if (stripos($this->value, $pattern) !== false) {
                        return true;
                    }
                }
                
                return false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveSecureRandomness')) {
    /**
     * Assert that value has sufficient randomness for security
     */
    function toHaveSecureRandomness(int $minEntropy = 128): Closure
    {
        return function () use ($minEntropy) {
            if (is_string($this->value)) {
                // Simple entropy calculation
                $chars = str_split($this->value);
                $charCounts = array_count_values($chars);
                $length = strlen($this->value);
                
                $entropy = 0;
                foreach ($charCounts as $count) {
                    $probability = $count / $length;
                    $entropy -= $probability * log($probability, 2);
                }
                
                $totalEntropy = $entropy * $length;
                return $totalEntropy >= $minEntropy;
            }
            
            return false;
        };
    }
}

if (!function_exists('toPassSecurityScan')) {
    /**
     * Assert that security scan results pass all checks
     */
    function toPassSecurityScan(): Closure
    {
        return function () {
            if (is_array($this->value)) {
                $requiredChecks = [
                    'protocol_secure',
                    'domain_allowed',
                    'path_safe',
                    'headers_present',
                    'content_safe',
                ];
                
                foreach ($requiredChecks as $check) {
                    if (($this->value[$check] ?? false) !== true) {
                        return false;
                    }
                }
                
                return true;
            }
            
            return false;
        };
    }
}