<?php

declare(strict_types=1);

namespace Tests\Support\Performance;

use Exception;

/**
 * Advanced memory usage tracker for monitoring memory consumption,
 * detecting memory leaks, and optimizing memory allocation patterns.
 * 
 * Provides real-time memory monitoring, leak detection, and memory
 * optimization recommendations for test suite performance.
 * 
 * @package Tests\Support\Performance
 * @author Roo
 * @since 1.0.0
 */
final class MemoryUsageTracker
{
    /**
     * Memory snapshots for tracking usage over time.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $snapshots = [];
    
    /**
     * Memory allocation tracking.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $allocations = [];
    
    /**
     * Memory leak detection data.
     * 
     * @var array<string, mixed>
     */
    private static array $leakDetection = [
        'baseline' => 0,
        'threshold' => 10 * 1024 * 1024, // 10MB
        'samples' => [],
        'leaks_detected' => [],
    ];
    
    /**
     * Memory optimization recommendations.
     * 
     * @var array<string>
     */
    private static array $optimizations = [];
    
    /**
     * Initialize memory tracking.
     */
    public static function initialize(): void
    {
        self::$snapshots = [];
        self::$allocations = [];
        self::$leakDetection['baseline'] = memory_get_usage(true);
        self::$leakDetection['samples'] = [];
        self::$leakDetection['leaks_detected'] = [];
        self::$optimizations = [];
        
        // Take initial snapshot
        self::takeSnapshot('initialization');
    }
    
    /**
     * Take a memory snapshot at a specific point.
     */
    public static function takeSnapshot(string $label, array $context = []): array
    {
        $snapshot = [
            'label' => $label,
            'context' => $context,
            'timestamp' => microtime(true),
            'memory_usage' => memory_get_usage(false),
            'memory_usage_real' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(false),
            'peak_memory_real' => memory_get_peak_usage(true),
            'allocated_objects' => self::countAllocatedObjects(),
        ];
        
        // Add memory limit information
        $memoryLimit = self::getMemoryLimit();
        if ($memoryLimit > 0) {
            $snapshot['memory_limit'] = $memoryLimit;
            $snapshot['memory_usage_percentage'] = ($snapshot['memory_usage_real'] / $memoryLimit) * 100;
        }
        
        self::$snapshots[$label] = $snapshot;
        
        // Add to leak detection samples
        self::$leakDetection['samples'][] = [
            'label' => $label,
            'memory' => $snapshot['memory_usage_real'],
            'timestamp' => $snapshot['timestamp'],
        ];
        
        return $snapshot;
    }
    
    /**
     * Track memory allocation for a specific operation.
     */
    public static function trackAllocation(string $operation, callable $callback): mixed
    {
        $beforeSnapshot = self::takeSnapshot("before_{$operation}");
        
        try {
            $result = $callback();
            return $result;
        } finally {
            $afterSnapshot = self::takeSnapshot("after_{$operation}");
            
            $allocation = [
                'operation' => $operation,
                'memory_allocated' => $afterSnapshot['memory_usage_real'] - $beforeSnapshot['memory_usage_real'],
                'peak_memory_increase' => $afterSnapshot['peak_memory_real'] - $beforeSnapshot['peak_memory_real'],
                'objects_created' => $afterSnapshot['allocated_objects'] - $beforeSnapshot['allocated_objects'],
                'duration' => $afterSnapshot['timestamp'] - $beforeSnapshot['timestamp'],
                'before_snapshot' => $beforeSnapshot,
                'after_snapshot' => $afterSnapshot,
            ];
            
            self::$allocations[$operation] = $allocation;
            
            // Check for potential memory issues
            self::analyzeAllocation($allocation);
        }
    }
    
    /**
     * Detect memory leaks by analyzing memory usage patterns.
     */
    public static function detectMemoryLeaks(): array
    {
        $samples = self::$leakDetection['samples'];
        $leaks = [];
        
        if (count($samples) < 3) {
            return $leaks; // Need at least 3 samples for trend analysis
        }
        
        // Analyze memory growth trend
        $recentSamples = array_slice($samples, -10); // Last 10 samples
        $memoryValues = array_column($recentSamples, 'memory');
        
        // Calculate linear regression to detect consistent growth
        $trend = self::calculateMemoryTrend($memoryValues);
        
        if ($trend['slope'] > 0 && $trend['correlation'] > 0.7) {
            $projectedGrowth = $trend['slope'] * count($samples);
            
            if ($projectedGrowth > self::$leakDetection['threshold']) {
                $leak = [
                    'type' => 'consistent_growth',
                    'growth_rate' => $trend['slope'],
                    'correlation' => $trend['correlation'],
                    'projected_growth' => $projectedGrowth,
                    'samples_analyzed' => count($recentSamples),
                    'severity' => $projectedGrowth > (self::$leakDetection['threshold'] * 2) ? 'high' : 'medium',
                ];
                
                $leaks[] = $leak;
                self::$leakDetection['leaks_detected'][] = $leak;
            }
        }
        
        // Check for sudden memory spikes
        $spikes = self::detectMemorySpikes($samples);
        foreach ($spikes as $spike) {
            $leaks[] = $spike;
            self::$leakDetection['leaks_detected'][] = $spike;
        }
        
        return $leaks;
    }
    
