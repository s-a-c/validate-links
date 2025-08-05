<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use InvalidArgumentException;

/**
 * Comprehensive edge case data generator for testing boundary conditions,
 * extreme scenarios, and unexpected inputs.
 * 
 * Provides deterministic generation of edge case data including null inputs,
 * empty collections, malformed data, and resource exhaustion scenarios.
 * 
 * @package Tests\Support
 * @author Roo
 * @since 1.0.0
 */
final class EdgeCaseDataGenerator
{
    /**
     * Deterministic seed for edge case generation.
     */
    private static int $seed = 98765;
    
    /**
     * Counter for sequential edge case generation.
     */
    private static int $counter = 0;
    
    /**
     * Set the seed for deterministic edge case generation.
     */
    public static function setSeed(int $seed): void
    {
        self::$seed = $seed;
        self::$counter = 0;
    }
    
    /**
     * Reset the edge case generation state.
     */
    public static function reset(): void
    {
        self::$seed = 98765;
        self::$counter = 0;
    }
    
    /**
     * Generate extreme URLs for boundary testing.
     * 
     * @param array<string, mixed> $options Generation options
     * @return array<string> Extreme URLs
     */
    public static function generateExtremeUrls(array $options = []): array
    {
        $type = $options['type'] ?? 'all';
        $count = $options['count'] ?? 10;
        
        $urls = [];
        
        if ($type === 'all' || $type === 'length') {
            $urls = array_merge($urls, self::generateLengthExtremeUrls($count));
        }
        
        if ($type === 'all' || $type === 'characters') {
            $urls = array_merge($urls, self::generateCharacterExtremeUrls($count));
        }
        
        if ($type === 'all' || $type === 'protocol') {
            $urls = array_merge($urls, self::generateProtocolExtremeUrls($count));
        }
        
        if ($type === 'all' || $type === 'domain') {
            $urls = array_merge($urls, self::generateDomainExtremeUrls($count));
        }
        
        return array_slice($urls, 0, $count);
    }
    
    /**
     * Generate malformed data for validation testing.
     * 
     * @param array<string, mixed> $options Generation options
     * @return array<string, mixed> Malformed data
     */
    public static function generateMalformedData(array $options = []): array
    {
        $type = $options['type'] ?? 'all';
        
        $data = [];
        
        if ($type === 'all' || $type === 'json') {
            $data['json'] = self::generateMalformedJson();
        }
        
        if ($type === 'all' || $type === 'urls') {
            $data['urls'] = self::generateMalformedUrls();
        }
        
        if ($type === 'all' || $type === 'paths') {
            $data['paths'] = self::generateMalformedPaths();
        }
        
        if ($type === 'all' || $type === 'config') {
            $data['config'] = self::generateMalformedConfig();
        }
        
        return $data;
    }
    
    /**
     * Generate large data sets for performance testing.
     * 
     * @param array<string, mixed> $options Generation options
     * @return array<string, mixed> Large data sets
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
        
        return match ($type) {
            'urls' => self::generateLargeUrlSet($count),
            'files' => self::generateLargeFileSet($count),
            'content' => self::generateLargeContent($count),
            'collections' => self::generateLargeCollections($count),
            default => self::generateLargeUrlSet($count),
        };
    }
    
    /**
     * Generate concurrency scenarios for testing.
     * 
     * @param array<string, mixed> $options Generation options
     * @return array<string, mixed> Concurrency scenarios
     */
    public static function generateConcurrencyScenarios(array $options = []): array
    {
        $scenario = $options['scenario'] ?? 'basic';
        $threadCount = $options['threads'] ?? 10;
        
        return match ($scenario) {
            'basic' => self::generateBasicConcurrencyScenario($threadCount),
            'race_condition' => self::generateRaceConditionScenario($threadCount),
            'deadlock' => self::generateDeadlockScenario($threadCount),
            'resource_contention' => self::generateResourceContentionScenario($threadCount),
            'high_load' => self::generateHighLoadScenario($threadCount),
            default => self::generateBasicConcurrencyScenario($threadCount),
        };
    }
    
