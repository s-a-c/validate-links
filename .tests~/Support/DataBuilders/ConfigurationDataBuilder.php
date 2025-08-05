<?php

declare(strict_types=1);

namespace Tests\Support\DataBuilders;

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;

/**
 * Specialized builder for creating comprehensive validation configuration test data.
 * 
 * Provides deterministic configuration generation for various testing scenarios including
 * strict validation, permissive validation, custom rules, and edge cases. All data
 * generation is seeded and reproducible across test runs.
 * 
 * @package Tests\Support\DataBuilders
 * @author Roo
 * @since 1.0.0
 */
final class ConfigurationDataBuilder
{
    /**
     * Deterministic seed for configuration generation.
     * 
     * @var int
     */
    private static int $seed = 98765;
    
    /**
     * Counter for sequential configuration generation.
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
     * Set the seed for deterministic configuration generation.
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
     * Reset the configuration generation state.
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$seed = 98765;
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
     * Create a strict validation configuration for security-focused testing.
     * 
     * @param array<string, mixed> $overrides Configuration overrides
     * @return ValidationConfig Strict validation configuration
     */
    public static function createStrictConfig(array $overrides = []): ValidationConfig
    {
        $strictFixture = self::loadFixture('configurations/strict-validation.json');
        $baseConfig = $strictFixture['configuration'];
        
        $counter = self::nextCounter();
        
        // Apply deterministic variations
        $variations = [
            'timeout' => 5 + ($counter % 3) * 5, // 5, 10, or 15 seconds
            'max_redirects' => 1 + ($counter % 3), // 1, 2, or 3
            'concurrent_requests' => 1 + ($counter % 3), // 1, 2, or 3
            'user_agent' => "validate-links-strict-test/{$counter}.0",
        ];
        
        $config = array_merge($baseConfig, $variations, $overrides);
        return ValidationConfig::create($config);
    }
    
    /**
     * Create a permissive validation configuration for development testing.
     * 
     * @param array<string, mixed> $overrides Configuration overrides
     * @return ValidationConfig Permissive validation configuration
     */
    public static function createPermissiveConfig(array $overrides = []): ValidationConfig
    {
        $permissiveFixture = self::loadFixture('configurations/permissive-validation.json');
        $baseConfig = $permissiveFixture['configuration'];
        
        $counter = self::nextCounter();
        
        // Apply deterministic variations
        $variations = [
            'timeout' => 30 + ($counter % 4) * 15, // 30, 45, 60, or 75 seconds
            'max_redirects' => 10 + ($counter % 3) * 5, // 10, 15, or 20
            'concurrent_requests' => 20 + ($counter % 3) * 10, // 20, 30, or 40
            'user_agent' => "validate-links-permissive-test/{$counter}.0",
        ];
        
        $config = array_merge($baseConfig, $variations, $overrides);
        return ValidationConfig::create($config);
    }
    
    /**
     * Create a custom validation configuration with specialized rules.
     * 
     * @param array<string, mixed> $overrides Configuration overrides
     * @return ValidationConfig Custom validation configuration
     */
    public static function createCustomConfig(array $overrides = []): ValidationConfig
    {
        $customFixture = self::loadFixture('configurations/custom-rules.json');
        $baseConfig = $customFixture['configuration'];
        
        $counter = self::nextCounter();
        
        // Apply deterministic variations
        $variations = [
            'timeout' => 15 + ($counter % 3) * 10, // 15, 25, or 35 seconds
            'max_redirects' => 3 + ($counter % 5), // 3-7
            'concurrent_requests' => 5 + ($counter % 4) * 5, // 5, 10, 15, or 20
            'user_agent' => "validate-links-custom-test/{$counter}.0",
        ];
        
        $config = array_merge($baseConfig, $variations, $overrides);
        return ValidationConfig::create($config);
    }
    
