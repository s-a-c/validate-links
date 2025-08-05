<?php

declare(strict_types=1);

namespace Tests\Support;

use Exception;
use RuntimeException;

/**
 * Resource limit simulator for testing resource exhaustion scenarios
 * and performance characteristics under extreme conditions.
 * 
 * Provides controlled simulation of memory pressure, disk space limits,
 * network constraints, and CPU load for comprehensive edge case testing.
 * 
 * @package Tests\Support
 * @author Roo
 * @since 1.0.0
 */
final class ResourceLimitSimulator
{
    /**
     * Current memory usage tracking.
     */
    private static int $baseMemoryUsage = 0;
    
    /**
     * Allocated memory blocks for simulation.
     * 
     * @var array<string>
     */
    private static array $memoryBlocks = [];
    
    /**
     * Simulated network latency in milliseconds.
     */
    private static int $networkLatency = 0;
    
    /**
     * Simulated bandwidth limit in bytes per second.
     */
    private static int $bandwidthLimit = 0;
    
    /**
     * Initialize the resource limit simulator.
     */
    public static function initialize(): void
    {
        self::$baseMemoryUsage = memory_get_usage(true);
        self::$memoryBlocks = [];
        self::$networkLatency = 0;
        self::$bandwidthLimit = 0;
    }
    
    /**
     * Reset all resource simulations.
     */
    public static function reset(): void
    {
        self::releaseMemoryPressure();
        self::$networkLatency = 0;
        self::$bandwidthLimit = 0;
    }
    
