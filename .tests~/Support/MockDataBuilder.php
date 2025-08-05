<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\FormatterOptions;
use App\Services\ValueObjects\LinkExtractionResult;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationBatch;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use App\Services\ValueObjects\ValidationSummary;

/**
 * Enhanced builder for creating deterministic test data objects.
 *
 * Provides fluent interface for building complex value objects with deterministic,
 * seeded data generation that ensures reproducible test results across different
 * test runs and environments. Replaces random data generation with predictable
 * alternatives while maintaining realistic data patterns.
 *
 * @package Tests\Support
 * @author Roo
 * @since 1.0.0
 */
final class MockDataBuilder
{
    /**
     * Deterministic seed for data generation.
     *
     * @var int
     */
    private static int $seed = 12345;
    
    /**
     * Counter for generating sequential data.
     *
     * @var int
     */
    private static int $counter = 0;
    
    /**
     * Cache for fixture data to improve performance.
     *
     * @var array<string, mixed>
     */
    private static array $fixtureCache = [];
    /**
     * Set the seed for deterministic data generation.
     *
     * @param int $seed The seed value for deterministic generation
     * @return void
     */
    public static function setSeed(int $seed): void
    {
        self::$seed = $seed;
        self::$counter = 0;
        self::$fixtureCache = [];
    }
    
    /**
     * Reset the data generation state.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$seed = 12345;
        self::$counter = 0;
        self::$fixtureCache = [];
    }
    
    /**
     * Generate a deterministic pseudo-random number.
     *
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int Deterministic pseudo-random number
     */
    private static function deterministicRandom(int $min = 0, int $max = 100): int
    {
        // Simple linear congruential generator for deterministic "random" numbers
        self::$seed = (self::$seed * 1103515245 + 12345) & 0x7fffffff;
        return $min + (self::$seed % ($max - $min + 1));
    }
    
    /**
     * Generate a deterministic float between 0 and 1.
     *
     * @return float Deterministic pseudo-random float
     */
    private static function deterministicFloat(): float
    {
        return self::deterministicRandom(0, 1000000) / 1000000.0;
    }
    
    /**
     * Get the next counter value for sequential data generation.
     *
     * @return int Next counter value
     */
    private static function nextCounter(): int
    {
        return ++self::$counter;
    }
    
    /**
     * Load fixture data from JSON files with caching.
     *
     * @param string $fixturePath Path to fixture file relative to tests/Fixtures/
     * @return array<string, mixed> Fixture data
     */
    private static function loadFixture(string $fixturePath): array
    {
        if (isset(self::$fixtureCache[$fixturePath])) {
            return self::$fixtureCache[$fixturePath];
        }
        
        $fullPath = __DIR__ . '/../Fixtures/' . $fixturePath;
        
        if (!file_exists($fullPath)) {
            throw new \InvalidArgumentException("Fixture file not found: {$fullPath}");
        }
        
        $content = file_get_contents($fullPath);
        if ($content === false) {
            throw new \RuntimeException("Failed to read fixture file: {$fullPath}");
        }
        
        $data = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in fixture file: {$fullPath}");
        }
        