    /**
     * Generate null and empty inputs for validation testing.
     * 
     * @return array<string, mixed> Null and empty inputs
     */
    public static function generateNullAndEmptyInputs(): array
    {
        return [
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
                'very_long' => str_repeat('a', 10000),
                'unicode_empty' => "\u{200B}", // Zero-width space
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
    }
    
    /**
     * Generate invalid character encodings and special characters.
     * 
     * @return array<string> Invalid character data
     */
    public static function generateInvalidCharacterData(): array
    {
        return [
            // Invalid UTF-8 sequences
            "\xFF\xFE\x00\x00",
            "\xEF\xBB\xBF\x00",
            "\x80\x81\x82\x83",
            
            // Control characters
            "\x00\x01\x02\x03\x04\x05\x06\x07",
            "\x08\x09\x0A\x0B\x0C\x0D\x0E\x0F",
            
            // Unicode edge cases
            "\u{FEFF}", // BOM
            "\u{FFFF}", // Non-character
            "\u{10FFFF}", // Maximum Unicode
            
            // Mixed encodings
            "Hello\x80World",
            "Test\xFF\xFEData",
            
            // Overlong UTF-8 sequences
            "\xC0\x80", // Overlong encoding of null
            "\xE0\x80\x80", // Overlong encoding
        ];
    }
    
    /**
     * Generate URLs with extreme lengths.
     * 
     * @param int $count Number of URLs to generate
     * @return array<string> Length extreme URLs
     */
    private static function generateLengthExtremeUrls(int $count): array
    {
        $urls = [];
        
        for ($i = 0; $i < $count; $i++) {
            $length = match ($i % 5) {
                0 => 1, // Minimum length
                1 => 2048, // Standard maximum
                2 => 4096, // Extended maximum
                3 => 8192, // Very long
                4 => 65536, // Extremely long
                default => 1000,
            };
            
            if ($length === 1) {
                $urls[] = '/';
            } else {
                $baseUrl = 'https://example.com/';
                $pathLength = $length - strlen($baseUrl);
                $path = str_repeat('a', max(0, $pathLength));
                $urls[] = $baseUrl . $path;
            }
        }
        
        return $urls;
    }
    
    /**
     * Generate URLs with extreme characters.
     * 
     * @param int $count Number of URLs to generate
     * @return array<string> Character extreme URLs
     */
    private static function generateCharacterExtremeUrls(int $count): array
    {
        $specialChars = [
            // Unicode characters
            'https://example.com/测试',
            'https://example.com/тест',
            'https://example.com/テスト',
            
            // Special URL characters
            'https://example.com/path%20with%20spaces',
            'https://example.com/path?query=value&other=test',
            'https://example.com/path#fragment',
            
            // Problematic characters
            'https://example.com/path with spaces',
            'https://example.com/path"with"quotes',
            'https://example.com/path<with>brackets',
            
            // Control characters
            "https://example.com/path\x00null",
            "https://example.com/path\nnewline",
            "https://example.com/path\ttab",
        ];
        
        return array_slice($specialChars, 0, $count);
    }
    
    /**
     * Generate URLs with extreme protocols.
     * 
     * @param int $count Number of URLs to generate
     * @return array<string> Protocol extreme URLs
     */
    private static function generateProtocolExtremeUrls(int $count): array
    {
        $protocols = [
            'ftp://example.com/file.txt',
            'file:///etc/passwd',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            'mailto:test@example.com',
            'tel:+1234567890',
            'ssh://user@example.com',
            'ldap://example.com',
            'custom-protocol://example.com',
            '://example.com', // Missing protocol
            'http//example.com', // Missing colon
            'ht tp://example.com', // Space in protocol
        ];
        
        return array_slice($protocols, 0, $count);
    }
    
    /**
     * Generate URLs with extreme domains.
     * 
     * @param int $count Number of URLs to generate
     * @return array<string> Domain extreme URLs
     */
    private static function generateDomainExtremeUrls(int $count): array
    {
        $domains = [
            // Very long domain
            'https://' . str_repeat('a', 253) . '.com',
            
            // International domains
            'https://xn--nxasmq6b.xn--j6w193g', // 测试.中国
            'https://пример.рф',
            
            // IP addresses
            'https://192.168.1.1',
            'https://[::1]',
            'https://[2001:db8::1]',
            
            // Invalid domains
            'https://.',
            'https://.com',
            'https://example.',
            'https://example..com',
            'https://example-.com',
            'https://-example.com',
        ];
        
        return array_slice($domains, 0, $count);
    }
    
    /**
     * Generate malformed JSON data.
     * 
     * @return array<string> Malformed JSON strings
     */
    private static function generateMalformedJson(): array
    {
        return [
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
        ];
    }
    
    /**
     * Generate malformed URLs.
     * 
     * @return array<string> Malformed URLs
     */
    private static function generateMalformedUrls(): array
    {
        return [
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
        ];
    }
    
    /**
     * Generate malformed file paths.
     * 
     * @return array<string> Malformed paths
     */
    private static function generateMalformedPaths(): array
    {
        return [
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
        ];
    }
    
    /**
     * Generate malformed configuration data.
     * 
     * @return array<string, mixed> Malformed config
     */
    private static function generateMalformedConfig(): array
    {
        return [
            'invalid_timeout' => [
                'timeout' => -1,
                'max_redirects' => 'not-a-number',
                'concurrent_requests' => 0,
            ],
            'invalid_scopes' => [
                'scopes' => ['invalid-scope'],
                'output_format' => 'invalid-format',
            ],
            'circular_reference' => [
                'self_reference' => null, // Will be set to reference itself
            ],
            'extreme_values' => [
                'timeout' => PHP_INT_MAX,
                'max_redirects' => -999,
                'concurrent_requests' => 999999,
            ],
        ];
    }
    
    /**
     * Generate large URL set.
     * 
     * @param int $count Number of URLs
     * @return array<string> Large URL set
     */
    private static function generateLargeUrlSet(int $count): array
    {
        $urls = [];
        
        for ($i = 0; $i < $count; $i++) {
            $urls[] = "https://example{$i}.com/path/{$i}";
        }
        
        return $urls;
    }
    
    /**
     * Generate large file set.
     * 
     * @param int $count Number of files
     * @return array<string> Large file set
     */
    private static function generateLargeFileSet(int $count): array
    {
        $files = [];
        
        for ($i = 0; $i < $count; $i++) {
            $files[] = "file{$i}.md";
        }
        
        return $files;
    }
    
    /**
     * Generate large content.
     * 
     * @param int $size Content size multiplier
     * @return string Large content
     */
    private static function generateLargeContent(int $size): string
    {
        $baseContent = "This is a test content with [link](https://example.com) and more text.\n";
        
        return str_repeat($baseContent, $size);
    }
    
    /**
     * Generate large collections.
     * 
     * @param int $count Collection size
     * @return array<string, array<mixed>> Large collections
     */
    private static function generateLargeCollections(int $count): array
    {
        return [
            'array' => range(0, $count - 1),
            'associative' => array_fill_keys(range(0, $count - 1), 'value'),
            'nested' => array_fill(0, $count, ['nested' => 'value']),
        ];
    }
    
    /**
     * Generate basic concurrency scenario.
     * 
     * @param int $threadCount Number of threads
     * @return array<string, mixed> Concurrency scenario
     */
    private static function generateBasicConcurrencyScenario(int $threadCount): array
    {
        return [
            'type' => 'basic',
            'threads' => $threadCount,
            'operations' => array_fill(0, $threadCount, 'validate_url'),
            'data' => array_fill(0, $threadCount, 'https://example.com'),
        ];
    }
    
    /**
     * Generate race condition scenario.
     * 
     * @param int $threadCount Number of threads
     * @return array<string, mixed> Race condition scenario
     */
    private static function generateRaceConditionScenario(int $threadCount): array
    {
        return [
            'type' => 'race_condition',
            'threads' => $threadCount,
            'shared_resource' => 'validation_cache',
            'operations' => array_fill(0, $threadCount, 'read_write_cache'),
        ];
    }
    
    /**
     * Generate deadlock scenario.
     * 
     * @param int $threadCount Number of threads
     * @return array<string, mixed> Deadlock scenario
     */
    private static function generateDeadlockScenario(int $threadCount): array
    {
        return [
            'type' => 'deadlock',
            'threads' => $threadCount,
            'resources' => ['resource_a', 'resource_b'],
            'lock_order' => 'alternating',
        ];
    }
    
    /**
     * Generate resource contention scenario.
     * 
     * @param int $threadCount Number of threads
     * @return array<string, mixed> Resource contention scenario
     */
    private static function generateResourceContentionScenario(int $threadCount): array
    {
        return [
            'type' => 'resource_contention',
            'threads' => $threadCount,
            'limited_resource' => 'http_connections',
            'resource_limit' => 5,
        ];
    }
    
    /**
     * Generate high load scenario.
     * 
     * @param int $threadCount Number of threads
     * @return array<string, mixed> High load scenario
     */
    private static function generateHighLoadScenario(int $threadCount): array
    {
        return [
            'type' => 'high_load',
            'threads' => $threadCount,
            'requests_per_thread' => 1000,
            'duration' => 60, // seconds
        ];
    }
}