    /**
     * Create a configuration for performance testing.
     * 
     * @param array<string, mixed> $options Performance test options
     * @return ValidationConfig Performance test configuration
     */
    public static function createPerformanceConfig(array $options = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        $baseConfig = [
            'scopes' => [ValidationScope::ALL],
            'timeout' => $options['timeout'] ?? 60,
            'max_redirects' => $options['max_redirects'] ?? 10,
            'concurrent_requests' => $options['concurrent_requests'] ?? 50,
            'cache_enabled' => $options['cache_enabled'] ?? true,
            'follow_redirects' => true,
            'user_agent' => "validate-links-performance-test/{$counter}.0",
            'verify_ssl' => false, // Faster for performance testing
            'security_checks' => false, // Skip for performance
        ];
        
        return ValidationConfig::create(array_merge($baseConfig, $options));
    }
    
    /**
     * Create a configuration for security testing.
     * 
     * @param array<string, mixed> $options Security test options
     * @return ValidationConfig Security test configuration
     */
    public static function createSecurityConfig(array $options = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        $baseConfig = [
            'scopes' => [ValidationScope::ALL],
            'timeout' => 10,
            'max_redirects' => 0, // No redirects for security testing
            'concurrent_requests' => 1, // Sequential for security analysis
            'cache_enabled' => false,
            'follow_redirects' => false,
            'user_agent' => "validate-links-security-test/{$counter}.0",
            'verify_ssl' => true,
            'security_checks' => true,
            'excluded_domains' => [
                'malicious.com',
                'suspicious.net',
                'blocked.org',
            ],
            'blocked_protocols' => [
                'javascript',
                'data',
                'vbscript',
                'file',
                'ftp',
            ],
        ];
        
        return ValidationConfig::create(array_merge($baseConfig, $options));
    }
    
    /**
     * Create a configuration for timeout testing.
     * 
     * @param array<string, mixed> $options Timeout test options
     * @return ValidationConfig Timeout test configuration
     */
    public static function createTimeoutConfig(array $options = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        // Deterministic timeout values: 1, 5, 10, 30 seconds
        $timeouts = [1, 5, 10, 30];
        $timeout = $options['timeout'] ?? $timeouts[$counter % count($timeouts)];
        
        $baseConfig = [
            'scopes' => [ValidationScope::EXTERNAL],
            'timeout' => $timeout,
            'max_redirects' => 3,
            'concurrent_requests' => 5,
            'cache_enabled' => false,
            'follow_redirects' => true,
            'user_agent' => "validate-links-timeout-test/{$counter}.0",
            'verify_ssl' => true,
            'security_checks' => false,
        ];
        
        return ValidationConfig::create(array_merge($baseConfig, $options));
    }
    
    /**
     * Create a configuration for concurrent testing.
     * 
     * @param array<string, mixed> $options Concurrent test options
     * @return ValidationConfig Concurrent test configuration
     */
    public static function createConcurrentConfig(array $options = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        // Deterministic concurrent request values: 1, 5, 10, 20, 50
        $concurrentValues = [1, 5, 10, 20, 50];
        $concurrent = $options['concurrent_requests'] ?? $concurrentValues[$counter % count($concurrentValues)];
        
        $baseConfig = [
            'scopes' => [ValidationScope::ALL],
            'timeout' => 30,
            'max_redirects' => 5,
            'concurrent_requests' => $concurrent,
            'cache_enabled' => true,
            'follow_redirects' => true,
            'user_agent' => "validate-links-concurrent-test/{$counter}.0",
            'verify_ssl' => true,
            'security_checks' => true,
        ];
        
        return ValidationConfig::create(array_merge($baseConfig, $options));
    }
    
    /**
     * Create a configuration with specific scopes for scope testing.
     * 
     * @param array<ValidationScope> $scopes Validation scopes to test
     * @param array<string, mixed> $options Additional options
     * @return ValidationConfig Scope-specific configuration
     */
    public static function createScopeConfig(array $scopes, array $options = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        $baseConfig = [
            'scopes' => $scopes,
            'timeout' => 30,
            'max_redirects' => 5,
            'concurrent_requests' => 10,
            'cache_enabled' => false,
            'follow_redirects' => true,
            'user_agent' => "validate-links-scope-test/{$counter}.0",
            'verify_ssl' => true,
            'security_checks' => true,
        ];
        
        return ValidationConfig::create(array_merge($baseConfig, $options));
    }
    
