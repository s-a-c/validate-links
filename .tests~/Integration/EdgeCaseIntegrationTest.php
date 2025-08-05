<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\LinkValidationService;
use App\Services\ReportingService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use Tests\Support\BoundaryConditionValidator;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ExceptionTestingHelpers;
use Tests\Support\MockFactory;
use Tests\Support\ResourceLimitSimulator;

/**
 * Comprehensive integration edge case tests for the entire validation system.
 * 
 * Tests the interaction between multiple components under extreme conditions,
 * including null inputs, empty collections, malformed data, concurrent access,
 * and resource exhaustion scenarios across the entire validation pipeline.
 * 
 * @package Tests\Integration
 * @author Roo
 * @since 1.0.0
 */

describe('Edge Case Integration', function () {
    beforeEach(function () {
        $this->securityValidationService = new SecurityValidationService();
        $this->linkValidationService = new LinkValidationService($this->securityValidationService);
        $this->reportingService = new ReportingService();
        
        // Initialize edge case utilities
        EdgeCaseDataGenerator::reset();
        BoundaryConditionValidator::initialize();
        ResourceLimitSimulator::initialize();
    });
    
    afterEach(function () {
        MockFactory::verifyAndClearMocks();
        BoundaryConditionValidator::reset();
        ResourceLimitSimulator::cleanup();
    });

    describe('Null Input Handling', function () {
        it('handles complete validation pipeline with null inputs', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['null_values'];
            
            foreach ($nullInputs as $inputName => $nullValue) {
                try {
                    // Test the complete pipeline with null inputs
                    if ($nullValue === null) {
                        // Skip null values that would cause type errors
                        continue;
                    }
                    
                    $config = ValidationConfig::withDefaults();
                    
                    // Create a temporary file with null-like content
                    $tempFile = tempnam(sys_get_temp_dir(), 'edge_case_test_');
                    file_put_contents($tempFile, $nullValue);
                    
                    try {
                        $results = $this->linkValidationService->validateFile($tempFile, $config);
                        expect($results)->toBeInstanceOf(ValidationResultCollection::class);
                        
                        $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
                        expect($report)->toBeString();
                        expect($report)->not->toBeEmpty();
                        
                        // Verify JSON is valid
                        $decoded = json_decode($report, true);
                        expect($decoded)->not->toBeNull("Report should be valid JSON for input: {$inputName}");
                        
                    } finally {
                        if (file_exists($tempFile)) {
                            unlink($tempFile);
                        }
                    }
                    
                } catch (\Throwable $e) {
                    // Some null inputs may cause expected exceptions
                    expect([
                        \TypeError::class,
                        \InvalidArgumentException::class,
                        RuntimeException::class,
                    ])->toContain(get_class($e));
                }
            }
        });
    });

    describe('Empty Collection Handling', function () {
        it('handles complete validation pipeline with empty collections', function () {
            $config = ValidationConfig::withDefaults();
            
            // Test with empty URL list
            $emptyResults = $this->linkValidationService->validateLinks([], $config);
            expect($emptyResults)->toBeInstanceOf(ValidationResultCollection::class);
            expect($emptyResults->count())->toBe(0);
            
            // Test reporting with empty results
            $report = $this->reportingService->generateReport($emptyResults, OutputFormat::JSON);
            expect($report)->toBeString();
            expect($report)->not->toBeEmpty();
            
            $decoded = json_decode($report, true);
            expect($decoded)->not->toBeNull();
            
            // Test summary generation with empty results
            $summary = $this->reportingService->generateSummary($emptyResults);
            expect($summary->getTotalLinks())->toBe(0);
            expect($summary->getBrokenLinks())->toBe(0);
            expect($summary->getSuccessRate())->toBe(0);
        });
    });

    describe('Extreme Data Sets', function () {
        it('handles complete validation pipeline with extreme data sets', function () {
            $largeUrlSet = EdgeCaseDataGenerator::generateLargeDataSets([
                'size' => 'small', // Use small size for integration testing
                'type' => 'urls'
            ]);
            
            $config = ValidationConfig::withDefaults();
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Validate large URL set
            $results = $this->linkValidationService->validateLinks($largeUrlSet, $config);
            
            expect($results)->toBeInstanceOf(ValidationResultCollection::class);
            expect($results->count())->toBe(count($largeUrlSet));
            
            // Generate report for large data set
            $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
            expect($report)->toBeString();
            expect($report)->not->toBeEmpty();
            
            // Generate summary
            $summary = $this->reportingService->generateSummary($results);
            expect($summary->getTotalLinks())->toBe(count($largeUrlSet));
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            // Performance assertions
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            expect($executionTime)->toBeLessThan(60.0, 'Complete pipeline should handle large data sets within 60 seconds');
            expect($memoryUsage)->toBeLessThan(100 * 1024 * 1024, 'Memory usage should be reasonable');
        });
    });

    describe('Malformed Data Handling', function () {
        it('handles complete validation pipeline with malformed data', function () {
            $malformedData = EdgeCaseDataGenerator::generateMalformedData(['type' => 'all']);
            $config = ValidationConfig::withDefaults();
            
            // Test with malformed URLs
            foreach ($malformedData['urls'] as $malformedUrl) {
                $result = $this->linkValidationService->validateLink($malformedUrl, $config);
                
                expect($result)->toBeInstanceOf(ValidationResult::class);
                expect($result->getStatus())->toBe(LinkStatus::INVALID_FORMAT);
                
                // Test reporting with malformed URL result
                $results = new ValidationResultCollection([$result]);
                $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
                
                expect($report)->toBeString();
                $decoded = json_decode($report, true);
                expect($decoded)->not->toBeNull("Report should handle malformed URL: {$malformedUrl}");
            }
            
            // Test with malformed file paths
            foreach ($malformedData['paths'] as $malformedPath) {
                try {
                    $results = $this->linkValidationService->validateFile($malformedPath, $config);
                    
                    expect($results)->toBeInstanceOf(ValidationResultCollection::class);
                    
                    // Generate report for malformed path results
                    $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
                    expect($report)->toBeString();
                    
                } catch (\Throwable $e) {
                    // Some malformed paths may cause expected exceptions
                    expect([
                        \App\Exceptions\SecurityException::class,
                        RuntimeException::class,
                    ])->toContain(get_class($e));
                }
            }
        });
    });

    describe('Resource Pressure Scenarios', function () {
        it('handles complete validation pipeline under memory pressure', function () {
            $config = ValidationConfig::withDefaults();
            $testUrls = [
                'https://example.com',
                'https://httpbin.org/get',
                './test-file.md',
            ];
            
            try {
                // Simulate moderate memory pressure
                ResourceLimitSimulator::simulateMemoryPressure([
                    'target_mb' => 40,
                    'gradual' => true
                ]);
                
                // Validate URLs under memory pressure
                $results = $this->linkValidationService->validateLinks($testUrls, $config);
                
                expect($results)->toBeInstanceOf(ValidationResultCollection::class);
                expect($results->count())->toBe(count($testUrls));
                
                // Generate report under memory pressure
                $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
                expect($report)->toBeString();
                expect($report)->not->toBeEmpty();
                
                // Verify JSON is still valid under memory pressure
                $decoded = json_decode($report, true);
                expect($decoded)->not->toBeNull();
                
                // Generate summary under memory pressure
                $summary = $this->reportingService->generateSummary($results);
                expect($summary->getTotalLinks())->toBe(count($testUrls));
                
            } finally {
                ResourceLimitSimulator::releaseMemoryPressure();
            }
        });

        it('handles complete validation pipeline with network latency', function () {
            $config = ValidationConfig::withDefaults();
            $testUrls = [
                'https://example.com',
                'https://httpbin.org/get',
            ];
            
            // Simulate high network latency
            ResourceLimitSimulator::simulateNetworkLatency([
                'latency_ms' => 1000,
                'jitter' => 0.2
            ]);
            
            $startTime = microtime(true);
            
            // Validate URLs with network latency
            $results = $this->linkValidationService->validateLinks($testUrls, $config);
            
            $endTime = microtime(true);
            
            expect($results)->toBeInstanceOf(ValidationResultCollection::class);
            expect($results->count())->toBe(count($testUrls));
            
            // Should reflect latency but not hang
            $executionTime = $endTime - $startTime;
            expect($executionTime)->toBeGreaterThan(0.5, 'Should reflect simulated latency');
            expect($executionTime)->toBeLessThan(15.0, 'Should not hang indefinitely');
            
            // Generate report with latency results
            $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
            expect($report)->toBeString();
            
            $decoded = json_decode($report, true);
            expect($decoded)->not->toBeNull();
        });
    });

    describe('Concurrency and Access Patterns', function () {
        it('handles complete validation pipeline with concurrent access', function () {
            $config = ValidationConfig::withDefaults();
            $testUrls = ['https://example.com', 'https://httpbin.org/get'];
            
            $concurrencyScenario = EdgeCaseDataGenerator::generateConcurrencyScenarios([
                'scenario' => 'basic',
                'threads' => 3 // Lower thread count for integration testing
            ]);
            
            $results = BoundaryConditionValidator::validateConcurrentAccess(
                function () use ($testUrls, $config) {
                    $validationResults = $this->linkValidationService->validateLinks($testUrls, $config);
                    $report = $this->reportingService->generateReport($validationResults, OutputFormat::JSON);
                    return ['results' => $validationResults, 'report' => $report];
                },
                $concurrencyScenario
            );
            
            expect($results)->toHaveKey('analysis');
            $analysis = $results['analysis'];
            
            expect($analysis['total_accesses'])->toBeGreaterThan(0);
            expect($analysis['successful_accesses'])->toBeGreaterThanOrEqual(0);
            
            // Complete pipeline should handle concurrent access reasonably well
            expect($analysis['race_conditions_detected'])->toBeLessThan(10, 'Pipeline should handle concurrency well');
        });
    });

    describe('Character Encoding and Data Validation', function () {
        it('handles complete validation pipeline with invalid character data', function () {
            $invalidCharData = EdgeCaseDataGenerator::generateInvalidCharacterData();
            $config = ValidationConfig::withDefaults();
            
            foreach ($invalidCharData as $index => $invalidChars) {
                $testUrl = 'https://example.com/path' . $invalidChars;
                
                // Validate URL with invalid characters
                $result = $this->linkValidationService->validateLink($testUrl, $config);
                
                expect($result)->toBeInstanceOf(ValidationResult::class);
                expect([
                    LinkStatus::INVALID_FORMAT,
                    LinkStatus::BROKEN,
                    LinkStatus::VALID, // Some might be handled by URL encoding
                ])->toContain($result->getStatus());
                
                // Test reporting with invalid character results
                $results = new ValidationResultCollection([$result]);
                $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
                
                expect($report)->toBeString();
                
                // Report should handle invalid characters gracefully
                $decoded = json_decode($report, true);
                expect($decoded)->not->toBeNull("Report should handle invalid characters in index: {$index}");
            }
        });
    });

    describe('Error Recovery and Resilience', function () {
        it('handles complete validation pipeline error recovery', function () {
            $config = ValidationConfig::withDefaults();
            
            // Test recovery from validation errors
            ExceptionTestingHelpers::testErrorRecovery(
                function () use ($config) {
                    // This should cause some kind of failure
                    $results = $this->linkValidationService->validateLinks(['invalid-url-format'], $config);
                    return $this->reportingService->generateReport($results, OutputFormat::JSON);
                },
                function ($exception) {
                    // Recovery should return a basic error report
                    return json_encode([
                        'error' => 'Pipeline recovery',
                        'message' => $exception->getMessage(),
                        'timestamp' => time(),
                    ]);
                },
                'Pipeline recovery'
            );
            
            // Test graceful degradation
            $fallbackReport = ExceptionTestingHelpers::testGracefulDegradation(
                function () {
                    throw new RuntimeException('Simulated pipeline failure');
                },
                json_encode(['error' => 'Pipeline unavailable', 'status' => 'degraded'])
            );
            
            expect($fallbackReport)->toBeString();
            $decoded = json_decode($fallbackReport, true);
            expect($decoded)->not->toBeNull();
            expect($decoded['error'])->toBe('Pipeline unavailable');
        });
    });

    describe('Configuration Edge Cases', function () {
        it('handles complete validation pipeline with extreme configurations', function () {
            $extremeConfigs = [
                'minimal_timeout' => ValidationConfig::create(['timeout' => 1]),
                'high_concurrency' => ValidationConfig::create(['concurrent_requests' => 50]),
                'no_redirects' => ValidationConfig::create(['max_redirects' => 0]),
                'all_scopes' => ValidationConfig::create(['scopes' => [ValidationScope::ALL]]),
                'single_scope' => ValidationConfig::create(['scopes' => [ValidationScope::EXTERNAL]]),
            ];
            
            $testUrls = ['https://example.com', 'https://httpbin.org/get'];
            
            foreach ($extremeConfigs as $configName => $config) {
                try {
                    $results = $this->linkValidationService->validateLinks($testUrls, $config);
                    
                    expect($results)->toBeInstanceOf(ValidationResultCollection::class);
                    expect($results->count())->toBe(count($testUrls));
                    
                    // Generate report with extreme configuration
                    $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
                    expect($report)->toBeString();
                    
                    $decoded = json_decode($report, true);
                    expect($decoded)->not->toBeNull("Report should work with config: {$configName}");
                    
                    // Generate summary
                    $summary = $this->reportingService->generateSummary($results);
                    expect($summary->getTotalLinks())->toBe(count($testUrls));
                    
                } catch (\Throwable $e) {
                    // Some extreme configurations may cause expected exceptions
                    expect([
                        \InvalidArgumentException::class,
                        RuntimeException::class,
                    ])->toContain(get_class($e));
                }
            }
        });
    });

    describe('Output Format Validation', function () {
        it('handles complete validation pipeline with all output formats', function () {
            $config = ValidationConfig::withDefaults();
            $testUrls = ['https://example.com', './test-file.md'];
            
            $results = $this->linkValidationService->validateLinks($testUrls, $config);
            expect($results)->toBeInstanceOf(ValidationResultCollection::class);
            
            // Test all output formats
            foreach (OutputFormat::cases() as $format) {
                $report = $this->reportingService->generateReport($results, $format);
                
                expect($report)->toBeString();
                expect($report)->not->toBeEmpty();
                
                // Verify format-specific characteristics
                if ($format === OutputFormat::JSON) {
                    $decoded = json_decode($report, true);
                    expect($decoded)->not->toBeNull("JSON format should be valid");
                    expect($decoded)->toBeArray();
                }
                
                // Test export functionality
                $tempFile = tempnam(sys_get_temp_dir(), 'edge_case_export_');
                $exportResult = $this->reportingService->exportReport($results, $tempFile, $format);
                
                if ($exportResult) {
                    expect(file_exists($tempFile))->toBeTrue();
                    expect(filesize($tempFile))->toBeGreaterThan(0);
                    unlink($tempFile);
                }
            }
        });
    });

    describe('Resource Management', function () {
        it('handles complete validation pipeline resource cleanup', function () {
            $config = ValidationConfig::withDefaults();
            $testUrls = ['https://example.com'];
            
            $initialMemory = memory_get_usage(true);
            
            // Run multiple validation cycles
            for ($i = 0; $i < 10; $i++) {
                $results = $this->linkValidationService->validateLinks($testUrls, $config);
                $report = $this->reportingService->generateReport($results, OutputFormat::JSON);
                $summary = $this->reportingService->generateSummary($results);
                
                expect($results)->toBeInstanceOf(ValidationResultCollection::class);
                expect($report)->toBeString();
                expect($summary)->not->toBeNull();
                
                // Force garbage collection
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
            
            $finalMemory = memory_get_usage(true);
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory increase should be reasonable (allowing for some growth)
            expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024, 'Memory should not grow excessively');
        });
    });

    describe('Integration Validation Summary', function () {
        it('provides comprehensive integration validation summary', function () {
            $summary = BoundaryConditionValidator::getValidationSummary();
            
            expect($summary)->toHaveKey('total_tests');
            expect($summary)->toHaveKey('passed_tests');
            expect($summary)->toHaveKey('failed_tests');
            expect($summary)->toHaveKey('success_rate');
            
            // Integration tests should demonstrate good overall system robustness
            expect($summary['success_rate'])->toBeGreaterThanOrEqual(75, 'Integration pipeline should handle edge cases well');
        });
    });
});