        self::$fixtureCache[$fixturePath] = $data;
        return $data;
    }
    
    /**
     * Create a realistic ValidationConfig with deterministic defaults.
     *
     * @param array<string, mixed> $overrides Configuration overrides
     * @return ValidationConfig Deterministic validation configuration
     */
    public static function createValidationConfig(array $overrides = []): ValidationConfig
    {
        $configFixtures = self::loadFixture('configurations/strict-validation.json');
        $baseConfig = $configFixtures['configuration'];
        
        // Apply deterministic variations based on counter
        $counter = self::nextCounter();
        $variations = [
            'timeout' => 10 + ($counter % 3) * 10, // 10, 20, or 30
            'max_redirects' => 3 + ($counter % 3), // 3, 4, or 5
            'concurrent_requests' => 5 + ($counter % 3) * 5, // 5, 10, or 15
            'cache_enabled' => ($counter % 2) === 0, // Alternating true/false
            'user_agent' => "validate-links-test/{$counter}.0",
        ];
        
        $defaults = array_merge($baseConfig, $variations);
        return ValidationConfig::create(array_merge($defaults, $overrides));
    }

    /**
     * Create a ValidationResult with deterministic realistic data.
     *
     * @param array<string, mixed> $config Configuration overrides
     * @return ValidationResult Deterministic validation result
     */
    public static function createValidationResult(array $config = []): ValidationResult
    {
        $counter = self::nextCounter();
        
        // Get deterministic URL if not provided
        $url = $config['url'] ?? self::getDeterministicUrl($counter);
        $status = $config['status'] ?? self::getDeterministicStatus($url, $counter);
        $scope = $config['scope'] ?? self::getDeterministicScope($url);
        
        // Generate deterministic response time based on status and counter
        $responseTime = match ($status) {
            LinkStatus::TIMEOUT => 30.0,
            LinkStatus::BROKEN => 5.0 + (($counter % 5) * 1.0), // 5-9 seconds
            LinkStatus::ANCHOR => 0.0,
            default => 0.05 + (($counter % 20) * 0.1), // 0.05-2.05 seconds
        };
        
        // Generate deterministic HTTP status code
        $httpStatusCode = match ($status) {
            LinkStatus::VALID => 200,
            LinkStatus::NOT_FOUND => 404,
            LinkStatus::FORBIDDEN => 403,
            LinkStatus::BROKEN => 500 + ($counter % 4), // 500-503
            LinkStatus::TIMEOUT => null,
            LinkStatus::SECURITY_VIOLATION => null,
            LinkStatus::INVALID_FORMAT => null,
            default => 200,
        };
        
        // Generate deterministic error message
        $error = match ($status) {
            LinkStatus::BROKEN => 'Connection failed: ' . self::getDeterministicErrorMessage($counter),
            LinkStatus::NOT_FOUND => 'HTTP 404: Resource not found',
            LinkStatus::TIMEOUT => 'Request timeout after 30 seconds',
            LinkStatus::FORBIDDEN => 'HTTP 403: Access forbidden',
            LinkStatus::SECURITY_VIOLATION => 'URL failed security validation: ' . self::getDeterministicSecurityError($counter),
            LinkStatus::INVALID_FORMAT => 'Invalid URL format: ' . self::getDeterministicFormatError($counter),
            default => '',
        };
        
        return ValidationResult::create(
            url: $url,
            status: $status,
            message: $error,
            scope: $scope,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime,
            metadata: $config['metadata'] ?? []
        );
    }
    
    /**
     * Get a deterministic URL based on counter.
     *
     * @param int $counter Counter value for deterministic selection
     * @return string Deterministic URL
     */
    private static function getDeterministicUrl(int $counter): string
    {
        $urlFixtures = self::loadFixture('urls/valid-urls.json');
        $allUrls = [];
        
        foreach ($urlFixtures['categories'] as $category => $urls) {
            $allUrls = array_merge($allUrls, $urls);
        }
        
        return $allUrls[$counter % count($allUrls)];
    }
    
    /**
     * Get a deterministic status based on URL and counter.
     *
     * @param string $url The URL to determine status for
     * @param int $counter Counter value for deterministic selection
     * @return LinkStatus Deterministic link status
     */
    private static function getDeterministicStatus(string $url, int $counter): LinkStatus
    {
        // Check if URL indicates a specific status
        if (str_contains($url, 'broken')) {
            return LinkStatus::BROKEN;
        }
        if (str_contains($url, '404') || str_contains($url, 'notfound')) {
            return LinkStatus::NOT_FOUND;
        }
        if (str_contains($url, 'timeout')) {
            return LinkStatus::TIMEOUT;
        }
        if (str_contains($url, 'forbidden') || str_contains($url, '403')) {
            return LinkStatus::FORBIDDEN;
        }
        
        // Use deterministic distribution: 70% valid, 15% broken, 10% not found, 5% timeout
        $statusIndex = $counter % 20;
        return match (true) {
            $statusIndex < 14 => LinkStatus::VALID,      // 70%
            $statusIndex < 17 => LinkStatus::BROKEN,     // 15%
            $statusIndex < 19 => LinkStatus::NOT_FOUND,  // 10%
            default => LinkStatus::TIMEOUT,              // 5%
        };
    }
    
    /**
     * Get a deterministic scope based on URL.
     *
     * @param string $url The URL to determine scope for
     * @return ValidationScope Deterministic validation scope
     */
    private static function getDeterministicScope(string $url): ValidationScope
    {
        return match (true) {
            str_starts_with($url, '#') => ValidationScope::ANCHOR,
            str_starts_with($url, 'http') => ValidationScope::EXTERNAL,
            str_starts_with($url, './') || str_starts_with($url, '/') => ValidationScope::INTERNAL,
            default => ValidationScope::ALL,
        };
    }

    /**
     * Create a collection of ValidationResults with deterministic mixed statuses.
     *
     * @param array<string, mixed> $config Configuration overrides
     * @return ValidationResultCollection Deterministic validation result collection
     */
    public static function createValidationResultCollection(array $config = []): ValidationResultCollection
    {
        $count = $config['count'] ?? 10;
        $statusDistribution = $config['status_distribution'] ?? [
            LinkStatus::VALID => 0.7,      // 70% valid
            LinkStatus::BROKEN => 0.15,    // 15% broken
            LinkStatus::NOT_FOUND => 0.1,  // 10% not found
            LinkStatus::TIMEOUT => 0.05,   // 5% timeout
        ];
        
        $results = [];
        $urlTemplates = [
            'https://example{}.com',
            'https://test{}.org',
            'https://demo{}.net',
            './file{}.md',
            '#section{}',
            'https://api{}.service.com/endpoint',
        ];
        
        for ($i = 0; $i < $count; $i++) {
            // Select status based on deterministic distribution
            $status = self::selectStatusByDeterministicDistribution($statusDistribution, $i);
            
            // Generate URL based on deterministic template selection
            $urlTemplate = $urlTemplates[$i % count($urlTemplates)];
            $url = str_replace('{}', (string)$i, $urlTemplate);
            
            // Modify URL to match status if needed
            if ($status === LinkStatus::BROKEN) {
                $url = str_replace('example', 'broken', $url);
            } elseif ($status === LinkStatus::NOT_FOUND) {
                $url = str_replace('example', 'notfound', $url);
            } elseif ($status === LinkStatus::TIMEOUT) {
                $url = str_replace('example', 'timeout', $url);
            }
            
            $results[] = self::createValidationResult([
                'url' => $url,
                'status' => $status,
                'scope' => self::getScopeFromUrl($url),
            ]);
        }
        
        return new ValidationResultCollection($results);
    }

    /**
     * Create a ReportSummary with deterministic realistic statistics.
     *
     * @param array<string, mixed> $config Configuration overrides
     * @return ReportSummary Deterministic report summary
     */
    public static function createReportSummary(array $config = []): ReportSummary
    {
        $counter = self::nextCounter();
        $totalLinks = $config['total_links'] ?? 100;
        $brokenLinks = $config['broken_links'] ?? 15;
        $successRate = $config['success_rate'] ?? round((($totalLinks - $brokenLinks) / $totalLinks) * 100, 2);
        
        // Deterministic execution time based on counter: 5-30 seconds
        $executionTime = $config['execution_time'] ?? (5.0 + (($counter % 25) * 1.0));
        
        $statusBreakdown = $config['status_breakdown'] ?? [
            LinkStatus::VALID->value => $totalLinks - $brokenLinks,
            LinkStatus::BROKEN->value => $brokenLinks,
        ];
        
        $scopeBreakdown = $config['scope_breakdown'] ?? [
            ValidationScope::EXTERNAL->value => (int)($totalLinks * 0.6),
            ValidationScope::INTERNAL->value => (int)($totalLinks * 0.3),
            ValidationScope::ANCHOR->value => (int)($totalLinks * 0.1),
        ];
        
        return new ReportSummary(
            totalLinks: $totalLinks,
            brokenLinks: $brokenLinks,
            successRate: $successRate,
            statusBreakdown: $statusBreakdown,
            executionTime: $executionTime,
            filesProcessed: $config['files_processed'] ?? 10
        );
    }

    /**
     * Create ValidationStatistics with realistic data.
     */
    public static function createValidationStatistics(array $config = []): ValidationStatistics
    {
        $total = $config['total'] ?? 100;
        $valid = $config['valid'] ?? 85;
        $broken = $config['broken'] ?? 10;
        $timeout = $config['timeout'] ?? 3;
        $notFound = $config['not_found'] ?? 2;
        $forbidden = $config['forbidden'] ?? 0;
        $securityViolation = $config['security_violation'] ?? 0;
        $invalidFormat = $config['invalid_format'] ?? 0;
        
        return new ValidationStatistics(
            total: $total,
            valid: $valid,
            broken: $broken,
            timeout: $timeout,
            notFound: $notFound,
            forbidden: $forbidden,
            securityViolation: $securityViolation,
            invalidFormat: $invalidFormat
        );
    }

    /**
     * Create FormatterOptions with realistic settings.
     */
    public static function createFormatterOptions(array $config = []): FormatterOptions
    {
        return FormatterOptions::create(array_merge([
            'show_summary' => true,
            'show_details' => true,
            'group_by_status' => true,
            'include_metadata' => false,
            'color_output' => true,
            'max_url_length' => 80,
        ], $config));
    }

    /**
     * Create LinkExtractionResult with deterministic realistic data.
     *
     * @param array<string, mixed> $config Configuration overrides
     * @return LinkExtractionResult Deterministic link extraction result
     */
    public static function createLinkExtractionResult(array $config = []): LinkExtractionResult
    {
        $counter = self::nextCounter();
        $links = $config['links'] ?? [
            'https://example.com',
            './relative/file.md',
            '#anchor-section',
            'https://api.service.com/endpoint',
        ];
        
        // Deterministic extraction time: 0.01-0.1 seconds
        $extractionTime = $config['extraction_time'] ?? (0.01 + (($counter % 90) * 0.001));
        
        return LinkExtractionResult::create(
            links: $links,
            sourceFile: $config['source_file'] ?? 'test.md',
            extractionTime: $extractionTime,
            metadata: $config['metadata'] ?? []
        );
    }

    /**
     * Create ValidationBatch for concurrent processing testing with deterministic data.
     *
     * @param array<string, mixed> $config Configuration overrides
     * @return ValidationBatch Deterministic validation batch
     */
    public static function createValidationBatch(array $config = []): ValidationBatch
    {
        $counter = self::nextCounter();
        $urls = $config['urls'] ?? [
            'https://example.com',
            'https://test.org',
            'https://demo.net',
        ];
        
        // Deterministic batch ID based on counter
        $batchId = $config['batch_id'] ?? "batch_{$counter}";
        
        return ValidationBatch::create(
            urls: $urls,
            batchId: $batchId,
            priority: $config['priority'] ?? 1,
            timeout: $config['timeout'] ?? 30,
            metadata: $config['metadata'] ?? []
        );
    }

    /**
     * Create ValidationSummary with comprehensive data.
     */
    public static function createValidationSummary(array $config = []): ValidationSummary
    {
        return ValidationSummary::create(
            totalFiles: $config['total_files'] ?? 10,
            totalLinks: $config['total_links'] ?? 100,
            validLinks: $config['valid_links'] ?? 85,
            brokenLinks: $config['broken_links'] ?? 15,
            executionTime: $config['execution_time'] ?? 25.5,
            averageResponseTime: $config['average_response_time'] ?? 0.255,
            successRate: $config['success_rate'] ?? 85.0,
            metadata: $config['metadata'] ?? []
        );
    }

    /**
     * Select a status based on deterministic probability distribution.
     *
     * @param array<LinkStatus, float> $distribution Status probability distribution
     * @param int $index Index for deterministic selection
     * @return LinkStatus Selected status
     */
    private static function selectStatusByDeterministicDistribution(array $distribution, int $index): LinkStatus
    {
        // Convert probabilities to cumulative counts for deterministic selection
        $totalWeight = 100;
        $cumulativeWeights = [];
        $cumulative = 0;
        
        foreach ($distribution as $status => $probability) {
            $cumulative += (int)($probability * $totalWeight);
            $cumulativeWeights[] = ['status' => $status, 'weight' => $cumulative];
        }
        
        $deterministicValue = $index % $totalWeight;
        
        foreach ($cumulativeWeights as $item) {
            if ($deterministicValue < $item['weight']) {
                return $item['status'];
            }
        }
        
        // Fallback to first status
        return array_key_first($distribution);
    }

    /**
     * Determine scope from URL pattern.
     *
     * @param string $url The URL to analyze
     * @return ValidationScope Determined validation scope
     */
    private static function getScopeFromUrl(string $url): ValidationScope
    {
        return match (true) {
            str_starts_with($url, '#') => ValidationScope::ANCHOR,
            str_starts_with($url, 'http') => ValidationScope::EXTERNAL,
            str_starts_with($url, './') || str_starts_with($url, '/') => ValidationScope::INTERNAL,
            default => ValidationScope::ALL,
        };
    }

    /**
     * Get deterministic error message for broken links.
     *
     * @param int $counter Counter for deterministic selection
     * @return string Deterministic error message
     */
    private static function getDeterministicErrorMessage(int $counter): string
    {
        $messages = [
            'Connection refused',
            'Host unreachable',
            'Network timeout',
            'DNS resolution failed',
            'SSL handshake failed',
            'Connection reset by peer',
            'Too many redirects',
        ];
        
        return $messages[$counter % count($messages)];
    }

    /**
     * Get deterministic security error message.
     *
     * @param int $counter Counter for deterministic selection
     * @return string Deterministic security error message
     */
    private static function getDeterministicSecurityError(int $counter): string
    {
        $errors = [
            'Blocked domain detected',
            'Unsafe protocol',
            'Path traversal attempt',
            'Malicious URL pattern',
            'Blacklisted host',
        ];
        
        return $errors[$counter % count($errors)];
    }

    /**
     * Get deterministic format error message.
     *
     * @param int $counter Counter for deterministic selection
     * @return string Deterministic format error message
     */
    private static function getDeterministicFormatError(int $counter): string
    {
        $errors = [
            'Missing protocol',
            'Invalid characters',
            'Malformed URL structure',
            'Empty URL',
            'Invalid port number',
        ];
        
        return $errors[$counter % count($errors)];
    }
}