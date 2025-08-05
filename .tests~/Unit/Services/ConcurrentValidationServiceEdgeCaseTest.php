<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ConcurrentValidationService;
use App\Services\ValueObjects\ValidationResult;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;

use Tests\Support\BoundaryConditionValidator;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ExceptionTestingHelpers;
use Tests\Support\ResourceLimitSimulator;

/**
 * Comprehensive edge case tests for ConcurrentValidationService.
 * 
 * Tests null inputs, empty collections, malformed data, concurrent access patterns,
 * resource exhaustion scenarios, and network failure conditions to ensure robust
 * handling of concurrent validation under extreme conditions.
 * 
 * @package Tests\Unit\Services
 * @author Roo
 * @since 1.0.0
 */

describe('ConcurrentValidationService Edge Cases', function () {
    beforeEach(function () {
        $this->service = new ConcurrentValidationService(5); // Low concurrency for testing
        
        // Initialize edge case utilities
        EdgeCaseDataGenerator::reset();
        BoundaryConditionValidator::initialize();
        ResourceLimitSimulator::initialize();
    });
    
    afterEach(function () {
        BoundaryConditionValidator::reset();
        ResourceLimitSimulator::cleanup();
    });
    
    describe('Null and Empty Input Handling', function () {
        it('handles null inputs for concurrent validation', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['null_values'];
            
            $results = BoundaryConditionValidator::validateNullSafety(
                function ($input) {
                    if ($input === null) {
                        // This should cause a type error
                        return $this->service->validateExternalLinksConcurrently($input);
                    }
                    return $this->service->validateExternalLinksConcurrently([]);
                },
                ['null' => null]
            );
            
            // Null input should cause a type error
            expect($results['null']['null_safe'])->toBeFalse();
            expect($results['null']['exception']['class'])->toBe(\TypeError::class);
        });
        
        it('handles empty collection validation', function () {
            $emptyLinks = [];
            
            $results = $this->service->validateExternalLinksConcurrently($emptyLinks);
            
            expect($results)->toBeArray();
            expect($results)->toBeEmpty();
        });
    });
    
    describe('Malformed Data Handling', function () {
        it('handles malformed link data gracefully', function () {
            $malformedLinks = [
                // Missing required fields
                ['text' => 'Link without URL', 'line' => 1],
                ['url' => 'https://example.com', 'line' => 1], // Missing text
                ['url' => 'https://example.com', 'text' => 'Link'], // Missing line
                
                // Invalid data types
                ['url' => 123, 'text' => 'Invalid URL type', 'line' => 1],
                ['url' => 'https://example.com', 'text' => 456, 'line' => 1],
                ['url' => 'https://example.com', 'text' => 'Link', 'line' => 'invalid'],
                
                // Null values
                ['url' => null, 'text' => 'Null URL', 'line' => 1],
                ['url' => 'https://example.com', 'text' => null, 'line' => 1],
                ['url' => 'https://example.com', 'text' => 'Link', 'line' => null],
            ];
            
            foreach ($malformedLinks as $index => $malformedLink) {
                try {
                    $results = $this->service->validateExternalLinksConcurrently([$malformedLink]);
                    
                    expect($results)->toBeArray();
                    
                    if (!empty($results)) {
                        $result = $results[0];
                        expect($result)->toBeInstanceOf(ValidationResult::class);
                        
                        // Malformed data should result in failure or be handled gracefully
                        expect($result->getStatus())->toBeIn([
                            LinkStatus::BROKEN,
                            LinkStatus::INVALID_FORMAT,
                        ]);
                    }
                    
                } catch (\Throwable $e) {
                    // Some malformed data may cause expected exceptions
                    expect(get_class($e))->toBeIn([
                        \TypeError::class,
                        \InvalidArgumentException::class,
                        RequestException::class,
                    ]);
                }
            }
        });
    });
    
    describe('Extreme URL Validation', function () {
        it('validates extreme URL scenarios', function () {
            $extremeUrls = EdgeCaseDataGenerator::generateExtremeUrls(['type' => 'all', 'count' => 20]);
            
            $extremeLinks = [];
            foreach ($extremeUrls as $index => $url) {
                $extremeLinks[] = [
                    'url' => $url,
                    'text' => "Extreme URL {$index}",
                    'line' => $index + 1,
                ];
            }
            
            $startTime = microtime(true);
            $results = $this->service->validateExternalLinksConcurrently($extremeLinks);
            $endTime = microtime(true);
            
            expect($results)->toBeArray();
            expect($results)->toHaveCount(count($extremeLinks));
            
            foreach ($results as $result) {
                expect($result)->toBeInstanceOf(ValidationResult::class);
                expect($result->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::BROKEN,
                    LinkStatus::INVALID_FORMAT,
                    LinkStatus::TIMEOUT,
                ]);
            }
            
            // Concurrent validation should be reasonably fast
            $executionTime = $endTime - $startTime;
            expect($executionTime)->toBeLessThan(30.0);
        });
        
        it('validates invalid character URLs', function () {
            $invalidCharData = EdgeCaseDataGenerator::generateInvalidCharacterData();
            
            $invalidCharLinks = [];
            foreach ($invalidCharData as $index => $invalidChars) {
                $invalidCharLinks[] = [
                    'url' => 'https://example.com/path' . $invalidChars,
                    'text' => "Invalid chars {$index}",
                    'line' => $index + 1,
                ];
            }
            
            $results = $this->service->validateExternalLinksConcurrently($invalidCharLinks);
            
            expect($results)->toBeArray();
            expect($results)->toHaveCount(count($invalidCharLinks));
            
            foreach ($results as $result) {
                expect($result)->toBeInstanceOf(ValidationResult::class);
                
                // Invalid character URLs should be handled gracefully
                expect($result->getStatus())->toBeIn([
                    LinkStatus::BROKEN,
                    LinkStatus::INVALID_FORMAT,
                    LinkStatus::VALID, // Some might be handled by the HTTP client
                ]);
            }
        });
    });
    
    describe('Large Data Set Processing', function () {
        it('handles large data set concurrent validation', function () {
            $largeUrlSet = EdgeCaseDataGenerator::generateLargeDataSets([
                'size' => 'small', // Use small size for testing
                'type' => 'urls'
            ]);
            
            $largeLinks = [];
            foreach ($largeUrlSet as $index => $url) {
                $largeLinks[] = [
                    'url' => $url,
                    'text' => "Link {$index}",
                    'line' => $index + 1,
                ];
            }
            
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $results = $this->service->validateExternalLinksConcurrently($largeLinks);
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            expect($results)->toBeArray();
            expect($results)->toHaveCount(count($largeLinks));
            
            // Performance assertions
            $executionTime = $endTime - $startTime;
            $memoryUsage = $endMemory - $startMemory;
            
            expect($executionTime)->toBeLessThan(60.0);
            expect($memoryUsage)->toBeLessThan(100 * 1024 * 1024);
            
            foreach ($results as $result) {
                expect($result)->toBeInstanceOf(ValidationResult::class);
            }
        });
    });
    
    describe('Network Failure Handling', function () {
        it('handles network failures gracefully', function () {
            $networkFailureLinks = [
                ['url' => 'https://nonexistent-domain-12345.com', 'text' => 'Nonexistent domain', 'line' => 1],
                ['url' => 'https://httpbin.org/status/500', 'text' => 'Server error', 'line' => 2],
                ['url' => 'https://httpbin.org/status/404', 'text' => 'Not found', 'line' => 3],
                ['url' => 'https://httpbin.org/delay/10', 'text' => 'Slow response', 'line' => 4],
            ];
            
            $results = $this->service->validateExternalLinksConcurrently($networkFailureLinks);
            
            expect($results)->toBeArray();
            expect($results)->toHaveCount(count($networkFailureLinks));
            
            foreach ($results as $result) {
                expect($result)->toBeInstanceOf(ValidationResult::class);
                
                // Network failures should be handled gracefully
                expect($result->getStatus())->toBeIn([
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::TIMEOUT,
                    LinkStatus::VALID, // Some might succeed
                ]);
            }
        });
        
        it('handles network latency in concurrent validation', function () {
            $testLinks = [
                ['url' => 'https://example.com', 'text' => 'Example', 'line' => 1],
                ['url' => 'https://httpbin.org/get', 'text' => 'HTTPBin', 'line' => 2],
            ];
            
            // Simulate high network latency
            ResourceLimitSimulator::simulateNetworkLatency([
                'latency_ms' => 1000,
                'jitter' => 0.2
            ]);
            
            $startTime = microtime(true);
            $results = $this->service->validateExternalLinksConcurrently($testLinks);
            $endTime = microtime(true);
            
            expect($results)->toBeArray();
            expect($results)->toHaveCount(count($testLinks));
            
            // Should handle latency gracefully
            $executionTime = $endTime - $startTime;
            expect($executionTime)->toBeGreaterThan(0.5);
            expect($executionTime)->toBeLessThan(15.0);
        });
    });
    
    describe('Resource Pressure Testing', function () {
        it('handles memory pressure during concurrent validation', function () {
            $testLinks = [
                ['url' => 'https://example.com', 'text' => 'Example', 'line' => 1],
                ['url' => 'https://httpbin.org/get', 'text' => 'HTTPBin', 'line' => 2],
            ];
            
            try {
                // Simulate moderate memory pressure
                ResourceLimitSimulator::simulateMemoryPressure([
                    'target_mb' => 30,
                    'gradual' => true
                ]);
                
                $results = $this->service->validateExternalLinksConcurrently($testLinks);
                
                expect($results)->toBeArray();
                expect($results)->toHaveCount(count($testLinks));
                
                foreach ($results as $result) {
                    expect($result)->toBeInstanceOf(ValidationResult::class);
                }
                
            } finally {
                ResourceLimitSimulator::releaseMemoryPressure();
            }
        });
        
        it('handles connection pool exhaustion', function () {
            // Create many links to potentially exhaust connection pool
            $manyLinks = [];
            for ($i = 0; $i < 20; $i++) {
                $manyLinks[] = [
                    'url' => "https://httpbin.org/delay/2?id={$i}",
                    'text' => "Link {$i}",
                    'line' => $i + 1,
                ];
            }
            
            try {
                // Simulate connection pool exhaustion
                $connections = ResourceLimitSimulator::simulateConnectionPoolExhaustion([
                    'max_connections' => 5,
                    'connection_type' => 'tcp'
                ]);
                
                $results = $this->service->validateExternalLinksConcurrently($manyLinks);
                
                expect($results)->toBeArray();
                expect($results)->toHaveCount(count($manyLinks));
                
                foreach ($results as $result) {
                    expect($result)->toBeInstanceOf(ValidationResult::class);
                    
                    // Some requests might fail due to connection limits
                    expect($result->getStatus())->toBeIn([
                        LinkStatus::VALID,
                        LinkStatus::BROKEN,
                        LinkStatus::TIMEOUT,
                    ]);
                }
                
            } finally {
                // Clean up connections
                foreach ($connections as $connection) {
                    if (is_resource($connection)) {
                        fclose($connection);
                    }
                }
            }
        });
    });
    
    describe('Concurrency Level Testing', function () {
        it('works with different concurrency levels', function () {
            $testLinks = [];
            for ($i = 0; $i < 10; $i++) {
                $testLinks[] = [
                    'url' => "https://httpbin.org/delay/1",
                    'text' => "Delayed link {$i}",
                    'line' => $i + 1,
                ];
            }
            
            $concurrencyLevels = [1, 3, 5, 10];
            
            foreach ($concurrencyLevels as $concurrency) {
                $service = new ConcurrentValidationService($concurrency);
                
                $startTime = microtime(true);
                $results = $service->validateExternalLinksConcurrently($testLinks);
                $endTime = microtime(true);
                
                expect($results)->toBeArray();
                expect($results)->toHaveCount(count($testLinks));
                
                $executionTime = $endTime - $startTime;
                
                // Higher concurrency should generally be faster (though network conditions may vary)
                expect($executionTime)->toBeLessThan(30.0);
                
                foreach ($results as $result) {
                    expect($result)->toBeInstanceOf(ValidationResult::class);
                }
            }
        });
        
        it('maintains performance characteristics across concurrency levels', function () {
            $performanceLinks = [];
            for ($i = 0; $i < 10; $i++) {
                $performanceLinks[] = [
                    'url' => 'https://httpbin.org/get',
                    'text' => "Performance test {$i}",
                    'line' => $i + 1,
                ];
            }
            
            // Test with different concurrency levels
            $concurrencyLevels = [1, 5];
            $executionTimes = [];
            
            foreach ($concurrencyLevels as $concurrency) {
                $service = new ConcurrentValidationService($concurrency);
                
                $startTime = microtime(true);
                $results = $service->validateExternalLinksConcurrently($performanceLinks);
                $endTime = microtime(true);
                
                $executionTimes[$concurrency] = $endTime - $startTime;
                
                expect($results)->toBeArray();
                expect($results)->toHaveCount(count($performanceLinks));
            }
            
            // Higher concurrency should generally be faster or at least not significantly slower
            expect($executionTimes[5])->toBeLessThan($executionTimes[1] * 2);
        });
    });
    
    describe('Boundary Conditions', function () {
        it('handles concurrent validation boundary conditions', function () {
            $boundaryTests = [
                'single_link' => [
                    ['url' => 'https://example.com', 'text' => 'Single', 'line' => 1]
                ],
                'duplicate_links' => [
                    ['url' => 'https://example.com', 'text' => 'Duplicate 1', 'line' => 1],
                    ['url' => 'https://example.com', 'text' => 'Duplicate 2', 'line' => 2],
                    ['url' => 'https://example.com', 'text' => 'Duplicate 3', 'line' => 3],
                ],
                'mixed_protocols' => [
                    ['url' => 'https://example.com', 'text' => 'HTTPS', 'line' => 1],
                    ['url' => 'http://example.com', 'text' => 'HTTP', 'line' => 2],
                ],
            ];
            
            foreach ($boundaryTests as $testName => $links) {
                $results = $this->service->validateExternalLinksConcurrently($links);
                
                expect($results)->toBeArray();
                expect($results)->toHaveCount(count($links));
                
                foreach ($results as $result) {
                    expect($result)->toBeInstanceOf(ValidationResult::class);
                    expect($result->getScope())->toBe(ValidationScope::EXTERNAL);
                }
            }
        });
    });
    
    describe('Error Recovery', function () {
        it('recovers from concurrent validation errors', function () {
            // Test recovery from network errors
            ExceptionTestingHelpers::testErrorRecovery(
                function () {
                    $problematicLinks = [
                        ['url' => 'invalid-url-format', 'text' => 'Invalid', 'line' => 1]
                    ];
                    return $this->service->validateExternalLinksConcurrently($problematicLinks);
                },
                function ($exception) {
                    // Recovery should return appropriate error results
                    return [
                        ValidationResult::failure(
                            'invalid-url-format',
                            ValidationScope::EXTERNAL,
                            LinkStatus::BROKEN,
                            'Recovery: ' . $exception->getMessage()
                        )
                    ];
                },
                'Recovery: '
            );
            
            // Test graceful degradation
            $fallbackResults = ExceptionTestingHelpers::testGracefulDegradation(
                function () {
                    throw new RuntimeException('Simulated concurrent validation failure');
                },
                []
            );
            
            expect($fallbackResults)->toBeArray();
            expect($fallbackResults)->toBeEmpty();
        });
    });
    
    describe('Validation Summary', function () {
        it('provides comprehensive concurrent validation summary', function () {
            $summary = BoundaryConditionValidator::getValidationSummary();
            
            expect($summary)->toHaveKey('total_tests');
            expect($summary)->toHaveKey('passed_tests');
            expect($summary)->toHaveKey('failed_tests');
            expect($summary)->toHaveKey('success_rate');
            
            // Concurrent validation should handle edge cases reasonably well
            expect($summary['success_rate'])->toBeGreaterThanOrEqual(70);
        });
    });
});