    /**
     * Get memory usage statistics.
     */
    public static function getMemoryStats(): array
    {
        if (empty(self::$snapshots)) {
            return [
                'total_snapshots' => 0,
                'memory_range' => ['min' => 0, 'max' => 0],
                'peak_memory' => 0,
                'average_usage' => 0,
            ];
        }
        
        $memoryValues = array_column(self::$snapshots, 'memory_usage_real');
        $peakValues = array_column(self::$snapshots, 'peak_memory_real');
        
        return [
            'total_snapshots' => count(self::$snapshots),
            'memory_range' => [
                'min' => min($memoryValues),
                'max' => max($memoryValues),
                'difference' => max($memoryValues) - min($memoryValues),
            ],
            'peak_memory' => max($peakValues),
            'average_usage' => array_sum($memoryValues) / count($memoryValues),
            'current_usage' => memory_get_usage(true),
            'memory_limit' => self::getMemoryLimit(),
            'allocations_tracked' => count(self::$allocations),
            'leaks_detected' => count(self::$leakDetection['leaks_detected']),
        ];
    }
    
    /**
     * Get memory allocation analysis.
     */
    public static function getAllocationAnalysis(): array
    {
        $analysis = [
            'total_allocations' => count(self::$allocations),
            'memory_intensive_operations' => [],
            'object_intensive_operations' => [],
            'slow_allocations' => [],
            'recommendations' => [],
        ];
        
        foreach (self::$allocations as $operation => $allocation) {
            // Identify memory-intensive operations
            if ($allocation['memory_allocated'] > 5 * 1024 * 1024) { // 5MB
                $analysis['memory_intensive_operations'][] = [
                    'operation' => $operation,
                    'memory_allocated' => $allocation['memory_allocated'],
                    'memory_allocated_mb' => round($allocation['memory_allocated'] / 1024 / 1024, 2),
                ];
            }
            
            // Identify object-intensive operations
            if ($allocation['objects_created'] > 1000) {
                $analysis['object_intensive_operations'][] = [
                    'operation' => $operation,
                    'objects_created' => $allocation['objects_created'],
                ];
            }
            
            // Identify slow allocations
            if ($allocation['duration'] > 1.0) { // 1 second
                $analysis['slow_allocations'][] = [
                    'operation' => $operation,
                    'duration' => $allocation['duration'],
                    'memory_allocated' => $allocation['memory_allocated'],
                ];
            }
        }
        
        // Generate recommendations
        $analysis['recommendations'] = self::generateMemoryOptimizations();
        
        return $analysis;
    }
    
    /**
     * Generate memory optimization recommendations.
     */
    public static function generateMemoryOptimizations(): array
    {
        $optimizations = [];
        $stats = self::getMemoryStats();
        $allocations = self::getAllocationAnalysis();
        
        // Check overall memory usage
        if ($stats['memory_range']['difference'] > 50 * 1024 * 1024) { // 50MB variation
            $optimizations[] = 'High memory variation detected - implement better memory management and cleanup';
        }
        
        // Check for memory-intensive operations
        if (!empty($allocations['memory_intensive_operations'])) {
            $optimizations[] = 'Memory-intensive operations detected - consider using generators, streaming, or chunking';
        }
        
        // Check for object-intensive operations
        if (!empty($allocations['object_intensive_operations'])) {
            $optimizations[] = 'Object-intensive operations detected - consider object pooling or factory patterns';
        }
        
        // Check for memory leaks
        $leaks = self::detectMemoryLeaks();
        if (!empty($leaks)) {
            $optimizations[] = 'Memory leaks detected - implement proper cleanup and resource management';
        }
        
        // Check memory limit usage
        $memoryLimit = self::getMemoryLimit();
        if ($memoryLimit > 0 && ($stats['current_usage'] / $memoryLimit) > 0.8) {
            $optimizations[] = 'Approaching memory limit - optimize memory usage or increase memory limit';
        }
        
        self::$optimizations = array_unique($optimizations);
        
        return self::$optimizations;
    }
    
