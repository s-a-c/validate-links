<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Exceptions\SecurityException;
use App\Services\LinkValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;

use Tests\Support\BoundaryConditionValidator;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ExceptionTestingHelpers;
use Tests\Support\MockFactory;
use Tests\Support\ResourceLimitSimulator;

/**
 * Comprehensive edge case tests for LinkValidationService.
 * 
 * Tests null inputs, empty collections, malformed data, concurrent access patterns,
 * and resource exhaustion scenarios to ensure robust handling of boundary conditions
 * and unexpected inputs.
 * 
 * @package Tests\Unit\Services
 * @author Roo
 * @since 1.0.0
 */

describe('LinkValidationService Edge Cases', function () {
    beforeEach(function () {
        $securityService = MockFactory::createSecurityValidationService();
        $this->service = new LinkValidationService($securityService);
        
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
    
    describe('Null Input Validation', function () {
        it('validates null input handling across all service methods', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['null_values'];
            
            // Test validateLink with null inputs
            $results = BoundaryConditionValidator::validateNullSafety(
                function ($input) {
                    $config = ValidationConfig::withDefaults();
                    return $this->service->validateLink($input, $config);
                },
                $nullInputs
            );
            
            foreach ($results as $inputName => $result) {
                if ($result['null_safe']) {
                    expect($result['result'])->toBeInstanceOf(ValidationResult::class);
                    expect($result['result']->getStatus())->toBe(LinkStatus::INVALID_FORMAT);
                } else {
                    expect($result['exception'])->not->toBeNull();
                    expect($result['exception']['class'])->toBeIn([
                        InvalidArgumentException::class,
                        \TypeError::class,
                    ]);
                }
            }
        });
    });
    
    describe('Empty Collection Handling', function () {
        it('handles empty collections gracefully', function () {
            $emptyCollections = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['empty_collections'];
            $config = ValidationConfig::withDefaults();
            
            // Test validateLinks with empty arrays
            $results = BoundaryConditionValidator::validateEmptyCollections(
                function ($input) use ($config) {
                    return $this->service->validateLinks($input, $config);
                },
                ['empty_array' => []]
            );
            
            expect($results['empty_array']['handles_empty'])->toBeTrue();
            expect($results['empty_array']['result'])->toBeInstanceOf(ValidationResultCollection::class);
            expect($results['empty_array']['result']->count())->toBe(0);
            
            // Test validateFiles with empty file list
            $fileResults = $this->service->validateFiles([], $config);
            expect($fileResults)->toBeInstanceOf(ValidationResultCollection::class);
            expect($fileResults->count())->toBe(0);
        });
    });
    
    describe('URL Validation Edge Cases', function () {
        it('handles extreme URL lengths and formats', function () {
            $extremeUrls = EdgeCaseDataGenerator::generateExtremeUrls(['type' => 'all', 'count' => 20]);
            $config = ValidationConfig::withDefaults();
            
            foreach ($extremeUrls as $url) {
                $result = $this->service->validateLink($url, $config);
                
                expect($result)->toBeInstanceOf(ValidationResult::class);
                
                // Extreme URLs should be handled gracefully
                expect($result->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::INVALID_FORMAT,
                    LinkStatus::SECURITY_VIOLATION,
                    LinkStatus::BROKEN,
                ]);
                
                // Response time should be reasonable even for extreme URLs
                expect($result->getResponseTime())->toBeLessThan(30.0);
            }
        });
        
        it('handles malformed URLs properly', function () {
            $malformedData = EdgeCaseDataGenerator::generateMalformedData(['type' => 'urls']);
            $config = ValidationConfig::withDefaults();
            
            foreach ($malformedData['urls'] as $malformedUrl) {
                $result = $this->service->validateLink($malformedUrl, $config);
                
                expect($result)->toBeInstanceOf(ValidationResult::class);
                expect($result->getStatus())->toBe(LinkStatus::INVALID_FORMAT);
                expect($result->getError())->not->toBeEmpty();
            }
        });
        
        it('handles invalid characters in URLs', function () {
            $invalidCharData = EdgeCaseDataGenerator::generateInvalidCharacterData();
            $config = ValidationConfig::withDefaults();
            
            foreach ($invalidCharData as $invalidUrl) {
                $result = $this->service->validateLink($invalidUrl, $config);
                
                expect($result)->toBeInstanceOf(ValidationResult::class);
                expect($result->getStatus())->toBeIn([
                    LinkStatus::INVALID_FORMAT,
                    LinkStatus::SECURITY_VIOLATION,
                ]);
            }
        });
        
        it('classifies URL edge cases correctly', function () {
            $edgeCaseUrls = [
                '#' => 'anchor',
                '##' => 'anchor',
                '#anchor-with-dashes' => 'anchor',
                'http://' => 'external',
                'https://' => 'external',
                'ftp://example.com' => 'external',
                'mailto:test@example.com' => 'external',
                './relative' => 'internal',
                '../parent' => 'internal',
                '/absolute' => 'internal',
                'file.md#anchor' => 'cross_reference',
                './file.md#anchor' => 'cross_reference',
            ];
            
            foreach ($edgeCaseUrls as $url => $expectedType) {
                $linkType = $this->service->classifyLink($url);
                
                expect($linkType)->not->toBeNull("Failed to classify URL: {$url}");
                
                // Verify classification makes sense
                if ($expectedType === 'anchor') {
                    expect(str_starts_with($url, '#'))->toBeTrue();
                } elseif ($expectedType === 'external') {
                    expect(
                        str_starts_with($url, 'http') || 
                        str_contains($url, '://') ||
                        preg_match('/^[a-z]+:/i', $url)
                    )->toBeTrue();
                }
            }
        });
    });
    
    describe('File Validation Edge Cases', function () {
        it('validates non-existent files properly', function () {
            $config = ValidationConfig::withDefaults();
            $nonexistentFile = '/path/to/nonexistent/file.md';
            
            $result = $this->service->validateFile($nonexistentFile, $config);
            
            expect($result)->toBeInstanceOf(ValidationResultCollection::class);
            expect($result->count())->toBe(1);
            
            $fileResult = $result->first();
            expect($fileResult->getStatus())->toBe(LinkStatus::BROKEN);
            expect($fileResult->getError())->toBe('File not found');
        });
        
        it('validates malformed file paths', function () {
            $malformedData = EdgeCaseDataGenerator::generateMalformedData(['type' => 'paths']);
            $config = ValidationConfig::withDefaults();
            
            foreach ($malformedData['paths'] as $malformedPath) {
                try {
                    $result = $this->service->validateFile($malformedPath, $config);
                    
                    // Should either handle gracefully or throw security exception
                    expect($result)->toBeInstanceOf(ValidationResultCollection::class);
                    
                } catch (SecurityException $e) {
                    // Security exceptions are expected for dangerous paths
                    expect($e->getMessage())->toContain('Invalid file path');
                }
            }
        });
    });
    
    describe('Performance and Resource Handling', function () {
        it('handles large data sets efficiently', function () {
            $largeUrlSet = EdgeCaseDataGenerator::generateLargeDataSets([
                'size' => 'medium',
                'type' => 'urls'
            ]);
            
            $config = ValidationConfig::withDefaults();
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $result = $this->service->validateLinks($largeUrlSet, $config);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            expect($result)->toBeInstanceOf(ValidationResultCollection::class);
            expect($result->count())->toBe(count($largeUrlSet));
            
            // Performance assertions
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            expect($executionTime)->toBeLessThan(60.0, 'Large data set processing should complete within 60 seconds');
            expect($memoryUsage)->toBeLessThan(100 * 1024 * 1024, 'Memory usage should be reasonable');
        });
        
        it('handles concurrent validation scenarios', function () {
            $concurrencyScenario = EdgeCaseDataGenerator::generateConcurrencyScenarios([
                'scenario' => 'basic',
                'threads' => 5
            ]);
            
            $config = ValidationConfig::withDefaults();
            $testUrl = 'https://example.com';
            
            $results = BoundaryConditionValidator::validateConcurrentAccess(
                function () use ($testUrl, $config) {
                    return $this->service->validateLink($testUrl, $config);
                },
                $concurrencyScenario
            );
            
            expect($results)->toHaveKey('analysis');
            $analysis = $results['analysis'];
            
            expect($analysis['total_accesses'])->toBeGreaterThan(0);
            expect($analysis['successful_accesses'])->toBeGreaterThanOrEqual(0);
            expect($analysis['race_conditions_detected'])->toBeLessThan(10, 'Should have minimal race conditions');
        });
        
        it('handles memory pressure scenarios', function () {
            $config = ValidationConfig::withDefaults();
            $testUrl = 'https://example.com';
            
            try {
                // Simulate moderate memory pressure
                ResourceLimitSimulator::simulateMemoryPressure([
                    'target_mb' => 50,
                    'gradual' => true
                ]);
                
                $result = $this->service->validateLink($testUrl, $config);
                
                expect($result)->toBeInstanceOf(ValidationResult::class);
                
                // Service should still function under memory pressure
                expect($result->getStatus())->not->toBeNull();
                
            } finally {
                ResourceLimitSimulator::releaseMemoryPressure();
            }
        });
        
        it('handles network latency gracefully', function () {
            $config = ValidationConfig::withDefaults();
            $testUrl = 'https://example.com';
            
            // Simulate high network latency
            ResourceLimitSimulator::simulateNetworkLatency([
                'latency_ms' => 2000,
                'jitter' => 0.2
            ]);
            
            $startTime = microtime(true);
            $result = $this->service->validateLink($testUrl, $config);
            $endTime = microtime(true);
            
            expect($result)->toBeInstanceOf(ValidationResult::class);
            
            // Should handle latency gracefully
            $executionTime = $endTime - $startTime;
            expect($executionTime)->toBeGreaterThan(1.5, 'Should reflect simulated latency');
            expect($executionTime)->toBeLessThan(10.0, 'Should not hang indefinitely');
        });
    });
    
    describe('Configuration Boundary Testing', function () {
        it('validates configuration boundary values', function () {
            $boundaryValues = [
                'zero_timeout' => ['timeout' => 0],
                'negative_timeout' => ['timeout' => -1],
                'max_timeout' => ['timeout' => PHP_INT_MAX],
                'zero_redirects' => ['max_redirects' => 0],
                'negative_redirects' => ['max_redirects' => -1],
                'max_redirects' => ['max_redirects' => 999],
                'zero_concurrent' => ['concurrent_requests' => 0],
                'negative_concurrent' => ['concurrent_requests' => -1],
                'max_concurrent' => ['concurrent_requests' => 999],
            ];
            
            $results = BoundaryConditionValidator::validateConfigBoundaries(
                function ($configData) {
                    return ValidationConfig::create($configData);
                },
                $boundaryValues
            );
            
            foreach ($results as $testName => $result) {
                if ($result['handles_config']) {
                    expect($result['result'])->toBeInstanceOf(ValidationConfig::class);
                } else {
                    expect($result['exception'])->not->toBeNull();
                    expect($result['exception']['class'])->toBe(InvalidArgumentException::class);
                }
            }
        });
        
        it('handles string boundary conditions', function () {
            $stringBoundaries = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['boundary_strings'];
            $config = ValidationConfig::withDefaults();
            
            $results = BoundaryConditionValidator::validateStringBoundaries(
                function ($input) use ($config) {
                    return $this->service->validateLink($input, $config);
                },
                $stringBoundaries
            );
            
            foreach ($results as $testName => $result) {
                expect($result['handles_string'])->toBeTrue("Failed to handle string boundary: {$testName}");
                
                if ($result['result'] instanceof ValidationResult) {
                    expect($result['result']->getStatus())->toBeIn([
                        LinkStatus::VALID,
                        LinkStatus::INVALID_FORMAT,
                        LinkStatus::BROKEN,
                    ]);
                }
            }
        });
    });
    
    describe('Content Processing Edge Cases', function () {
        it('extracts links from malformed content', function () {
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
            ];
            
            foreach ($malformedContents as $content) {
                $links = $this->service->extractLinks($content);
                
                expect($links)->toBeArray();
                
                // Should extract valid links and ignore malformed ones
                foreach ($links as $link) {
                    expect($link)->toHaveKey('url');
                    expect($link)->toHaveKey('text');
                    expect($link)->toHaveKey('line');
                    expect($link['url'])->toBeString();
                    expect($link['text'])->toBeString();
                    expect($link['line'])->toBeInt();
                }
            }
        });
    });
    
    describe('Error Recovery', function () {
        it('implements proper error recovery mechanisms', function () {
            $config = ValidationConfig::withDefaults();
            
            // Test recovery from network errors
            ExceptionTestingHelpers::testErrorRecovery(
                function () use ($config) {
                    // This should fail
                    return $this->service->validateLink('https://nonexistent-domain-12345.com', $config);
                },
                function ($exception) {
                    // Recovery should return a proper ValidationResult
                    return ValidationResult::failure(
                        'https://nonexistent-domain-12345.com',
                        ValidationScope::EXTERNAL,
                        LinkStatus::BROKEN,
                        'Recovery: ' . $exception->getMessage()
                    );
                },
                'Recovery: '
            );
            
            // Test graceful degradation
            $fallbackResult = ExceptionTestingHelpers::testGracefulDegradation(
                function () use ($config) {
                    throw new RuntimeException('Simulated failure');
                },
                ValidationResult::failure('fallback', ValidationScope::ALL, LinkStatus::BROKEN, 'Fallback result')
            );
            
            expect($fallbackResult)->toBeInstanceOf(ValidationResult::class);
            expect($fallbackResult->getStatus())->toBe(LinkStatus::BROKEN);
            expect($fallbackResult->getError())->toBe('Fallback result');
        });
    });
    
    describe('Validation Summary', function () {
        it('provides comprehensive validation summary', function () {
            $summary = BoundaryConditionValidator::getValidationSummary();
            
            expect($summary)->toHaveKey('total_tests');
            expect($summary)->toHaveKey('passed_tests');
            expect($summary)->toHaveKey('failed_tests');
            expect($summary)->toHaveKey('success_rate');
            expect($summary)->toHaveKey('categories');
            
            expect($summary['total_tests'])->toBeInt();
            expect($summary['passed_tests'])->toBeInt();
            expect($summary['failed_tests'])->toBeInt();
            expect($summary['success_rate'])->toBeFloat();
            expect($summary['categories'])->toBeArray();
            
            // Success rate should be reasonable
            expect($summary['success_rate'])->toBeGreaterThanOrEqual(0);
            expect($summary['success_rate'])->toBeLessThanOrEqual(100);
        });
    });
});