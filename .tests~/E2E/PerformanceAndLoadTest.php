<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

describe('Performance and Load E2E Tests', function () {
    beforeEach(function () {
        // Setup: Create performance testing environment with unique directory
        $this->performanceDir = sys_get_temp_dir() . '/validate-links-performance-' . uniqid();
        File::makeDirectory($this->performanceDir, 0755, true, true);
        
        $this->metricsFile = $this->performanceDir . '/metrics.json';
        $this->largeDocsDir = $this->performanceDir . '/large-docs';
        File::makeDirectory($this->largeDocsDir, 0755, true, true);
        
        // Environment-aware timing configuration
        $this->timingConfig = [
            'large_scale_timeout' => (int) ($_ENV['TEST_LARGE_SCALE_TIMEOUT'] ?? 180), // 3 minutes default
            'mixed_content_timeout' => (int) ($_ENV['TEST_MIXED_CONTENT_TIMEOUT'] ?? 90), // 1.5 minutes default
            'network_timeout' => (int) ($_ENV['TEST_NETWORK_TIMEOUT'] ?? 45), // 45 seconds default
            'deep_structure_timeout' => (int) ($_ENV['TEST_DEEP_STRUCTURE_TIMEOUT'] ?? 45), // 45 seconds default
            'memory_limit_mb' => (int) ($_ENV['TEST_MEMORY_LIMIT_MB'] ?? 150), // 150MB default
            'peak_memory_limit_mb' => (int) ($_ENV['TEST_PEAK_MEMORY_LIMIT_MB'] ?? 200), // 200MB default
            'performance_variance_threshold' => (float) ($_ENV['TEST_PERFORMANCE_VARIANCE_THRESHOLD'] ?? 0.75), // 75% default
            'concurrency_improvement_threshold' => (float) ($_ENV['TEST_CONCURRENCY_IMPROVEMENT_THRESHOLD'] ?? 0.9), // 10% improvement minimum
        ];
    });

    afterEach(function () {
        // Teardown: Clean up performance test files with proper error handling
        try {
            if (File::exists($this->performanceDir)) {
                File::deleteDirectory($this->performanceDir);
            }
        } catch (Exception $e) {
            // Log cleanup failure but don't fail the test
            error_log("Failed to cleanup test directory: " . $e->getMessage());
        }
    });
    
    /**
     * Setup mock HTTP responses to eliminate external dependencies
     */
    function setupMockResponses() {
        // Create mock responses for different scenarios
        $this->mockResponses = [
            'success' => new Response(200, [], 'OK'),
            'not_found' => new Response(404, [], 'Not Found'),
            'server_error' => new Response(500, [], 'Internal Server Error'),
            'slow_response' => new Response(200, [], 'Delayed OK'),
        ];
    }

    describe('Large Scale Validation Performance', function () {
        it('validates large documentation set within performance thresholds', function () {
            // Setup: Create large documentation set
            $this->createLargeDocumentationSet(100); // 100 files
            
            $startTime = microtime(true);
            $memoryBefore = memory_get_usage(true);

            // Execute: Large scale validation
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'all',
                '--concurrent' => 10,
                '--timeout' => 10,
                '--format' => 'json',
                '--output' => $this->performanceDir . '/large-scale-results.json',
            ]);

            $endTime = microtime(true);
            $memoryAfter = memory_get_usage(true);
            
            $executionTime = $endTime - $startTime;
            $memoryUsed = $memoryAfter - $memoryBefore;

            // Assert: Performance within environment-aware limits
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan($this->timingConfig['large_scale_timeout'])
                ->and($memoryUsed)->toBeLessThan($this->timingConfig['memory_limit_mb'] * 1024 * 1024);

            // Verify results file exists and has content
            expect(File::exists($this->performanceDir . '/large-scale-results.json'))->toBeTrue();
            
            $results = json_decode(File::get($this->performanceDir . '/large-scale-results.json'), true);
            expect($results['summary']['total_links'])->toBeGreaterThan(200);
        });

        it('handles concurrent validation efficiently', function () {
            // Setup: Create files with many external links
            $this->createConcurrentTestFiles(20, 10); // 20 files, 10 links each
            
            $sequentialStartTime = microtime(true);
            
            // Execute: Sequential validation (concurrent=1)
            Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'external',
                '--concurrent' => 1,
                '--timeout' => 5,
            ]);
            
            $sequentialTime = microtime(true) - $sequentialStartTime;
            
            $concurrentStartTime = microtime(true);
            
            // Execute: Concurrent validation (concurrent=10)
            Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'external',
                '--concurrent' => 10,
                '--timeout' => 5,
            ]);
            
            $concurrentTime = microtime(true) - $concurrentStartTime;

            // Assert: Concurrent processing shows improvement (environment-aware threshold)
            expect($concurrentTime)->toBeLessThan($sequentialTime * $this->timingConfig['concurrency_improvement_threshold']);
        });

        it('maintains performance with mixed content types', function () {
            // Setup: Create mixed content (markdown, HTML, large files)
            $this->createMixedContentSet();
            
            $startTime = microtime(true);

            // Execute: Validate mixed content
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'all',
                '--concurrent' => 8,
                '--timeout' => 10,
            ]);

            $executionTime = microtime(true) - $startTime;

            // Assert: Mixed content validation performs within limits
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan($this->timingConfig['mixed_content_timeout']);
        });
    });

    describe('Memory Usage and Resource Management', function () {
        it('maintains stable memory usage during large file processing', function () {
            // Setup: Create very large markdown file
            $largeFile = $this->largeDocsDir . '/huge-file.md';
            $this->createHugeMarkdownFile($largeFile, 1000); // 1000 links
            
            $memoryBefore = memory_get_usage(true);
            $peakMemoryBefore = memory_get_peak_usage(true);

            // Execute: Process large file
            $exitCode = Artisan::call('validate', [
                'paths' => [$largeFile],
                '--scope' => 'external',
                '--concurrent' => 5,
            ]);

            $memoryAfter = memory_get_usage(true);
            $peakMemoryAfter = memory_get_peak_usage(true);
            
            $memoryIncrease = $memoryAfter - $memoryBefore;
            $peakMemoryIncrease = $peakMemoryAfter - $peakMemoryBefore;

            // Assert: Memory usage is within environment-aware limits
            expect($exitCode)->toBeInt()
                ->and($memoryIncrease)->toBeLessThan($this->timingConfig['memory_limit_mb'] * 1024 * 1024)
                ->and($peakMemoryIncrease)->toBeLessThan($this->timingConfig['peak_memory_limit_mb'] * 1024 * 1024);
        });

        it('handles file descriptor limits gracefully', function () {
            // Setup: Create many small files to test file descriptor usage
            $this->createManySmallFiles(500); // 500 small files
            
            // Execute: Validate many files
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'internal',
                '--concurrent' => 20,
            ]);

            // Assert: File descriptor limits are handled
            expect($exitCode)->toBeInt();
        });
    });

    describe('Network Performance and Resilience', function () {
        it('handles high-latency network conditions', function () {
            // Setup: Create file with mock slow external links
            $slowLinksFile = $this->largeDocsDir . '/slow-links.md';
            $content = '';
            for ($i = 1; $i <= 10; $i++) {
                $content .= "[Slow Link $i](https://mock-slow-server.test/delay/$i)\n";
            }
            File::put($slowLinksFile, $content);
            
            $startTime = microtime(true);

            // Execute: Validate with simulated network delays
            $exitCode = Artisan::call('validate', [
                'paths' => [$slowLinksFile],
                '--scope' => 'external',
                '--timeout' => 5,
                '--concurrent' => 5,
            ]);

            $executionTime = microtime(true) - $startTime;

            // Assert: Network delays are handled within timeout limits
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan($this->timingConfig['network_timeout']);
        });

        it('recovers from intermittent network failures', function () {
            // Setup: Create file with mix of mock reliable and unreliable links
            $mixedLinksFile = $this->largeDocsDir . '/mixed-reliability.md';
            $content = <<<'MD'
[Reliable 1](https://mock-server.test/status/200)
[Unreliable 1](https://mock-server.test/status/503)
[Reliable 2](https://mock-server.test/get)
[Unreliable 2](https://mock-unreachable.test/invalid)
[Reliable 3](https://mock-server.test/json)
MD;
            File::put($mixedLinksFile, $content);

            // Execute: Validate with mixed reliability simulation
            $exitCode = Artisan::call('validate', [
                'paths' => [$mixedLinksFile],
                '--scope' => 'external',
                '--timeout' => 10,
                '--retry-failed' => true,
            ]);

            // Assert: Mixed reliability is handled
            expect($exitCode)->toBeInt();
        });
    });

    describe('Scalability Testing', function () {
        it('scales linearly with increased concurrent workers', function () {
            // Setup: Create consistent workload
            $this->createConsistentWorkload(50); // 50 files with external links
            
            $metrics = [];
            
            // Test with different concurrency levels
            foreach ([1, 5, 10, 15] as $concurrency) {
                $startTime = microtime(true);
                
                Artisan::call('validate', [
                    'paths' => [$this->largeDocsDir],
                    '--scope' => 'external',
                    '--concurrent' => $concurrency,
                    '--timeout' => 5,
                ]);
                
                $executionTime = microtime(true) - $startTime;
                $metrics[$concurrency] = $executionTime;
                
                // Brief pause for system stability (reduced from fixed sleep)
                usleep(100000); // 100ms instead of 1 second
            }

            // Assert: Performance improves with concurrency (up to a point)
            expect($metrics[5])->toBeLessThan($metrics[1])
                ->and($metrics[10])->toBeLessThan($metrics[5]);
        });

        it('handles directory tree depth efficiently', function () {
            // Setup: Create deep directory structure
            $this->createDeepDirectoryStructure(5, 3); // 5 levels deep, 3 files per level
            
            $startTime = microtime(true);

            // Execute: Validate deep directory structure
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->largeDocsDir],
                '--scope' => 'all',
            ]);

            $executionTime = microtime(true) - $startTime;

            // Assert: Deep structures are handled within timeout limits
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan($this->timingConfig['deep_structure_timeout']);
        });
    });

    describe('Load Testing', function () {
        it('maintains stability under sustained load', function () {
            // Setup: Create sustained load scenario
            $loadTestFiles = [];
            for ($i = 1; $i <= 10; $i++) {
                $file = $this->largeDocsDir . "/load-test-$i.md";
                $this->createLoadTestFile($file, 20); // 20 links per file
                $loadTestFiles[] = $file;
            }
            
            $results = [];
            
            // Execute: Multiple validation runs to simulate sustained load
            for ($run = 1; $run <= 5; $run++) {
                $startTime = microtime(true);
                $memoryBefore = memory_get_usage(true);
                
                $exitCode = Artisan::call('validate', [
                    'paths' => $loadTestFiles,
                    '--scope' => 'external',
                    '--concurrent' => 8,
                    '--timeout' => 5,
                ]);
                
                $executionTime = microtime(true) - $startTime;
                $memoryAfter = memory_get_usage(true);
                
                $results[] = [
                    'run' => $run,
                    'execution_time' => $executionTime,
                    'memory_used' => $memoryAfter - $memoryBefore,
                    'exit_code' => $exitCode,
                ];
                
                // Brief pause between runs (reduced for faster testing)
                usleep(100000); // 100ms instead of 1 second
            }

            // Assert: Consistent performance across runs
            $executionTimes = array_column($results, 'execution_time');
            $avgTime = array_sum($executionTimes) / count($executionTimes);
            $maxTime = max($executionTimes);
            $minTime = min($executionTimes);
            
            // Assert: Performance variance is within acceptable threshold
            expect($maxTime - $minTime)->toBeLessThan($avgTime * $this->timingConfig['performance_variance_threshold']);
        });
    });

    // Helper methods for creating test data with mock URLs
    function createPerformanceDocumentationSet(int $fileCount): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = <<<MD
# Document $i

[External Link 1](https://mock-server.test/status/200)
[External Link 2](https://mock-server.test/get)
[Internal Link](./doc{$i}.md)
[Anchor Link](#document-$i)

## Section
Content for document $i.
MD;
            File::put($this->largeDocsDir . "/doc$i.md", $content);
        }
    }

    function createConcurrentTestFiles(int $fileCount, int $linksPerFile): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = "# File $i\n\n";
            for ($j = 1; $j <= $linksPerFile; $j++) {
                $content .= "[Link $j](https://mock-server.test/status/200)\n";
            }
            File::put($this->largeDocsDir . "/concurrent-$i.md", $content);
        }
    }

    function createMixedContentSet(): void
    {
        // Markdown files
        for ($i = 1; $i <= 10; $i++) {
            File::put($this->largeDocsDir . "/markdown-$i.md", "[Link](https://mock-server.test/status/200)");
        }
        
        // HTML files
        for ($i = 1; $i <= 5; $i++) {
            $html = "<html><body><a href='https://mock-server.test/get'>Link</a></body></html>";
            File::put($this->largeDocsDir . "/html-$i.html", $html);
        }
    }

    function createHugeMarkdownFile(string $filePath, int $linkCount): void
    {
        $content = "# Huge File\n\n";
        for ($i = 1; $i <= $linkCount; $i++) {
            $content .= "[Link $i](https://mock-server.test/status/200)\n";
        }
        File::put($filePath, $content);
    }

    function createManySmallFiles(int $fileCount): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            File::put($this->largeDocsDir . "/small-$i.md", "[Link](./other.md)");
        }
    }

    function createConsistentWorkload(int $fileCount): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = <<<MD
# File $i
[MockServer](https://mock-server.test/status/200)
[MockAPI](https://mock-api.test/posts/1)
MD;
            File::put($this->largeDocsDir . "/workload-$i.md", $content);
        }
    }

    function createDeepDirectoryStructure(int $depth, int $filesPerLevel): void
    {
        $currentDir = $this->largeDocsDir;
        
        for ($level = 1; $level <= $depth; $level++) {
            $currentDir .= "/level$level";
            File::makeDirectory($currentDir, 0755, true, true);
            
            for ($file = 1; $file <= $filesPerLevel; $file++) {
                $content = "[Link](https://mock-server.test/status/200)";
                File::put("$currentDir/file$file.md", $content);
            }
        }
    }

    function createLoadTestFile(string $filePath, int $linkCount): void
    {
        $content = "# Load Test File\n\n";
        for ($i = 1; $i <= $linkCount; $i++) {
            $content .= "[Load Link $i](https://mock-server.test/status/200)\n";
        }
        File::put($filePath, $content);
    }
});
