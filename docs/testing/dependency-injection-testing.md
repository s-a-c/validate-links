# Dependency Injection Testing Guide

This guide provides comprehensive documentation for testing dependency injection in the validate-links application, including container management, service resolution validation, circular dependency detection, and test isolation patterns.

## Table of Contents

1. [Overview](#overview)
2. [Test Infrastructure](#test-infrastructure)
3. [Container Testing](#container-testing)
4. [Service Resolution Validation](#service-resolution-validation)
5. [Circular Dependency Detection](#circular-dependency-detection)
6. [Test Isolation Management](#test-isolation-management)
7. [Integration Testing](#integration-testing)
8. [Performance Testing](#performance-testing)
9. [Best Practices](#best-practices)
10. [Troubleshooting](#troubleshooting)

## Overview

The dependency injection testing framework provides bulletproof test isolation, comprehensive container state management, service binding validation, and circular dependency detection for Laravel Zero applications.

### Key Features

- **Complete Test Isolation**: Prevents cross-test contamination through proper container state management
- **Service Container Testing**: Validates all service bindings and their resolution
- **Circular Dependency Detection**: Identifies and prevents circular dependencies in service configuration
- **Performance Monitoring**: Tracks container performance and memory usage
- **Integration Testing**: End-to-end dependency injection workflow validation

### Architecture

```
tests/Support/
├── TestIsolationManager.php      # Complete test isolation and cleanup
├── ContainerTestHelper.php       # Service container testing utilities
├── DependencyGraphValidator.php  # Circular dependency detection
└── MockLifecycleManager.php      # Enhanced mock management

tests/Unit/DependencyInjection/
├── ServiceContainerTest.php      # Container functionality tests
├── ServiceProviderTest.php       # Service provider tests
└── CircularDependencyTest.php    # Circular dependency tests

tests/Integration/
└── DependencyInjectionIntegrationTest.php  # End-to-end tests
```

## Test Infrastructure

### Core Components

#### TestIsolationManager

Provides bulletproof test isolation with complete container state management:

```php
use Tests\Support\TestIsolationManager;

// Initialize test isolation
TestIsolationManager::initializeIsolation($this);

// Create isolated container
$container = TestIsolationManager::createIsolatedContainer();

// Reset container state
TestIsolationManager::resetContainerState($container);

// Validate isolation effectiveness
$results = TestIsolationManager::validateTestIsolation($container);

// Cleanup resources
TestIsolationManager::cleanupTestResources();
```

#### ContainerTestHelper

Service container testing utilities:

```php
use Tests\Support\ContainerTestHelper;

// Create isolated container
$container = ContainerTestHelper::createIsolatedContainer();

// Validate container bindings
$validation = ContainerTestHelper::validateContainerBindings($container);

// Test service resolution
$resolution = ContainerTestHelper::testServiceResolution($container);

// Test service lifecycle
$lifecycle = ContainerTestHelper::testServiceLifecycle($container);
```

#### DependencyGraphValidator

Circular dependency detection and analysis:

```php
use Tests\Support\DependencyGraphValidator;

// Build dependency graph
$graph = DependencyGraphValidator::buildDependencyGraph($container);

// Detect circular dependencies
$circular = DependencyGraphValidator::detectCircularDependencies($container);

// Validate dependency graph
$validation = DependencyGraphValidator::validateDependencyGraph($container);

// Analyze complexity
$complexity = DependencyGraphValidator::analyzeDependencyComplexity();
```

## Container Testing

### Basic Container Validation

```php
public function test_container_bindings_are_valid(): void
{
    $container = createIsolatedContainer();
    $validation = validateContainerBindings($container);
    
    expect($validation['invalid_bindings'])->toBeEmpty()
        ->and($validation['missing_dependencies'])->toBeEmpty()
        ->and($validation['binding_conflicts'])->toBeEmpty();
}
```

### Service Resolution Testing

```php
public function test_all_services_can_be_resolved(): void
{
    $container = createIsolatedContainer();
    $resolution = testServiceResolution($container);
    
    expect($resolution['failed_resolutions'])->toBeEmpty()
        ->and($resolution['successful_resolutions'])->toHaveCount(5);
    
    // Verify resolution times are reasonable
    foreach ($resolution['resolution_times'] as $interface => $time) {
        expect($time)->toBeLessThan(0.1);
    }
}
```

### Singleton Behavior Testing

```php
public function test_services_are_singletons(): void
{
    $container = createIsolatedContainer();
    $lifecycle = testServiceLifecycle($container);
    
    expect($lifecycle['singleton_services'])->toHaveCount(5)
        ->and($lifecycle['transient_services'])->toBeEmpty();
    
    // Verify singleton behavior
    $service1 = $container->make(SecurityValidationInterface::class);
    $service2 = $container->make(SecurityValidationInterface::class);
    
    expect($service1)->toBe($service2);
}
```

### Dependency Injection Validation

```php
public function test_dependency_injection_works_correctly(): void
{
    $container = createIsolatedContainer();
    $linkService = $container->make(LinkValidationInterface::class);
    
    // Verify dependency was injected
    $reflection = new ReflectionClass($linkService);
    $securityProperty = $reflection->getProperty('security');
    $securityProperty->setAccessible(true);
    $injectedSecurity = $securityProperty->getValue($linkService);
    
    expect($injectedSecurity)->toBeInstanceOf(SecurityValidationInterface::class);
}
```

## Service Resolution Validation

### Interface Binding Validation

```php
public function test_interface_bindings_are_correct(): void
{
    $container = createIsolatedContainer();
    $validation = validateInterfaceBindings($container);
    
    expect($validation['missing_bindings'])->toBeEmpty()
        ->and($validation['incorrect_bindings'])->toBeEmpty()
        ->and($validation['correct_bindings'])->toHaveCount(5);
}
```

### Mock Service Integration

```php
public function test_mock_service_integration(): void
{
    $container = createIsolatedContainer();
    
    $mocks = createMockServiceBindings($container, [
        SecurityValidationInterface::class => [
            'name' => 'security_mock',
            'setup' => function ($mock) {
                $mock->shouldReceive('validateUrl')->andReturn(true);
                $mock->shouldReceive('validatePath')->andReturn(true);
            },
        ],
    ]);
    
    $linkService = $container->make(LinkValidationInterface::class);
    
    // Verify mock is used
    $reflection = new ReflectionClass($linkService);
    $securityProperty = $reflection->getProperty('security');
    $securityProperty->setAccessible(true);
    $injectedSecurity = $securityProperty->getValue($linkService);
    
    expect($injectedSecurity)->toBe($mocks[SecurityValidationInterface::class]);
}
```

## Circular Dependency Detection

### Basic Circular Dependency Detection

```php
public function test_no_circular_dependencies(): void
{
    $container = createIsolatedContainer();
    $circular = detectCircularDependencies($container);
    
    expect($circular['has_circular_dependencies'])->toBeFalse()
        ->and($circular['circular_dependencies'])->toBeEmpty();
}
```

### Artificial Circular Dependency Testing

```php
public function test_circular_dependency_detection(): void
{
    $container = createIsolatedContainer();
    
    // Create artificial circular dependency
    $container->bind('ServiceA', function ($app) {
        return new class($app->make('ServiceB')) {};
    });
    
    $container->bind('ServiceB', function ($app) {
        return new class($app->make('ServiceA')) {};
    });
    
    $circular = detectCircularDependencies($container);
    
    expect($circular['has_circular_dependencies'])->toBeTrue()
        ->and($circular['circular_dependencies'])->not->toBeEmpty();
}
```

### Dependency Graph Analysis

```php
public function test_dependency_graph_analysis(): void
{
    $container = createIsolatedContainer();
    
    // Build and analyze dependency graph
    $graph = buildDependencyGraph($container);
    $complexity = analyzeDependencyComplexity();
    $bottlenecks = findDependencyBottlenecks();
    
    expect($graph['total_services'])->toBeGreaterThan(0)
        ->and($complexity['max_dependencies'])->toBeLessThanOrEqual(2)
        ->and($bottlenecks['total_bottlenecks'])->toBeGreaterThanOrEqual(0);
}
```

## Test Isolation Management

### Complete Test Isolation

```php
protected function setUp(): void
{
    parent::setUp();
    
    // Initialize complete test isolation
    initializeTestIsolation($this);
    isolateTestEnvironment($this);
}

protected function tearDown(): void
{
    // Comprehensive cleanup
    cleanupTestResources();
    preventCrossTestContamination();
    
    parent::tearDown();
}
```

### Isolation Validation

```php
public function test_isolation_effectiveness(): void
{
    $container = createIsolatedContainer();
    
    // Modify container state
    $container->bind('test.service', fn() => new stdClass());
    
    // Reset and validate
    resetContainerState($container);
    $isolation = validateTestIsolation($container);
    
    expect($isolation['container_state_clean'])->toBeTrue()
        ->and($isolation['no_singleton_leakage'])->toBeTrue()
        ->and($isolation['no_binding_conflicts'])->toBeTrue();
}
```

### Custom Isolation Callbacks

```php
public function test_custom_isolation_callbacks(): void
{
    // Register custom cleanup
    registerIsolationCallback('custom_cleanup', function () {
        // Custom cleanup logic
        clearCustomCaches();
        resetGlobalState();
    });
    
    // Isolation will automatically run custom callbacks
    cleanupTestResources();
}
```

## Integration Testing

### End-to-End Workflow Testing

```php
public function test_complete_dependency_injection_workflow(): void
{
    $container = createIsolatedContainer();
    
    // Validate container setup
    $validation = validateContainerBindings($container);
    expect($validation['invalid_bindings'])->toBeEmpty();
    
    // Resolve service with dependencies
    $linkService = $container->make(LinkValidationInterface::class);
    
    // Test actual functionality
    $config = ValidationConfig::create(['validate_anchors' => true]);
    $result = $linkService->validateLink('#test', $config);
    
    expect($result)->toBeInstanceOf(ValidationResult::class)
        ->and($result->status)->toBe(LinkStatus::VALID);
}
```

### Cross-Service Integration

```php
public function test_cross_service_integration(): void
{
    $container = createIsolatedContainer();
    
    // Resolve all services
    $securityService = $container->make(SecurityValidationInterface::class);
    $linkService = $container->make(LinkValidationInterface::class);
    
    // Test cross-service functionality
    $testUrl = 'https://example.com/test';
    $isUrlSafe = $securityService->validateUrl($testUrl);
    
    expect($isUrlSafe)->toBeTrue();
    
    // Verify LinkValidationService uses SecurityValidationService
    $config = ValidationConfig::create(['validate_external' => true]);
    $result = $linkService->validateLink($testUrl, $config);
    
    expect($result)->toBeInstanceOf(ValidationResult::class);
}
```

## Performance Testing

### Container Performance Testing

```php
public function test_container_performance(): void
{
    $container = createIsolatedContainer();
    $performance = testContainerPerformance($container, 100);
    
    expect($performance['average_resolution_time'])->toBeLessThan(0.01)
        ->and($performance['peak_memory'])->toBeLessThan(10 * 1024 * 1024)
        ->and($performance['performance_issues'])->toBeEmpty();
}
```

### Memory Management Testing

```php
public function test_memory_management(): void
{
    $initialMemory = memory_get_usage(true);
    
    // Perform multiple operations
    for ($i = 0; $i < 100; $i++) {
        $container = createIsolatedContainer();
        $service = $container->make(LinkValidationInterface::class);
        resetContainerState($container);
    }
    
    $finalMemory = memory_get_usage(true);
    $memoryGrowth = $finalMemory - $initialMemory;
    
    expect($memoryGrowth)->toBeLessThan(5 * 1024 * 1024);
}
```

## Best Practices

### 1. Always Use Test Isolation

```php
// ✅ Good: Use test isolation
protected function setUp(): void
{
    parent::setUp();
    initializeTestIsolation($this);
}

protected function tearDown(): void
{
    cleanupTestResources();
    parent::tearDown();
}

// ❌ Bad: No test isolation
protected function setUp(): void
{
    parent::setUp();
    // No isolation - tests may interfere with each other
}
```

### 2. Validate Container State

```php
// ✅ Good: Validate container bindings
public function test_service_functionality(): void
{
    $container = createIsolatedContainer();
    $validation = validateContainerBindings($container);
    expect($validation['invalid_bindings'])->toBeEmpty();
    
    // Now test functionality
    $service = $container->make(ServiceInterface::class);
    // ... test logic
}

// ❌ Bad: Assume container is valid
public function test_service_functionality(): void
{
    $service = $this->app->make(ServiceInterface::class);
    // No validation - may fail unexpectedly
}
```

### 3. Test Dependency Injection Explicitly

```php
// ✅ Good: Verify dependency injection
public function test_dependency_injection(): void
{
    $container = createIsolatedContainer();
    $service = $container->make(ServiceWithDependencies::class);
    
    // Verify dependencies were injected
    $reflection = new ReflectionClass($service);
    $dependencyProperty = $reflection->getProperty('dependency');
    $dependencyProperty->setAccessible(true);
    $injectedDependency = $dependencyProperty->getValue($service);
    
    expect($injectedDependency)->toBeInstanceOf(DependencyInterface::class);
}

// ❌ Bad: Assume dependency injection works
public function test_service_functionality(): void
{
    $service = $this->app->make(ServiceWithDependencies::class);
    // Test functionality without verifying injection
}
```

### 4. Use Helper Functions

```php
// ✅ Good: Use helper functions
public function test_container_validation(): void
{
    $container = createIsolatedContainer();
    $validation = validateContainerBindings($container);
    $circular = detectCircularDependencies($container);
    
    expect($validation['invalid_bindings'])->toBeEmpty()
        ->and($circular['has_circular_dependencies'])->toBeFalse();
}

// ❌ Bad: Manual container testing
public function test_container_validation(): void
{
    // Manual, error-prone container testing
    $container = new Container();
    // ... lots of manual validation code
}
```

### 5. Test Performance Impact

```php
// ✅ Good: Monitor performance
public function test_service_performance(): void
{
    $container = createIsolatedContainer();
    $performance = testContainerPerformance($container, 1000);
    
    expect($performance['average_resolution_time'])->toBeLessThan(0.01)
        ->and($performance['performance_issues'])->toBeEmpty();
}
```

## Troubleshooting

### Common Issues

#### 1. Container State Leakage

**Problem**: Tests interfere with each other due to shared container state.

**Solution**: Use proper test isolation:

```php
protected function setUp(): void
{
    parent::setUp();
    initializeTestIsolation($this);
}

protected function tearDown(): void
{
    cleanupTestResources();
    parent::tearDown();
}
```

#### 2. Circular Dependencies

**Problem**: Services have circular dependencies causing resolution failures.

**Solution**: Use dependency graph validation:

```php
public function test_no_circular_dependencies(): void
{
    $container = createIsolatedContainer();
    $circular = detectCircularDependencies($container);
    
    if ($circular['has_circular_dependencies']) {
        $this->fail('Circular dependencies detected: ' . json_encode($circular['circular_dependencies']));
    }
}
```

#### 3. Memory Leaks

**Problem**: Container instances accumulate causing memory issues.

**Solution**: Use proper cleanup and monitoring:

```php
public function test_memory_management(): void
{
    $initialMemory = memory_get_usage(true);
    
    // Perform operations
    for ($i = 0; $i < 100; $i++) {
        $container = createIsolatedContainer();
        // ... operations
        resetContainerState($container);
    }
    
    $finalMemory = memory_get_usage(true);
    expect($finalMemory - $initialMemory)->toBeLessThan(5 * 1024 * 1024);
}
```

#### 4. Service Resolution Failures

**Problem**: Services fail to resolve due to missing dependencies.

**Solution**: Validate bindings before testing:

```php
public function test_service_resolution(): void
{
    $container = createIsolatedContainer();
    $validation = validateContainerBindings($container);
    
    expect($validation['missing_dependencies'])->toBeEmpty()
        ->and($validation['unresolvable_dependencies'])->toBeEmpty();
    
    $resolution = testServiceResolution($container);
    expect($resolution['failed_resolutions'])->toBeEmpty();
}
```

### Debugging Tools

#### Container Statistics

```php
$stats = getContainerStatistics($container);
echo "Total bindings: " . $stats['total_bindings'] . "\n";
echo "Singleton bindings: " . $stats['singleton_bindings'] . "\n";
echo "Memory usage: " . $stats['memory_usage'] . "\n";
```

#### Dependency Graph Analysis

```php
$graph = buildDependencyGraph($container);
$complexity = analyzeDependencyComplexity();
$bottlenecks = findDependencyBottlenecks();

echo "Total services: " . $graph['total_services'] . "\n";
echo "Max dependencies: " . $complexity['max_dependencies'] . "\n";
echo "Bottlenecks: " . $bottlenecks['total_bottlenecks'] . "\n";
```

#### Isolation Validation

```php
$isolation = validateTestIsolation($container);
if (!$isolation['container_state_clean']) {
    echo "Container state is not clean!\n";
}
if (!$isolation['no_singleton_leakage']) {
    echo "Singleton leakage detected!\n";
}
```

## Conclusion

This dependency injection testing framework provides comprehensive tools for ensuring reliable, isolated, and performant dependency injection in Laravel Zero applications. By following the patterns and best practices outlined in this guide, you can create robust tests that validate container behavior, detect circular dependencies, and maintain proper test isolation.

The framework includes:

- **Complete test isolation** preventing cross-test contamination
- **Comprehensive container validation** ensuring proper service binding
- **Circular dependency detection** preventing configuration issues
- **Performance monitoring** tracking container efficiency
- **Integration testing** validating end-to-end workflows

Use these tools consistently to maintain high-quality dependency injection testing throughout your application development lifecycle.