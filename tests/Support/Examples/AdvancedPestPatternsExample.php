<?php

declare(strict_types=1);

namespace Tests\Support\Examples;

use Tests\Support\PestTestCase;
use Tests\Support\Builders\ValidationConfigBuilder;
use Tests\Support\Doubles\TestDoubleFactory;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;

/**
 * Advanced PEST Patterns Example
 * 
 * Demonstrates all the advanced patterns implemented in the test infrastructure:
 * - Lazy initialization with let() pattern
 * - Dependency injection for test doubles
 * - Builder pattern for complex objects
 * - Performance monitoring and lifecycle management
 * - Realistic test doubles with state management
 * 
 * This is an example class to show how to use the infrastructure.
 * It should not be run as an actual test.
 */
class AdvancedPestPatternsExample extends PestTestCase
{
    /**
     * Example: Using lazy initialization with let() pattern
     */
    public function exampleLazyInitialization(): void
    {
        // Define lazy properties that are created only when accessed
        $this->let('complexService', function () {
            // This expensive operation only runs when $this->complexService is accessed
            return $this->createComplexService();
        });
        
        $this->let('testData', function () {
            // This data is only created when needed
            return $this->generateLargeTestDataset();
        });
        
        // Access lazy properties - they're created on first access
        $service = $this->lazy('complexService');
        $data = $this->lazy('testData');
        
        // Or use magic method access
        $service2 = $this->complexService;
        $data2 = $this->testData;
        
        expect($service)->toBe($service2); // Same instance
        expect($data)->toBe($data2); // Same instance
    }
    
    /**
     * Example: Using dependency injection with test doubles
     */
    public function exampleDependencyInjection(): void
    {
        // Create and bind a mock with automatic cleanup
        $linkValidation = $this->bindMock(LinkValidationInterface::class, [
            'validateLink' => ['andReturn' => $this->createValidationResult()],
            'validateLinks' => ['andReturn' => $this->createValidationCollection()],
        ]);
        
        // Create a spy to record interactions
        $securityValidation = $this->spy(SecurityValidationInterface::class);
        $this->bindService(SecurityValidationInterface::class, $securityValidation);
        
        // Create a partial mock
        $reporting = $this->partialMock(ReportingInterface::class, [
            'generateReport' => 'Custom Report',
        ]);
        $this->bindService(ReportingInterface::class, $reporting);
        
        // Use the services - they're automatically injected
        $linkService = app(LinkValidationInterface::class);
        $securityService = app(SecurityValidationInterface::class);
        $reportingService = app(ReportingInterface::class);
        
        // Perform operations
        $result = $linkService->validateLink('https://example.com');
        $isSecure = $securityService->validateUrl('https://example.com');
        $report = $reportingService->generateReport([]);
        
        // Assert interactions
        $this->assertMockCalled($securityValidation, 'validateUrl', ['https://example.com']);
        expect($report)->toBe('Custom Report');
    }
    
    /**
     * Example: Using builder pattern for complex objects
     */
    public function exampleBuilderPattern(): void
    {
        // Create a simple config
        $basicConfig = ValidationConfigBuilder::create()
            ->withTimeout(30)
            ->withMaxRedirects(5)
            ->build();
        
        // Create a complex config with fluent interface
        $advancedConfig = ValidationConfigBuilder::create()
            ->forFastTesting()
            ->withScopes([ValidationScope::EXTERNAL, ValidationScope::INTERNAL])
            ->addHeader('Authorization', 'Bearer token')
            ->withExcludePatterns(['*.pdf', '*.zip'])
            ->build();
        
        // Create config for CI environment
        $ciConfig = ValidationConfigBuilder::create()
            ->forCI()
            ->withConcurrentRequests(20)
            ->build();
        
        // Create multiple configs with variations
        $configs = ValidationConfigBuilder::create()
            ->buildMany(3, function ($builder, $index) {
                return $builder->withTimeout(10 + ($index * 5));
            });
        
        expect($configs)->toHaveCount(3);
        expect($configs[0]->timeout)->toBe(10);
        expect($configs[1]->timeout)->toBe(15);
        expect($configs[2]->timeout)->toBe(20);
    }
    
    /**
     * Example: Using performance monitoring and lifecycle management
     */
    public function examplePerformanceMonitoring(): void
    {
        // Record a checkpoint
        $this->recordCheckpoint('setup_complete');
        
        // Measure performance of a specific operation
        $result = $this->measurePerformance(function () {
            // Simulate some work
            usleep(100000); // 100ms
            return 'operation_result';
        }, 'expensive_operation');
        
        // Assert performance within thresholds
        $this->assertPerformance('expensive_operation', 0.2, 1024 * 1024); // 200ms, 1MB
        
        // Use performance tracking wrapper
        $result2 = $this->withPerformanceTracking(function () {
            return $this->performComplexCalculation();
        }, 'complex_calculation');
        
        // Assert that operation is performant
        $result3 = $this->assertPerformant(function () {
            return $this->quickOperation();
        }, 0.1, 512 * 1024); // 100ms, 512KB
        
        expect($result)->toBe('operation_result');
    }
    
