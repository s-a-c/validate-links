<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use Exception;

/**
 * Comprehensive resource utilization monitor for tracking CPU, memory,
 * I/O, and network usage during test execution.
 * 
 * Provides real-time monitoring, resource optimization recommendations,
 * and performance bottleneck identification for test suite efficiency.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class ResourceUtilizationMonitor
{
    /**
     * Resource monitoring data.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $resourceData = [];
    
    /**
     * Monitoring intervals and thresholds.
     * 
     * @var array<string, mixed>
     */
    private static array $config = [
        'monitoring_interval' => 0.1, // 100ms
        'cpu_threshold' => 80.0, // 80% CPU usage
        'memory_threshold' => 80.0, // 80% memory usage
        'io_threshold' => 1000, // 1000 operations per second
        'network_threshold' => 10 * 1024 * 1024, // 10MB/s
    ];
    
    /**
     * Active monitoring sessions.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $activeSessions = [];
    
    /**
     * Resource optimization recommendations.
     * 
     * @var array<string>
     */
    private static array $recommendations = [];
    
    /**
     * Initialize resource monitoring.
     */
    public static function initialize(): void
    {
        self::$resourceData = [];
        self::$activeSessions = [];
        self::$recommendations = [];
        
        // Take baseline measurements
        self::recordResourceSnapshot('baseline');
    }
    
    /**
     * Start monitoring resources for a specific operation.
     */
    public static function startMonitoring(string $identifier, array $options = []): void
    {
        $sessionId = self::generateSessionId($identifier);
        
        self::$activeSessions[$sessionId] = [
            'identifier' => $identifier,
            'start_time' => microtime(true),
            'options' => $options,
            'snapshots' => [],
            'resource_usage' => [],
        ];
        
        // Take initial snapshot
        self::recordResourceSnapshot($identifier . '_start');
    }
    
    /**
     * Stop monitoring and analyze resource usage.
     */
    public static function stopMonitoring(string $identifier): array
    {
        $sessionId = self::generateSessionId($identifier);
        
        if (!isset(self::$activeSessions[$sessionId])) {
            throw new Exception("No active monitoring session found for: {$identifier}");
        }
        
        $session = self::$activeSessions[$sessionId];
        $endTime = microtime(true);
        
        // Take final snapshot
        $finalSnapshot = self::recordResourceSnapshot($identifier . '_end');
        
        // Calculate resource usage
        $resourceUsage = self::calculateResourceUsage($session, $finalSnapshot);
        
        // Store results
        self::$resourceData[$identifier] = $resourceUsage;
        
        // Clean up session
        unset(self::$activeSessions[$sessionId]);
        
        return $resourceUsage;
    }
    
    /**
     * Monitor resources for a callable operation.
     */
    public static function monitor(string $identifier, callable $operation, array $options = []): mixed
    {
        self::startMonitoring($identifier, $options);
        
        try {
            $result = $operation();
            return $result;
        } finally {
            self::stopMonitoring($identifier);
        }
    }
    
    /**
     * Record a resource snapshot at a specific point.
     */
    public static function recordResourceSnapshot(string $label): array
    {
        $snapshot = [
            'label' => $label,
            'timestamp' => microtime(true),
            'memory' => self::getMemoryUsage(),
            'cpu' => self::getCpuUsage(),
            'io' => self::getIoUsage(),
            'network' => self::getNetworkUsage(),
            'processes' => self::getProcessInfo(),
            'system_load' => self::getSystemLoad(),
        ];
        
        return $snapshot;
    }
    
    /**
     * Get comprehensive resource utilization statistics.
     */
    public static function getResourceStats(): array
    {
        $stats = [
            'total_operations' => count(self::$resourceData),
            'cpu_intensive_operations' => [],
            'memory_intensive_operations' => [],
            'io_intensive_operations' => [],
            'network_intensive_operations' => [],
            'resource_bottlenecks' => [],
            'optimization_opportunities' => [],
        ];
        
        foreach (self::$resourceData as $identifier => $data) {
            // Identify CPU-intensive operations
            if ($data['cpu']['average_usage'] > self::$config['cpu_threshold']) {
                $stats['cpu_intensive_operations'][] = [
                    'identifier' => $identifier,
                    'cpu_usage' => $data['cpu']['average_usage'],
                    'duration' => $data['duration'],
                ];
            }
            
            // Identify memory-intensive operations
            if ($data['memory']['peak_usage_mb'] > 100) { // 100MB
                $stats['memory_intensive_operations'][] = [
                    'identifier' => $identifier,
                    'peak_memory_mb' => $data['memory']['peak_usage_mb'],
                    'memory_growth_mb' => $data['memory']['growth_mb'],
                ];
            }
            
            // Identify I/O-intensive operations
            if ($data['io']['total_operations'] > 1000) {
                $stats['io_intensive_operations'][] = [
                    'identifier' => $identifier,
                    'io_operations' => $data['io']['total_operations'],
                    'io_rate' => $data['io']['operations_per_second'],
                ];
            }
            
            // Identify network-intensive operations
            if ($data['network']['total_bytes'] > 10 * 1024 * 1024) { // 10MB
                $stats['network_intensive_operations'][] = [
                    'identifier' => $identifier,
                    'network_mb' => round($data['network']['total_bytes'] / 1024 / 1024, 2),
                    'network_rate_mbps' => $data['network']['mbps'],
                ];
            }
        }
        
        // Identify resource bottlenecks
        $stats['resource_bottlenecks'] = self::identifyResourceBottlenecks();
        
        // Generate optimization opportunities
        $stats['optimization_opportunities'] = self::generateOptimizationRecommendations();
        
        return $stats;
    }
    
    /**
     * Identify resource bottlenecks across operations.
     */
    public static function identifyResourceBottlenecks(): array
    {
        $bottlenecks = [];
        
        foreach (self::$resourceData as $identifier => $data) {
            $issues = [];
            
            // Check CPU bottlenecks
            if ($data['cpu']['peak_usage'] > 90) {
                $issues[] = [
                    'type' => 'cpu_bottleneck',
                    'severity' => 'high',
                    'value' => $data['cpu']['peak_usage'],
                    'description' => 'High CPU usage detected',
                ];
            }
            
            // Check memory bottlenecks
            if ($data['memory']['growth_mb'] > 50) {
                $issues[] = [
                    'type' => 'memory_bottleneck',
                    'severity' => 'medium',
                    'value' => $data['memory']['growth_mb'],
                    'description' => 'Significant memory growth detected',
                ];
            }
            
            // Check I/O bottlenecks
            if ($data['io']['operations_per_second'] > self::$config['io_threshold']) {
                $issues[] = [
                    'type' => 'io_bottleneck',
                    'severity' => 'medium',
                    'value' => $data['io']['operations_per_second'],
                    'description' => 'High I/O operation rate detected',
                ];
            }
            
            // Check for resource contention
            if ($data['cpu']['average_usage'] > 70 && $data['memory']['growth_mb'] > 30) {
                $issues[] = [
                    'type' => 'resource_contention',
                    'severity' => 'high',
                    'description' => 'Multiple resource types under pressure',
                ];
            }
            
            if (!empty($issues)) {
                $bottlenecks[$identifier] = $issues;
            }
        }
        
        return $bottlenecks;
    }
    
    /**
     * Generate resource optimization recommendations.
     */
    public static function generateOptimizationRecommendations(): array
    {
        $recommendations = [];
        $stats = self::getResourceStats();
        
        // CPU optimization recommendations
        if (!empty($stats['cpu_intensive_operations'])) {
            $recommendations[] = 'CPU-intensive operations detected - consider algorithm optimization or caching';
            $recommendations[] = 'Implement CPU usage monitoring and throttling for resource-heavy tests';
        }
        
        // Memory optimization recommendations
        if (!empty($stats['memory_intensive_operations'])) {
            $recommendations[] = 'Memory-intensive operations detected - implement streaming or chunking';
            $recommendations[] = 'Add memory cleanup and garbage collection between test operations';
        }
        
        // I/O optimization recommendations
        if (!empty($stats['io_intensive_operations'])) {
            $recommendations[] = 'I/O-intensive operations detected - implement batching or async operations';
            $recommendations[] = 'Consider using in-memory alternatives for test data';
        }
        
        // Network optimization recommendations
        if (!empty($stats['network_intensive_operations'])) {
            $recommendations[] = 'Network-intensive operations detected - implement connection pooling';
            $recommendations[] = 'Use mock services to reduce external network dependencies';
        }
        
        // General optimization recommendations
        if (count(self::$resourceData) > 100) {
            $recommendations[] = 'Large number of monitored operations - consider test suite optimization';
        }
        
        self::$recommendations = array_unique($recommendations);
        
        return self::$recommendations;
    }
    
    /**
     * Export resource utilization report.
     */
    public static function exportReport(string $filePath, string $format = 'json'): bool
    {
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'resource_stats' => self::getResourceStats(),
            'resource_data' => self::$resourceData,
            'bottlenecks' => self::identifyResourceBottlenecks(),
            'recommendations' => self::generateOptimizationRecommendations(),
            'configuration' => self::$config,
        ];
        
        $content = match ($format) {
            'json' => json_encode($report, JSON_PRETTY_PRINT),
            'csv' => self::convertToCsv($report),
            default => json_encode($report, JSON_PRETTY_PRINT),
        };
        
        return file_put_contents($filePath, $content) !== false;
    }
    
    /**
     * Reset resource monitoring.
     */
    public static function reset(): void
    {
        self::$resourceData = [];
        self::$activeSessions = [];
        self::$recommendations = [];
    }
    
    /**
     * Configure monitoring thresholds.
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Get current configuration.
     */
    public static function getConfiguration(): array
    {
        return self::$config;
    }
    
    /**
     * Generate unique session ID.
     */
    private static function generateSessionId(string $identifier): string
    {
        return md5($identifier . microtime(true));
    }
    
    /**
     * Calculate resource usage for a monitoring session.
     */
    private static function calculateResourceUsage(array $session, array $finalSnapshot): array
    {
        $duration = microtime(true) - $session['start_time'];
        
        // Get initial snapshot (if available)
        $initialSnapshot = self::recordResourceSnapshot($session['identifier'] . '_initial');
        
        return [
            'identifier' => $session['identifier'],
            'duration' => $duration,
            'cpu' => [
                'average_usage' => ($initialSnapshot['cpu']['usage'] + $finalSnapshot['cpu']['usage']) / 2,
                'peak_usage' => max($initialSnapshot['cpu']['usage'], $finalSnapshot['cpu']['usage']),
                'cpu_time' => $finalSnapshot['cpu']['time'] - $initialSnapshot['cpu']['time'],
            ],
            'memory' => [
                'initial_usage_mb' => round($initialSnapshot['memory']['usage'] / 1024 / 1024, 2),
                'final_usage_mb' => round($finalSnapshot['memory']['usage'] / 1024 / 1024, 2),
                'peak_usage_mb' => round($finalSnapshot['memory']['peak'] / 1024 / 1024, 2),
                'growth_mb' => round(($finalSnapshot['memory']['usage'] - $initialSnapshot['memory']['usage']) / 1024 / 1024, 2),
            ],
            'io' => [
                'total_operations' => $finalSnapshot['io']['operations'] - $initialSnapshot['io']['operations'],
                'operations_per_second' => $duration > 0 ? ($finalSnapshot['io']['operations'] - $initialSnapshot['io']['operations']) / $duration : 0,
                'bytes_read' => $finalSnapshot['io']['bytes_read'] - $initialSnapshot['io']['bytes_read'],
                'bytes_written' => $finalSnapshot['io']['bytes_written'] - $initialSnapshot['io']['bytes_written'],
            ],
            'network' => [
                'total_bytes' => $finalSnapshot['network']['bytes'] - $initialSnapshot['network']['bytes'],
                'mbps' => $duration > 0 ? (($finalSnapshot['network']['bytes'] - $initialSnapshot['network']['bytes']) / 1024 / 1024) / $duration : 0,
                'connections' => $finalSnapshot['network']['connections'],
            ],
            'system' => [
                'load_average' => $finalSnapshot['system_load'],
                'process_count' => $finalSnapshot['processes']['count'],
            ],
        ];
    }
    
    /**
     * Get memory usage information.
     */
    private static function getMemoryUsage(): array
    {
        return [
            'usage' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => self::getMemoryLimit(),
        ];
    }
    
    /**
     * Get CPU usage information.
     */
    private static function getCpuUsage(): array
    {
        $usage = 0;
        $time = 0;
        
        if (function_exists('getrusage')) {
            $rusage = getrusage();
            $time = $rusage['ru_utime.tv_sec'] + $rusage['ru_utime.tv_usec'] / 1000000;
            
            // Approximate CPU usage (this is a simplified calculation)
            static $lastTime = null;
            static $lastCpuTime = null;
            
            $currentTime = microtime(true);
            
            if ($lastTime !== null && $lastCpuTime !== null) {
                $timeDiff = $currentTime - $lastTime;
                $cpuDiff = $time - $lastCpuTime;
                $usage = $timeDiff > 0 ? ($cpuDiff / $timeDiff) * 100 : 0;
            }
            
            $lastTime = $currentTime;
            $lastCpuTime = $time;
        }
        
        return [
            'usage' => min(100, max(0, $usage)), // Clamp between 0-100
            'time' => $time,
        ];
    }
    
    /**
     * Get I/O usage information.
     */
    private static function getIoUsage(): array
    {
        $operations = 0;
        $bytesRead = 0;
        $bytesWritten = 0;
        
        if (function_exists('getrusage')) {
            $rusage = getrusage();
            $operations = $rusage['ru_inblock'] + $rusage['ru_oublock'];
            $bytesRead = $rusage['ru_inblock'] * 512; // Approximate
            $bytesWritten = $rusage['ru_oublock'] * 512; // Approximate
        }
        
        return [
            'operations' => $operations,
            'bytes_read' => $bytesRead,
            'bytes_written' => $bytesWritten,
        ];
    }
    
    /**
     * Get network usage information.
     */
    private static function getNetworkUsage(): array
    {
        // This is a simplified implementation
        // In a real scenario, you might parse /proc/net/dev or use system tools
        return [
            'bytes' => 0, // Would need system-specific implementation
            'connections' => 0, // Would need system-specific implementation
        ];
    }
    
    /**
     * Get process information.
     */
    private static function getProcessInfo(): array
    {
        $count = 0;
        
        // Try to get process count (Unix-like systems)
        if (function_exists('shell_exec')) {
            $output = @shell_exec('ps aux | wc -l');
            if ($output !== null) {
                $count = (int)trim($output) - 1; // Subtract header line
            }
        }
        
        return [
            'count' => $count,
            'pid' => getmypid(),
        ];
    }
    
    /**
     * Get system load average.
     */
    private static function getSystemLoad(): array
    {
        $load = [0, 0, 0];
        
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
        }
        
        return [
            '1min' => $load[0] ?? 0,
            '5min' => $load[1] ?? 0,
            '15min' => $load[2] ?? 0,
        ];
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
    
    /**
     * Convert report to CSV format.
     */
    private static function convertToCsv(array $report): string
    {
        $csv = "Identifier,Duration,CPU Usage,Memory Growth (MB),I/O Ops,Network (MB)\n";
        
        foreach ($report['resource_data'] as $identifier => $data) {
            $csv .= sprintf(
                "%s,%.4f,%.2f,%.2f,%d,%.2f\n",
                $identifier,
                $data['duration'],
                $data['cpu']['average_usage'],
                $data['memory']['growth_mb'],
                $data['io']['total_operations'],
                $data['network']['mbps']
            );
        }
        
        return $csv;
    }
}