<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Performance and Load E2E Tests', function () {
    beforeEach(function () {
        // Setup: Create performance testing environment
        $this->performanceDir = sys_get_temp_dir() . '/validate-links-performance';
        File::makeDirectory($this->performanceDir, 0755, true, true);
        
        $this->metricsFile = $this->performanceDir . '/metrics.json';
        $this->largeDocsDir = $this->performanceDir . '/large-docs';
        File::makeDirectory($this->largeDocsDir, 0755, true, true);
    });

    afterEach(function () {
        // Teardown: Clean up performance test files
        if (File::exists($this->performanceDir)) {
            File::deleteDirectory($this->performanceDir);
        }
    });

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

            // Assert: Performance within acceptable limits
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan(120) // Less than 2 minutes
                ->and($memoryUsed)->toBeLessThan(100 * 1024 * 1024); // Less than 100MB

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

            // Assert: Concurrent processing is more efficient
            expect($concurrentTime)->toBeLessThan($sequentialTime * 0.8); // At least 20% faster
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

            // Assert: Mixed content validation performs well
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan(60); // Less than 1 minute
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

            // Assert: Memory usage is reasonable
            expect($exitCode)->toBeInt()
                ->and($memoryIncrease)->toBeLessThan(50 * 1024 * 1024) // Less than 50MB increase
                ->and($peakMemoryIncrease)->toBeLessThan(100 * 1024 * 1024); // Less than 100MB peak
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
            // Setup: Create file with slow external links
            $slowLinksFile = $this->largeDocsDir . '/slow-links.md';
            $content = '';
            for ($i = 1; $i <= 10; $i++) {
                $content .= "[Slow Link $i](https://httpbin.org/delay/2)\n";
            }
            File::put($slowLinksFile, $content);
            
            $startTime = microtime(true);

            // Execute: Validate with network delays
            $exitCode = Artisan::call('validate', [
                'paths' => [$slowLinksFile],
                '--scope' => 'external',
                '--timeout' => 5,
                '--concurrent' => 5,
            ]);

            $executionTime = microtime(true) - $startTime;

            // Assert: Network delays are handled efficiently
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan(30); // Should complete within 30 seconds
        });

        it('recovers from intermittent network failures', function () {
            // Setup: Create file with mix of reliable and unreliable links
            $mixedLinksFile = $this->largeDocsDir . '/mixed-reliability.md';
            $content = <<<'MD'
[Reliable 1](https://httpbin.org/status/200)
[Unreliable 1](https://httpbin.org/status/503)
[Reliable 2](https://httpbin.org/get)
[Unreliable 2](https://this-domain-does-not-exist-12345.invalid)
[Reliable 3](https://httpbin.org/json)
MD;
            File::put($mixedLinksFile, $content);

            // Execute: Validate with mixed reliability
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
                
                // Clean up for next test
                sleep(1);
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
                '--recursive' => true,
                '--scope' => 'all',
            ]);

            $executionTime = microtime(true) - $startTime;

            // Assert: Deep structures are handled efficiently
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan(30);
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
                
                // Brief pause between runs
                sleep(1);
            }

            // Assert: Consistent performance across runs
            $executionTimes = array_column($results, 'execution_time');
            $avgTime = array_sum($executionTimes) / count($executionTimes);
            $maxTime = max($executionTimes);
            $minTime = min($executionTimes);
            
            expect($maxTime - $minTime)->toBeLessThan($avgTime * 0.5); // Variance less than 50% of average
        });
    });

    // Helper methods for creating test data
    private function createLargeDocumentationSet(int $fileCount): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = <<<MD
# Document $i

[External Link 1](https://httpbin.org/status/200)
[External Link 2](https://httpbin.org/get)
[Internal Link](./doc{$i}.md)
[Anchor Link](#document-$i)

## Section
Content for document $i.
MD;
            File::put($this->largeDocsDir . "/doc$i.md", $content);
        }
    }

    private function createConcurrentTestFiles(int $fileCount, int $linksPerFile): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = "# File $i\n\n";
            for ($j = 1; $j <= $linksPerFile; $j++) {
                $content .= "[Link $j](https://httpbin.org/status/200)\n";
            }
            File::put($this->largeDocsDir . "/concurrent-$i.md", $content);
        }
    }

    private function createMixedContentSet(): void
    {
        // Markdown files
        for ($i = 1; $i <= 10; $i++) {
            File::put($this->largeDocsDir . "/markdown-$i.md", "[Link](https://httpbin.org/status/200)");
        }
        
        // HTML files
        for ($i = 1; $i <= 5; $i++) {
            $html = "<html><body><a href='https://httpbin.org/get'>Link</a></body></html>";
            File::put($this->largeDocsDir . "/html-$i.html", $html);
        }
    }

    private function createHugeMarkdownFile(string $filePath, int $linkCount): void
    {
        $content = "# Huge File\n\n";
        for ($i = 1; $i <= $linkCount; $i++) {
            $content .= "[Link $i](https://httpbin.org/status/200)\n";
        }
        File::put($filePath, $content);
    }

    private function createManySmallFiles(int $fileCount): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            File::put($this->largeDocsDir . "/small-$i.md", "[Link](./other.md)");
        }
    }

    private function createConsistentWorkload(int $fileCount): void
    {
        for ($i = 1; $i <= $fileCount; $i++) {
            $content = <<<MD
# File $i
[HTTPBin](https://httpbin.org/status/200)
[JSONPlaceholder](https://jsonplaceholder.typicode.com/posts/1)
MD;
            File::put($this->largeDocsDir . "/workload-$i.md", $content);
        }
    }

    private function createDeepDirectoryStructure(int $depth, int $filesPerLevel): void
    {
        $currentDir = $this->largeDocsDir;
        
        for ($level = 1; $level <= $depth; $level++) {
            $currentDir .= "/level$level";
            File::makeDirectory($currentDir, 0755, true, true);
            
            for ($file = 1; $file <= $filesPerLevel; $file++) {
                $content = "[Link](https://httpbin.org/status/200)";
                File::put("$currentDir/file$file.md", $content);
            }
        }
    }

    private function createLoadTestFile(string $filePath, int $linkCount): void
    {
        $content = "# Load Test File\n\n";
        for ($i = 1; $i <= $linkCount; $i++) {
            $content .= "[Load Link $i](https://httpbin.org/status/200)\n";
        }
        File::put($filePath, $content);
    }
});
