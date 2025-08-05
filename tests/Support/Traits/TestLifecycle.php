<?php

declare(strict_types=1);

namespace Tests\Support\Traits;

/**
 * Test Lifecycle Management Trait for PEST Tests
 * 
 * Provides advanced lifecycle management with performance monitoring,
 * memory tracking, and efficient cleanup patterns.
 */
trait TestLifecycle
{
    /**
     * Test performance metrics
     * @var array<string, array{time: float, memory: int}>
     */
    private array $performanceMetrics = [];
    
    /**
     * Test start time
     */
    private float $testStartTime;
    
    /**
     * Test start memory usage
     */
    private int $testStartMemory;
    
    /**
     * Cleanup callbacks
     * @var array<callable(): void>
     */
    private array $cleanupCallbacks = [];
    
    /**
     * Initialize test lifecycle tracking
     * Call this in beforeEach()
     * 
     * @return void
     */
    protected function initializeTestLifecycle(): void
    {
        $this->testStartTime = microtime(true);
        $this->testStartMemory = memory_get_usage(true);
        $this->performanceMetrics = [];
        $this->cleanupCallbacks = [];
    }
    
    /**
     * Finalize test lifecycle tracking
     * Call this in afterEach()
     * 
     * @return void
     */
    protected function finalizeTestLifecycle(): void
    {
        // Record performance metrics
        $this->recordPerformanceMetrics();
        
        // Execute cleanup callbacks
        $this->executeCleanupCallbacks();
        
        // Run garbage collection if needed
        $this->conditionalGarbageCollection();
    }
    
    /**
     * Add a cleanup callback to be executed after the test
     *
     * @param callable(): void $callback Cleanup function
     * @return void
     */
    protected function addCleanup(callable $callback): void
    {
        $this->cleanupCallbacks[] = $callback;
    }
    
    /**
     * Record a performance checkpoint
     * 
     * @param string $checkpoint Checkpoint name
     * @return void
     */
    protected function recordCheckpoint(string $checkpoint): void
    {
        $this->performanceMetrics[$checkpoint] = [
            'time' => microtime(true) - $this->testStartTime,
            'memory' => memory_get_usage(true) - $this->testStartMemory,
        ];
    }
    
    /**
     * Measure performance of a code block
     *
     * @param callable(): mixed $callback Code to measure
     * @param string|null $label Optional label for the measurement
     * @return mixed Result of the callback
     */
    protected function measurePerformance(callable $callback, ?string $label = null): mixed
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        $result = $callback();
        
        $executionTime = microtime(true) - $startTime;
        $memoryUsage = memory_get_usage(true) - $startMemory;
        
        $label = $label ?? 'measurement_' . count($this->performanceMetrics);
        
        $this->performanceMetrics[$label] = [
            'time' => $executionTime,
            'memory' => $memoryUsage,
        ];
        
        return $result;
    }
    
    /**
     * Assert performance within thresholds
     * 
     * @param string $checkpoint Checkpoint name
     * @param float $maxTime Maximum execution time in seconds
     * @param int $maxMemory Maximum memory usage in bytes
     * @return void
     */
    protected function assertPerformance(string $checkpoint, float $maxTime, int $maxMemory): void
    {
        if (!isset($this->performanceMetrics[$checkpoint])) {
            throw new \InvalidArgumentException("Performance checkpoint '{$checkpoint}' not found");
        }
        
        $metrics = $this->performanceMetrics[$checkpoint];
        
        expect($metrics['time'])
            ->toBeLessThan($maxTime, "Execution time exceeded threshold: {$metrics['time']}s > {$maxTime}s");
            
        expect($metrics['memory'])
            ->toBeLessThan($maxMemory, "Memory usage exceeded threshold: {$metrics['memory']} bytes > {$maxMemory} bytes");
    }
    
    /**
     * Get performance metrics for the current test
     *
     * @return array<string, array{time: float, memory: int}>
     */
    protected function getPerformanceMetrics(): array
    {
        return $this->performanceMetrics;
    }
    
    /**
     * Get total test execution time
     * 
     * @return float
     */
    protected function getTotalExecutionTime(): float
    {
        return microtime(true) - $this->testStartTime;
    }
    
    /**
     * Get total memory usage
     * 
     * @return int
     */
    protected function getTotalMemoryUsage(): int
    {
        return memory_get_usage(true) - $this->testStartMemory;
    }
    
    /**
     * Skip test if running in CI and condition is met
     * 
     * @param bool $condition Condition to check
     * @param string $reason Reason for skipping
     * @return void
     */
    protected function skipInCI(bool $condition, string $reason = 'Skipped in CI environment'): void
    {
        if ($condition && $this->isCI()) {
            $this->markTestSkipped($reason);
        }
    }
    
    /**
     * Skip test if performance requirements can't be met
     * 
     * @param float $requiredTime Required execution time
     * @param int $requiredMemory Required memory
     * @return void
     */
    protected function skipIfSlowEnvironment(float $requiredTime = 1.0, int $requiredMemory = 50 * 1024 * 1024): void
    {
        // Quick performance check
        $start = microtime(true);
        $startMem = memory_get_usage(true);
        
        // Simple operation to test environment speed
        for ($i = 0; $i < 10000; $i++) {
            $dummy = md5((string) $i);
        }
        
        $elapsed = microtime(true) - $start;
        $memUsed = memory_get_usage(true) - $startMem;
        
        if ($elapsed > $requiredTime || $memUsed > $requiredMemory) {
            $this->markTestSkipped('Environment too slow for performance test');
        }
    }
    
    /**
     * Record final performance metrics
     * 
     * @return void
     */
    private function recordPerformanceMetrics(): void
    {
        $this->performanceMetrics['total'] = [
            'time' => $this->getTotalExecutionTime(),
            'memory' => $this->getTotalMemoryUsage(),
        ];
        
        // Warn about slow tests
        if ($this->getTotalExecutionTime() > 1.0) {
            echo "\n⚠️  Slow test detected: {$this->getTotalExecutionTime()}s\n";
        }
        
        // Warn about memory-heavy tests
        if ($this->getTotalMemoryUsage() > 10 * 1024 * 1024) {
            $memoryMB = round($this->getTotalMemoryUsage() / 1024 / 1024, 2);
            echo "\n⚠️  Memory-heavy test detected: {$memoryMB}MB\n";
        }
    }
    
    /**
     * Execute all cleanup callbacks
     * 
     * @return void
     */
    private function executeCleanupCallbacks(): void
    {
        foreach ($this->cleanupCallbacks as $callback) {
            try {
                if (is_callable($callback)) {
                    $callback();
                }
            } catch (\Throwable $e) {
                // Log cleanup errors but don't fail the test
                error_log("Cleanup callback failed: " . $e->getMessage());
            }
        }
        
        $this->cleanupCallbacks = [];
    }
    
    /**
     * Run garbage collection if memory usage is high
     * 
     * @return void
     */
    private function conditionalGarbageCollection(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->parseMemoryLimit(ini_get('memory_limit'));
        
        // Run GC if using more than 70% of memory limit
        if ($memoryUsage > ($memoryLimit * 0.7)) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Parse memory limit string to bytes
     * 
     * @param string $limit Memory limit string
     * @return int Memory limit in bytes
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $value = (int) $limit;
        
        return match ($last) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
    
    /**
     * Check if running in CI environment
     * 
     * @return bool
     */
    private function isCI(): bool
    {
        return !empty($_ENV['CI']) || !empty($_ENV['GITHUB_ACTIONS']) || !empty($_ENV['TRAVIS']);
    }
}