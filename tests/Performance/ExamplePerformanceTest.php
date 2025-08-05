<?php

declare(strict_types=1);

/**
 * Example Performance Test
 * 
 * Demonstrates how to write performance tests using the advanced PEST infrastructure.
 * This test showcases performance monitoring, memory tracking, and threshold assertions.
 */

use Tests\Support\Builders\ValidationConfigBuilder;
use Tests\Support\Doubles\TestDoubleFactory;
use App\Services\Contracts\LinkValidationInterface;

describe('Performance Testing Example', function () {
    
    beforeEach(function () {
        // Skip if environment is too slow for performance testing
        $this->skipIfSlowEnvironment(0.5, 20 * 1024 * 1024); // 500ms, 20MB
    });
    
    it('validates links within performance thresholds', function () {
        // Setup test data using lazy initialization
        $this->let('testUrls', fn() => array_fill(0, 100, 'https://example.com'));
        $this->let('validationConfig', fn() => ValidationConfigBuilder::create()
            ->forFastTesting()
            ->withConcurrentRequests(20)
            ->build()
        );
        
        // Create realistic test double with state
        $linkValidation = TestDoubleFactory::linkValidationWithState([
            'valid_urls' => ['https://example.com'],
            'broken_urls' => [],
        ]);
        
        $this->bindService(LinkValidationInterface::class, $linkValidation);
        
        // Measure performance of validation operation
        $results = $this->assertPerformant(function () {
            $service = app(LinkValidationInterface::class);
            return $service->validateLinks($this->testUrls);
        }, 2.0, 50 * 1024 * 1024); // 2 seconds, 50MB
        
        // Verify results
        expect($results)->toBeValidationCollection(100);
        
        // Record checkpoint for detailed analysis
        $this->recordCheckpoint('validation_complete');
        
        // Assert specific performance metrics
        $this->assertPerformance('validation_complete', 2.0, 50 * 1024 * 1024);
    });
    
    it('handles large datasets efficiently', function () {
        // Create large dataset
        $largeDataset = array_fill(0, 1000, 'https://example.com');
        
        // Measure memory usage during processing
        $memoryBefore = memory_get_usage(true);
        
        $result = $this->measurePerformance(function () use ($largeDataset) {
            // Simulate processing large dataset
            $processed = [];
            foreach ($largeDataset as $url) {
                $processed[] = strtolower($url);
            }
            return $processed;
        }, 'large_dataset_processing');
        
        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;
        
        // Assert memory efficiency
        expect($memoryUsed)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB
        
        // Assert processing time
        $this->assertPerformance('large_dataset_processing', 1.0, 10 * 1024 * 1024);
        
        expect($result)->toHaveCount(1000);
    });
    
    it('performs concurrent operations efficiently', function () {
        // Setup concurrent test scenario
        $this->let('concurrentTasks', fn() => array_fill(0, 10, function () {
            usleep(100000); // 100ms per task
            return 'task_complete';
        }));
        
        // Measure concurrent execution
        $results = $this->measurePerformance(function () {
            $tasks = $this->concurrentTasks;
            $results = [];
            
            // Simulate concurrent execution (in real scenario, use proper async)
            foreach ($tasks as $task) {
                $results[] = $task();
            }
            
            return $results;
        }, 'concurrent_execution');
        
        // Should complete faster than sequential execution
        // (In real concurrent execution, this would be much faster)
        $this->assertPerformance('concurrent_execution', 2.0, 5 * 1024 * 1024);
        
        expect($results)->toHaveCount(10);
        expect($results[0])->toBe('task_complete');
    });
    
    it('maintains performance under stress', function () {
        // Skip in CI if too resource intensive
        $this->skipInCI(env('SKIP_STRESS_TESTS', true), 'Stress tests disabled in CI');
        
        // Create stress test scenario
        $iterations = 100;
        $performanceMetrics = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $metrics = $this->measurePerformance(function () {
                // Simulate work that might degrade over time
                $data = array_fill(0, 100, 'test_data');
                return array_map('strtoupper', $data);
            }, "iteration_{$i}");
            
            $performanceMetrics[] = $this->getPerformanceMetrics()["iteration_{$i}"];
        }
        
        // Analyze performance degradation
        $firstTenAvg = array_sum(array_slice(array_column($performanceMetrics, 'time'), 0, 10)) / 10;
        $lastTenAvg = array_sum(array_slice(array_column($performanceMetrics, 'time'), -10)) / 10;
        
        // Performance should not degrade significantly
        $degradationRatio = $lastTenAvg / $firstTenAvg;
        expect($degradationRatio)->toBeLessThan(2.0); // Less than 2x slower
        
        // Memory usage should remain stable
        $firstTenMemory = array_sum(array_slice(array_column($performanceMetrics, 'memory'), 0, 10)) / 10;
        $lastTenMemory = array_sum(array_slice(array_column($performanceMetrics, 'memory'), -10)) / 10;
        
        $memoryGrowthRatio = $lastTenMemory / max($firstTenMemory, 1);
        expect($memoryGrowthRatio)->toBeLessThan(1.5); // Less than 50% memory growth
    });
    
})->group('performance', 'slow', 'memory-intensive');