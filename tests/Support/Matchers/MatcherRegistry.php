<?php

declare(strict_types=1);

namespace Tests\Support\Matchers;

/**
 * Matcher Registry for PEST Custom Matchers
 * 
 * Centralizes registration and management of all custom matchers,
 * providing a single point for matcher discovery and loading.
 */
class MatcherRegistry
{
    /**
     * Available matcher categories
     */
    public const CATEGORIES = [
        'link_validation' => 'Link Validation Matchers',
        'performance' => 'Performance Testing Matchers',
        'security' => 'Security Validation Matchers',
        'reporting' => 'Report Generation Matchers',
    ];

    /**
     * Matcher files to load
     */
    private static array $matcherFiles = [
        'LinkValidationMatchers.php',
        'PerformanceMatchers.php',
        'SecurityMatchers.php',
        'ReportingMatchers.php',
    ];

    /**
     * Registered matchers by category
     */
    private static array $registeredMatchers = [];

    /**
     * Load all custom matchers
     */
    public static function loadAll(): void
    {
        $matchersPath = __DIR__;
        
        foreach (self::$matcherFiles as $file) {
            $filePath = $matchersPath . '/' . $file;
            if (file_exists($filePath)) {
                require_once $filePath;
            }
        }
        
        self::registerMatchers();
    }

    /**
     * Load matchers for specific category
     */
    public static function loadCategory(string $category): void
    {
        $categoryFiles = [
            'link_validation' => 'LinkValidationMatchers.php',
            'performance' => 'PerformanceMatchers.php',
            'security' => 'SecurityMatchers.php',
            'reporting' => 'ReportingMatchers.php',
        ];

        if (isset($categoryFiles[$category])) {
            $filePath = __DIR__ . '/' . $categoryFiles[$category];
            if (file_exists($filePath)) {
                require_once $filePath;
            }
        }
    }

