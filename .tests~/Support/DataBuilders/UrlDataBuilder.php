<?php

declare(strict_types=1);

namespace Tests\Support\DataBuilders;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;

/**
 * Specialized builder for creating comprehensive URL test data.
 * 
 * Provides deterministic URL generation for various testing scenarios including
 * valid URLs, invalid URLs, security test cases, and edge cases. All data
 * generation is seeded and reproducible across test runs.
 * 
 * @package Tests\Support\DataBuilders
 * @author Roo
 * @since 1.0.0
 */
final class UrlDataBuilder
{
    /**
     * Deterministic seed for URL generation.
     * 
     * @var int
     */
    private static int $seed = 54321;
    
    /**
     * Counter for sequential URL generation.
     * 
     * @var int
     */
    private static int $counter = 0;
    
    /**
     * Cache for loaded fixture data.
     * 
     * @var array<string, mixed>
     */
    private static array $fixtureCache = [];
    
    /**
     * Set the seed for deterministic URL generation.
     * 
     * @param int $seed The seed value for deterministic generation
     * @return void
     */
    public static function setSeed(int $seed): void
    {
        self::$seed = $seed;
        self::$counter = 0;
    }
    
    /**
     * Reset the URL generation state.
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$seed = 54321;
        self::$counter = 0;
        self::$fixtureCache = [];
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
        
        $fullPath = __DIR__ . '/../../Fixtures/' . $fixturePath;
        
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
     * Get the next counter value for sequential data generation.
     * 
     * @return int Next counter value
     */
    private static function nextCounter(): int
    {
        return ++self::$counter;
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
        self::$seed = (self::$seed * 1103515245 + 12345) & 0x7fffffff;
        return $min + (self::$seed % ($max - $min + 1));
    }
    
    /**
     * Create a valid URL for testing successful validation scenarios.
     * 
     * @param array<string, mixed> $options URL generation options
     * @return string Valid URL
     */
    public static function createValidUrl(array $options = []): string
    {
        $counter = self::nextCounter();
        $validUrls = self::loadFixture('urls/valid-urls.json');
        
        $category = $options['category'] ?? null;
        if ($category && isset($validUrls['categories'][$category])) {
            $urls = $validUrls['categories'][$category];
            return $urls[$counter % count($urls)];
        }
        
        // Get all valid URLs
        $allUrls = [];
        foreach ($validUrls['categories'] as $categoryUrls) {
            $allUrls = array_merge($allUrls, $categoryUrls);
        }
        
        return $allUrls[$counter % count($allUrls)];
    }
    
    /**
     * Create an invalid URL for testing validation error handling.
     * 
     * @param array<string, mixed> $options URL generation options
     * @return string Invalid URL
     */
    public static function createInvalidUrl(array $options = []): string
    {
        $counter = self::nextCounter();
        $invalidUrls = self::loadFixture('urls/invalid-urls.json');
        
        $category = $options['category'] ?? null;
        if ($category && isset($invalidUrls['categories'][$category])) {
            $urls = $invalidUrls['categories'][$category];
            return $urls[$counter % count($urls)];
        }
        
        // Get all invalid URLs
        $allUrls = [];
        foreach ($invalidUrls['categories'] as $categoryUrls) {
            $allUrls = array_merge($allUrls, $categoryUrls);
        }
        
        return $allUrls[$counter % count($allUrls)];
    }
    
    /**
     * Create a security test URL for testing security validation.
     * 
     * @param array<string, mixed> $options URL generation options
     * @return string Security test URL
     */
    public static function createSecurityTestUrl(array $options = []): string
    {
        $counter = self::nextCounter();
        $securityUrls = self::loadFixture('urls/security-test-urls.json');
        
        $category = $options['category'] ?? null;
        if ($category && isset($securityUrls['categories'][$category])) {
            $urls = $securityUrls['categories'][$category];
            return $urls[$counter % count($urls)];
        }
        
        // Get all security test URLs
        $allUrls = [];
        foreach ($securityUrls['categories'] as $categoryUrls) {
            $allUrls = array_merge($allUrls, $categoryUrls);
        }
        
        return $allUrls[$counter % count($allUrls)];
    }
    
    /**
     * Create a URL with a specific expected status for testing.
     * 
     * @param LinkStatus $expectedStatus Expected validation status
     * @param array<string, mixed> $options URL generation options
     * @return string URL with expected status
     */
    public static function createUrlWithStatus(LinkStatus $expectedStatus, array $options = []): string
    {
        $counter = self::nextCounter();
        
        return match ($expectedStatus) {
            LinkStatus::VALID => self::createValidUrl($options),
            LinkStatus::BROKEN => "https://broken{$counter}.example.com",
            LinkStatus::NOT_FOUND => "https://example.com/404-page-{$counter}",
            LinkStatus::TIMEOUT => "https://timeout{$counter}.example.com",
            LinkStatus::FORBIDDEN => "https://example.com/forbidden-{$counter}",
            LinkStatus::SECURITY_VIOLATION => self::createSecurityTestUrl($options),
            LinkStatus::INVALID_FORMAT => self::createInvalidUrl($options),
            LinkStatus::ANCHOR => "#test-anchor-{$counter}",
        };
    }
    