    /**
     * Export memory usage report.
     */
    public static function exportReport(string $filePath, string $format = 'json'): bool
    {
        $report = [
            'generated_at' => date('Y-m-d H:i:s'),
            'memory_stats' => self::getMemoryStats(),
            'allocation_analysis' => self::getAllocationAnalysis(),
            'memory_leaks' => self::detectMemoryLeaks(),
            'snapshots' => self::$snapshots,
            'allocations' => self::$allocations,
            'optimizations' => self::generateMemoryOptimizations(),
        ];
        
        $content = match ($format) {
            'json' => json_encode($report, JSON_PRETTY_PRINT),
            'csv' => self::convertToCsv($report),
            default => json_encode($report, JSON_PRETTY_PRINT),
        };
        
        return file_put_contents($filePath, $content) !== false;
    }
    
    /**
     * Reset memory tracking.
     */
    public static function reset(): void
    {
        self::$snapshots = [];
        self::$allocations = [];
        self::$leakDetection = [
            'baseline' => memory_get_usage(true),
            'threshold' => 10 * 1024 * 1024,
            'samples' => [],
            'leaks_detected' => [],
        ];
        self::$optimizations = [];
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
     * Count allocated objects (approximation).
     */
    private static function countAllocatedObjects(): int
    {
        if (function_exists('gc_status')) {
            $status = gc_status();
            return $status['roots'] ?? 0;
        }
        
        return 0;
    }
    
    /**
     * Analyze memory allocation for potential issues.
     */
    private static function analyzeAllocation(array $allocation): void
    {
        $operation = $allocation['operation'];
        $memoryAllocated = $allocation['memory_allocated'];
        
        // Check for excessive memory allocation
        if ($memoryAllocated > 20 * 1024 * 1024) { // 20MB
            self::$optimizations[] = "Operation '{$operation}' uses excessive memory ({$memoryAllocated} bytes)";
        }
        
        // Check for memory allocation without corresponding cleanup
        if ($memoryAllocated > 0 && $allocation['objects_created'] > 100) {
            self::$optimizations[] = "Operation '{$operation}' creates many objects - consider cleanup";
        }
    }
    
    /**
     * Calculate memory usage trend using linear regression.
     */
    private static function calculateMemoryTrend(array $memoryValues): array
    {
        $n = count($memoryValues);
        if ($n < 2) {
            return ['slope' => 0, 'correlation' => 0];
        }
        
        $x = range(0, $n - 1);
        $y = $memoryValues;
        
        $sumX = array_sum($x);
        $sumY = array_sum($y);
        $sumXY = 0;
        $sumXX = 0;
        $sumYY = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumXY += $x[$i] * $y[$i];
            $sumXX += $x[$i] * $x[$i];
            $sumYY += $y[$i] * $y[$i];
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        
        // Calculate correlation coefficient
        $numerator = $n * $sumXY - $sumX * $sumY;
        $denominator = sqrt(($n * $sumXX - $sumX * $sumX) * ($n * $sumYY - $sumY * $sumY));
        $correlation = $denominator != 0 ? $numerator / $denominator : 0;
        
        return [
            'slope' => $slope,
            'correlation' => abs($correlation),
        ];
    }
    
    /**
     * Detect sudden memory spikes.
     */
    private static function detectMemorySpikes(array $samples): array
    {
        $spikes = [];
        
        for ($i = 1; $i < count($samples); $i++) {
            $current = $samples[$i]['memory'];
            $previous = $samples[$i - 1]['memory'];
            $increase = $current - $previous;
            
            // Detect spikes greater than 10MB
            if ($increase > 10 * 1024 * 1024) {
                $spikes[] = [
                    'type' => 'memory_spike',
                    'label' => $samples[$i]['label'],
                    'memory_increase' => $increase,
                    'memory_increase_mb' => round($increase / 1024 / 1024, 2),
                    'timestamp' => $samples[$i]['timestamp'],
                    'severity' => $increase > 50 * 1024 * 1024 ? 'high' : 'medium',
                ];
            }
        }
        
        return $spikes;
    }
    
    /**
     * Convert report to CSV format.
     */
    private static function convertToCsv(array $report): string
    {
        $csv = "Label,Memory Usage (MB),Peak Memory (MB),Objects,Timestamp\n";
        
        foreach ($report['snapshots'] as $snapshot) {
            $csv .= sprintf(
                "%s,%.2f,%.2f,%d,%s\n",
                $snapshot['label'],
                $snapshot['memory_usage_real'] / 1024 / 1024,
                $snapshot['peak_memory_real'] / 1024 / 1024,
                $snapshot['allocated_objects'],
                date('Y-m-d H:i:s', (int)$snapshot['timestamp'])
            );
        }
        
        return $csv;
    }
}