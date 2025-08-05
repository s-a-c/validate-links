<?php

declare(strict_types=1);

namespace Tests\Support\Helpers;

/**
 * Streamlined Test Helper
 * 
 * Consolidated helper utilities that replace complex support classes
 * with simple, focused functionality.
 */
final class TestHelper
{
    /**
     * Create temporary file with automatic cleanup
     */
    public static function createTempFile(string $content = '', string $extension = '.tmp'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . $extension;
        file_put_contents($tempFile, $content);
        
        // Register for cleanup
        register_shutdown_function(function () use ($tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        });
        
        return $tempFile;
    }
    
    /**
     * Create temporary directory with automatic cleanup
     */
    public static function createTempDirectory(string $prefix = 'test_'): string
    {
        $tempDir = sys_get_temp_dir() . '/' . $prefix . uniqid();
        mkdir($tempDir, 0755, true);
        
        // Register for cleanup
        register_shutdown_function(function () use ($tempDir) {
            self::removeDirectory($tempDir);
        });
        
        return $tempDir;
    }
    
    /**
     * Remove directory recursively
     */
    private static function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    /**
     * Measure execution time and memory usage
     */
    public static function measurePerformance(callable $callback): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = $callback();
        
        return [
            'result' => $result,
            'execution_time' => microtime(true) - $startTime,
            'memory_usage' => memory_get_usage(true) - $startMemory,
        ];
    }
    
    /**
     * Generate random test data
     */
    public static function randomString(int $length = 10): string
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, $length);
    }
    
    /**
     * Generate random URL
     */
    public static function randomUrl(string $domain = 'example.com'): string
    {
        return 'https://' . $domain . '/' . self::randomString();
    }
    
    /**
     * Check if running in CI environment
     */
    public static function isCI(): bool
    {
        return !empty($_ENV['CI']) || !empty($_ENV['GITHUB_ACTIONS']);
    }
    
    /**
     * Skip test if condition is met
     */
    public static function skipIf(bool $condition, string $reason = 'Condition met'): void
    {
        if ($condition) {
            test()->markTestSkipped($reason);
        }
    }
}