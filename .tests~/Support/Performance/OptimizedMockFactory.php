<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mockery;
use Mockery\MockInterface;

/**
 * Performance-optimized mock factory with intelligent caching,
 * object pooling, and streamlined mock creation patterns.
 * 
 * Eliminates redundant mock creation, implements efficient reuse patterns,
 * and provides high-performance mock management for test suite optimization.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class OptimizedMockFactory
{
    /**
     * Pre-configured mock templates for rapid instantiation.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $mockTemplates = [];
    
    /**
     * Mock object pool for efficient reuse.
     * 
     * @var array<string, array<MockInterface>>
     */
    private static array $mockPool = [];
    
    /**
     * Mock configuration cache for performance.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $configCache = [];
    
    /**
     * Performance metrics tracking.
     * 
     * @var array<string, int>
     */
    private static array $metrics = [
        'cache_hits' => 0,
        'cache_misses' => 0,
        'pool_reuses' => 0,
        'new_creations' => 0,
    ];
    
    /**
     * Initialize optimized mock factory with pre-warmed templates.
     */
    public static function initialize(): void
    {
        self::$mockTemplates = [];
        self::$mockPool = [];
        self::$configCache = [];
        self::$metrics = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'pool_reuses' => 0,
            'new_creations' => 0,
        ];
        
        // Pre-warm commonly used mock templates
        self::preWarmMockTemplates();
    }
    
    /**
     * Get optimized security validation service mock with intelligent caching.
     */
    public static function createSecurityValidationService(array $config = []): MockInterface
    {
        $cacheKey = self::generateCacheKey('security_validation', $config);
        
        // Try to get from pool first
        if ($pooledMock = self::getFromPool($cacheKey)) {
            self::$metrics['pool_reuses']++;
            return $pooledMock;
        }
        
        // Check template cache
        if (isset(self::$mockTemplates['security_validation'])) {
            $mock = self::createFromTemplate('security_validation', $config);
            self::$metrics['cache_hits']++;
        } else {
            $mock = self::createSecurityValidationFromScratch($config);
            self::$metrics['cache_misses']++;
            self::$metrics['new_creations']++;
        }
        
        return $mock;
    }
    
    /**
     * Get optimized link validation service mock with performance enhancements.
     */
    public static function createLinkValidationService(array $config = []): MockInterface
    {
        $cacheKey = self::generateCacheKey('link_validation', $config);
        
        // Try to get from pool first
        if ($pooledMock = self::getFromPool($cacheKey)) {
            self::$metrics['pool_reuses']++;
            return $pooledMock;
        }
        
        // Check template cache
        if (isset(self::$mockTemplates['link_validation'])) {
            $mock = self::createFromTemplate('link_validation', $config);
            self::$metrics['cache_hits']++;
        } else {
            $mock = self::createLinkValidationFromScratch($config);
            self::$metrics['cache_misses']++;
            self::$metrics['new_creations']++;
        }
        
        return $mock;
    }
    
    /**
     * Get optimized reporting service mock with streamlined creation.
     */
    public static function createReportingService(array $config = []): MockInterface
    {
        $cacheKey = self::generateCacheKey('reporting', $config);
        
        // Try to get from pool first
        if ($pooledMock = self::getFromPool($cacheKey)) {
            self::$metrics['pool_reuses']++;
            return $pooledMock;
        }
        
        // Check template cache
        if (isset(self::$mockTemplates['reporting'])) {
            $mock = self::createFromTemplate('reporting', $config);
            self::$metrics['cache_hits']++;
        } else {
            $mock = self::createReportingFromScratch($config);
            self::$metrics['cache_misses']++;
            self::$metrics['new_creations']++;
        }
        
        return $mock;
    }
    
    /**
     * Return mock to pool for efficient reuse.
     */
    public static function returnToPool(string $type, MockInterface $mock, array $config = []): void
    {
        $cacheKey = self::generateCacheKey($type, $config);
        
        if (!isset(self::$mockPool[$cacheKey])) {
            self::$mockPool[$cacheKey] = [];
        }
        
        // Limit pool size to prevent memory issues
        if (count(self::$mockPool[$cacheKey]) < 10) {
            // Reset mock state before returning to pool
            self::resetMockState($mock);
            self::$mockPool[$cacheKey][] = $mock;
        }
    }
    
    /**
     * Create batch of mocks efficiently for high-volume testing.
     */
    public static function createBatch(string $type, int $count, array $config = []): array
    {
        $mocks = [];
        
        // Use template-based creation for efficiency
        if (isset(self::$mockTemplates[$type])) {
            for ($i = 0; $i < $count; $i++) {
                $mocks[] = self::createFromTemplate($type, $config);
            }
        } else {
            // Create template from first mock, then use it for the rest
            $firstMock = self::createMockByType($type, $config);
            $mocks[] = $firstMock;
            
            // Create template for future use
            self::createTemplate($type, $config);
            
            // Create remaining mocks from template
            for ($i = 1; $i < $count; $i++) {
                $mocks[] = self::createFromTemplate($type, $config);
            }
        }
        
        return $mocks;
    }
    
    /**
     * Get performance metrics for optimization analysis.
     */
    public static function getMetrics(): array
    {
        $totalOperations = array_sum(self::$metrics);
        
        return [
            'cache_hit_rate' => $totalOperations > 0 ? (self::$metrics['cache_hits'] / $totalOperations) * 100 : 0,
            'pool_reuse_rate' => $totalOperations > 0 ? (self::$metrics['pool_reuses'] / $totalOperations) * 100 : 0,
            'total_operations' => $totalOperations,
            'cache_hits' => self::$metrics['cache_hits'],
            'cache_misses' => self::$metrics['cache_misses'],
            'pool_reuses' => self::$metrics['pool_reuses'],
            'new_creations' => self::$metrics['new_creations'],
            'template_count' => count(self::$mockTemplates),
            'pool_sizes' => array_map('count', self::$mockPool),
        ];
    }
    
    /**
     * Clear all caches and pools for memory optimization.
     */
    public static function clearCaches(): void
    {
        self::$mockPool = [];
        self::$configCache = [];
        
        // Keep templates but clear instances
        foreach (self::$mockTemplates as &$template) {
            unset($template['instance']);
        }
    }
    
    /**
     * Reset factory state completely.
     */
    public static function reset(): void
    {
        self::$mockTemplates = [];
        self::$mockPool = [];
        self::$configCache = [];
        self::$metrics = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'pool_reuses' => 0,
            'new_creations' => 0,
        ];
    }
    
    /**
     * Pre-warm commonly used mock templates for optimal performance.
     */
    private static function preWarmMockTemplates(): void
    {
        $commonConfigs = [
            'security_validation' => [
                'default' => [],
                'strict' => ['strict_mode' => true],
                'permissive' => ['strict_mode' => false],
            ],
            'link_validation' => [
                'default' => [],
                'fast' => ['timeout' => 5],
                'thorough' => ['timeout' => 30],
            ],
            'reporting' => [
                'default' => [],
                'json' => ['format' => 'json'],
                'html' => ['format' => 'html'],
            ],
        ];
        
        foreach ($commonConfigs as $type => $configs) {
            foreach ($configs as $configName => $config) {
                self::createTemplate($type, $config);
            }
        }
    }
    
    /**
     * Create mock template for efficient reuse.
     */
    private static function createTemplate(string $type, array $config): void
    {
        $templateKey = self::generateCacheKey($type, $config);
        
        if (isset(self::$mockTemplates[$templateKey])) {
            return; // Template already exists
        }
        
        $template = [
            'type' => $type,
            'config' => $config,
            'creation_method' => self::getCreationMethod($type),
            'expectations' => self::getDefaultExpectations($type, $config),
        ];
        
        self::$mockTemplates[$templateKey] = $template;
    }
    
    /**
     * Create mock from pre-configured template.
     */
    private static function createFromTemplate(string $type, array $config): MockInterface
    {
        $templateKey = self::generateCacheKey($type, $config);
        $template = self::$mockTemplates[$templateKey];
        
        $mock = Mockery::mock($template['creation_method']);
        
        // Apply cached expectations efficiently
        foreach ($template['expectations'] as $method => $expectation) {
            $mock->shouldReceive($method)
                ->andReturn($expectation['return'])
                ->byDefault();
        }
        
        return $mock;
    }
    
    /**
     * Get mock from pool if available.
     */
    private static function getFromPool(string $cacheKey): ?MockInterface
    {
        if (isset(self::$mockPool[$cacheKey]) && !empty(self::$mockPool[$cacheKey])) {
            return array_pop(self::$mockPool[$cacheKey]);
        }
        
        return null;
    }
    
    /**
     * Create security validation mock from scratch with optimizations.
     */
    private static function createSecurityValidationFromScratch(array $config): MockInterface
    {
        $mock = Mockery::mock(SecurityValidationInterface::class);
        
        // Optimized URL validation with batch processing
        $safeUrls = $config['safe_urls'] ?? self::getDefaultSafeUrls();
        $maliciousUrls = $config['malicious_urls'] ?? self::getDefaultMaliciousUrls();
        
        // Batch configure URL validations
        foreach ($safeUrls as $url) {
            $mock->shouldReceive('validateUrl')->with($url)->andReturn(true)->byDefault();
        }
        
        foreach ($maliciousUrls as $url) {
            $mock->shouldReceive('validateUrl')->with($url)->andReturn(false)->byDefault();
        }
        
        // Configure other methods with optimized defaults
        $mock->shouldReceive('isDomainBlocked')->andReturn(false)->byDefault();
        $mock->shouldReceive('isProtocolAllowed')->andReturn(true)->byDefault();
        $mock->shouldReceive('validateFileSize')->andReturn(true)->byDefault();
        $mock->shouldReceive('getSecurityConfig')->andReturn(self::getDefaultSecurityConfig())->byDefault();
        
        return $mock;
    }
    
    /**
     * Create link validation mock from scratch with performance optimizations.
     */
    private static function createLinkValidationFromScratch(array $config): MockInterface
    {
        $mock = Mockery::mock(LinkValidationInterface::class);
        
        // Optimized validation with cached results
        $mock->shouldReceive('validateLink')
            ->andReturnUsing(function (string $url, ValidationConfig $config) {
                return self::getCachedValidationResult($url);
            })
            ->byDefault();
        
        $mock->shouldReceive('validateLinks')
            ->andReturnUsing(function (array $urls, ValidationConfig $config) {
                $results = [];
                foreach ($urls as $url) {
                    $results[] = self::getCachedValidationResult($url);
                }
                return new ValidationResultCollection($results);
            })
            ->byDefault();
        
        // Configure other methods with optimized implementations
        $mock->shouldReceive('extractLinks')->andReturn([])->byDefault();
        $mock->shouldReceive('isValidUrl')->andReturn(true)->byDefault();
        $mock->shouldReceive('normalizeUrl')->andReturnUsing(fn($url) => trim($url))->byDefault();
        $mock->shouldReceive('getSupportedScopes')->andReturn([ValidationScope::ALL])->byDefault();
        $mock->shouldReceive('supportsScope')->andReturn(true)->byDefault();
        
        return $mock;
    }
    
    /**
     * Create reporting mock from scratch with streamlined setup.
     */
    private static function createReportingFromScratch(array $config): MockInterface
    {
        $mock = Mockery::mock(ReportingInterface::class);
        
        // Optimized report generation
        $mock->shouldReceive('generateReport')
            ->andReturnUsing(function (ValidationResultCollection $results, $format = null) {
                return "Optimized mock report with {$results->count()} results";
            })
            ->byDefault();
        
        $mock->shouldReceive('generateSummary')
            ->andReturnUsing(function (ValidationResultCollection $results) {
                return self::getCachedReportSummary($results->count());
            })
            ->byDefault();
        
        // Configure other methods efficiently
        $mock->shouldReceive('exportReport')->andReturn(true)->byDefault();
        $mock->shouldReceive('format')->andReturn('Formatted output')->byDefault();
        $mock->shouldReceive('getAvailableFormats')->andReturn(['json', 'html'])->byDefault();
        $mock->shouldReceive('supportsFormat')->andReturn(true)->byDefault();
        
        return $mock;
    }
    
    /**
     * Generate cache key for mock configuration.
     */
    private static function generateCacheKey(string $type, array $config): string
    {
        return $type . '_' . md5(serialize($config));
    }
    
    /**
     * Get creation method for mock type.
     */
    private static function getCreationMethod(string $type): string
    {
        return match ($type) {
            'security_validation' => SecurityValidationInterface::class,
            'link_validation' => LinkValidationInterface::class,
            'reporting' => ReportingInterface::class,
            default => throw new \InvalidArgumentException("Unknown mock type: {$type}"),
        };
    }
    
    /**
     * Get default expectations for mock type.
     */
    private static function getDefaultExpectations(string $type, array $config): array
    {
        return match ($type) {
            'security_validation' => [
                'validateUrl' => ['return' => true],
                'isDomainBlocked' => ['return' => false],
                'isProtocolAllowed' => ['return' => true],
            ],
            'link_validation' => [
                'isValidUrl' => ['return' => true],
                'supportsScope' => ['return' => true],
            ],
            'reporting' => [
                'supportsFormat' => ['return' => true],
                'exportReport' => ['return' => true],
            ],
            default => [],
        };
    }
    
    /**
     * Create mock by type with optimized routing.
     */
    private static function createMockByType(string $type, array $config): MockInterface
    {
        return match ($type) {
            'security_validation' => self::createSecurityValidationFromScratch($config),
            'link_validation' => self::createLinkValidationFromScratch($config),
            'reporting' => self::createReportingFromScratch($config),
            default => throw new \InvalidArgumentException("Unknown mock type: {$type}"),
        };
    }
    
    /**
     * Reset mock state for pool reuse.
     */
    private static function resetMockState(MockInterface $mock): void
    {
        // Reset any stateful properties
        // This is a simplified implementation
    }
    
    /**
     * Get cached validation result for performance.
     */
    private static function getCachedValidationResult(string $url): ValidationResult
    {
        $cacheKey = 'validation_result_' . md5($url);
        
        if (isset(self::$configCache[$cacheKey])) {
            return self::$configCache[$cacheKey];
        }
        
        // Create optimized validation result
        $status = str_contains($url, 'broken') ? LinkStatus::BROKEN : LinkStatus::VALID;
        $scope = str_starts_with($url, 'http') ? ValidationScope::EXTERNAL : ValidationScope::INTERNAL;
        
        $result = ValidationResult::create(
            url: $url,
            status: $status,
            message: $status === LinkStatus::BROKEN ? 'Connection failed' : '',
            scope: $scope,
            responseTime: 0.1 // Fast mock response
        );
        
        self::$configCache[$cacheKey] = $result;
        
        return $result;
    }
    
    /**
     * Get cached report summary for performance.
     */
    private static function getCachedReportSummary(int $totalLinks): mixed
    {
        $cacheKey = 'report_summary_' . $totalLinks;
        
        if (isset(self::$configCache[$cacheKey])) {
            return self::$configCache[$cacheKey];
        }
        
        // Create optimized report summary
        $summary = new \stdClass();
        $summary->total_links = $totalLinks;
        $summary->broken_links = 0;
        $summary->success_rate = 100;
        
        self::$configCache[$cacheKey] = $summary;
        
        return $summary;
    }
    
    /**
     * Get default safe URLs for security validation.
     */
    private static function getDefaultSafeUrls(): array
    {
        return [
            'https://example.com',
            'http://test.org',
            'https://github.com',
            'https://localhost:8080',
        ];
    }
    
    /**
     * Get default malicious URLs for security validation.
     */
    private static function getDefaultMaliciousUrls(): array
    {
        return [
            'https://malicious.com',
            'javascript:alert(1)',
            'ftp://dangerous.com',
        ];
    }
    
    /**
     * Get default security configuration.
     */
    private static function getDefaultSecurityConfig(): array
    {
        return [
            'blocked_domains' => [],
            'allowed_protocols' => ['http', 'https'],
            'max_file_size' => 50 * 1024 * 1024,
            'blocked_paths' => ['/etc/', '/var/', '/usr/', '/bin/'],
        ];
    }
}