    /**
     * Register all matchers with their metadata
     */
    private static function registerMatchers(): void
    {
        self::$registeredMatchers = [
            'link_validation' => [
                'toBeValidLink' => 'Assert that a validation result represents a valid link',
                'toBeBrokenLink' => 'Assert that a validation result represents a broken link',
                'toHaveStatus' => 'Assert that a validation result has a specific status',
                'toHaveHttpStatus' => 'Assert that a validation result has a specific HTTP status code',
                'toBeSuccessfulHttpStatus' => 'Assert that a validation result has a successful HTTP status (2xx)',
                'toBeRedirectStatus' => 'Assert that a validation result has a redirect HTTP status (3xx)',
                'toBeClientErrorStatus' => 'Assert that a validation result has a client error HTTP status (4xx)',
                'toBeServerErrorStatus' => 'Assert that a validation result has a server error HTTP status (5xx)',
                'toHaveLinkType' => 'Assert that a validation result has a specific link type',
                'toBeExternalLink' => 'Assert that a validation result represents an external link',
                'toBeInternalLink' => 'Assert that a validation result represents an internal link',
                'toBeAnchorLink' => 'Assert that a validation result represents an anchor link',
                'toHaveResponseTime' => 'Assert that a validation result has a specific response time',
                'toHaveResponseTimeBelow' => 'Assert that a validation result has response time below threshold',
                'toHaveValidationError' => 'Assert that a validation result has a specific error message',
                'toBeValidationCollection' => 'Assert that value is a validation result collection with specific count',
                'toHaveValidResults' => 'Assert that a validation collection has a specific number of valid results',
                'toHaveBrokenResults' => 'Assert that a validation collection has a specific number of broken results',
                'toHaveSuccessRate' => 'Assert that a validation collection has a specific success rate',
                'toContainUrl' => 'Assert that a validation collection contains a specific URL',
                'toHaveAverageResponseTime' => 'Assert that a validation collection has a specific average response time',
            ],
            'performance' => [
                'toCompleteWithin' => 'Assert that a callable completes within a specified time',
                'toExecuteInLessThan' => 'Assert that execution time is less than specified duration',
                'toExecuteInMoreThan' => 'Assert that execution time is more than specified duration',
                'toExecuteInBetween' => 'Assert that execution time is between specified durations',
                'toUseMemoryLessThan' => 'Assert that memory usage is less than specified amount',
                'toUseMemoryMoreThan' => 'Assert that memory usage is more than specified amount',
                'toUseMemoryBetween' => 'Assert that memory usage is between specified amounts',
                'toHaveThroughputAbove' => 'Assert that throughput is above specified operations per second',
                'toHaveThroughputBelow' => 'Assert that throughput is below specified operations per second',
                'toBePerformant' => 'Assert that operation meets general performance criteria',
                'toScaleLinearly' => 'Assert that operation scales linearly with input size',
                'toHaveConstantTime' => 'Assert that operation has constant time complexity',
                'toNotLeakMemory' => 'Assert that operation doesn\'t leak memory',
                'toBeFasterThan' => 'Assert that one operation is faster than another',
                'toUseMemoryEfficiently' => 'Assert that operation uses memory efficiently compared to baseline',
                'toMeetPerformanceBenchmark' => 'Assert that operation meets a comprehensive performance benchmark',
            ],
            'security' => [
                'toBeSecure' => 'Assert that a URL or validation result is secure',
                'toPassSecurityCheck' => 'Assert that a security validation passes',
                'toFailSecurityCheck' => 'Assert that a security validation fails',
                'toHaveSecureProtocol' => 'Assert that URL uses a secure protocol',
                'toHaveInsecureProtocol' => 'Assert that URL uses an insecure protocol',
                'toBeBlockedDomain' => 'Assert that domain is in blocked list',
                'toBeAllowedDomain' => 'Assert that domain is in allowed list',
                'toHaveSuspiciousPath' => 'Assert that URL path contains suspicious patterns',
                'toHaveValidCertificate' => 'Assert that URL has a valid SSL certificate',
                'toHaveExpiredCertificate' => 'Assert that URL has an expired SSL certificate',
                'toContainMaliciousContent' => 'Assert that content contains malicious patterns',
                'toHaveSecurityHeaders' => 'Assert that response has required security headers',
                'toHaveStrictTransportSecurity' => 'Assert that response has HSTS header',
                'toHaveContentSecurityPolicy' => 'Assert that response has CSP header',
                'toPreventClickjacking' => 'Assert that response prevents clickjacking',
                'toPreventMimeSniffing' => 'Assert that response prevents MIME sniffing',
                'toHaveXSSProtection' => 'Assert that response has XSS protection enabled',
                'toBeVulnerableToInjection' => 'Assert that input is vulnerable to injection attacks',
                'toHaveSecureRandomness' => 'Assert that value has sufficient randomness for security',
                'toPassSecurityScan' => 'Assert that security scan results pass all checks',
            ],
            'reporting' => [
                'toMatchReportFormat' => 'Assert that report matches expected format',
                'toContainValidationResults' => 'Assert that report contains validation results',
                'toHaveReportSummary' => 'Assert that report has a summary section',
                'toHaveValidJsonStructure' => 'Assert that JSON report has valid structure',
                'toHaveValidHtmlStructure' => 'Assert that HTML report has valid structure',
                'toHaveValidMarkdownStructure' => 'Assert that Markdown report has valid structure',
                'toContainStatistics' => 'Assert that report contains statistics',
                'toHaveTimestamp' => 'Assert that report has a timestamp',
                'toHaveMetadata' => 'Assert that report has metadata',
                'toBeValidReport' => 'Assert that value is a valid report with all required sections',
                'toHaveErrorDetails' => 'Assert that report contains error details for broken links',
                'toHaveSuccessRate' => 'Assert that report shows a specific success rate',
                'toBeExportable' => 'Assert that report can be exported to a file',
            ],
        ];
    }

