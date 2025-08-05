# PEST Test Infrastructure Documentation

This directory contains the foundational infrastructure for comprehensive PEST testing with advanced patterns.

## Overview

The test infrastructure provides:

- **Lazy Initialization**: Deferred object creation using PEST's `let()` pattern
- **Dependency Injection**: Advanced test double management with automatic cleanup
- **Builder Patterns**: Fluent interfaces for complex object creation
- **Performance Monitoring**: Built-in performance tracking and assertions
- **Realistic Test Doubles**: Stateful mocks with complex behaviors
- **Lifecycle Management**: Automatic resource cleanup and memory management

## Directory Structure

```
tests/Support/
├── Traits/                     # Core traits for PEST patterns
│   ├── LazyInitialization.php  # let() pattern implementation
│   ├── DependencyInjection.php # Test double management
│   └── TestLifecycle.php       # Performance and lifecycle management
├── Builders/                   # Builder pattern implementations
│   └── ValidationConfigBuilder.php
├── Doubles/                    # Advanced test double factories
│   └── TestDoubleFactory.php
├── Factories/                  # Simple data factories
│   ├── DataFactory.php
│   └── MockFactory.php
├── Helpers/                    # Utility helpers
│   └── TestHelper.php
├── Examples/                   # Usage examples
│   └── AdvancedPestPatternsExample.php
├── PestTestCase.php           # Enhanced base test case
└── README.md                  # This file
```

## Core Components

### 1. PestTestCase

Enhanced base test case that combines all traits and provides a unified interface:

```php
use Tests\Support\PestTestCase;

class MyTest extends PestTestCase
{
    public function test_example()
    {
        // All advanced patterns are available
        $this->let('service', fn() => $this->createExpensiveService());
        $mock = $this->bindMock(SomeInterface::class);
        $result = $this->assertPerformant(fn() => $this->service->process());
    }
}
```

### 2. Lazy Initialization Trait

Implements PEST's `let()` pattern for deferred object creation:

```php
// Define lazy properties
$this->let('expensiveService', fn() => new ExpensiveService());
$this->let('testData', fn() => $this->generateLargeDataset());

// Access when needed (created on first access)
$service = $this->lazy('expensiveService');
$data = $this->testData; // Magic method access

// Define multiple at once
$this->letMany([
    'config' => fn() => new Config(),
    'client' => fn() => new HttpClient(),
]);
```

### 3. Dependency Injection Trait

Advanced test double management with automatic cleanup:

```php
// Create and bind mocks
$mock = $this->bindMock(ServiceInterface::class, [
    'method' => 'return_value',
    'complexMethod' => [
        'with' => ['param1', 'param2'],
        'andReturn' => 'result',
        'times' => 2,
    ],
]);

// Create spies
$spy = $this->spy(ServiceInterface::class);

// Create partial mocks
$partial = $this->partialMock(ConcreteClass::class, [
    'methodToMock' => 'mocked_return',
]);

// Replace existing services
$mock = $this->replaceMock(ServiceInterface::class);
```

### 4. Builder Pattern

Fluent interface for complex object creation:

```php
use Tests\Support\Builders\ValidationConfigBuilder;

// Simple usage
$config = ValidationConfigBuilder::create()
    ->withTimeout(30)
    ->withMaxRedirects(5)
    ->build();

// Complex configuration
$config = ValidationConfigBuilder::create()
    ->forFastTesting()
    ->withScopes([ValidationScope::EXTERNAL])
    ->addHeader('Authorization', 'Bearer token')
    ->withExcludePatterns(['*.pdf'])
    ->build();

// Predefined configurations
$fastConfig = ValidationConfigBuilder::create()->forFastTesting()->build();
$ciConfig = ValidationConfigBuilder::create()->forCI()->build();

// Create multiple variations
$configs = ValidationConfigBuilder::create()
    ->buildMany(3, fn($builder, $index) => $builder->withTimeout(10 + $index * 5));
```

### 5. Advanced Test Doubles

Realistic test doubles with state management:

```php
use Tests\Support\Doubles\TestDoubleFactory;

// Link validation with realistic behavior
$linkValidation = TestDoubleFactory::linkValidationWithState([
    'valid_urls' => ['https://example.com'],
    'broken_urls' => ['https://broken.com'],
]);

// Security validation with custom rules
$security = TestDoubleFactory::securityValidationWithRules([
    'blocked_domains' => ['malicious.com'],
    'allowed_protocols' => ['https'],
]);

// Reporting with interaction tracking
$reporting = TestDoubleFactory::reportingWithTracking();

// Failing services for error testing
$failing = TestDoubleFactory::failingService(ServiceInterface::class, [
    'method' => new Exception('Simulated failure'),
]);

// Slow services for performance testing
$slow = TestDoubleFactory::slowService(ServiceInterface::class, [
    'method' => 500, // 500ms delay
]);
```

