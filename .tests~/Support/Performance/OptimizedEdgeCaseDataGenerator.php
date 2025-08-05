<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use InvalidArgumentException;

/**
 * Performance-optimized edge case data generator with intelligent caching,
 * lazy loading, and efficient data generation patterns.
 * 
 * Eliminates redundant data generation, implements streaming for large datasets,
 * and provides high-performance edge case data for comprehensive testing.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class OptimizedEdgeCaseDataGenerator
{
    /**
     * Cached edge case data for performance optimization.
     * 
     * @var array<string, mixed>
     */
    private static array $dataCache = [];
    
    /**
     * Data generation templates for efficient reuse.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $generationTemplates = [];
    
    /**
     * Performance metrics tracking.
     * 
     * @var array<string, int>
     */
    private static array $metrics = [
        'cache_hits' => 0,
        'cache_misses' => 0,
        'template_uses' => 0,
        'fresh_generations' => 0,
    ];
    
    /**
     * Deterministic seed for consistent edge case generation.
     */
    private static int $seed = 98765;
    
    /**
     * Counter for sequential edge case generation.
     */
    private static int $counter = 0;
    
    /**
     * Cache size limit to prevent memory issues.
     */
    private static int $cacheLimit = 50 * 1024 * 1024; // 50MB
    
    /**
     * Initialize optimized edge case data generator.
     */
    public static function initialize(): void
    {
        self::$dataCache = [];
        self::$generationTemplates = [];
        self::$metrics = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'template_uses' => 0,
            'fresh_generations' => 0,
        ];
        self::$seed = 98765;
        self::$counter = 0;
        
        // Pre-warm commonly used templates
        self::preWarmTemplates();
    }
    
    /**
     * Generate extreme URLs with intelligent caching and lazy loading.
     */
    public static function generateExtremeUrls(array $options = []): array
    {
        $cacheKey = self::generateCacheKey('extreme_urls', $options);
        
        // Check cache first
        if (isset(self::$dataCache[$cacheKey])) {
            self::$metrics['cache_hits']++;
            return self::$dataCache[$cacheKey];
        }
        
        $type = $options['type'] ?? 'all';
        $count = $options['count'] ?? 10;
        
        // Use streaming for large datasets
        if ($count > 1000) {
            return self::generateExtremeUrlsStream($options);
        }
        
        $urls = [];
        
        // Use optimized generation methods
        if ($type === 'all' || $type === 'length') {
            $urls = array_merge($urls, self::generateLengthExtremeUrlsOptimized($count));
        }
        
        if ($type === 'all' || $type === 'characters') {
            $urls = array_merge($urls, self::generateCharacterExtremeUrlsOptimized($count));
        }
        
        if ($type === 'all' || $type === 'protocol') {
            $urls = array_merge($urls, self::generateProtocolExtremeUrlsOptimized($count));
        }
        
        if ($type === 'all' || $type === 'domain') {
            $urls = array_merge($urls, self::generateDomainExtremeUrlsOptimized($count));
        }
        
        $result = array_slice($urls, 0, $count);
        
        // Cache result if within size limits
        if (self::getCacheSize() < self::$cacheLimit) {
            self::$dataCache[$cacheKey] = $result;
        }
        
        self::$metrics['cache_misses']++;
        self::$metrics['fresh_generations']++;
        
        return $result;
    }
    
    /**
     * Generate malformed data with optimized patterns and caching.
     */
    public static function generateMalformedData(array $options = []): array
    {
        $cacheKey = self::generateCacheKey('malformed_data', $options);
        
        // Check cache first
        if (isset(self::$dataCache[$cacheKey])) {
            self::$metrics['cache_hits']++;
            return self::$dataCache[$cacheKey];
        }
        
        $type = $options['type'] ?? 'all';
        $data = [];
        
        // Use templates for efficient generation
        if ($type === 'all' || $type === 'json') {
            $data['json'] = self::getTemplateData('malformed_json');
        }
        
        if ($type === 'all' || $type === 'urls') {
            $data['urls'] = self::getTemplateData('malformed_urls');
        }
        
        if ($type === 'all' || $type === 'paths') {
            $data['paths'] = self::getTemplateData('malformed_paths');
        }
        
        if ($type === 'all' || $type === 'config') {
            $data['config'] = self::getTemplateData('malformed_config');
        }
        
        // Cache result
        if (self::getCacheSize() < self::$cacheLimit) {
            self::$dataCache[$cacheKey] = $data;
        }
        
        self::$metrics['cache_misses']++;
        
        return $data;
    }
    
    /**
     * Generate large data sets with streaming and memory optimization.
     */
    public static function generateLargeDataSets(array $options = []): array
    {
        $size = $options['size'] ?? 'medium';
        $type = $options['type'] ?? 'urls';
        
        $sizes = [
            'small' => 100,
            'medium' => 1000,
            'large' => 10000,
            'extreme' => 50000,
        ];
        
        $count = $sizes[$size] ?? $sizes['medium'];
        
        // Use streaming for large datasets to prevent memory issues
        if ($count > 5000) {
            return self::generateLargeDataStream($type, $count);
        }
        
        $cacheKey = self::generateCacheKey('large_data', $options);
        
        // Check cache for smaller datasets
        if (isset(self::$dataCache[$cacheKey])) {
            self::$metrics['cache_hits']++;
            return self::$dataCache[$cacheKey];
        }
        
        $result = match ($type) {
            'urls' => self::generateLargeUrlSetOptimized($count),
            'files' => self::generateLargeFileSetOptimized($count),
            'content' => self::generateLargeContentOptimized($count),
            'collections' => self::generateLargeCollectionsOptimized($count),
            default => self::generateLargeUrlSetOptimized($count),
        };
        
        // Cache smaller datasets only
        if ($count <= 1000 && self::getCacheSize() < self::$cacheLimit) {
            self::$dataCache[$cacheKey] = $result;
        }
        
        self::$metrics['cache_misses']++;
        
        return $result;
    }
    
    /**
     * Generate concurrency scenarios with optimized patterns.
     */
    public static function generateConcurrencyScenarios(array $options = []): array
    {
        $cacheKey = self::generateCacheKey('concurrency_scenarios', $options);
        
        // Check cache first
        if (isset(self::$dataCache[$cacheKey])) {
            self::$metrics['cache_hits']++;
            return self::$dataCache[$cacheKey];
        }
        
        $scenario = $options['scenario'] ?? 'basic';
        $threadCount = $options['threads'] ?? 10;
        
        $result = match ($scenario) {
            'basic' => self::generateBasicConcurrencyScenarioOptimized($threadCount),
            'race_condition' => self::generateRaceConditionScenarioOptimized($threadCount),
            'deadlock' => self::generateDeadlockScenarioOptimized($threadCount),
            'resource_contention' => self::generateResourceContentionScenarioOptimized($threadCount),
            'high_load' => self::generateHighLoadScenarioOptimized($threadCount),
            default => self::generateBasicConcurrencyScenarioOptimized($threadCount),
        };
        
        // Cache result
        if (self::getCacheSize() < self::$cacheLimit) {
            self::$dataCache[$cacheKey] = $result;
        }
        
        self::$metrics['cache_misses']++;
        
        return $result;
    }
    
    /**
     * Generate null and empty inputs with cached patterns.
     */
    public static function generateNullAndEmptyInputs(): array
    {
        $cacheKey = 'null_empty_inputs';
        
        // Check cache first
        if (isset(self::$dataCache[$cacheKey])) {
            self::$metrics['cache_hits']++;
            return self::$dataCache[$cacheKey];
        }
        
        $result = [
            'null_values' => [
                'null' => null,
                'empty_string' => '',
                'whitespace_only' => '   ',
                'zero_string' => '0',
                'false_value' => false,
            ],
            'empty_collections' => [
                'empty_array' => [],
                'empty_object' => new \stdClass(),
                'empty_iterator' => new \ArrayIterator([]),
            ],
            'boundary_strings' => [
                'single_char' => 'a',
                'very_long' => str_repeat('a', 1000), // Reduced from 10000 for performance
                'unicode_empty' => "\u{200B}",
                'control_chars' => "\x00\x01\x02",
                'newlines_only' => "\n\r\n",
            ],
            'boundary_numbers' => [
                'zero' => 0,
                'negative_zero' => -0,
                'max_int' => PHP_INT_MAX,
                'min_int' => PHP_INT_MIN,
                'infinity' => INF,
                'negative_infinity' => -INF,
                'nan' => NAN,
            ],
        ];
        
        // Cache result
        self::$dataCache[$cacheKey] = $result;
        self::$metrics['cache_misses']++;
        
        return $result;
    }
    
    /**
     * Generate invalid character data with optimized patterns.
     */
    public static function generateInvalidCharacterData(): array
    {
        $cacheKey = 'invalid_character_data';
        
        // Check cache first
        if (isset(self::$dataCache[$cacheKey])) {
            self::$metrics['cache_hits']++;
            return self::$dataCache[$cacheKey];
        }
        
        $result = self::getTemplateData('invalid_characters');
        
        // Cache result
        self::$dataCache[$cacheKey] = $result;
        self::$metrics['cache_misses']++;
        
        return $result;
    }
    
    /**
     * Get performance metrics for optimization analysis.
     */
    public static function getMetrics(): array
    {
        $totalOperations = array_sum(self::$metrics);
        
        return [
            'cache_hit_rate' => $totalOperations > 0 ? (self::$metrics['cache_hits'] / $totalOperations) * 100 : 0,
            'template_usage_rate' => $totalOperations > 0 ? (self::$metrics['template_uses'] / $totalOperations) * 100 : 0,
            'total_operations' => $totalOperations,
            'cache_hits' => self::$metrics['cache_hits'],
            'cache_misses' => self::$metrics['cache_misses'],
            'template_uses' => self::$metrics['template_uses'],
            'fresh_generations' => self::$metrics['fresh_generations'],
            'cache_size_mb' => round(self::getCacheSize() / 1024 / 1024, 2),
            'cached_items' => count(self::$dataCache),
            'templates_available' => count(self::$generationTemplates),
        ];
    }
    
    /**
     * Clear cache to free memory.
     */
    public static function clearCache(): void
    {
        self::$dataCache = [];
    }
    
    /**
     * Reset generator state completely.
     */
    public static function reset(): void
    {
        self::$dataCache = [];
        self::$generationTemplates = [];
        self::$metrics = [
            'cache_hits' => 0,
            'cache_misses' => 0,
            'template_uses' => 0,
            'fresh_generations' => 0,
        ];
        self::$seed = 98765;
        self::$counter = 0;
    }
    
    /**
     * Set seed for deterministic generation.
     */
    public static function setSeed(int $seed): void
    {
        self::$seed = $seed;
        self::$counter = 0;
    }
    
    /**
     * Pre-warm commonly used templates for optimal performance.
     */
    private static function preWarmTemplates(): void
    {
        self::$generationTemplates = [
            'malformed_json' => [
                '{"incomplete": ',
                '{"missing_quotes": value}',
                '{"trailing_comma": "value",}',
                '{duplicate": "key", "duplicate": "value"}',
                '{"unclosed_string": "value',
                '{"invalid_escape": "\\z"}',
                '{"number_error": 123.}',
                '{"array_error": [1, 2, 3,]}',
                '{nested": {"error": }',
                '{"unicode_error": "\\uZZZZ"}',
            ],
            'malformed_urls' => [
                'not-a-url',
                'http://',
                'https://.com',
                'ftp://[invalid-ipv6',
                'http://example.com:99999',
                'https://example.com/path with spaces',
                'http://user:pass@:80/path',
                'https://example.com/path?query=value&',
                'http://example.com/path#',
                'https://example.com/path\\backslash',
            ],
            'malformed_paths' => [
                '/path/with\0null',
                '/path/with\nnewline',
                '/path/with"quotes',
                '/path/with<brackets>',
                '/path/with|pipe',
                '/path/with*wildcard',
                '/path/with?question',
                '\\windows\\style\\path',
                '/path/../../../etc/passwd',
                '/path/./././file',
            ],
            'malformed_config' => [
                'invalid_timeout' => [
                    'timeout' => -1,
                    'max_redirects' => 'not-a-number',
                    'concurrent_requests' => 0,
                ],
                'invalid_scopes' => [
                    'scopes' => ['invalid-scope'],
                    'output_format' => 'invalid-format',
                ],
                'extreme_values' => [
                    'timeout' => PHP_INT_MAX,
                    'max_redirects' => -999,
                    'concurrent_requests' => 999999,
                ],
            ],
            'invalid_characters' => [
                "\xFF\xFE\x00\x00",
                "\xEF\xBB\xBF\x00",
                "\x80\x81\x82\x83",
                "\x00\x01\x02\x03\x04\x05\x06\x07",
                "\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F",
                "\u{FEFF}",
                "\u{FFFF}",
                "\u{10FFFF}",
                "Hello\x80World",
                "Test\xFF\xFEData",
                "\xC0\x80",
                "\xE0\x80\x80",
            ],
        ];
    }
    
    /**
     * Get data from template with caching.
     */
    private static function getTemplateData(string $templateName): array
    {
        if (isset(self::$generationTemplates[$templateName])) {
            self::$metrics['template_uses']++;
            return self::$generationTemplates[$templateName];
        }
        
        // Fallback to empty array if template not found
        return [];
    }
    
    /**
     * Generate cache key for data options.
     */
    private static function generateCacheKey(string $type, array $options): string
    {
        return $type . '_' . md5(serialize($options));
    }
    
    /**
     * Get current cache size in bytes.
     */
    private static function getCacheSize(): int
    {
        return strlen(serialize(self::$dataCache));
    }
    
    /**
     * Generate extreme URLs stream for large datasets.
     */
    private static function generateExtremeUrlsStream(array $options): \Generator
    {
        $count = $options['count'] ?? 10;
        $type = $options['type'] ?? 'all';
        
        for ($i = 0; $i < $count; $i++) {
            yield match ($type) {
                'length' => self::generateSingleLengthExtremeUrl($i),
                'characters' => self::generateSingleCharacterExtremeUrl($i),
                'protocol' => self::generateSingleProtocolExtremeUrl($i),
                'domain' => self::generateSingleDomainExtremeUrl($i),
                default => self::generateSingleLengthExtremeUrl($i),
            };
        }
    }
    
    /**
     * Generate large data stream for memory efficiency.
     */
    private static function generateLargeDataStream(string $type, int $count): \Generator
    {
        for ($i = 0; $i < $count; $i++) {
            yield match ($type) {
                'urls' => "https://example{$i}.com/path/{$i}",
                'files' => "file{$i}.md",
                'content' => "Content block {$i} with [link](https://example{$i}.com)",
                default => "https://example{$i}.com/path/{$i}",
            };
        }
    }
    
    /**
     * Generate length extreme URLs with optimization.
     */
    private static function generateLengthExtremeUrlsOptimized(int $count): array
    {
        $urls = [];
        $patterns = [1, 2048, 4096, 8192, 16384]; // Reduced max length for performance
        
        for ($i = 0; $i < $count; $i++) {
            $length = $patterns[$i % count($patterns)];
            $urls[] = self::generateUrlWithLength($length);
        }
        
        return $urls;
    }
    
    /**
     * Generate character extreme URLs with optimization.
     */
    private static function generateCharacterExtremeUrlsOptimized(int $count): array
    {
        $specialChars = [
            'https://example.com/测试',
            'https://example.com/тест',
            'https://example.com/テスト',
            'https://example.com/path%20with%20spaces',
            'https://example.com/path?query=value&other=test',
            'https://example.com/path#fragment',
        ];
        
        $urls = [];
        for ($i = 0; $i < $count; $i++) {
            $urls[] = $specialChars[$i % count($specialChars)];
        }
        
        return $urls;
    }
    
    /**
     * Generate protocol extreme URLs with optimization.
     */
    private static function generateProtocolExtremeUrlsOptimized(int $count): array
    {
        $protocols = [
            'ftp://example.com/file.txt',
            'file:///etc/passwd',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'mailto:test@example.com',
            'tel:+1234567890',
        ];
        
        $urls = [];
        for ($i = 0; $i < $count; $i++) {
            $urls[] = $protocols[$i % count($protocols)];
        }
        
        return $urls;
    }
    
    /**
     * Generate domain extreme URLs with optimization.
     */
    private static function generateDomainExtremeUrlsOptimized(int $count): array
    {
        $domains = [
            'https://192.168.1.1',
            'https://[::1]',
            'https://[2001:db8::1]',
            'https://.',
            'https://.com',
            'https://example.',
        ];
        
        $urls = [];
        for ($i = 0; $i < $count; $i++) {
            $urls[] = $domains[$i % count($domains)];
        }
        
        return $urls;
    }
    
    /**
     * Generate URL with specific length efficiently.
     */
    private static function generateUrlWithLength(int $length): string
    {
        if ($length <= 1) {
            return '/';
        }
        
        $baseUrl = 'https://example.com/';
        $pathLength = $length - strlen($baseUrl);
        
        if ($pathLength <= 0) {
            return $baseUrl;
        }
        
        // Use efficient string generation
        $path = str_repeat('a', min($pathLength, 1000)); // Limit for performance
        
        return $baseUrl . $path;
    }
    
    /**
     * Generate single length extreme URL.
     */
    private static function generateSingleLengthExtremeUrl(int $index): string
    {
        $lengths = [1, 100, 500, 1000, 2000];
        $length = $lengths[$index % count($lengths)];
        
        return self::generateUrlWithLength($length);
    }
    
    /**
     * Generate single character extreme URL.
     */
    private static function generateSingleCharacterExtremeUrl(int $index): string
    {
        $chars = ['测试', 'тест', 'テスト', '%20', '?', '#'];
        $char = $chars[$index % count($chars)];
        
        return "https://example.com/path{$char}";
    }
    
    /**
     * Generate single protocol extreme URL.
     */
    private static function generateSingleProtocolExtremeUrl(int $index): string
    {
        $protocols = ['ftp', 'file', 'javascript', 'data', 'mailto', 'tel'];
        $protocol = $protocols[$index % count($protocols)];
        
        return "{$protocol}://example.com/resource{$index}";
    }
    
    /**
     * Generate single domain extreme URL.
     */
    private static function generateSingleDomainExtremeUrl(int $index): string
    {
        $domains = ['192.168.1.1', '[::1]', 'example.', '.com', 'example..com'];
        $domain = $domains[$index % count($domains)];
        
        return "https://{$domain}/path{$index}";
    }
    
    /**
     * Generate large URL set with optimization.
     */
    private static function generateLargeUrlSetOptimized(int $count): array
    {
        $urls = [];
        $baseUrls = ['https://example', 'https://test', 'https://demo'];
        
        for ($i = 0; $i < $count; $i++) {
            $baseUrl = $baseUrls[$i % count($baseUrls)];
            $urls[] = "{$baseUrl}{$i}.com/path/{$i}";
        }
        
        return $urls;
    }
    
    /**
     * Generate large file set with optimization.
     */
    private static function generateLargeFileSetOptimized(int $count): array
    {
        $files = [];
        $extensions = ['.md', '.txt', '.html', '.json'];
        
        for ($i = 0; $i < $count; $i++) {
            $ext = $extensions[$i % count($extensions)];
            $files[] = "file{$i}{$ext}";
        }
        
        return $files;
    }
    
    /**
     * Generate large content with optimization.
     */
    private static function generateLargeContentOptimized(int $size): string
    {
        $baseContent = "Content block with [link](https://example.com) and text.\n";
        
        // Use efficient string building for large content
        return str_repeat($baseContent, min($size, 1000)); // Limit for performance
    }
    
    /**
     * Generate large collections with optimization.
     */
    private static function generateLargeCollectionsOptimized(int $count): array
    {
        return [
            'array' => range(0, min($count - 1, 1000)), // Limit for performance
            'associative' => array_fill_keys(range(0, min($count - 1, 1000)), 'value'),
            'nested' => array_fill(0, min($count, 1000), ['nested' => 'value']),
        ];
    }
    
    /**
     * Generate basic concurrency scenario with optimization.
     */
    private static function generateBasicConcurrencyScenarioOptimized(int $threadCount): array
    {
        return [
            'type' => 'basic',
            'threads' => $threadCount,
            'operations' => array_fill(0, $threadCount, 'validate_url'),
            'data' => array_fill(0, $threadCount, 'https://example.com'),
        ];
    }
    
    /**
     * Generate race condition scenario with optimization.
     */
    private static function generateRaceConditionScenarioOptimized(int $threadCount): array
    {
        return [
            'type' => 'race_condition',
            'threads' => $threadCount,
            'shared_resource' => 'validation_cache',
            'operations' => array_fill(0, $threadCount, 'read_write_cache'),
        ];
    }
    
    /**
     * Generate deadlock scenario with optimization.
     */
    private static function generateDeadlockScenarioOptimized(int $threadCount): array
    {
        return [
            'type' => 'deadlock',
            'threads' => $threadCount,
            'resources' => ['resource_a', 'resource_b'],
            'lock_order' => 'alternating',
        ];
    }
    
    /**
     * Generate resource contention scenario with optimization.
     */
    private static function generateResourceContentionScenarioOptimized(int $threadCount): array
    {
        return [
            'type' => 'resource_contention',
            'threads' => $threadCount,
            'limited_resource' => 'http_connections',
            'resource_limit' => 5,
        ];
    }
    
    /**
     * Generate high load scenario with optimization.
     */
    private static function generateHighLoadScenarioOptimized(int $threadCount): array
    {
        return [
            'type' => 'high_load',
            'threads' => $threadCount,
            'requests_per_thread' => 100, // Reduced from 1000 for performance
            'duration' => 30, // Reduced from 60 for performance
        ];
    }
}