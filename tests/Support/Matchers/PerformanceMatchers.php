<?php

declare(strict_types=1);

/**
 * Performance Custom Matchers for PEST
 * 
 * Provides domain-specific matchers for performance testing,
 * including execution time, memory usage, and throughput assertions.
 */

if (!function_exists('toCompleteWithin')) {
    /**
     * Assert that a callable completes within a specified time
     */
    function toCompleteWithin(float $maxSeconds): Closure
    {
        return function () use ($maxSeconds) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            $startTime = microtime(true);
            ($this->value)();
            $endTime = microtime(true);
            
            $executionTime = $endTime - $startTime;
            return $executionTime <= $maxSeconds;
        };
    }
}

if (!function_exists('toExecuteInLessThan')) {
    /**
     * Assert that execution time is less than specified duration
     */
    function toExecuteInLessThan(float $maxSeconds): Closure
    {
        return function () use ($maxSeconds) {
            if (is_float($this->value) || is_int($this->value)) {
                return (float) $this->value < $maxSeconds;
            }
            
            return false;
        };
    }
}

if (!function_exists('toExecuteInMoreThan')) {
    /**
     * Assert that execution time is more than specified duration
     */
    function toExecuteInMoreThan(float $minSeconds): Closure
    {
        return function () use ($minSeconds) {
            if (is_float($this->value) || is_int($this->value)) {
                return (float) $this->value > $minSeconds;
            }
            
            return false;
        };
    }
}

if (!function_exists('toExecuteInBetween')) {
    /**
     * Assert that execution time is between specified durations
     */
    function toExecuteInBetween(float $minSeconds, float $maxSeconds): Closure
    {
        return function () use ($minSeconds, $maxSeconds) {
            if (is_float($this->value) || is_int($this->value)) {
                $time = (float) $this->value;
                return $time >= $minSeconds && $time <= $maxSeconds;
            }
            
            return false;
        };
    }
}

if (!function_exists('toUseMemoryLessThan')) {
    /**
     * Assert that memory usage is less than specified amount
     */
    function toUseMemoryLessThan(int $maxBytes): Closure
    {
        return function () use ($maxBytes) {
            if (is_callable($this->value)) {
                $startMemory = memory_get_usage(true);
                ($this->value)();
                $endMemory = memory_get_usage(true);
                $memoryUsed = $endMemory - $startMemory;
                
                return $memoryUsed < $maxBytes;
            }
            
            if (is_int($this->value)) {
                return $this->value < $maxBytes;
            }
            
            return false;
        };
    }
}

if (!function_exists('toUseMemoryMoreThan')) {
    /**
     * Assert that memory usage is more than specified amount
     */
    function toUseMemoryMoreThan(int $minBytes): Closure
    {
        return function () use ($minBytes) {
            if (is_callable($this->value)) {
                $startMemory = memory_get_usage(true);
                ($this->value)();
                $endMemory = memory_get_usage(true);
                $memoryUsed = $endMemory - $startMemory;
                
                return $memoryUsed > $minBytes;
            }
            
            if (is_int($this->value)) {
                return $this->value > $minBytes;
            }
            
            return false;
        };
    }
}