    /**
     * Example: Using realistic test doubles with state management
     */
    public function exampleRealisticTestDoubles(): void
    {
        // Create a link validation service with realistic state
        $linkValidation = TestDoubleFactory::linkValidationWithState([
            'valid_urls' => ['https://example.com', 'https://google.com'],
            'broken_urls' => ['https://broken.example.com', 'https://404.example.com'],
        ]);
        
        // Create security validation with custom rules
        $securityValidation = TestDoubleFactory::securityValidationWithRules([
            'blocked_domains' => ['malicious.com', 'spam.com'],
            'allowed_protocols' => ['https'],
            'blocked_paths' => ['/admin', '/private'],
        ]);
        
        // Create reporting service with interaction tracking
        $reporting = TestDoubleFactory::reportingWithTracking();
        
        // Use the services - they behave realistically
        $validResult = $linkValidation->validateLink('https://example.com');
        $brokenResult = $linkValidation->validateLink('https://broken.example.com');
        
        expect($validResult->getStatus())->toBe(LinkStatus::VALID);
        expect($brokenResult->getStatus())->toBe(LinkStatus::BROKEN);
        
        // Security validation follows the rules
        expect($securityValidation->validateUrl('https://example.com'))->toBeTrue();
        expect($securityValidation->validateUrl('http://example.com'))->toBeFalse(); // HTTP not allowed
        expect($securityValidation->validateUrl('https://malicious.com'))->toBeFalse(); // Blocked domain
        
        // Reporting tracks interactions
        $report = $reporting->generateReport([], 'json');
        $summary = $reporting->generateSummary([]);
        
        // Access the tracker to verify interactions
        $tracker = $reporting->getTracker();
        expect($tracker->getReports())->toHaveCount(1);
        expect($tracker->getSummaries())->toHaveCount(1);
    }
    
    /**
     * Example: Using cleanup and lifecycle management
     */
    public function exampleCleanupManagement(): void
    {
        // Create temporary resources with automatic cleanup
        $tempFile = $this->createTestFile('test content', '.txt');
        $tempDir = $this->createTestDirectory('test_');
        
        // Add custom cleanup callbacks
        $this->addCleanup(function () {
            // Custom cleanup logic
            echo "Cleaning up custom resources\n";
        });
        
        // Resources will be automatically cleaned up after the test
        expect(file_exists($tempFile))->toBeTrue();
        expect(is_dir($tempDir))->toBeTrue();
        
        // Performance metrics are automatically recorded
        $metrics = $this->getPerformanceMetrics();
        expect($metrics)->toBeArray();
    }
    
    /**
     * Example: Combining all patterns in a realistic test scenario
     */
    public function exampleCombinedPatterns(): void
    {
        // Setup lazy initialization
        $this->let('validationConfig', fn() => ValidationConfigBuilder::create()
            ->forFastTesting()
            ->withScopes([ValidationScope::EXTERNAL])
            ->build()
        );
        
        $this->let('testUrls', fn() => [
            'https://example.com',
            'https://broken.example.com',
            'https://malicious.com',
        ]);
        
        // Setup realistic test doubles
        $linkValidation = $this->bindMock(LinkValidationInterface::class);
        $linkValidation->shouldReceive('validateLinks')
            ->with($this->lazy('testUrls'))
            ->andReturnUsing(function ($urls) {
                return $this->createValidationCollection(count($urls));
            });
        
        // Measure performance of the operation
        $results = $this->assertPerformant(function () {
            $service = app(LinkValidationInterface::class);
            return $service->validateLinks($this->testUrls);
        }, 0.5, 2 * 1024 * 1024); // 500ms, 2MB
        
        // Verify results
        expect($results)->toBeValidationCollection(3);
        
        // Check that lazy properties were accessed
        expect($this->hasLazy('validationConfig'))->toBeTrue();
        expect($this->hasLazy('testUrls'))->toBeTrue();
    }
    
    // Helper methods for examples
    
    private function createComplexService(): object
    {
        // Simulate expensive service creation
        usleep(50000); // 50ms
        return new class {
            public function process(): string
            {
                return 'processed';
            }
        };
    }
    
    private function generateLargeTestDataset(): array
    {
        // Simulate expensive data generation
        return array_fill(0, 1000, 'test_data');
    }
    
    private function performComplexCalculation(): int
    {
        // Simulate complex calculation
        $result = 0;
        for ($i = 0; $i < 10000; $i++) {
            $result += $i;
        }
        return $result;
    }
    
    private function quickOperation(): string
    {
        return 'quick_result';
    }
}