    /**
     * Get all registered matchers
     */
    public static function getAllMatchers(): array
    {
        if (empty(self::$registeredMatchers)) {
            self::registerMatchers();
        }
        
        return self::$registeredMatchers;
    }

    /**
     * Get matchers for specific category
     */
    public static function getMatchersForCategory(string $category): array
    {
        $allMatchers = self::getAllMatchers();
        return $allMatchers[$category] ?? [];
    }

    /**
     * Check if matcher exists
     */
    public static function hasMatch(string $matcherName): bool
    {
        $allMatchers = self::getAllMatchers();
        
        foreach ($allMatchers as $category => $matchers) {
            if (array_key_exists($matcherName, $matchers)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get matcher description
     */
    public static function getMatcherDescription(string $matcherName): ?string
    {
        $allMatchers = self::getAllMatchers();
        
        foreach ($allMatchers as $category => $matchers) {
            if (isset($matchers[$matcherName])) {
                return $matchers[$matcherName];
            }
        }
        
        return null;
    }

    /**
     * Get matcher category
     */
    public static function getMatcherCategory(string $matcherName): ?string
    {
        $allMatchers = self::getAllMatchers();
        
        foreach ($allMatchers as $category => $matchers) {
            if (array_key_exists($matcherName, $matchers)) {
                return $category;
            }
        }
        
        return null;
    }

    /**
     * Get usage statistics for matchers
     */
    public static function getUsageStatistics(): array
    {
        $allMatchers = self::getAllMatchers();
        $stats = [];
        
        foreach ($allMatchers as $category => $matchers) {
            $stats[$category] = [
                'count' => count($matchers),
                'matchers' => array_keys($matchers),
            ];
        }
        
        $stats['total'] = array_sum(array_column($stats, 'count'));
        
        return $stats;
    }

    /**
     * Generate documentation for all matchers
     */
    public static function generateDocumentation(): string
    {
        $allMatchers = self::getAllMatchers();
        $doc = "# PEST Custom Matchers Documentation\n\n";
        
        foreach ($allMatchers as $category => $matchers) {
            $categoryTitle = self::CATEGORIES[$category] ?? ucfirst(str_replace('_', ' ', $category));
            $doc .= "## {$categoryTitle}\n\n";
            
            foreach ($matchers as $matcher => $description) {
                $doc .= "### `{$matcher}()`\n\n";
                $doc .= "{$description}\n\n";
                $doc .= "```php\n";
                $doc .= "expect(\$value)->{$matcher}();\n";
                $doc .= "```\n\n";
            }
        }
        
        return $doc;
    }

    /**
     * Validate that all matcher functions exist
     */
    public static function validateMatchers(): array
    {
        $allMatchers = self::getAllMatchers();
        $missing = [];
        
        foreach ($allMatchers as $category => $matchers) {
            foreach (array_keys($matchers) as $matcher) {
                if (!function_exists($matcher)) {
                    $missing[] = $matcher;
                }
            }
        }
        
        return $missing;
    }

    /**
     * Get recommended matchers for test type
     */
    public static function getRecommendedMatchers(string $testType): array
    {
        $recommendations = [
            'link_validation' => [
                'toBeValidLink',
                'toHaveStatus',
                'toHaveHttpStatus',
                'toBeValidationCollection',
            ],
            'performance' => [
                'toCompleteWithin',
                'toUseMemoryLessThan',
                'toBePerformant',
                'toNotLeakMemory',
            ],
            'security' => [
                'toBeSecure',
                'toPassSecurityCheck',
                'toHaveSecureProtocol',
                'toHaveSecurityHeaders',
            ],
            'reporting' => [
                'toMatchReportFormat',
                'toContainValidationResults',
                'toHaveReportSummary',
                'toBeValidReport',
            ],
        ];
        
        return $recommendations[$testType] ?? [];
    }
}