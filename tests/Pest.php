<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Enhanced Pest Configuration with Intelligent Mock Management
|--------------------------------------------------------------------------
|
| High-performance Pest configuration that addresses the identified issues:
| - 63 complex beforeEach blocks
| - Heavy mock management bottlenecks
| - Inconsistent naming conventions
| - Verbose test structures
| - Mixed PHPUnit-Pest framework usage
|
| Implements intelligent mock lifecycle management, performance-optimized
| beforeEach patterns, memory-efficient test isolation, advanced assertion
| helpers, and consolidated global test utilities.
|
*/

use Tests\TestCase;
use Tests\Support\PestTestCase;
use Tests\Support\Factories\MockFactory;
use Tests\Support\Factories\DataFactory;
use Tests\Support\Helpers\TestHelper;
use Tests\Support\Builders\ValidationConfigBuilder;
use Tests\Support\Doubles\TestDoubleFactory;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationConfig;

// Use optimized TestCase for all test directories
uses(TestCase::class)->in('Unit', 'Feature', 'Integration', 'E2E', 'Architecture');

// Use enhanced PestTestCase for advanced patterns
uses(PestTestCase::class)->in('Performance');

// Organize tests into logical groups with performance considerations
uses()->group('unit', 'fast')->in('Unit');
uses()->group('feature', 'medium')->in('Feature');
uses()->group('integration', 'medium')->in('Integration');
uses()->group('e2e', 'slow')->in('E2E');
uses()->group('architecture', 'fast')->in('Architecture');
uses()->group('performance', 'slow', 'memory-intensive')->in('Performance');

/*
|--------------------------------------------------------------------------
| Intelligent Mock Lifecycle Management
|--------------------------------------------------------------------------
|
| Replaces the complex 63 beforeEach blocks with efficient, lazy-loaded
| mock management that only creates what's needed when it's needed.
|
*/

// Global mock registry for efficient cleanup
$GLOBALS['pest_mock_registry'] = [];
$GLOBALS['pest_performance_metrics'] = [];

/**
 * Intelligent beforeEach that only runs necessary setup
 */
beforeEach(function () {
    // Performance tracking
    $this->testStartTime = microtime(true);
    $this->testStartMemory = memory_get_usage(true);
    
    // Clear previous test state efficiently
    clearTestState();
    
    // Initialize only essential services
    initializeEssentialServices();
})->in('Unit', 'Feature', 'Integration', 'E2E');

/**
 * Efficient afterEach with intelligent cleanup
 */
afterEach(function () {
    // Performance metrics collection
    recordTestMetrics($this);
    
    // Intelligent cleanup - only clean what was used
    cleanupUsedResources();
    
    // Memory management
    if (shouldRunGarbageCollection()) {
        gc_collect_cycles();
    }
})->in('Unit', 'Feature', 'Integration', 'E2E');

/*
|--------------------------------------------------------------------------
| Performance-Optimized Helper Functions
|--------------------------------------------------------------------------
|
| Lazy-loaded, memory-efficient helpers that replace complex mock factories
| and reduce the overhead identified in the analysis.
|
*/

/**
 * Clear test state efficiently
 */
function clearTestState(): void
{
    // Only clear if there's something to clear
    if (!empty($GLOBALS['pest_mock_registry'])) {
        foreach ($GLOBALS['pest_mock_registry'] as $mock) {
            if (method_exists($mock, 'mockery_teardown')) {
                $mock->mockery_teardown();
            }
        }
        $GLOBALS['pest_mock_registry'] = [];
    }
}

/**
 * Initialize only essential services
 */
function initializeEssentialServices(): void
{
    // Minimal service initialization
    app()->forgetInstance('cache');
    app()->forgetInstance('events');
    app()->forgetInstance('queue');
}

/**
 * Lazy-loaded mock creation with automatic registration
 */
function createMock(string $interface, array $config = []): object
{
    $mockId = $interface . '_' . md5(serialize($config));
    
    // Return existing mock if available
    if (isset($GLOBALS['pest_mock_registry'][$mockId])) {
        return $GLOBALS['pest_mock_registry'][$mockId];
    }
    
    // Create new mock with minimal configuration
    $mock = match ($interface) {
        'App\Services\Contracts\LinkValidationInterface' => MockFactory::linkValidation($config),
        'App\Services\Contracts\SecurityValidationInterface' => MockFactory::securityValidation($config),
        'App\Services\Contracts\ReportingInterface' => MockFactory::reporting($config),
        default => mock($interface),
    };
    
    // Register for cleanup
    $GLOBALS['pest_mock_registry'][$mockId] = $mock;
    
    return $mock;
}

/**
 * Efficient test data creation
 */
function createTestData(string $type, array $overrides = []): mixed
{
    return match ($type) {
        'validation_config' => DataFactory::validationConfig($overrides),
        'validation_result' => DataFactory::validationResult($overrides),
        'validation_collection' => DataFactory::validationCollection($overrides),
        'test_urls' => DataFactory::testUrls($overrides),
        default => throw new InvalidArgumentException("Unknown test data type: {$type}"),
    };
}

