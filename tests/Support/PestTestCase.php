<?php

declare(strict_types=1);

namespace Tests\Support;

use Tests\TestCase;
use Tests\Support\Traits\LazyInitialization;
use Tests\Support\Traits\DependencyInjection;
use Tests\Support\Traits\TestLifecycle;

/**
 * Enhanced PEST TestCase with Advanced Patterns
 * 
 * Combines all PEST-specific traits and patterns into a single base class
 * that provides lazy initialization, dependency injection, and lifecycle management.
 */
abstract class PestTestCase extends TestCase
{
    use LazyInitialization;
    use DependencyInjection;
    use TestLifecycle;
    
    /**
     * Setup method called before each test
     * Integrates all trait initialization
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Initialize all trait systems
        $this->initializeTestLifecycle();
        $this->setupLazyInitialization();
        $this->setupDependencyInjection();
    }
    
    /**
     * Teardown method called after each test
     * Integrates all trait cleanup
     */
    protected function tearDown(): void
    {
        // Clean up all trait systems
        $this->cleanupDependencyInjection();
        $this->cleanupLazyInstances();
        $this->finalizeTestLifecycle();
        
        parent::tearDown();
    }
    
    /**
     * Setup lazy initialization with common patterns
     */
    private function setupLazyInitialization(): void
    {
        // Define common lazy properties
        $this->letMany([
            'validationConfig' => fn() => \Tests\Support\Factories\DataFactory::validationConfig(),
            'validationResult' => fn() => \Tests\Support\Factories\DataFactory::validationResult(),
            'validationCollection' => fn() => \Tests\Support\Factories\DataFactory::validationCollection(),
            'testUrls' => fn() => \Tests\Support\Factories\DataFactory::testUrls(),
            'reportSummary' => fn() => \Tests\Support\Factories\DataFactory::reportSummary(),
        ]);
    }
    
    /**
     * Setup dependency injection with common mocks
     */
    private function setupDependencyInjection(): void
    {
        // Pre-configure common service mocks if needed
        // This can be overridden in specific test classes
    }
    
    /**
     * Create a mock for LinkValidationInterface with common setup
     */
    protected function mockLinkValidation(array $config = []): \Mockery\MockInterface
    {
        return $this->bindMock(
            \App\Services\Contracts\LinkValidationInterface::class,
            array_merge([
                'validateLink' => fn($url) => $this->lazy('validationResult'),
                'validateLinks' => $this->lazy('validationCollection'),
                'extractLinks' => [],
                'isValidUrl' => true,
                'getSupportedScopes' => [\App\Enums\ValidationScope::ALL],
            ], $config)
        );
    }
    
    /**
     * Create a mock for SecurityValidationInterface with common setup
     */
    protected function mockSecurityValidation(array $config = []): \Mockery\MockInterface
    {
        return $this->bindMock(
            \App\Services\Contracts\SecurityValidationInterface::class,
            array_merge([
                'validateUrl' => true,
                'validatePath' => true,
                'isDomainBlocked' => false,
                'isProtocolAllowed' => true,
            ], $config)
        );
    }
    
    /**
     * Create a mock for ReportingInterface with common setup
     */
    protected function mockReporting(array $config = []): \Mockery\MockInterface
    {
        return $this->bindMock(
            \App\Services\Contracts\ReportingInterface::class,
            array_merge([
                'generateReport' => 'Mock Report',
                'generateSummary' => fn() => $this->lazy('reportSummary'),
                'exportReport' => true,
            ], $config)
        );
    }
    
    /**
     * Create test file with automatic cleanup
     */
    protected function createTestFile(string $content = '', string $extension = '.tmp'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pest_test_') . $extension;
        file_put_contents($tempFile, $content);
        
        // Register for cleanup
        $this->addCleanup(function () use ($tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        });
        
        return $tempFile;
    }
    
    /**
     * Create test directory with automatic cleanup
     */
    protected function createTestDirectory(string $prefix = 'pest_test_'): string
    {
        $tempDir = sys_get_temp_dir() . '/' . $prefix . uniqid();
        mkdir($tempDir, 0755, true);
        
        // Register for cleanup
        $this->addCleanup(function () use ($tempDir) {
            if (is_dir($tempDir)) {
                $this->removeDirectoryRecursively($tempDir);
            }
        });
        
        return $tempDir;
    }
    
    /**
     * Execute code with performance measurement
     */
    protected function withPerformanceTracking(callable $callback, string $label = 'operation'): mixed
    {
        return $this->measurePerformance($callback, $label);
    }
    
    /**
     * Assert that an operation completes within performance thresholds
     */
    protected function assertPerformant(
        callable $callback,
        float $maxTime = 1.0,
        int $maxMemory = 10 * 1024 * 1024,
        string $label = 'performance_test'
    ): mixed {
        $result = $this->measurePerformance($callback, $label);
        $this->assertPerformance($label, $maxTime, $maxMemory);
        
        return $result;
    }
    
    /**
     * Create a validation result with realistic data
     */
    protected function createValidationResult(array $overrides = []): \App\Services\ValueObjects\ValidationResult
    {
        return \Tests\Support\Factories\DataFactory::validationResult($overrides);
    }
    
    /**
     * Create a validation collection with realistic data
     */
    protected function createValidationCollection(int $count = 3): \App\Services\ValueObjects\ValidationResultCollection
    {
        return \Tests\Support\Factories\DataFactory::validationCollection(['count' => $count]);
    }
    
    /**
     * Create test URLs of specific type
     */
    protected function createTestUrls(string $type = 'mixed'): array
    {
        return \Tests\Support\Factories\DataFactory::testUrls(['type' => $type]);
    }
    
    /**
     * Remove directory recursively
     */
    private function removeDirectoryRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectoryRecursively($path) : unlink($path);
        }
        rmdir($dir);
    }
}