    /**
     * Simulate memory pressure by allocating large amounts of memory.
     * 
     * @param array<string, mixed> $options Simulation options
     * @throws RuntimeException When memory allocation fails
     */
    public static function simulateMemoryPressure(array $options = []): void
    {
        $targetMb = $options['target_mb'] ?? 100;
        $blockSizeMb = $options['block_size_mb'] ?? 10;
        $gradual = $options['gradual'] ?? false;
        
        $targetBytes = $targetMb * 1024 * 1024;
        $blockSizeBytes = $blockSizeMb * 1024 * 1024;
        
        $allocatedBytes = 0;
        $blockIndex = 0;
        
        try {
            while ($allocatedBytes < $targetBytes) {
                $remainingBytes = $targetBytes - $allocatedBytes;
                $currentBlockSize = min($blockSizeBytes, $remainingBytes);
                
                // Allocate memory block
                $block = str_repeat('x', $currentBlockSize);
                self::$memoryBlocks["block_{$blockIndex}"] = $block;
                
                $allocatedBytes += $currentBlockSize;
                $blockIndex++;
                
                if ($gradual) {
                    // Add small delay for gradual allocation
                    usleep(10000); // 10ms
                }
                
                // Check if we're approaching memory limit
                $currentUsage = memory_get_usage(true);
                $memoryLimit = self::getMemoryLimit();
                
                if ($memoryLimit > 0 && $currentUsage > ($memoryLimit * 0.9)) {
                    throw new RuntimeException(
                        "Memory pressure simulation approaching limit: {$currentUsage} bytes"
                    );
                }
            }
        } catch (Exception $e) {
            // Clean up allocated blocks on failure
            self::releaseMemoryPressure();
            throw new RuntimeException("Memory pressure simulation failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Release memory pressure by freeing allocated blocks.
     */
    public static function releaseMemoryPressure(): void
    {
        self::$memoryBlocks = [];
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Simulate disk space limits by creating temporary files.
     * 
     * @param array<string, mixed> $options Simulation options
     * @return array<string> Created temporary files
     * @throws RuntimeException When disk space simulation fails
     */
    public static function simulateDiskSpaceLimit(array $options = []): array
    {
        $targetMb = $options['target_mb'] ?? 50;
        $tempDir = $options['temp_dir'] ?? sys_get_temp_dir();
        $filePrefix = $options['file_prefix'] ?? 'edge_case_test_';
        
        $targetBytes = $targetMb * 1024 * 1024;
        $fileSizeBytes = 1024 * 1024; // 1MB per file
        
        $createdFiles = [];
        $allocatedBytes = 0;
        $fileIndex = 0;
        
        try {
            while ($allocatedBytes < $targetBytes) {
                $remainingBytes = $targetBytes - $allocatedBytes;
                $currentFileSize = min($fileSizeBytes, $remainingBytes);
                
                $fileName = $tempDir . DIRECTORY_SEPARATOR . $filePrefix . $fileIndex . '.tmp';
                
                // Create file with specified size
                $handle = fopen($fileName, 'w');
                if ($handle === false) {
                    throw new RuntimeException("Failed to create temporary file: {$fileName}");
                }
                
                $written = fwrite($handle, str_repeat('x', $currentFileSize));
                fclose($handle);
                
                if ($written === false || $written < $currentFileSize) {
                    throw new RuntimeException("Failed to write to temporary file: {$fileName}");
                }
                
                $createdFiles[] = $fileName;
                $allocatedBytes += $currentFileSize;
                $fileIndex++;
                
                // Check available disk space
                $freeSpace = disk_free_space($tempDir);
                if ($freeSpace !== false && $freeSpace < (10 * 1024 * 1024)) { // 10MB threshold
                    throw new RuntimeException("Disk space simulation approaching limit");
                }
            }
            
            return $createdFiles;
        } catch (Exception $e) {
            // Clean up created files on failure
            foreach ($createdFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            throw new RuntimeException("Disk space simulation failed: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Simulate network latency for testing network performance.
     * 
     * @param array<string, mixed> $options Simulation options
     */
    public static function simulateNetworkLatency(array $options = []): void
    {
        $latencyMs = $options['latency_ms'] ?? 1000;
        $jitter = $options['jitter'] ?? 0.1; // 10% jitter
        
        self::$networkLatency = $latencyMs;
        
        // Apply latency with jitter
        $actualLatency = $latencyMs * (1 + (mt_rand(-100, 100) / 1000) * $jitter);
        $latencyMicroseconds = (int)($actualLatency * 1000);
        
        usleep($latencyMicroseconds);
    }
    
    /**
     * Simulate bandwidth limitations.
     * 
     * @param array<string, mixed> $options Simulation options
     */
    public static function simulateBandwidthLimit(array $options = []): void
    {
        $bytesPerSecond = $options['bytes_per_second'] ?? 1024; // 1KB/s
        $dataSize = $options['data_size'] ?? 1024;
        
        self::$bandwidthLimit = $bytesPerSecond;
        
        // Calculate delay based on bandwidth limit
        $transferTimeSeconds = $dataSize / $bytesPerSecond;
        $transferTimeMicroseconds = (int)($transferTimeSeconds * 1000000);
        
        usleep($transferTimeMicroseconds);
    }
    
    /**
     * Simulate CPU load by performing intensive calculations.
     * 
     * @param array<string, mixed> $options Simulation options
     */
    public static function simulateCpuLoad(array $options = []): void
    {
        $durationMs = $options['duration_ms'] ?? 1000;
        $intensity = $options['intensity'] ?? 0.8; // 80% CPU usage
        
        $startTime = microtime(true);
        $endTime = $startTime + ($durationMs / 1000);
        
        while (microtime(true) < $endTime) {
            // Perform CPU-intensive calculation
            $workTime = 0.001 * $intensity; // Work for intensity fraction of time
            $sleepTime = 0.001 * (1 - $intensity); // Sleep for remaining time
            
            $workEndTime = microtime(true) + $workTime;
            while (microtime(true) < $workEndTime) {
                // Busy work
                $dummy = 0;
                for ($i = 0; $i < 1000; $i++) {
                    $dummy += sqrt($i);
                }
            }
            
            // Sleep to control CPU usage
            usleep((int)($sleepTime * 1000000));
        }
    }
    
    /**
     * Simulate connection pool exhaustion.
     * 
     * @param array<string, mixed> $options Simulation options
     * @return array<resource|false> Simulated connections
     */
    public static function simulateConnectionPoolExhaustion(array $options = []): array
    {
        $maxConnections = $options['max_connections'] ?? 10;
        $connectionType = $options['connection_type'] ?? 'tcp';
        
        $connections = [];
        
        for ($i = 0; $i < $maxConnections; $i++) {
            if ($connectionType === 'tcp') {
                // Simulate TCP connection
                $connection = @fsockopen('127.0.0.1', 80, $errno, $errstr, 1);
                $connections[] = $connection;
            } else {
                // Simulate other connection types
                $connections[] = false;
            }
        }
        
        return $connections;
    }
    
    /**
     * Simulate file descriptor limit exhaustion.
     * 
     * @param array<string, mixed> $options Simulation options
     * @return array<resource|false> Opened file descriptors
     */
    public static function simulateFileDescriptorLimit(array $options = []): array
    {
        $maxDescriptors = $options['max_descriptors'] ?? 100;
        $tempDir = $options['temp_dir'] ?? sys_get_temp_dir();
        
        $descriptors = [];
        
        for ($i = 0; $i < $maxDescriptors; $i++) {
            $fileName = $tempDir . DIRECTORY_SEPARATOR . "fd_test_{$i}.tmp";
            
            // Create temporary file
            $handle = @fopen($fileName, 'w+');
            if ($handle !== false) {
                $descriptors[] = $handle;
            } else {
                // Reached file descriptor limit
                break;
            }
        }
        
        return $descriptors;
    }
    
    /**
     * Get current memory usage statistics.
     * 
     * @return array<string, mixed> Memory usage statistics
     */
    public static function getMemoryUsageStats(): array
    {
        return [
            'current_usage' => memory_get_usage(true),
            'peak_usage' => memory_get_peak_usage(true),
            'base_usage' => self::$baseMemoryUsage,
            'allocated_blocks' => count(self::$memoryBlocks),
            'memory_limit' => self::getMemoryLimit(),
        ];
    }
    
    /**
     * Get current network simulation statistics.
     * 
     * @return array<string, mixed> Network simulation statistics
     */
    public static function getNetworkStats(): array
    {
        return [
            'simulated_latency_ms' => self::$networkLatency,
            'bandwidth_limit_bps' => self::$bandwidthLimit,
        ];
    }
    
    /**
     * Clean up all simulated resources.
     */
    public static function cleanup(): void
    {
        self::releaseMemoryPressure();
        
        // Clean up any remaining temporary files
        $tempDir = sys_get_temp_dir();
        $pattern = $tempDir . DIRECTORY_SEPARATOR . 'edge_case_test_*.tmp';
        
        foreach (glob($pattern) as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
        
        // Clean up file descriptor test files
        $fdPattern = $tempDir . DIRECTORY_SEPARATOR . 'fd_test_*.tmp';
        foreach (glob($fdPattern) as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Get memory limit in bytes.
     */
    private static function getMemoryLimit(): int
    {
        $memoryLimit = ini_get('memory_limit');
        
        if ($memoryLimit === false || $memoryLimit === '' || $memoryLimit === '-1') {
            return 0; // No limit
        }
        
        // Convert memory limit to bytes
        $value = (int)$memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
    
    /**
     * Check if system is under resource pressure.
     * 
     * @return array<string, mixed> Resource pressure indicators
     */
    public static function checkResourcePressure(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = self::getMemoryLimit();
        $memoryPressure = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) : 0;
        
        $loadAverage = null;
        if (function_exists('sys_getloadavg')) {
            $loadAverage = sys_getloadavg();
        }
        
        return [
            'memory_pressure' => $memoryPressure,
            'memory_usage_mb' => round($memoryUsage / (1024 * 1024), 2),
            'memory_limit_mb' => $memoryLimit > 0 ? round($memoryLimit / (1024 * 1024), 2) : null,
            'load_average' => $loadAverage,
            'simulated_latency' => self::$networkLatency,
            'simulated_bandwidth_limit' => self::$bandwidthLimit,
        ];
    }
}