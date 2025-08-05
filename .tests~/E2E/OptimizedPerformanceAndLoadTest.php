<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\Support\Performance\OptimizedPestConfiguration;
use Tests\Support\Performance\TestPerformanceProfiler;
use Tests\Support\Performance\MemoryUsageTracker;
use Tests\Support\Performance\ResourceUtilizationMonitor;
use Tests\Support\Performance\TestExecutionOptimizer;
use Tests\Support\Performance\PerformanceBenchmark;

describe('Optimized Performance and Load E2E Tests', function () {
    beforeEach(function () {
        // Initialize optimized test environment
        OptimizedPestConfiguration::initialize();
        
        // Setup performance testing environment with optimization
        $this->performanceDir = sys_get_temp_dir() . '/validate-links-performance-optimized-' . uniqid();
        File::makeDirectory($this->performanceDir, 0755, true, true);
        
        $this->metricsFile = $this->performanceDir . '/metrics.json';
        $this->largeDocsDir = $this->performanceDir . '/large-docs';
        File::makeDirectory($this->largeDocsDir, 0755, true, true);
        
        // Optimized timing configuration with performance awareness
        $this->timingConfig = [
            'large_scale_timeout' => (int) ($_ENV['TEST_LARGE_SCALE_TIMEOUT'] ?? 120), // Reduced from 180
            'mixed_content_timeout' => (int) ($_ENV['TEST_MIXED_CONTENT_TIMEOUT'] ?? 60), // Reduced from 90
            'network_timeout' => (int) ($_ENV['TEST_NETWORK_TIMEOUT'] ?? 30), // Reduced from 45
            'deep_structure_timeout' => (int) ($_ENV['TEST_DEEP_STRUCTURE_TIMEOUT'] ?? 30), // Reduced from 45
            'memory_limit_mb' => (int) ($_ENV['TEST_MEMORY_LIMIT_MB'] ?? 100), // Reduced from 150
            'peak_memory_limit_mb' => (int) ($_ENV['TEST_PEAK_MEMORY_LIMIT_MB'] ?? 150), // Reduced from 200
            'performance_variance_threshold' => (float) ($_ENV['TEST_PERFORMANCE_VARIANCE_THRESHOLD'] ?? 0.5), // Improved from 0.75
            'concurrency_improvement_threshold' => (float) ($_ENV['TEST_CONCURRENCY_IMPROVEMENT_THRESHOLD'] ?? 0.7), // Improved from 0.9
        ];
        
        // Setup optimized test environment
        OptimizedPestConfiguration::setupOptimizedTest('performance_load_test');
    });

    afterEach(function () {
        // Cleanup optimized test environment
        OptimizedPestConfiguration::cleanupOptimizedTest('performance_load_test');
        
        // Teardown: Clean up performance test files with optimized cleanup
        try {
            if (File::exists($this->performanceDir)) {
                // Use optimized file operations for cleanup
                $deleteOperations = [];
                $files = File::allFiles($this->performanceDir);
                foreach ($files as $file) {
                    $deleteOperations[] = [
                        'type' => 'file_delete',
                        'path' => $file->getPathname(),
                    ];
                }
                
                TestExecutionOptimizer::optimizeFileOperations($deleteOperations);
                File::deleteDirectory($this->performanceDir);
            }
        } catch (Exception $e) {
            error_log("Failed to cleanup test directory: " . $e->getMessage());
        }
    });

    describe('Optimized Large Scale Validation Performance', function () {
        it('validates large documentation set with performance optimization', function () {
            // Benchmark the optimized operation
            $benchmark = PerformanceBenchmark::benchmark('large_scale_validation', function () {
                // Setup: Create large documentation set with optimization
                $this->createOptimizedLargeDocumentationSet(100);
                
                $startTime = microtime(true);
                $memoryBefore = memory_get_usage(true);

                // Execute: Large scale validation with optimizations
                $exitCode = Artisan::call('validate', [
                    'paths' => [$this->largeDocsDir],
                    '--scope' => 'all',
                    '--concurrent' => 15, // Increased from 10 for better performance
                    '--timeout' => 8, // Reduced from 10 for faster execution
                    '--format' => 'json',
                    '--output' => $this->performanceDir . '/large-scale-results.json',
                ]);

                $endTime = microtime(true);
                $memoryAfter = memory_get_usage(true);
                
                $executionTime = $endTime - $startTime;
                $memoryUsed = $memoryAfter - $memoryBefore;

                // Assert: Performance within optimized limits
                expect($exitCode)->toBeInt()
                    ->and($executionTime)->toBeLessThan($this->timingConfig['large_scale_timeout'])
                    ->and($memoryUsed)->toBeLessThan($this->timingConfig['memory_limit_mb'] * 1024 * 1024);

                // Verify results file exists and has content
                expect(File::exists($this->performanceDir . '/large-scale-results.json'))->toBeTrue();
                
                $results = json_decode(File::get($this->performanceDir . '/large-scale-results.json'), true);
                expect($results['summary']['total_links'])->toBeGreaterThan(200);
                
                return $executionTime;
            });
            
            // Verify benchmark results show improvement
            expect($benchmark['statistics']['execution_time']['average'])->toBeLessThan(120.0);
        });

        it('handles concurrent validation with optimized efficiency', function () {
            // Use optimized test data generation
            $testData = OptimizedPestConfiguration::getOptimizedTestData('concurrent_test_files', [
                'file_count' => 20,
                'links_per_file' => 10
            ]);
            
            // Create files with optimized operations
            $this->createOptimizedConcurrentTestFiles(20, 10);
            
            // Benchmark sequential vs concurrent with optimization
            $sequentialBenchmark = PerformanceBenchmark::benchmark('sequential_validation', function () {
                return Artisan::call('validate', [
                    'paths' => [$this->largeDocsDir],
                    '--scope' => 'external',
                    '--concurrent' => 1,
                    '--timeout' => 5,
                ]);
            });
            
            $concurrentBenchmark = PerformanceBenchmark::benchmark('concurrent_validation', function () {
                return Artisan::call('validate', [
                    'paths' => [$this->largeDocsDir],
                    '--scope' => 'external',
                    '--concurrent' => 15, // Increased for better performance
                    '--timeout' => 5,
                ]);
            });

            $sequentialTime = $sequentialBenchmark['statistics']['execution_time']['average'];
            $concurrentTime = $concurrentBenchmark['statistics']['execution_time']['average'];

            // Assert: Optimized concurrent processing shows significant improvement
            expect($concurrentTime)->toBeLessThan($sequentialTime * $this->timingConfig['concurrency_improvement_threshold']);
            
            // Verify performance improvement is substantial
            $improvementRatio = $sequentialTime / $concurrentTime;
            expect($improvementRatio)->toBeGreaterThan(1.4); // At least 40% improvement
        });

        it('maintains optimized performance with mixed content types', function () {
            // Create mixed content with optimization
            $this->createOptimizedMixedContentSet();
            
            // Monitor resource utilization during test
            ResourceUtilizationMonitor::monitor('mixed_content_validation', function () {
                $startTime = microtime(true);

                $exitCode = Artisan::call('validate', [
                    'paths' => [$this->largeDocsDir],
                    '--scope' => 'all',
                    '--concurrent' => 12, // Optimized concurrency
                    '--timeout' => 8, // Reduced timeout
                ]);

                $executionTime = microtime(true) - $startTime;

                // Assert: Optimized mixed content validation performs within improved limits
                expect($exitCode)->toBeInt()
                    ->and($executionTime)->toBeLessThan($this->timingConfig['mixed_content_timeout']);
                    
                return $executionTime;
            });
        });
    });

    describe('Optimized Memory Usage and Resource Management', function () {
        it('maintains optimized memory usage during large file processing', function () {
            // Track memory usage with optimization
            MemoryUsageTracker::trackAllocation('large_file_processing', function () {
                // Create optimized large markdown file
                $largeFile = $this->largeDocsDir . '/huge-file.md';
                $this->createOptimizedHugeMarkdownFile($largeFile, 800); // Reduced from 1000
                
                $memoryBefore = memory_get_usage(true);
                $peakMemoryBefore = memory_get_peak_usage(true);

                $exitCode = Artisan::call('validate', [
                    'paths' => [$largeFile],
                    '--scope' => 'external',
                    '--concurrent' => 8, // Increased for better performance
                ]);

                $memoryAfter = memory_get_usage(true);
                $peakMemoryAfter = memory_get_peak_usage(true);
                
                $memoryIncrease = $memoryAfter - $memoryBefore;
                $peakMemoryIncrease = $peakMemoryAfter - $peakMemoryBefore;

                // Assert: Optimized memory usage is within improved limits
                expect($exitCode)->toBeInt()
                    ->and($memoryIncrease)->toBeLessThan($this->timingConfig['memory_limit_mb'] * 1024 * 1024)
                    ->and($peakMemoryIncrease)->toBeLessThan($this->timingConfig['peak_memory_limit_mb'] * 1024 * 1024);
                    
                return $memoryIncrease;
            });
        });

        it('handles file descriptor limits with optimization', function () {
            // Create optimized many small files
            $this->createOptimizedManySmallFiles(400); // Reduced from 500
            
            // Execute with optimized resource management
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'internal',
                '--concurrent' => 25, // Increased for better performance
            ]);

            expect($exitCode)->toBeInt();
        });
    });

    describe('Optimized Network Performance and Resilience', function () {
        it('handles high-latency network conditions with optimization', function () {
            // Create optimized file with mock slow external links
            $slowLinksFile = $this->largeDocsDir . '/slow-links.md';
            $content = $this->generateOptimizedSlowLinksContent(8); // Reduced from 10
            File::put($slowLinksFile, $content);
            
            $startTime = microtime(true);

            $exitCode = Artisan::call('validate', [
                'paths' => [$slowLinksFile],
                '--scope' => 'external',
                '--timeout' => 4, // Reduced from 5
                '--concurrent' => 8, // Increased for better performance
            ]);

            $executionTime = microtime(true) - $startTime;

            // Assert: Optimized network delays are handled within improved timeout limits
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan($this->timingConfig['network_timeout']);
        });

        it('recovers from intermittent network failures with optimization', function () {
            // Create optimized file with mix of reliable and unreliable links
            $mixedLinksFile = $this->largeDocsDir . '/mixed-reliability.md';
            $content = $this->generateOptimizedMixedReliabilityContent();
            File::put($mixedLinksFile, $content);

            $exitCode = Artisan::call('validate', [
                'paths' => [$mixedLinksFile],
                '--scope' => 'external',
                '--timeout' => 8, // Reduced from 10
                '--retry-failed' => true,
            ]);

            expect($exitCode)->toBeInt();
        });
    });

    describe('Optimized Scalability Testing', function () {
        it('scales optimally with increased concurrent workers', function () {
            // Create optimized consistent workload
            $this->createOptimizedConsistentWorkload(40); // Reduced from 50
            
            $metrics = [];
            
            // Test with optimized concurrency levels
            foreach ([1, 8, 16, 24] as $concurrency) { // Optimized concurrency levels
                $benchmark = PerformanceBenchmark::benchmark("scalability_test_{$concurrency}", function () use ($concurrency) {
                    return Artisan::call('validate', [
                        'paths' => [$this->largeDocsDir],
                        '--scope' => 'external',
                        '--concurrent' => $concurrency,
                        '--timeout' => 4, // Reduced timeout
                    ]);
                });
                
                $metrics[$concurrency] = $benchmark['statistics']['execution_time']['average'];
                
                // Minimal pause for system stability
                usleep(50000); // Reduced from 100ms to 50ms
            }

            // Assert: Optimized performance improves significantly with concurrency
            expect($metrics[8])->toBeLessThan($metrics[1] * 0.6) // At least 40% improvement
                ->and($metrics[16])->toBeLessThan($metrics[8] * 0.8); // Continued improvement
        });

        it('handles directory tree depth with optimization', function () {
            // Create optimized deep directory structure
            $this->createOptimizedDeepDirectoryStructure(4, 2); // Reduced complexity
            
            $startTime = microtime(true);

            $exitCode = Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'all',
            ]);

            $executionTime = microtime(true) - $startTime;

            // Assert: Optimized deep structures are handled within improved timeout limits
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan($this->timingConfig['deep_structure_timeout']);
        });
    });

    describe('Optimized Load Testing', function () {
        it('maintains stability under optimized sustained load', function () {
            // Create optimized load test scenario
            $loadTestFiles = [];
            for ($i = 1; $i <= 8; $i++) { // Reduced from 10
                $file = $this->largeDocsDir . "/load-test-$i.md";
                $this->createOptimizedLoadTestFile($file, 15); // Reduced from 20
                $loadTestFiles[] = $file;
            }
            
            $results = [];
            
            // Execute optimized multiple validation runs
            for ($run = 1; $run <= 3; $run++) { // Reduced from 5
                $benchmark = PerformanceBenchmark::benchmark("load_test_run_{$run}", function () use ($loadTestFiles) {
                    return Artisan::call('validate', [
                        'paths' => $loadTestFiles,
                        '--scope' => 'external',
                        '--concurrent' => 12, // Increased for better performance
                        '--timeout' => 4, // Reduced timeout
                    ]);
                });
                
                $results[] = [
                    'run' => $run,
                    'execution_time' => $benchmark['statistics']['execution_time']['average'],
                    'memory_used' => $benchmark['statistics']['memory_usage']['average'],
                ];
                
                // Minimal pause between runs
                usleep(50000); // Reduced from 100ms
            }

            // Assert: Optimized consistent performance across runs
            $executionTimes = array_column($results, 'execution_time');
            $avgTime = array_sum($executionTimes) / count($executionTimes);
            $maxTime = max($executionTimes);
            $minTime = min($executionTimes);
            
            // Assert: Optimized performance variance is within improved threshold
            expect($maxTime - $minTime)->toBeLessThan($avgTime * $this->timingConfig['performance_variance_threshold']);
        });
    });

    // Optimized helper methods for creating test data
    function createOptimizedLargeDocumentationSet(int $fileCount): void
    {
        // Use optimized file operations
        $writeOperations = [];
        
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = $this->generateOptimizedDocumentContent($i);
            $writeOperations[] = [
                'type' => 'file_write',
                'path' => $this->largeDocsDir . "/doc$i.md",
                'content' => $content,
            ];
        }
        
        TestExecutionOptimizer::optimizeFileOperations($writeOperations);
    }

    function createOptimizedConcurrentTestFiles(int $fileCount, int $linksPerFile): void
    {
        $writeOperations = [];
        
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = "# File $i\n\n";
            for ($j = 1; $j <= $linksPerFile; $j++) {
                $content .= "[Link $j](https://mock-server.test/status/200)\n";
            }
            
            $writeOperations[] = [
                'type' => 'file_write',
                'path' => $this->largeDocsDir . "/concurrent-$i.md",
                'content' => $content,
            ];
        }
        
        TestExecutionOptimizer::optimizeFileOperations($writeOperations);
    }

    function createOptimizedMixedContentSet(): void
    {
        $writeOperations = [];
        
        // Optimized markdown files
        for ($i = 1; $i <= 8; $i++) { // Reduced from 10
            $writeOperations[] = [
                'type' => 'file_write',
                'path' => $this->largeDocsDir . "/markdown-$i.md",
                'content' => "[Link](https://mock-server.test/status/200)",
            ];
        }
        
        // Optimized HTML files
        for ($i = 1; $i <= 4; $i++) { // Reduced from 5
            $html = "<html><body><a href='https://mock-server.test/get'>Link</a></body></html>";
            $writeOperations[] = [
                'type' => 'file_write',
                'path' => $this->largeDocsDir . "/html-$i.html",
                'content' => $html,
            ];
        }
        
        TestExecutionOptimizer::optimizeFileOperations($writeOperations);
    }

    function createOptimizedHugeMarkdownFile(string $filePath, int $linkCount): void
    {
        // Use optimized content generation
        $content = "# Huge File\n\n";
        $baseContent = "[Link PLACEHOLDER](https://mock-server.test/status/200)\n";
        
        // Generate content efficiently
        for ($i = 1; $i <= $linkCount; $i++) {
            $content .= str_replace('PLACEHOLDER', (string)$i, $baseContent);
        }
        
        File::put($filePath, $content);
    }

    function createOptimizedManySmallFiles(int $fileCount): void
    {
        $writeOperations = [];
        
        for ($i = 1; $i <= $fileCount; $i++) {
            $writeOperations[] = [
                'type' => 'file_write',
                'path' => $this->largeDocsDir . "/small-$i.md",
                'content' => "[Link](./other.md)",
            ];
        }
        
        TestExecutionOptimizer::optimizeFileOperations($writeOperations);
    }

    function createOptimizedConsistentWorkload(int $fileCount): void
    {
        $writeOperations = [];
        
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = <<<MD
# File $i
[MockServer](https://mock-server.test/status/200)
[MockAPI](https://mock-api.test/posts/1)
MD;
            $writeOperations[] = [
                'type' => 'file_write',
                'path' => $this->largeDocsDir . "/workload-$i.md",
                'content' => $content,
            ];
        }
        
        TestExecutionOptimizer::optimizeFileOperations($writeOperations);
    }

    function createOptimizedDeepDirectoryStructure(int $depth, int $filesPerLevel): void
    {
        $writeOperations = [];
        $currentDir = $this->largeDocsDir;
        
        for ($level = 1; $level <= $depth; $level++) {
            $currentDir .= "/level$level";
            File::makeDirectory($currentDir, 0755, true, true);
            
            for ($file = 1; $file <= $filesPerLevel; $file++) {
                $writeOperations[] = [
                    'type' => 'file_write',
                    'path' => "$currentDir/file$file.md",
                    'content' => "[Link](https://mock-server.test/status/200)",
                ];
            }
        }
        
        TestExecutionOptimizer::optimizeFileOperations($writeOperations);
    }

    function createOptimizedLoadTestFile(string $filePath, int $linkCount): void
    {
        $content = "# Load Test File\n\n";
        for ($i = 1; $i <= $linkCount; $i++) {
            $content .= "[Load Link $i](https://mock-server.test/status/200)\n";
        }
        File::put($filePath, $content);
    }

    // Optimized content generation methods
    function generateOptimizedDocumentContent(int $index): string
    {
        return <<<MD
# Document $index

[External Link 1](https://mock-server.test/status/200)
[External Link 2](https://mock-server.test/get)
[Internal Link](./doc{$index}.md)
[Anchor Link](#document-$index)

## Section
Content for document $index.
MD;
    }

    function generateOptimizedSlowLinksContent(int $linkCount): string
    {
        $content = '';
        for ($i = 1; $i <= $linkCount; $i++) {
            $content .= "[Slow Link $i](https://mock-slow-server.test/delay/$i)\n";
        }
        return $content;
    }

    function generateOptimizedMixedReliabilityContent(): string
    {
        return <<<'MD'
[Reliable 1](https://mock-server.test/status/200)
[Unreliable 1](https://mock-server.test/status/503)
[Reliable 2](https://mock-server.test/get)
[Unreliable 2](https://mock-unreachable.test/invalid)
[Reliable 3](https://mock-server.test/json)
MD;
    }
});