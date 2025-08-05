<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;

use Tests\Support\BoundaryConditionValidator;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ExceptionTestingHelpers;
use Tests\Support\ResourceLimitSimulator;

/**
 * Comprehensive end-to-end edge case tests for the complete validation system.
 * 
 * Tests the entire system from command input to final output under extreme
 * conditions, including null inputs, empty collections, malformed data,
 * concurrent access, and resource exhaustion scenarios.
 * 
 * @package Tests\E2E
 * @author Roo
 * @since 1.0.0
 */

describe('E2E Edge Case Testing', function () {
    
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir() . '/edge_case_e2e_' . uniqid();
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        // Initialize edge case utilities
        EdgeCaseDataGenerator::reset();
        BoundaryConditionValidator::initialize();
        ResourceLimitSimulator::initialize();
    });
    
    afterEach(function () {
        // Clean up temporary directory
        if (is_dir($this->tempDir)) {
            removeDirectory($this->tempDir);
        }
        
        BoundaryConditionValidator::reset();
        ResourceLimitSimulator::cleanup();
    });

    describe('Null and Empty Content Validation', function () {
        
        test('E2E validation with null and empty file content', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs();
            
            foreach ($nullInputs['null_values'] as $inputName => $nullValue) {
                if (!is_string($nullValue) && $nullValue !== null) {
                    continue; // Skip non-string values for file content
                }
                
                $testFile = $this->tempDir . "/test_{$inputName}.md";
                file_put_contents($testFile, $nullValue ?? '');
                
                try {
                    $config = ValidationConfig::withDefaults();
                    
                    // Use the LinkValidationService directly for E2E testing
                    $linkValidationService = app(\App\Services\LinkValidationService::class);
                    $results = $linkValidationService->validateFile($testFile, $config);
                    
                    expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
                    
                    // Empty/null content should result in no links found
                    if ($nullValue === null || $nullValue === '') {
                        expect($results->count())->toBe(0);
                    } else {
                        // Other values should be processed without errors
                        expect($results->count())->toBeGreaterThanOrEqual(0);
                    }
                    
                } catch (\Throwable $e) {
                    // Some null inputs may cause expected exceptions
                    expect(get_class($e))->toBeIn([
                        \App\Exceptions\SecurityException::class,
                        RuntimeException::class,
                    ]);
                }
            }
        });
        
    });

    describe('Malformed Content Handling', function () {
        
        test('E2E validation with malformed markdown files', function () {
            $malformedContents = [
                // Unclosed markdown links
                '[unclosed link](https://example.com',
                '[text](incomplete-url',
                '[](empty-url)',
                
                // Malformed HTML links
                '<a href="https://example.com">unclosed tag',
                '<a href=>empty href</a>',
                '<a>no href</a>',
                
                // Mixed content with errors
                '[good link](https://example.com) [bad link](incomplete',
                '<a href="good.com">Good</a> <a href=>Bad</a>',
                
                // Content with invalid characters
                "[link with\x00null](https://example.com)",
                "[link with\nnewline](https://example.com)",
                
                // Very large content
                str_repeat('[link](https://example.com) ', 1000),
                
                // Binary-like content
                "\xFF\xFE\x00\x00[link](https://example.com)",
            ];
            
            foreach ($malformedContents as $index => $content) {
                $testFile = $this->tempDir . "/malformed_{$index}.md";
                file_put_contents($testFile, $content);
                
                $config = ValidationConfig::withDefaults();
                $linkValidationService = app(\App\Services\LinkValidationService::class);
                
                $results = $linkValidationService->validateFile($testFile, $config);
                
                expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
                
                // Should extract valid links and ignore malformed ones
                foreach ($results as $result) {
                    expect($result)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResult::class);
                    expect($result->getUrl())->toBeString();
                    expect($result->getStatus())->not->toBeNull();
                }
            }
        });
        
    });

    describe('Extreme File Size Testing', function () {
        
        test('E2E validation with extreme file sizes', function () {
            $extremeFiles = [
                'empty_file' => '',
                'single_char' => 'a',
                'large_file' => str_repeat('[link](https://example.com) ', 10000),
                'very_large_file' => str_repeat('# Header\n[link](https://example.com)\n', 5000),
            ];
            
            foreach ($extremeFiles as $fileName => $content) {
                $testFile = $this->tempDir . "/{$fileName}.md";
                file_put_contents($testFile, $content);
                
                $startTime = microtime(true);
                $startMemory = memory_get_usage(true);
                
                $config = ValidationConfig::withDefaults();
                $linkValidationService = app(\App\Services\LinkValidationService::class);
                
                $results = $linkValidationService->validateFile($testFile, $config);
                
                $endTime = microtime(true);
                $endMemory = memory_get_usage(true);
                
                expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
                
                // Performance assertions
                $executionTime = $endTime - $startTime;
                $memoryUsage = $endMemory - $startMemory;
                
                expect($executionTime)->toBeLessThan(30.0)->and("File {$fileName} should process within 30 seconds");
                expect($memoryUsage)->toBeLessThan(100 * 1024 * 1024)->and("Memory usage should be reasonable for {$fileName}");
            }
        });
        
    });

    describe('Extreme URL Scenarios', function () {
        
        test('E2E validation with extreme URL scenarios', function () {
            $extremeUrls = EdgeCaseDataGenerator::generateExtremeUrls(['type' => 'all', 'count' => 30]);
            
            // Create markdown content with extreme URLs
            $content = "# Test Document\n\n";
            foreach ($extremeUrls as $index => $url) {
                $content .= "[Link {$index}]({$url})\n";
            }
            
            $testFile = $this->tempDir . '/extreme_urls.md';
            file_put_contents($testFile, $content);
            
            $config = ValidationConfig::withDefaults();
            $linkValidationService = app(\App\Services\LinkValidationService::class);
            
            $startTime = microtime(true);
            $results = $linkValidationService->validateFile($testFile, $config);
            $endTime = microtime(true);
            
            expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
            expect($results->count())->toBe(count($extremeUrls));
            
            // Should handle extreme URLs gracefully
            foreach ($results as $result) {
                expect($result)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResult::class);
                expect($result->getStatus())->toBeIn([
                    \App\Enums\LinkStatus::VALID,
                    \App\Enums\LinkStatus::BROKEN,
                    \App\Enums\LinkStatus::INVALID_FORMAT,
                    \App\Enums\LinkStatus::SECURITY_VIOLATION,
                    \App\Enums\LinkStatus::TIMEOUT,
                ]);
            }
            
            $executionTime = $endTime - $startTime;
            expect($executionTime)->toBeLessThan(60.0)->and('Extreme URL validation should complete within 60 seconds');
        });
        
    });

    describe('Resource Pressure Testing', function () {
        
        test('E2E validation under memory pressure', function () {
            // Create test file with moderate content
            $content = "# Test Document\n\n";
            for ($i = 0; $i < 100; $i++) {
                $content .= "[Link {$i}](https://example{$i}.com)\n";
            }
            
            $testFile = $this->tempDir . '/memory_pressure_test.md';
            file_put_contents($testFile, $content);
            
            try {
                // Simulate memory pressure
                ResourceLimitSimulator::simulateMemoryPressure([
                    'target_mb' => 50,
                    'gradual' => true
                ]);
                
                $config = ValidationConfig::withDefaults();
                $linkValidationService = app(\App\Services\LinkValidationService::class);
                
                $results = $linkValidationService->validateFile($testFile, $config);
                
                expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
                expect($results->count())->toBe(100);
                
                // System should still function under memory pressure
                foreach ($results as $result) {
                    expect($result)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResult::class);
                }
                
            } finally {
                ResourceLimitSimulator::releaseMemoryPressure();
            }
        });
        
        test('E2E validation with network latency simulation', function () {
            // Create test file with external URLs
            $content = "# Network Test\n\n";
            $content .= "[Example](https://example.com)\n";
            $content .= "[HTTPBin](https://httpbin.org/get)\n";
            
            $testFile = $this->tempDir . '/network_test.md';
            file_put_contents($testFile, $content);
            
            // Simulate network latency
            ResourceLimitSimulator::simulateNetworkLatency([
                'latency_ms' => 1000,
                'jitter' => 0.2
            ]);
            
            $config = ValidationConfig::withDefaults();
            $linkValidationService = app(\App\Services\LinkValidationService::class);
            
            $startTime = microtime(true);
            $results = $linkValidationService->validateFile($testFile, $config);
            $endTime = microtime(true);
            
            expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
            expect($results->count())->toBe(2);
            
            // Should handle latency gracefully
            $executionTime = $endTime - $startTime;
            expect($executionTime)->toBeGreaterThan(0.5)->and('Should reflect simulated latency');
            expect($executionTime)->toBeLessThan(20.0)->and('Should not hang indefinitely');
        });
        
    });

    describe('Concurrent Processing', function () {
        
        test('E2E validation with concurrent file processing', function () {
            // Create multiple test files
            $testFiles = [];
            for ($i = 0; $i < 5; $i++) {
                $content = "# Test File {$i}\n\n";
                $content .= "[Link {$i}](https://example{$i}.com)\n";
                
                $testFile = $this->tempDir . "/concurrent_test_{$i}.md";
                file_put_contents($testFile, $content);
                $testFiles[] = $testFile;
            }
            
            $config = ValidationConfig::withDefaults();
            $linkValidationService = app(\App\Services\LinkValidationService::class);
            
            $concurrencyScenario = EdgeCaseDataGenerator::generateConcurrencyScenarios([
                'scenario' => 'basic',
                'threads' => 3
            ]);
            
            $results = BoundaryConditionValidator::validateConcurrentAccess(
                function () use ($testFiles, $config, $linkValidationService) {
                    $allResults = [];
                    foreach ($testFiles as $file) {
                        $fileResults = $linkValidationService->validateFile($file, $config);
                        $allResults[] = $fileResults;
                    }
                    return $allResults;
                },
                $concurrencyScenario
            );
            
            expect($results)->toHaveKey('analysis');
            $analysis = $results['analysis'];
            
            expect($analysis['total_accesses'])->toBeGreaterThan(0);
            expect($analysis['successful_accesses'])->toBeGreaterThanOrEqual(0);
            
            // E2E concurrent processing should be reasonably stable
            expect($analysis['race_conditions_detected'])->toBeLessThan(10)->and('E2E processing should handle concurrency well');
        });
        
    });

    describe('Complete Reporting Pipeline', function () {
        
        test('E2E validation with complete reporting pipeline', function () {
            // Create comprehensive test file
            $content = "# Comprehensive Test\n\n";
            $content .= "[Valid Link](https://example.com)\n";
            $content .= "[Broken Link](https://nonexistent-domain-12345.com)\n";
            $content .= "[Internal Link](./test-file.md)\n";
            $content .= "[Anchor Link](#section)\n";
            $content .= "[Invalid Link](not-a-url)\n";
            
            $testFile = $this->tempDir . '/comprehensive_test.md';
            file_put_contents($testFile, $content);
            
            $config = ValidationConfig::withDefaults();
            $linkValidationService = app(\App\Services\LinkValidationService::class);
            $reportingService = app(\App\Services\ReportingService::class);
            
            // Validate file
            $results = $linkValidationService->validateFile($testFile, $config);
            
            expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
            expect($results->count())->toBe(5);
            
            // Test all output formats
            foreach (OutputFormat::cases() as $format) {
                $report = $reportingService->generateReport($results, $format);
                
                expect($report)->toBeString();
                expect($report)->not->toBeEmpty();
                
                if ($format === OutputFormat::JSON) {
                    $decoded = json_decode($report, true);
                    expect($decoded)->not->toBeNull()->and("JSON report should be valid");
                    expect($decoded)->toBeArray();
                }
            }
            
            // Test summary generation
            $summary = $reportingService->generateSummary($results);
            expect($summary->getTotalLinks())->toBe(5);
            expect($summary->getBrokenLinks())->toBeGreaterThanOrEqual(0);
            expect($summary->getSuccessRate())->toBeGreaterThanOrEqual(0);
            expect($summary->getSuccessRate())->toBeLessThanOrEqual(100);
            
            // Test report export
            $exportFile = $this->tempDir . '/export_test.json';
            $exportResult = $reportingService->exportReport($results, $exportFile, OutputFormat::JSON);
            
            expect($exportResult)->toBeTrue();
            expect(file_exists($exportFile))->toBeTrue();
            expect(filesize($exportFile))->toBeGreaterThan(0);
            
            // Verify exported content
            $exportedContent = file_get_contents($exportFile);
            $decoded = json_decode($exportedContent, true);
            expect($decoded)->not->toBeNull();
        });
        
    });

    describe('Security Edge Cases', function () {
        
        test('E2E validation with security edge cases', function () {
            $securityTestContent = "# Security Test\n\n";
            $securityTestContent .= "[Path Traversal](../../../etc/passwd)\n";
            $securityTestContent .= "[JavaScript](javascript:alert(1))\n";
            $securityTestContent .= "[Data URL](data:text/html,<script>alert(1)</script>)\n";
            $securityTestContent .= "[File Protocol](file:///etc/passwd)\n";
            $securityTestContent .= "[FTP Protocol](ftp://example.com/file.txt)\n";
            
            $testFile = $this->tempDir . '/security_test.md';
            file_put_contents($testFile, $securityTestContent);
            
            $config = ValidationConfig::withDefaults();
            $linkValidationService = app(\App\Services\LinkValidationService::class);
            
            try {
                $results = $linkValidationService->validateFile($testFile, $config);
                
                expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
                
                // Security violations should be detected and handled
                foreach ($results as $result) {
                    expect($result)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResult::class);
                    
                    if (str_contains($result->getUrl(), 'etc/passwd') || 
                        str_contains($result->getUrl(), 'javascript:') ||
                        str_contains($result->getUrl(), 'data:') ||
                        str_contains($result->getUrl(), 'file:') ||
                        str_contains($result->getUrl(), 'ftp:')) {
                        
                        expect($result->getStatus())->toBeIn([
                            \App\Enums\LinkStatus::SECURITY_VIOLATION,
                            \App\Enums\LinkStatus::INVALID_FORMAT,
                        ]);
                    }
                }
                
            } catch (\App\Exceptions\SecurityException $e) {
                // Security exceptions are expected for dangerous content
                expect($e->getMessage())->toContain('Invalid file path');
            }
        });
        
    });

    describe('Error Recovery and Graceful Degradation', function () {
        
        test('E2E validation error recovery and graceful degradation', function () {
            // Test recovery from file system errors
            ExceptionTestingHelpers::testErrorRecovery(
                function () {
                    $config = ValidationConfig::withDefaults();
                    $linkValidationService = app(\App\Services\LinkValidationService::class);
                    
                    // Try to validate non-existent file
                    return $linkValidationService->validateFile('/nonexistent/file.md', $config);
                },
                function ($exception) {
                    // Recovery should return empty results or error result
                    return new \App\Services\ValueObjects\ValidationResultCollection([
                        \App\Services\ValueObjects\ValidationResult::create(
                            url: '/nonexistent/file.md',
                            status: \App\Enums\LinkStatus::BROKEN,
                            message: 'Recovery: File not found',
                            scope: \App\Enums\ValidationScope::INTERNAL
                        )
                    ]);
                },
                'Recovery: File not found'
            );
            
            // Test graceful degradation
            $fallbackResults = ExceptionTestingHelpers::testGracefulDegradation(
                function () {
                    throw new RuntimeException('Simulated E2E system failure');
                },
                new \App\Services\ValueObjects\ValidationResultCollection([])
            );
            
            expect($fallbackResults)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
            expect($fallbackResults->count())->toBe(0);
        });
        
    });

    describe('Resource Management', function () {
        
        test('E2E validation with resource cleanup', function () {
            $initialMemory = memory_get_usage(true);
            
            // Create and process multiple files
            for ($i = 0; $i < 10; $i++) {
                $content = "# Test {$i}\n[Link](https://example{$i}.com)\n";
                $testFile = $this->tempDir . "/cleanup_test_{$i}.md";
                file_put_contents($testFile, $content);
                
                $config = ValidationConfig::withDefaults();
                $linkValidationService = app(\App\Services\LinkValidationService::class);
                $reportingService = app(\App\Services\ReportingService::class);
                
                $results = $linkValidationService->validateFile($testFile, $config);
                $report = $reportingService->generateReport($results, OutputFormat::JSON);
                $summary = $reportingService->generateSummary($results);
                
                expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
                expect($report)->toBeString();
                expect($summary)->not->toBeNull();
                
                // Force garbage collection
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
            
            $finalMemory = memory_get_usage(true);
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory should not grow excessively
            expect($memoryIncrease)->toBeLessThan(20 * 1024 * 1024)->and('E2E processing should not leak memory');
        });
        
    });

    describe('System Validation Summary', function () {
        
        test('comprehensive E2E validation summary', function () {
            $summary = BoundaryConditionValidator::getValidationSummary();
            
            expect($summary)->toHaveKey('total_tests');
            expect($summary)->toHaveKey('passed_tests');
            expect($summary)->toHaveKey('failed_tests');
            expect($summary)->toHaveKey('success_rate');
            
            // E2E tests should demonstrate excellent overall system robustness
            expect($summary['success_rate'])->toBeGreaterThanOrEqual(80)->and('E2E system should handle edge cases excellently');
        });
        
    });
    
});

/**
 * Remove directory recursively.
 */
function removeDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    
    $files = array_diff(scandir($dir), ['.', '..']);
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    rmdir($dir);
}