if (!function_exists('toUseMemoryBetween')) {
    /**
     * Assert that memory usage is between specified amounts
     */
    function toUseMemoryBetween(int $minBytes, int $maxBytes): Closure
    {
        return function () use ($minBytes, $maxBytes) {
            if (is_callable($this->value)) {
                $startMemory = memory_get_usage(true);
                ($this->value)();
                $endMemory = memory_get_usage(true);
                $memoryUsed = $endMemory - $startMemory;
                
                return $memoryUsed >= $minBytes && $memoryUsed <= $maxBytes;
            }
            
            if (is_int($this->value)) {
                return $this->value >= $minBytes && $this->value <= $maxBytes;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveThroughputAbove')) {
    /**
     * Assert that throughput is above specified operations per second
     */
    function toHaveThroughputAbove(float $minOpsPerSecond): Closure
    {
        return function () use ($minOpsPerSecond) {
            if (is_callable($this->value)) {
                $operations = 100; // Default number of operations to test
                $startTime = microtime(true);
                
                for ($i = 0; $i < $operations; $i++) {
                    ($this->value)();
                }
                
                $endTime = microtime(true);
                $totalTime = $endTime - $startTime;
                $throughput = $operations / $totalTime;
                
                return $throughput > $minOpsPerSecond;
            }
            
            if (is_float($this->value) || is_int($this->value)) {
                return (float) $this->value > $minOpsPerSecond;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveThroughputBelow')) {
    /**
     * Assert that throughput is below specified operations per second
     */
    function toHaveThroughputBelow(float $maxOpsPerSecond): Closure
    {
        return function () use ($maxOpsPerSecond) {
            if (is_callable($this->value)) {
                $operations = 100; // Default number of operations to test
                $startTime = microtime(true);
                
                for ($i = 0; $i < $operations; $i++) {
                    ($this->value)();
                }
                
                $endTime = microtime(true);
                $totalTime = $endTime - $startTime;
                $throughput = $operations / $totalTime;
                
                return $throughput < $maxOpsPerSecond;
            }
            
            if (is_float($this->value) || is_int($this->value)) {
                return (float) $this->value < $maxOpsPerSecond;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBePerformant')) {
    /**
     * Assert that operation meets general performance criteria
     */
    function toBePerformant(float $maxTime = 1.0, int $maxMemory = 10 * 1024 * 1024): Closure
    {
        return function () use ($maxTime, $maxMemory) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            ($this->value)();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $executionTime = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;
            
            return $executionTime <= $maxTime && $memoryUsed <= $maxMemory;
        };
    }
}

if (!function_exists('toScaleLinearly')) {
    /**
     * Assert that operation scales linearly with input size
     */
    function toScaleLinearly(array $inputSizes, float $tolerance = 0.2): Closure
    {
        return function () use ($inputSizes, $tolerance) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            $times = [];
            foreach ($inputSizes as $size) {
                $startTime = microtime(true);
                ($this->value)($size);
                $endTime = microtime(true);
                $times[] = $endTime - $startTime;
            }
            
            // Check if times scale roughly linearly
            $ratios = [];
            for ($i = 1; $i < count($times); $i++) {
                $timeRatio = $times[$i] / $times[$i - 1];
                $sizeRatio = $inputSizes[$i] / $inputSizes[$i - 1];
                $ratios[] = abs($timeRatio - $sizeRatio) / $sizeRatio;
            }
            
            $avgDeviation = array_sum($ratios) / count($ratios);
            return $avgDeviation <= $tolerance;
        };
    }
}

if (!function_exists('toHaveConstantTime')) {
    /**
     * Assert that operation has constant time complexity
     */
    function toHaveConstantTime(array $inputSizes, float $tolerance = 0.1): Closure
    {
        return function () use ($inputSizes, $tolerance) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            $times = [];
            foreach ($inputSizes as $size) {
                $startTime = microtime(true);
                ($this->value)($size);
                $endTime = microtime(true);
                $times[] = $endTime - $startTime;
            }
            
            // Check if all times are within tolerance of the first time
            $baseTime = $times[0];
            foreach ($times as $time) {
                $deviation = abs($time - $baseTime) / $baseTime;
                if ($deviation > $tolerance) {
                    return false;
                }
            }
            
            return true;
        };
    }
}

if (!function_exists('toNotLeakMemory')) {
    /**
     * Assert that operation doesn't leak memory
     */
    function toNotLeakMemory(int $iterations = 100, int $tolerance = 1024): Closure
    {
        return function () use ($iterations, $tolerance) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            $initialMemory = memory_get_usage(true);
            
            for ($i = 0; $i < $iterations; $i++) {
                ($this->value)();
                
                // Force garbage collection periodically
                if ($i % 10 === 0) {
                    gc_collect_cycles();
                }
            }
            
            gc_collect_cycles();
            $finalMemory = memory_get_usage(true);
            $memoryDiff = $finalMemory - $initialMemory;
            
            return $memoryDiff <= $tolerance;
        };
    }
}

if (!function_exists('toBeFasterThan')) {
    /**
     * Assert that one operation is faster than another
     */
    function toBeFasterThan(callable $comparison, int $iterations = 10): Closure
    {
        return function () use ($comparison, $iterations) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            // Measure first operation
            $startTime1 = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                ($this->value)();
            }
            $endTime1 = microtime(true);
            $time1 = $endTime1 - $startTime1;
            
            // Measure comparison operation
            $startTime2 = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $comparison();
            }
            $endTime2 = microtime(true);
            $time2 = $endTime2 - $startTime2;
            
            return $time1 < $time2;
        };
    }
}

if (!function_exists('toUseMemoryEfficiently')) {
    /**
     * Assert that operation uses memory efficiently compared to baseline
     */
    function toUseMemoryEfficiently(callable $baseline, float $maxRatio = 1.5): Closure
    {
        return function () use ($baseline, $maxRatio) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            // Measure memory usage of the operation
            $startMemory1 = memory_get_usage(true);
            ($this->value)();
            $endMemory1 = memory_get_usage(true);
            $memoryUsed1 = $endMemory1 - $startMemory1;
            
            // Reset memory state
            gc_collect_cycles();
            
            // Measure memory usage of baseline
            $startMemory2 = memory_get_usage(true);
            $baseline();
            $endMemory2 = memory_get_usage(true);
            $memoryUsed2 = $endMemory2 - $startMemory2;
            
            if ($memoryUsed2 === 0) {
                return $memoryUsed1 === 0;
            }
            
            $ratio = $memoryUsed1 / $memoryUsed2;
            return $ratio <= $maxRatio;
        };
    }
}

if (!function_exists('toMeetPerformanceBenchmark')) {
    /**
     * Assert that operation meets a comprehensive performance benchmark
     */
    function toMeetPerformanceBenchmark(array $criteria): Closure
    {
        return function () use ($criteria) {
            if (!is_callable($this->value)) {
                return false;
            }
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $result = ($this->value)();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $executionTime = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;
            
            // Check time criteria
            if (isset($criteria['max_time']) && $executionTime > $criteria['max_time']) {
                return false;
            }
            
            if (isset($criteria['min_time']) && $executionTime < $criteria['min_time']) {
                return false;
            }
            
            // Check memory criteria
            if (isset($criteria['max_memory']) && $memoryUsed > $criteria['max_memory']) {
                return false;
            }
            
            if (isset($criteria['min_memory']) && $memoryUsed < $criteria['min_memory']) {
                return false;
            }
            
            // Check throughput criteria
            if (isset($criteria['min_throughput'])) {
                $operations = $criteria['operations'] ?? 1;
                $throughput = $operations / $executionTime;
                if ($throughput < $criteria['min_throughput']) {
                    return false;
                }
            }
            
            return true;
        };
    }
}