    /**
     * Create a configuration with specific output format.
     * 
     * @param OutputFormat $format Output format
     * @param array<string, mixed> $options Additional options
     * @return ValidationConfig Format-specific configuration
     */
    public static function createFormatConfig(OutputFormat $format, array $options = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        $baseConfig = [
            'scopes' => [ValidationScope::ALL],
            'timeout' => 30,
            'max_redirects' => 5,
            'concurrent_requests' => 10,
            'cache_enabled' => false,
            'follow_redirects' => true,
            'user_agent' => "validate-links-format-test/{$counter}.0",
            'output_format' => $format,
            'verify_ssl' => true,
            'security_checks' => true,
        ];
        
        return ValidationConfig::create(array_merge($baseConfig, $options));
    }
    
    /**
     * Create a minimal configuration for basic testing.
     * 
     * @param array<string, mixed> $overrides Configuration overrides
     * @return ValidationConfig Minimal configuration
     */
    public static function createMinimalConfig(array $overrides = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        $baseConfig = [
            'scopes' => [ValidationScope::ALL],
            'timeout' => 10,
            'max_redirects' => 3,
            'concurrent_requests' => 1,
            'cache_enabled' => false,
            'follow_redirects' => false,
            'user_agent' => "validate-links-minimal-test/{$counter}.0",
            'verify_ssl' => false,
            'security_checks' => false,
        ];
        
        return ValidationConfig::create(array_merge($baseConfig, $overrides));
    }
    
    /**
     * Create a configuration for edge case testing.
     * 
     * @param array<string, mixed> $options Edge case options
     * @return ValidationConfig Edge case configuration
     */
    public static function createEdgeCaseConfig(array $options = []): ValidationConfig
    {
        $counter = self::nextCounter();
        
        // Deterministic edge case values
        $edgeCases = [
            ['timeout' => 1, 'max_redirects' => 0, 'concurrent_requests' => 1],
            ['timeout' => 300, 'max_redirects' => 10, 'concurrent_requests' => 50],
            ['timeout' => 5, 'max_redirects' => 1, 'concurrent_requests' => 100], // Invalid, will be clamped
        ];
        
        $edgeCase = $edgeCases[$counter % count($edgeCases)];
        
        $baseConfig = array_merge([
            'scopes' => [ValidationScope::ALL],
            'cache_enabled' => ($counter % 2) === 0,
            'follow_redirects' => ($counter % 3) === 0,
            'user_agent' => "validate-links-edge-test/{$counter}.0",
            'verify_ssl' => ($counter % 2) === 1,
            'security_checks' => ($counter % 3) === 1,
        ], $edgeCase);
        
        return ValidationConfig::create(array_merge($baseConfig, $options));
    }
    
    /**
     * Get available configuration types from fixtures.
     * 
     * @return array<string> Available configuration types
     */
    public static function getAvailableConfigTypes(): array
    {
        return [
            'strict',
            'permissive',
            'custom',
            'performance',
            'security',
            'timeout',
            'concurrent',
            'minimal',
            'edge_case',
        ];
    }
    
    /**
     * Create a configuration by type name.
     * 
     * @param string $type Configuration type
     * @param array<string, mixed> $options Configuration options
     * @return ValidationConfig Configuration of specified type
     */
    public static function createByType(string $type, array $options = []): ValidationConfig
    {
        return match ($type) {
            'strict' => self::createStrictConfig($options),
            'permissive' => self::createPermissiveConfig($options),
            'custom' => self::createCustomConfig($options),
            'performance' => self::createPerformanceConfig($options),
            'security' => self::createSecurityConfig($options),
            'timeout' => self::createTimeoutConfig($options),
            'concurrent' => self::createConcurrentConfig($options),
            'minimal' => self::createMinimalConfig($options),
            'edge_case' => self::createEdgeCaseConfig($options),
            default => throw new \InvalidArgumentException("Unknown configuration type: {$type}"),
        };
    }
}