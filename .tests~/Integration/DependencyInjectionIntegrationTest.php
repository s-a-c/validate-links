<?php

declare(strict_types=1);

use Tests\Support\TestIsolationManager;
use Tests\Support\ContainerTestHelper;
use Tests\Support\DependencyGraphValidator;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use Illuminate\Contracts\Foundation\Application;

/**
 * Integration tests for dependency injection across the entire application.
 * 
 * Tests end-to-end dependency injection workflows, service integration,
 * real-world usage scenarios, and cross-service communication.
 */

describe('Dependency Injection Integration', function () {
    beforeEach(function () {
        // Initialize test isolation
        TestIsolationManager::initializeIsolation($this);
        
        // Create isolated container for testing
        $this->isolatedContainer = ContainerTestHelper::createIsolatedContainer();
    });

    afterEach(function () {
        // Clear dependency graph cache
        DependencyGraphValidator::clearCache();
        
        // Clean up test resources
        TestIsolationManager::cleanupTestResources();
    });

    describe('Service Resolution', function () {
        it('completes dependency injection workflow from container to service usage', function () {
            // Validate container setup
            $validationResults = ContainerTestHelper::validateContainerBindings($this->isolatedContainer);
            expect($validationResults['invalid_bindings'])->toBeEmpty();
            
            // Resolve LinkValidationService (which depends on SecurityValidationInterface)
            $linkValidationService = $this->isolatedContainer->make(LinkValidationInterface::class);
            expect($linkValidationService)->toBeInstanceOf(LinkValidationService::class);
            
            // Create validation configuration
            $config = ValidationConfig::create([
                'validate_external' => false,
                'validate_internal' => true,
                'validate_anchors' => true,
                'timeout' => 30,
                'max_redirects' => 5,
            ]);
            
            // Test actual service functionality with injected dependencies
            $result = $linkValidationService->validateLink('#test-anchor', $config);
            
            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->status)->toBe(LinkStatus::VALID)
                ->and($result->scope)->toBe(ValidationScope::ANCHOR);
        });

        it('integrates services across multiple services', function () {
            // Resolve all core services
            $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $reportingService = $this->isolatedContainer->make(ReportingInterface::class);
            $statisticsService = $this->isolatedContainer->make(StatisticsInterface::class);
            $githubService = $this->isolatedContainer->make(GitHubAnchorInterface::class);
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // Verify all services are properly instantiated
            expect($securityService)->toBeInstanceOf(SecurityValidationInterface::class)
                ->and($reportingService)->toBeInstanceOf(ReportingInterface::class)
                ->and($statisticsService)->toBeInstanceOf(StatisticsInterface::class)
                ->and($githubService)->toBeInstanceOf(GitHubAnchorInterface::class)
                ->and($linkService)->toBeInstanceOf(LinkValidationInterface::class);
            
            // Test cross-service functionality
            $testUrl = 'https://example.com/test';
            $isUrlSafe = $securityService->validateUrl($testUrl);
            expect($isUrlSafe)->toBeTrue();
            
            // Test that LinkValidationService uses the injected SecurityValidationService
            $config = ValidationConfig::create(['validate_external' => true]);
            $validationResult = $linkService->validateLink($testUrl, $config);
            
            expect($validationResult)->toBeInstanceOf(ValidationResult::class);
        });

        it('handles dependency injection with real file validation scenario', function () {
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // Create a temporary test file
            $testContent = "# Test Document\n\nThis is a [test link](https://example.com) and an [anchor link](#section).\n\n## Section\n\nContent here.";
            $tempFile = createTestFile('test-validation.md', $testContent);
            
            $config = ValidationConfig::create([
                'validate_external' => false, // Skip external to avoid network calls
                'validate_internal' => true,
                'validate_anchors' => true,
            ]);
            
            // Test file validation with dependency injection
            $results = $linkService->validateFile($tempFile, $config);
            
            expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class)
                ->and($results->count())->toBeGreaterThan(0);
            
            // Verify that security validation was used (injected dependency)
            foreach ($results as $result) {
                expect($result)->toBeInstanceOf(ValidationResult::class);
                // The security service should have been called for validation
            }
        });
    });

    describe('Performance Testing', function () {
        it('maintains performance in real-world scenarios', function () {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            // Simulate real-world usage pattern
            for ($i = 0; $i < 50; $i++) {
                // Resolve services multiple times (should use singletons)
                $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
                $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
                
                // Perform actual work
                $config = ValidationConfig::create(['validate_external' => false]);
                $result = $linkService->validateLink('#test', $config);
                
                expect($result)->toBeInstanceOf(ValidationResult::class);
            }
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $totalTime = $endTime - $startTime;
            $memoryUsed = $endMemory - $startMemory;
            
            // Performance should be good due to singleton pattern
            expect($totalTime)->toBeLessThan(1.0) // Less than 1 second for 50 iterations
                ->and($memoryUsed)->toBeLessThan(10 * 1024 * 1024); // Less than 10MB
        });

        it('manages memory in long-running scenarios', function () {
            $initialMemory = memory_get_usage(true);
            
            // Simulate long-running application behavior
            for ($batch = 0; $batch < 5; $batch++) {
                for ($i = 0; $i < 20; $i++) {
                    $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
                    $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
                    
                    // Perform work
                    $config = ValidationConfig::create(['validate_external' => false]);
                    $result = $linkService->validateLink('#test', $config);
                    
                    expect($result)->toBeInstanceOf(ValidationResult::class);
                }
                
                // Force garbage collection between batches
                if (function_exists('gc_collect_cycles')) {
                    gc_collect_cycles();
                }
            }
            
            $finalMemory = memory_get_usage(true);
            $memoryGrowth = $finalMemory - $initialMemory;
            
            // Memory growth should be minimal due to singleton pattern
            expect($memoryGrowth)->toBeLessThan(5 * 1024 * 1024); // Less than 5MB growth
        });
    });

    describe('Error Handling', function () {
        it('handles error scenarios and recovery', function () {
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            $config = ValidationConfig::create(['validate_external' => false]);
            
            // Test with invalid URL format
            $result1 = $linkService->validateLink('invalid url with spaces', $config);
            expect($result1->status)->toBe(LinkStatus::INVALID_FORMAT);
            
            // Test with valid URL after error (service should still work)
            $result2 = $linkService->validateLink('#valid-anchor', $config);
            expect($result2->status)->toBe(LinkStatus::VALID);
            
            // Verify service is still functional and dependencies are intact
            $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
            expect($securityService->validateUrl('https://example.com'))->toBeTrue();
        });
    });

    describe('Concurrent Access', function () {
        it('handles concurrent access patterns', function () {
            $services = [];
            $results = [];
            
            // Simulate concurrent access by rapidly resolving services
            for ($i = 0; $i < 20; $i++) {
                $services[] = $this->isolatedContainer->make(LinkValidationInterface::class);
            }
            
            // All services should be the same instance (singleton)
            $firstService = $services[0];
            foreach ($services as $service) {
                expect($service)->toBe($firstService);
            }
            
            // Test that all services work correctly
            $config = ValidationConfig::create(['validate_external' => false]);
            foreach ($services as $index => $service) {
                $result = $service->validateLink("#test-{$index}", $config);
                $results[] = $result;
                expect($result)->toBeInstanceOf(ValidationResult::class);
            }
            
            expect($results)->toHaveCount(20);
        });
    });

    describe('Complex Validation Scenarios', function () {
        it('handles complex validation scenarios', function () {
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // Create complex test content
            $complexContent = "
# Complex Document

## External Links
- [Valid HTTPS](https://example.com)
- [HTTP Link](http://example.org)

## Internal Links
- [Relative Link](./other-file.md)
- [Absolute Link](/docs/guide.md)

## Anchors
- [Section Link](#section-1)
- [Another Section](#section-2)

## Section 1
Content here.

## Section 2
More content.
";
            
            $tempFile = createTestFile('complex-validation.md', $complexContent);
            
            $config = ValidationConfig::create([
                'validate_external' => false, // Skip to avoid network calls
                'validate_internal' => false, // Skip file system checks
                'validate_anchors' => true,
            ]);
            
            // Test complex validation scenario
            $results = $linkService->validateFile($tempFile, $config);
            
            expect($results)->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class)
                ->and($results->count())->toBeGreaterThan(0);
            
            // Verify anchor validation worked (dependency injection successful)
            $anchorResults = $results->filter(fn($result) => $result->scope === ValidationScope::ANCHOR);
            expect($anchorResults->count())->toBeGreaterThan(0);
            
            foreach ($anchorResults as $result) {
                expect($result->status)->toBeIn([LinkStatus::VALID, LinkStatus::NOT_FOUND]);
            }
        });
    });

    describe('Container State Management', function () {
        it('maintains container state consistency across operations', function () {
            // Perform various container operations
            $service1 = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $service2 = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // Add temporary binding
            $this->isolatedContainer->bind('temp.service', fn() => new \stdClass());
            $tempService = $this->isolatedContainer->make('temp.service');
            
            // Remove temporary binding
            $this->isolatedContainer->forgetInstance('temp.service');
            
            // Resolve original services again
            $service1Again = $this->isolatedContainer->make(SecurityValidationInterface::class);
            $service2Again = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // Original services should still be singletons
            expect($service1)->toBe($service1Again)
                ->and($service2)->toBe($service2Again);
            
            // Validate container state
            $validationResults = ContainerTestHelper::validateContainerBindings($this->isolatedContainer);
            expect($validationResults['invalid_bindings'])->toBeEmpty();
        });
    });

    describe('Service Method Chaining', function () {
        it('supports service method chaining', function () {
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            $securityService = $this->isolatedContainer->make(SecurityValidationInterface::class);
            
            // Test method chaining across services
            $testUrl = 'https://example.com/test';
            
            // First, validate URL security
            $isSecure = $securityService->validateUrl($testUrl);
            expect($isSecure)->toBeTrue();
            
            // Then, validate the link using the link service (which uses the security service internally)
            $config = ValidationConfig::create(['validate_external' => true]);
            $result = $linkService->validateLink($testUrl, $config);
            
            expect($result)->toBeInstanceOf(ValidationResult::class);
            
            // The link service should have used the same security service instance
            $reflection = new \ReflectionClass($linkService);
            $securityProperty = $reflection->getProperty('security');
            $securityProperty->setAccessible(true);
            $injectedSecurity = $securityProperty->getValue($linkService);
            
            expect($injectedSecurity)->toBe($securityService);
        });
    });

    describe('Configuration-Driven Behavior', function () {
        it('handles configuration-driven behavior', function () {
            $linkService = $this->isolatedContainer->make(LinkValidationInterface::class);
            
            // Test different configuration scenarios
            $testUrl = '#test-anchor';
            
            // Configuration 1: Only anchor validation
            $config1 = ValidationConfig::create([
                'validate_external' => false,
                'validate_internal' => false,
                'validate_anchors' => true,
            ]);
            
            $result1 = $linkService->validateLink($testUrl, $config1);
            expect($result1->scope)->toBe(ValidationScope::ANCHOR);
            
            // Configuration 2: All validation disabled
            $config2 = ValidationConfig::create([
                'validate_external' => false,
                'validate_internal' => false,
                'validate_anchors' => false,
            ]);
            
            $result2 = $linkService->validateLink($testUrl, $config2);
            expect($result2)->toBeInstanceOf(ValidationResult::class);
            
            // Both results should come from the same service instance
            expect($linkService)->toBe($this->isolatedContainer->make(LinkValidationInterface::class));
        });
    });

    describe('Full Application Bootstrap', function () {
        it('works with full application bootstrap', function () {
            // Use the main application container (not isolated)
            $mainApp = $this->app;
            
            // Verify all services are available in the main application
            expect($mainApp->bound(SecurityValidationInterface::class))->toBeTrue()
                ->and($mainApp->bound(ReportingInterface::class))->toBeTrue()
                ->and($mainApp->bound(StatisticsInterface::class))->toBeTrue()
                ->and($mainApp->bound(GitHubAnchorInterface::class))->toBeTrue()
                ->and($mainApp->bound(LinkValidationInterface::class))->toBeTrue();
            
            // Resolve services from main application
            $linkService = $mainApp->make(LinkValidationInterface::class);
            $securityService = $mainApp->make(SecurityValidationInterface::class);
            
            expect($linkService)->toBeInstanceOf(LinkValidationService::class)
                ->and($securityService)->toBeInstanceOf(SecurityValidationService::class);
            
            // Test functionality with main application services
            $config = ValidationConfig::create(['validate_external' => false]);
            $result = $linkService->validateLink('#main-app-test', $config);
            
            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->status)->toBe(LinkStatus::VALID);
        });
    });
});