/**
 * Memory-efficient temporary file creation
 */
function createTestFile(string $content = '', string $extension = '.tmp'): string
{
    static $tempFiles = [];
    
    $tempFile = tempnam(sys_get_temp_dir(), 'pest_') . $extension;
    file_put_contents($tempFile, $content);
    
    $tempFiles[] = $tempFile;
    
    // Register cleanup
    register_shutdown_function(function () use (&$tempFiles) {
        foreach ($tempFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    });
    
    return $tempFile;
}

/**
 * Cleanup used resources intelligently
 */
function cleanupUsedResources(): void
{
    // Only cleanup what was actually used
    $registry = &$GLOBALS['pest_mock_registry'];
    
    if (!empty($registry)) {
        foreach ($registry as $key => $mock) {
            if (method_exists($mock, 'mockery_getExpectationCount') && 
                $mock->mockery_getExpectationCount() > 0) {
                // This mock was used, clean it up
                unset($registry[$key]);
            }
        }
    }
}

/**
 * Intelligent garbage collection decision
 */
function shouldRunGarbageCollection(): bool
{
    static $testCount = 0;
    $testCount++;
    
    // Run GC every 10 tests or if memory usage is high
    return $testCount % 10 === 0 || memory_get_usage(true) > 50 * 1024 * 1024;
}

/**
 * Record test performance metrics
 */
function recordTestMetrics(object $test): void
{
    if (!property_exists($test, 'testStartTime')) {
        return;
    }
    
    $executionTime = microtime(true) - $test->testStartTime;
    $memoryUsage = memory_get_usage(true) - $test->testStartMemory;
    
    $GLOBALS['pest_performance_metrics'][] = [
        'execution_time' => $executionTime,
        'memory_usage' => $memoryUsage,
        'timestamp' => time(),
    ];
    
    // Warn about slow tests
    if ($executionTime > 1.0) {
        echo "\n⚠️  Slow test detected: {$executionTime}s\n";
    }
}

/*
|--------------------------------------------------------------------------
| Advanced Assertion Helpers
|--------------------------------------------------------------------------
|
| Custom expectations that reduce verbose test code and improve clarity.
|
*/

expect()->extend('toBeValidationResult', function (?string $expectedStatus = null) {
    /** @var Pest\Expectation $this */
    $result = $this->value;
    
    expect($result)->toBeInstanceOf(ValidationResult::class);
    
    if ($expectedStatus !== null) {
        expect($result->getStatus()->value)->toBe($expectedStatus);
    }
    
    return $this;
});

expect()->extend('toBeValidationCollection', function (?int $expectedCount = null) {
    /** @var Pest\Expectation $this */
    $collection = $this->value;
    
    expect($collection)->toBeInstanceOf(ValidationResultCollection::class);
    
    if ($expectedCount !== null) {
        expect($collection->count())->toBe($expectedCount);
    }
    
    return $this;
});

expect()->extend('toBeValidUrl', function () {
    /** @var Pest\Expectation $this */
    $url = $this->value;
    
    expect($url)->toBeString()
        ->and(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse();
    
    return $this;
});

expect()->extend('toHaveValidLinkStatus', function () {
    /** @var Pest\Expectation $this */
    $status = $this->value;
    
    $validStatuses = array_column(LinkStatus::cases(), 'value');
    expect($status)->toBeIn($validStatuses);
    
    return $this;
});

expect()->extend('toBeWithinPerformanceThreshold', function (float $maxSeconds = 1.0) {
    /** @var Pest\Expectation $this */
    $executionTime = $this->value;
    
    expect($executionTime)->toBeLessThan($maxSeconds);
    
    return $this;
});

expect()->extend('toUseMemoryEfficiently', function (int $maxBytes = 10 * 1024 * 1024) {
    /** @var Pest\Expectation $this */
    $memoryUsage = $this->value;
    
    expect($memoryUsage)->toBeLessThan($maxBytes);
    
    return $this;
});

/*
|--------------------------------------------------------------------------
| Consolidated Global Test Utilities
|--------------------------------------------------------------------------
|
| Streamlined utilities that replace the complex helper system identified
| in the analysis, focusing on performance and clarity.
|
*/

/**
 * Quick mock creation for common interfaces
 */
function mockLinkValidation(array $config = []): object
{
    return createMock('App\Services\Contracts\LinkValidationInterface', $config);
}

function mockSecurityValidation(array $config = []): object
{
    return createMock('App\Services\Contracts\SecurityValidationInterface', $config);
}

function mockReporting(array $config = []): object
{
    return createMock('App\Services\Contracts\ReportingInterface', $config);
}

/**
 * Quick test data creation
 */
function validationConfig(array $overrides = []): ValidationConfig
{
    return createTestData('validation_config', $overrides);
}

function validationResult(array $overrides = []): ValidationResult
{
    return createTestData('validation_result', $overrides);
}

function validationCollection(array $overrides = []): ValidationResultCollection
{
    return createTestData('validation_collection', $overrides);
}

function testUrls(string $type = 'mixed'): array
{
    return createTestData('test_urls', ['type' => $type]);
}

/**
 * Advanced builder pattern helpers
 */
function buildValidationConfig(): ValidationConfigBuilder
{
    return ValidationConfigBuilder::create();
}

function fastConfig(): ValidationConfig
{
    return ValidationConfigBuilder::create()->forFastTesting()->build();
}

function thoroughConfig(): ValidationConfig
{
    return ValidationConfigBuilder::create()->forThoroughTesting()->build();
}

function ciConfig(): ValidationConfig
{
    return ValidationConfigBuilder::create()->forCI()->build();
}

/**
 * Advanced test double creation
 */
function linkValidationWithState(array $config = []): object
{
    return TestDoubleFactory::linkValidationWithState($config);
}

function securityValidationWithRules(array $rules = []): object
{
    return TestDoubleFactory::securityValidationWithRules($rules);
}

function reportingWithTracking(): object
{
    return TestDoubleFactory::reportingWithTracking();
}

function failingService(string $interface, array $failures = []): object
{
    return TestDoubleFactory::failingService($interface, $failures);
}

function slowService(string $interface, array $delays = []): object
{
    return TestDoubleFactory::slowService($interface, $delays);
}

function recordingSpy(string $interface): object
{
    return TestDoubleFactory::recordingSpy($interface);
}

/**
 * Performance testing utilities
 */
function measurePerformance(callable $callback): array
{
    $startTime = microtime(true);
    $startMemory = memory_get_usage(true);
    
    $result = $callback();
    
    return [
        'result' => $result,
        'execution_time' => microtime(true) - $startTime,
        'memory_usage' => memory_get_usage(true) - $startMemory,
    ];
}

function expectPerformant(callable $callback, float $maxTime = 1.0, int $maxMemory = 10 * 1024 * 1024): void
{
    $metrics = measurePerformance($callback);
    
    expect($metrics['execution_time'])->toBeWithinPerformanceThreshold($maxTime);
    expect($metrics['memory_usage'])->toUseMemoryEfficiently($maxMemory);
}

/**
 * Simplified HTTP client mocking
 */
function mockHttpClient(array $responses = []): object
{
    $mock = mock(\GuzzleHttp\Client::class);
    
    foreach ($responses as $url => $response) {
        $mock->shouldReceive('request')
            ->with('GET', $url)
            ->andReturn($response);
    }
    
    return $mock;
}

/**
 * Database testing helpers
 */
function withFreshDatabase(callable $callback): mixed
{
    return app(TestCase::class)->withConfig([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
    ], $callback);
}

/**
 * Environment testing helpers
 */
function withTestEnvironment(array $env, callable $callback): mixed
{
    return app(TestCase::class)->withEnv($env, $callback);
}

/*
|--------------------------------------------------------------------------
| Performance Monitoring and Reporting
|--------------------------------------------------------------------------
|
| Built-in performance monitoring to track improvements and regressions.
|
*/

/**
 * Get performance metrics for current test session
 */
function getPerformanceMetrics(): array
{
    return $GLOBALS['pest_performance_metrics'] ?? [];
}

/**
 * Generate performance report
 */
function generatePerformanceReport(): string
{
    $metrics = getPerformanceMetrics();
    
    if (empty($metrics)) {
        return "No performance metrics collected.\n";
    }
    
    $totalTests = count($metrics);
    $totalTime = array_sum(array_column($metrics, 'execution_time'));
    $totalMemory = array_sum(array_column($metrics, 'memory_usage'));
    $avgTime = $totalTime / $totalTests;
    $avgMemory = $totalMemory / $totalTests;
    
    $slowTests = array_filter($metrics, fn($m) => $m['execution_time'] > 1.0);
    $memoryHeavyTests = array_filter($metrics, fn($m) => $m['memory_usage'] > 10 * 1024 * 1024);
    
    return sprintf(
        "Performance Report:\n" .
        "- Total Tests: %d\n" .
        "- Total Time: %.2fs\n" .
        "- Average Time: %.3fs\n" .
        "- Total Memory: %.2fMB\n" .
        "- Average Memory: %.2fMB\n" .
        "- Slow Tests (>1s): %d\n" .
        "- Memory Heavy Tests (>10MB): %d\n",
        $totalTests,
        $totalTime,
        $avgTime,
        $totalMemory / 1024 / 1024,
        $avgMemory / 1024 / 1024,
        count($slowTests),
        count($memoryHeavyTests)
    );
}

// Register shutdown function to display performance report
register_shutdown_function(function () {
    if (env('PEST_PERFORMANCE_REPORT', false)) {
        echo "\n" . generatePerformanceReport();
    }
});