### 6. Performance Monitoring

Built-in performance tracking and assertions:

```php
// Record checkpoints
$this->recordCheckpoint('setup_complete');

// Measure specific operations
$result = $this->measurePerformance(function () {
    return $this->expensiveOperation();
}, 'expensive_op');

// Assert performance thresholds
$this->assertPerformance('expensive_op', 1.0, 10 * 1024 * 1024); // 1s, 10MB

// Performance wrapper
$result = $this->assertPerformant(function () {
    return $this->quickOperation();
}, 0.1, 1024 * 1024); // 100ms, 1MB

// Get metrics
$metrics = $this->getPerformanceMetrics();
$totalTime = $this->getTotalExecutionTime();
$totalMemory = $this->getTotalMemoryUsage();
```

## Global Helper Functions

Available in all PEST tests via `tests/Pest.php`:

```php
// Builder pattern helpers
$config = buildValidationConfig()->forFastTesting()->build();
$fastConfig = fastConfig();
$ciConfig = ciConfig();

// Advanced test doubles
$linkValidation = linkValidationWithState(['valid_urls' => ['https://example.com']]);
$security = securityValidationWithRules(['blocked_domains' => ['spam.com']]);
$reporting = reportingWithTracking();

// Specialized doubles
$failing = failingService(ServiceInterface::class, ['method' => new Exception()]);
$slow = slowService(ServiceInterface::class, ['method' => 500]);
$spy = recordingSpy(ServiceInterface::class);
```

## Performance Tests

Located in `tests/Performance/`, these tests are excluded by default and must be run explicitly:

```bash
# Run performance tests
pest tests/Performance/

# Run with performance reporting
PEST_PERFORMANCE_REPORT=true pest tests/Performance/

# Include performance tests in regular runs
INCLUDE_PERFORMANCE_TESTS=true pest
```

## Configuration

The infrastructure is configured via `pest.config.php`:

```php
'lazy_initialization' => [
    'enabled' => true,
    'cache_instances' => true,
    'auto_cleanup' => true,
    'memory_threshold' => '50M',
],

'test_doubles' => [
    'auto_cleanup' => true,
    'state_management' => true,
    'interaction_recording' => env('PEST_RECORD_INTERACTIONS', false),
    'realistic_behaviors' => true,
],

'builders' => [
    'fluent_interface' => true,
    'lazy_evaluation' => true,
    'method_chaining' => true,
    'validation' => true,
],
```

## Best Practices

### 1. Use Lazy Initialization for Expensive Operations

```php
// Good: Lazy initialization
$this->let('expensiveService', fn() => new ExpensiveService());

// Bad: Eager initialization in beforeEach
beforeEach(function () {
    $this->expensiveService = new ExpensiveService(); // Always created
});
```

### 2. Leverage Builder Pattern for Complex Objects

```php
// Good: Builder pattern
$config = ValidationConfigBuilder::create()
    ->forFastTesting()
    ->withScopes([ValidationScope::EXTERNAL])
    ->build();

// Bad: Manual construction
$config = new ValidationConfig(
    scopes: [ValidationScope::EXTERNAL],
    timeout: 5,
    maxRedirects: 2,
    concurrentRequests: 20,
    cacheEnabled: false,
    followRedirects: true,
    userAgent: 'validate-links/1.0'
);
```

### 3. Use Realistic Test Doubles

```php
// Good: Realistic behavior
$linkValidation = TestDoubleFactory::linkValidationWithState([
    'valid_urls' => ['https://example.com'],
    'broken_urls' => ['https://broken.com'],
]);

// Bad: Simple mock
$mock = mock(LinkValidationInterface::class);
$mock->shouldReceive('validateLink')->andReturn(true);
```

### 4. Monitor Performance

```php
// Good: Performance monitoring
$result = $this->assertPerformant(function () {
    return $this->service->process($largeDataset);
}, 2.0, 50 * 1024 * 1024);

// Bad: No performance awareness
$result = $this->service->process($largeDataset); // Could be slow
```

### 5. Clean Resource Management

```php
// Good: Automatic cleanup
$tempFile = $this->createTestFile('content');
$this->addCleanup(fn() => $this->customCleanup());

// Bad: Manual cleanup (error-prone)
$tempFile = tempnam(sys_get_temp_dir(), 'test');
// Might forget to clean up
```

## Examples

See `tests/Support/Examples/AdvancedPestPatternsExample.php` for comprehensive usage examples of all patterns.

See `tests/Performance/ExamplePerformanceTest.php` for performance testing examples.

## Migration Guide

To migrate existing tests to use the new infrastructure:

1. Extend `PestTestCase` instead of `TestCase` for advanced features
2. Replace manual mock creation with dependency injection methods
3. Use builder patterns for complex object creation
4. Add performance monitoring to critical tests
5. Leverage lazy initialization for expensive setup

The infrastructure is backward compatible, so migration can be gradual.