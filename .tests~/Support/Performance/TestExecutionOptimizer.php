<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use Exception;
use Tests\Support\MockFactory;
use Tests\Support\HttpMockFactory;
use Tests\Support\MockLifecycleManager;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ResourceLimitSimulator;

/**
 * Advanced test execution optimizer for eliminating performance bottlenecks,
 * optimizing resource usage, and streamlining test execution patterns.
 * 
 * Provides intelligent caching, resource pooling, parallel execution optimization,
 * and automated performance tuning for maximum test suite efficiency.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class TestExecutionOptimizer
{
    /**
     * Cached test resources for reuse.
     * 
     * @var array<string, mixed>
     */
    private static array $resourceCache = [];
    
    /**
     * Mock object pool for efficient reuse.
     * 
     * @var array<string, array<mixed>>
     */
    private static array $mockPool = [];
    
    /**
     * Optimized test data cache.
     * 
     * @var array<string, mixed>
     */
    private static array $testDataCache = [];
    
    /**
     * Performance optimization settings.
     * 
     * @var array<string, mixed>
     */
    private static array $optimizationConfig = [
        'enable_mock_pooling' => true,
        'enable_data_caching' => true,
        'enable_resource_reuse' => true,
        'enable_lazy_loading' => true,
        'enable_batch_operations' => true,
        'cache_size_limit' => 100 * 1024 * 1024, // 100MB
        'mock_pool_size' => 50,
        'parallel_execution_threshold' => 10,
    ];
    
    /**
     * Execution statistics for optimization analysis.
     * 
     * @var array<string, mixed>
     */
    private static array $executionStats = [
        'cache_hits' => 0,
        'cache_misses' => 0,
        'mock_reuses' => 0,
        'mock_creations' => 0,
        'optimizations_applied' => [],
    ];
    
    /**
     * Initialize the test execution optimizer.
     */
    public static function initialize(): void
    {
        self::$resourceCache = [];
        self::$mockPool = [];
        self::$testDataCache = [];
        self::$executionStats = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'mock_reuses' => 0,
            'mock_creations' => 0,
            'optimizations_applied' => [],
        ];
        
        // Pre-warm commonly used resources
        self::preWarmResources();
    }
    
    /**
     * Optimize mock creation with intelligent pooling and reuse.
     */
    public static function getOptimizedMock(string $type, array $config = []): mixed
    {
        $cacheKey = self::generateMockCacheKey($type, $config);
        
        // Check mock pool first
        if (self::$optimizationConfig['enable_mock_pooling'] && isset(self::$mockPool[$cacheKey])) {
            $availableMocks = self::$mockPool[$cacheKey];
            if (!empty($availableMocks)) {
                $mock = array_pop($availableMocks);
                self::$mockPool[$cacheKey] = $availableMocks;
                self::$executionStats['mock_reuses']++;
                return $mock;
            }
        }
        
        // Create new mock if not available in pool
        $mock = self::createOptimizedMock($type, $config);
        self::$executionStats['mock_creations']++;
        
        return $mock;
    }
    
    /**
     * Return mock to pool for reuse.
     */
    public static function returnMockToPool(string $type, mixed $mock, array $config = []): void
    {
        if (!self::$optimizationConfig['enable_mock_pooling']) {
            return;
        }
        
        $cacheKey = self::generateMockCacheKey($type, $config);
        
        if (!isset(self::$mockPool[$cacheKey])) {
            self::$mockPool[$cacheKey] = [];
        }
        
        // Limit pool size to prevent memory issues
        if (count(self::$mockPool[$cacheKey]) < self::$optimizationConfig['mock_pool_size']) {
            // Reset mock state before returning to pool
            self::resetMockState($mock);
            self::$mockPool[$cacheKey][] = $mock;
        }
    }
    
    /**
     * Get optimized test data with intelligent caching.
     */
    public static function getOptimizedTestData(string $type, array $options = []): mixed
    {
        $cacheKey = self::generateDataCacheKey($type, $options);
        
        // Check cache first
        if (self::$optimizationConfig['enable_data_caching'] && isset(self::$testDataCache[$cacheKey])) {
            self::$executionStats['cache_hits']++;
            return self::$testDataCache[$cacheKey];
        }
        
        // Generate data if not cached
        $data = self::generateOptimizedTestData($type, $options);
        
        // Cache the data if caching is enabled and within size limits
        if (self::$optimizationConfig['enable_data_caching'] && self::getCacheSize() < self::$optimizationConfig['cache_size_limit']) {
            self::$testDataCache[$cacheKey] = $data;
        }
        
        self::$executionStats['cache_misses']++;
        return $data;
    }
    
    /**
     * Optimize file operations with batching and caching.
     */
    public static function optimizeFileOperations(array $operations): array
    {
        $results = [];
        $batchedOperations = [];
        
        // Group operations by type for batching
        foreach ($operations as $operation) {
            $type = $operation['type'];
            if (!isset($batchedOperations[$type])) {
                $batchedOperations[$type] = [];
            }
            $batchedOperations[$type][] = $operation;
        }
        
        // Execute batched operations
        foreach ($batchedOperations as $type => $ops) {
            switch ($type) {
                case 'file_read':
                    $results = array_merge($results, self::batchFileReads($ops));
                    break;
                case 'file_write':
                    $results = array_merge($results, self::batchFileWrites($ops));
                    break;
                case 'file_delete':
                    $results = array_merge($results, self::batchFileDeletes($ops));
                    break;
                default:
                    // Execute individual operations
                    foreach ($ops as $op) {
                        $results[] = self::executeFileOperation($op);
                    }
            }
        }
        
        self::$executionStats['optimizations_applied'][] = 'file_operation_batching';
        
        return $results;
    }
    
    /**
     * Optimize HTTP operations with connection pooling and request batching.
     */
    public static function optimizeHttpOperations(array $requests): array
    {
        // Group requests by domain for connection reuse
        $groupedRequests = [];
        foreach ($requests as $request) {
            $domain = parse_url($request['url'], PHP_URL_HOST);
            if (!isset($groupedRequests[$domain])) {
                $groupedRequests[$domain] = [];
            }
            $groupedRequests[$domain][] = $request;
        }
        
        $results = [];
        
        // Process requests by domain with optimized HTTP client
        foreach ($groupedRequests as $domain => $domainRequests) {
            $client = self::getOptimizedHttpClient($domain);
            
            // Batch requests for the same domain
            $batchResults = self::executeBatchedHttpRequests($client, $domainRequests);
            $results = array_merge($results, $batchResults);
        }
        
        self::$executionStats['optimizations_applied'][] = 'http_operation_optimization';
        
        return $results;
    }
    
    /**
     * Optimize memory usage with intelligent cleanup and garbage collection.
     */
    public static function optimizeMemoryUsage(): void
    {
        // Clear expired cache entries
        self::clearExpiredCacheEntries();
        
        // Trim mock pools to optimal size
        self::trimMockPools();
        
        // Force garbage collection if memory usage is high
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimit();
        
        if ($memoryLimit > 0 && ($memoryUsage / $memoryLimit) > 0.7) {
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
            self::$executionStats['optimizations_applied'][] = 'memory_cleanup';
        }
        
        // Clear large cached items if memory pressure is high
        if ($memoryLimit > 0 && ($memoryUsage / $memoryLimit) > 0.8) {
            self::clearLargeCacheItems();
            self::$executionStats['optimizations_applied'][] = 'large_cache_cleanup';
        }
    }
    
    /**
     * Optimize parallel test execution.
     */
    public static function optimizeParallelExecution(array $tests): array
    {
        if (count($tests) < self::$optimizationConfig['parallel_execution_threshold']) {
            return $tests; // Not worth parallelizing
        }
        
        // Analyze test dependencies and resource requirements
        $testAnalysis = self::analyzeTestDependencies($tests);
        
        // Group tests for optimal parallel execution
        $testGroups = self::groupTestsForParallelExecution($testAnalysis);
        
        // Optimize resource allocation for each group
        foreach ($testGroups as &$group) {
            $group['resources'] = self::optimizeResourceAllocation($group['tests']);
        }
        
        self::$executionStats['optimizations_applied'][] = 'parallel_execution_optimization';
        
        return $testGroups;
    }
    
    /**
     * Get execution statistics and optimization metrics.
     */
    public static function getExecutionStats(): array
    {
        $cacheHitRate = self::$executionStats['cache_hits'] + self::$executionStats['cache_misses'] > 0
            ? (self::$executionStats['cache_hits'] / (self::$executionStats['cache_hits'] + self::$executionStats['cache_misses'])) * 100
            : 0;
        
        $mockReuseRate = self::$executionStats['mock_reuses'] + self::$executionStats['mock_creations'] > 0
            ? (self::$executionStats['mock_reuses'] / (self::$executionStats['mock_reuses'] + self::$executionStats['mock_creations'])) * 100
            : 0;
        
        return [
            'cache_hit_rate' => round($cacheHitRate, 2),
            'mock_reuse_rate' => round($mockReuseRate, 2),
            'total_cache_hits' => self::$executionStats['cache_hits'],
            'total_cache_misses' => self::$executionStats['cache_misses'],
            'total_mock_reuses' => self::$executionStats['mock_reuses'],
            'total_mock_creations' => self::$executionStats['mock_creations'],
            'optimizations_applied' => self::$executionStats['optimizations_applied'],
            'cache_size_mb' => round(self::getCacheSize() / 1024 / 1024, 2),
            'mock_pool_sizes' => array_map('count', self::$mockPool),
        ];
    }
    
    /**
     * Configure optimization settings.
     */
    public static function configure(array $config): void
    {
        self::$optimizationConfig = array_merge(self::$optimizationConfig, $config);
    }
    
    /**
     * Reset optimizer state.
     */
    public static function reset(): void
    {
        self::$resourceCache = [];
        self::$mockPool = [];
        self::$testDataCache = [];
        self::$executionStats = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'mock_reuses' => 0,
            'mock_creations' => 0,
            'optimizations_applied' => [],
        ];
    }
    
    /**
     * Pre-warm commonly used resources.
     */
    private static function preWarmResources(): void
    {
        // Pre-create commonly used mocks
        $commonMockTypes = ['security_validation', 'link_validation', 'reporting'];
        
        foreach ($commonMockTypes as $type) {
            for ($i = 0; $i < 3; $i++) { // Pre-create 3 of each type
                $mock = self::createOptimizedMock($type, []);
                self::returnMockToPool($type, $mock, []);
            }
        }
        
        // Pre-generate commonly used test data
        $commonDataTypes = ['valid_urls', 'invalid_urls', 'test_configurations'];
        
        foreach ($commonDataTypes as $type) {
            self::getOptimizedTestData($type, ['size' => 'small']);
        }
        
        self::$executionStats['optimizations_applied'][] = 'resource_prewarming';
    }
    
    /**
     * Create optimized mock with performance enhancements.
     */
    private static function createOptimizedMock(string $type, array $config): mixed
    {
        return match ($type) {
            'security_validation' => MockFactory::createSecurityValidationService($config),
            'link_validation' => MockFactory::createLinkValidationService($config),
            'reporting' => MockFactory::createReportingService($config),
            'http_client' => HttpMockFactory::createSuccessfulValidationClient(),
            default => throw new Exception("Unknown mock type: {$type}"),
        };
    }
    
    /**
     * Generate optimized test data with performance considerations.
     */
    private static function generateOptimizedTestData(string $type, array $options): mixed
    {
        return match ($type) {
            'valid_urls' => self::generateCachedUrls('valid', $options),
            'invalid_urls' => self::generateCachedUrls('invalid', $options),
            'extreme_urls' => EdgeCaseDataGenerator::generateExtremeUrls($options),
            'test_configurations' => self::generateCachedConfigurations($options),
            'large_datasets' => EdgeCaseDataGenerator::generateLargeDataSets($options),
            'concurrency_scenarios' => EdgeCaseDataGenerator::generateConcurrencyScenarios($options),
            default => throw new Exception("Unknown test data type: {$type}"),
        };
    }
    
    /**
     * Generate cached URLs with intelligent deduplication.
     */
    private static function generateCachedUrls(string $type, array $options): array
    {
        $size = $options['size'] ?? 'medium';
        $count = match ($size) {
            'small' => 10,
            'medium' => 50,
            'large' => 200,
            default => 50,
        };
        
        $urls = [];
        $baseUrls = match ($type) {
            'valid' => [
                'https://example.com',
                'https://test.org',
                'https://github.com',
                'https://stackoverflow.com',
            ],
            'invalid' => [
                'not-a-url',
                'invalid://url',
                'malformed-url',
                '',
            ],
            default => ['https://example.com'],
        };
        
        for ($i = 0; $i < $count; $i++) {
            $baseUrl = $baseUrls[$i % count($baseUrls)];
            $urls[] = $baseUrl . ($i > 0 ? "/path{$i}" : '');
        }
        
        return $urls;
    }
    
    /**
     * Generate cached configurations with optimization.
     */
    private static function generateCachedConfigurations(array $options): array
    {
        $type = $options['type'] ?? 'default';
        
        return match ($type) {
            'performance' => [
                'timeout' => 5,
                'concurrent_requests' => 20,
                'max_redirects' => 3,
                'retry_attempts' => 1,
            ],
            'strict' => [
                'timeout' => 30,
                'concurrent_requests' => 5,
                'max_redirects' => 5,
                'retry_attempts' => 3,
            ],
            default => [
                'timeout' => 10,
                'concurrent_requests' => 10,
                'max_redirects' => 3,
                'retry_attempts' => 2,
            ],
        };
    }
    
    /**
     * Batch file read operations for efficiency.
     */
    private static function batchFileReads(array $operations): array
    {
        $results = [];
        
        foreach ($operations as $operation) {
            $filePath = $operation['path'];
            
            // Check if file content is already cached
            $cacheKey = 'file_content_' . md5($filePath);
            if (isset(self::$resourceCache[$cacheKey])) {
                $results[] = ['success' => true, 'content' => self::$resourceCache[$cacheKey]];
                continue;
            }
            
            // Read file and cache content
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                self::$resourceCache[$cacheKey] = $content;
                $results[] = ['success' => true, 'content' => $content];
            } else {
                $results[] = ['success' => false, 'error' => 'File not found'];
            }
        }
        
        return $results;
    }
    
    /**
     * Batch file write operations for efficiency.
     */
    private static function batchFileWrites(array $operations): array
    {
        $results = [];
        
        foreach ($operations as $operation) {
            $filePath = $operation['path'];
            $content = $operation['content'];
            
            $success = file_put_contents($filePath, $content) !== false;
            $results[] = ['success' => $success];
            
            // Invalidate cache for this file
            $cacheKey = 'file_content_' . md5($filePath);
            unset(self::$resourceCache[$cacheKey]);
        }
        
        return $results;
    }
    
    /**
     * Batch file delete operations for efficiency.
     */
    private static function batchFileDeletes(array $operations): array
    {
        $results = [];
        
        foreach ($operations as $operation) {
            $filePath = $operation['path'];
            
            $success = file_exists($filePath) && unlink($filePath);
            $results[] = ['success' => $success];
            
            // Invalidate cache for this file
            $cacheKey = 'file_content_' . md5($filePath);
            unset(self::$resourceCache[$cacheKey]);
        }
        
        return $results;
    }
    
    /**
     * Execute individual file operation.
     */
    private static function executeFileOperation(array $operation): array
    {
        $type = $operation['type'];
        $path = $operation['path'];
        
        return match ($type) {
            'file_read' => self::batchFileReads([$operation])[0],
            'file_write' => self::batchFileWrites([$operation])[0],
            'file_delete' => self::batchFileDeletes([$operation])[0],
            default => ['success' => false, 'error' => 'Unknown operation type'],
        };
    }
    
    /**
     * Get optimized HTTP client with connection pooling.
     */
    private static function getOptimizedHttpClient(string $domain): mixed
    {
        $cacheKey = 'http_client_' . $domain;
        
        if (isset(self::$resourceCache[$cacheKey])) {
            return self::$resourceCache[$cacheKey];
        }
        
        // Create optimized HTTP client
        $client = HttpMockFactory::createSuccessfulValidationClient();
        self::$resourceCache[$cacheKey] = $client;
        
        return $client;
    }
    
    /**
     * Execute batched HTTP requests efficiently.
     */
    private static function executeBatchedHttpRequests(mixed $client, array $requests): array
    {
        $results = [];
        
        // This is a simplified implementation
        // In a real scenario, you would use concurrent HTTP requests
        foreach ($requests as $request) {
            $results[] = [
                'url' => $request['url'],
                'status' => 200,
                'success' => true,
            ];
        }
        
        return $results;
    }
    
    /**
     * Generate cache key for mocks.
     */
    private static function generateMockCacheKey(string $type, array $config): string
    {
        return $type . '_' . md5(serialize($config));
    }
    
    /**
     * Generate cache key for test data.
     */
    private static function generateDataCacheKey(string $type, array $options): string
    {
        return $type . '_' . md5(serialize($options));
    }
    
    /**
     * Reset mock state for reuse.
     */
    private static function resetMockState(mixed $mock): void
    {
        // This would reset the mock to a clean state
        // Implementation depends on the mocking framework
    }
    
    /**
     * Get current cache size in bytes.
     */
    private static function getCacheSize(): int
    {
        return strlen(serialize(self::$testDataCache)) + strlen(serialize(self::$resourceCache));
    }
    
    /**
     * Clear expired cache entries.
     */
    private static function clearExpiredCacheEntries(): void
    {
        // Simple implementation - in practice, you'd track timestamps
        if (self::getCacheSize() > self::$optimizationConfig['cache_size_limit']) {
            // Clear oldest entries (simplified)
            $keysToRemove = array_slice(array_keys(self::$testDataCache), 0, count(self::$testDataCache) / 4);
            foreach ($keysToRemove as $key) {
                unset(self::$testDataCache[$key]);
            }
        }
    }
    
    /**
     * Trim mock pools to optimal size.
     */
    private static function trimMockPools(): void
    {
        foreach (self::$mockPool as $type => &$pool) {
            if (count($pool) > self::$optimizationConfig['mock_pool_size']) {
                $pool = array_slice($pool, 0, self::$optimizationConfig['mock_pool_size']);
            }
        }
    }
    
    /**
     * Clear large cache items to free memory.
     */
    private static function clearLargeCacheItems(): void
    {
        foreach (self::$testDataCache as $key => $data) {
            if (strlen(serialize($data)) > 1024 * 1024) { // 1MB
                unset(self::$testDataCache[$key]);
            }
        }
    }
    
    /**
     * Analyze test dependencies for parallel execution.
     */
    private static function analyzeTestDependencies(array $tests): array
    {
        $analysis = [];
        
        foreach ($tests as $test) {
            $analysis[$test] = [
                'dependencies' => [], // Would analyze actual dependencies
                'resource_requirements' => self::estimateResourceRequirements($test),
                'execution_time_estimate' => self::estimateExecutionTime($test),
            ];
        }
        
        return $analysis;
    }
    
    /**
     * Group tests for optimal parallel execution.
     */
    private static function groupTestsForParallelExecution(array $testAnalysis): array
    {
        // Simplified grouping algorithm
        $groups = [];
        $currentGroup = [];
        $currentGroupWeight = 0;
        $maxGroupWeight = 100; // Arbitrary weight limit
        
        foreach ($testAnalysis as $test => $analysis) {
            $weight = $analysis['execution_time_estimate'] + $analysis['resource_requirements']['memory_mb'];
            
            if ($currentGroupWeight + $weight > $maxGroupWeight && !empty($currentGroup)) {
                $groups[] = ['tests' => $currentGroup, 'weight' => $currentGroupWeight];
                $currentGroup = [];
                $currentGroupWeight = 0;
            }
            
            $currentGroup[] = $test;
            $currentGroupWeight += $weight;
        }
        
        if (!empty($currentGroup)) {
            $groups[] = ['tests' => $currentGroup, 'weight' => $currentGroupWeight];
        }
        
        return $groups;
    }
    
    /**
     * Optimize resource allocation for test group.
     */
    private static function optimizeResourceAllocation(array $tests): array
    {
        return [
            'memory_limit' => '256M',
            'cpu_cores' => 2,
            'concurrent_processes' => min(4, count($tests)),
        ];
    }
    
    /**
     * Estimate resource requirements for a test.
     */
    private static function estimateResourceRequirements(string $test): array
    {
        // Simplified estimation based on test name patterns
        $memoryMb = 10; // Base memory
        $cpuIntensive = false;
        
        if (str_contains($test, 'Performance') || str_contains($test, 'Load')) {
            $memoryMb = 50;
            $cpuIntensive = true;
        } elseif (str_contains($test, 'EdgeCase') || str_contains($test, 'Large')) {
            $memoryMb = 30;
        }
        
        return [
            'memory_mb' => $memoryMb,
            'cpu_intensive' => $cpuIntensive,
            'io_intensive' => str_contains($test, 'File') || str_contains($test, 'IO'),
            'network_intensive' => str_contains($test, 'Http') || str_contains($test, 'Network'),
        ];
    }
    
    /**
     * Estimate execution time for a test.
     */
    private static function estimateExecutionTime(string $test): float
    {
        // Simplified estimation in seconds
        if (str_contains($test, 'Performance') || str_contains($test, 'Load')) {
            return 10.0;
        } elseif (str_contains($test, 'Integration') || str_contains($test, 'E2E')) {
            return 5.0;
        } else {
            return 1.0;
        }
    }
    
    /**
     * Get memory limit in bytes.
     */
    private static function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === false || $memoryLimit === '' || $memoryLimit === '-1') {
            return 0;
        }
        
        $value = (int)$memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}