    /**
     * Create a URL with a specific scope for testing.
     * 
     * @param ValidationScope $scope Expected validation scope
     * @param array<string, mixed> $options URL generation options
     * @return string URL with expected scope
     */
    public static function createUrlWithScope(ValidationScope $scope, array $options = []): string
    {
        $counter = self::nextCounter();
        
        return match ($scope) {
            ValidationScope::EXTERNAL => "https://external{$counter}.example.com",
            ValidationScope::INTERNAL => "./internal/file{$counter}.md",
            ValidationScope::ANCHOR => "#anchor-section-{$counter}",
            ValidationScope::IMAGE => "https://example.com/image{$counter}.jpg",
            ValidationScope::ALL => self::createValidUrl($options),
        };
    }
    
    /**
     * Create a collection of URLs for batch testing.
     * 
     * @param int $count Number of URLs to generate
     * @param array<string, mixed> $options Generation options
     * @return array<string> Collection of URLs
     */
    public static function createUrlCollection(int $count = 10, array $options = []): array
    {
        $urls = [];
        $distribution = $options['status_distribution'] ?? [
            'valid' => 0.7,
            'broken' => 0.15,
            'not_found' => 0.1,
            'timeout' => 0.05,
        ];
        
        for ($i = 0; $i < $count; $i++) {
            $statusType = self::selectStatusType($distribution, $i);
            $status = match ($statusType) {
                'valid' => LinkStatus::VALID,
                'broken' => LinkStatus::BROKEN,
                'not_found' => LinkStatus::NOT_FOUND,
                'timeout' => LinkStatus::TIMEOUT,
                default => LinkStatus::VALID,
            };
            
            $urls[] = self::createUrlWithStatus($status, $options);
        }
        
        return $urls;
    }
    
    /**
     * Create URLs for redirect testing.
     * 
     * @param array<string, mixed> $options Redirect options
     * @return array<string, mixed> Redirect test data
     */
    public static function createRedirectUrls(array $options = []): array
    {
        $redirectFixtures = self::loadFixture('urls/redirect-chains.json');
        $counter = self::nextCounter();
        
        $scenarioType = $options['scenario'] ?? 'simple_redirects';
        if (!isset($redirectFixtures['scenarios'][$scenarioType])) {
            $scenarioType = 'simple_redirects';
        }
        
        $scenarios = $redirectFixtures['scenarios'][$scenarioType];
        $scenarioKeys = array_keys($scenarios);
        $selectedScenario = $scenarios[$scenarioKeys[$counter % count($scenarioKeys)]];
        
        return [
            'initial_url' => $selectedScenario['initial_url'],
            'final_url' => $selectedScenario['final_url'] ?? $selectedScenario['initial_url'],
            'redirect_chain' => $selectedScenario['redirect_chain'] ?? [],
            'expected_status' => $selectedScenario['expected_status'] ?? 200,
        ];
    }
    
    /**
     * Create URLs for performance testing.
     * 
     * @param array<string, mixed> $options Performance test options
     * @return array<string> Performance test URLs
     */
    public static function createPerformanceTestUrls(array $options = []): array
    {
        $counter = self::nextCounter();
        $baseUrl = $options['base_url'] ?? 'https://httpbin.org';
        
        return [
            'fast' => "{$baseUrl}/delay/0",
            'medium' => "{$baseUrl}/delay/1",
            'slow' => "{$baseUrl}/delay/5",
            'timeout' => "{$baseUrl}/delay/35",
            'large_response' => "{$baseUrl}/bytes/1048576", // 1MB
            'small_response' => "{$baseUrl}/bytes/1024", // 1KB
        ];
    }
    
    /**
     * Select status type based on deterministic distribution.
     * 
     * @param array<string, float> $distribution Status distribution
     * @param int $index Index for deterministic selection
     * @return string Selected status type
     */
    private static function selectStatusType(array $distribution, int $index): string
    {
        $totalWeight = 100;
        $cumulativeWeights = [];
        $cumulative = 0;
        
        foreach ($distribution as $type => $probability) {
            $cumulative += (int)($probability * $totalWeight);
            $cumulativeWeights[] = ['type' => $type, 'weight' => $cumulative];
        }
        
        $deterministicValue = $index % $totalWeight;
        
        foreach ($cumulativeWeights as $item) {
            if ($deterministicValue < $item['weight']) {
                return $item['type'];
            }
        }
        
        return array_key_first($distribution);
    }
    
    /**
     * Get available URL categories from fixtures.
     * 
     * @param string $fixtureType Type of fixture ('valid', 'invalid', 'security')
     * @return array<string> Available categories
     */
    public static function getAvailableCategories(string $fixtureType = 'valid'): array
    {
        $fixturePath = match ($fixtureType) {
            'invalid' => 'urls/invalid-urls.json',
            'security' => 'urls/security-test-urls.json',
            default => 'urls/valid-urls.json',
        };
        
        $fixture = self::loadFixture($fixturePath);
        return array_keys($fixture['categories'] ?? []);
    }
}