# Lazy Initialization Guide

The lazy initialization feature implements PEST's `let()` pattern for memory-efficient test setup with deferred object creation. This pattern is essential for performance optimization and proper resource management in test suites.

## Overview

Lazy initialization allows you to define expensive objects that are only created when first accessed, reducing memory usage and improving test performance. This is particularly useful for:

- Database connections
- Large datasets
- Complex service instances
- External API clients
- Heavy computational objects

## Basic Usage

### Defining Lazy Properties

Use the [`let()`](../../Support/Traits/LazyInitialization.php:32) method to define lazy-loaded properties:

```php
use Tests\Support\PestTestCase;

uses(PestTestCase::class);

beforeEach(function () {
    // Define lazy properties
    $this->let('expensiveService', fn() => new ExpensiveService());
    $this->let('testData', fn() => $this->generateLargeDataset());
    $this->let('databaseConnection', fn() => $this->createDatabaseConnection());
});

it_should_use_lazy_initialization()
    ->expect(function () {
        // Objects are created only when accessed
        $service = $this->lazy('expensiveService'); // Created here
        $data = $this->testData; // Magic method access, created here
        
        return $service->process($data);
    })->not->toBeNull();
```

### Multiple Lazy Definitions

Define multiple lazy properties at once using [`letMany()`](../../Support/Traits/LazyInitialization.php:88):

```php
beforeEach(function () {
    $this->letMany([
        'config' => fn() => new ValidationConfig(),
        'client' => fn() => new HttpClient(),
        'cache' => fn() => new CacheManager(),
        'logger' => fn() => new Logger('test'),
    ]);
});
```

## Advanced Patterns

### Conditional Lazy Loading

Create objects conditionally based on test context:

```php
beforeEach(function () {
    $this->let('apiClient', function () {
        if ($this->needsRealApi()) {
            return new RealApiClient();
        }
        return new MockApiClient();
    });
});
```

### Lazy Dependencies

Create objects that depend on other lazy objects:

```php
beforeEach(function () {
    $this->let('config', fn() => new Config());
    $this->let('database', fn() => new Database($this->lazy('config')));
    $this->let('repository', fn() => new Repository($this->lazy('database')));
});
```

### Parameterized Lazy Objects

Create lazy objects with parameters:

```php
beforeEach(function () {
    $this->let('userFactory', fn() => function ($role = 'user') {
        return new User(['role' => $role]);
    });
});

it_should_create_users_with_different_roles()
    ->expect(function () {
        $factory = $this->lazy('userFactory');
        $admin = $factory('admin');
        $user = $factory('user');
        
        return [$admin->role, $user->role];
    })->toBe(['admin', 'user']);
```

## Memory Management

### Automatic Cleanup

Lazy instances are automatically cleaned up after each test:

```php
afterEach(function () {
    // Automatic cleanup happens here
    $this->cleanupLazyInstances();
});
```

### Manual Cleanup

Clear specific lazy instances when needed:

```php
it_should_handle_memory_cleanup()
    ->expect(function () {
        $service = $this->lazy('expensiveService');
        
        // Clear specific instance
        $this->clearLazy('expensiveService');
        
        // Next access will create a new instance
        $newService = $this->lazy('expensiveService');
        
        return $service !== $newService;
    })->toBeTrue();
```

### Memory Threshold Management

The system automatically clears lazy instances when memory usage exceeds thresholds:

```php
// Configuration in pest.config.php
'lazy_initialization' => [
    'enabled' => true,
    'cache_instances' => true,
    'auto_cleanup' => true,
    'memory_threshold' => '50M', // Clear cache when exceeded
],
```

## Performance Benefits

### Before and After Comparison

**Without Lazy Initialization:**
```php
// ❌ Bad: Always creates expensive objects
beforeEach(function () {
    $this->expensiveService = new ExpensiveService(); // Always created
    $this->largeDataset = $this->generateLargeDataset(); // Always created
    $this->databaseConnection = $this->createConnection(); // Always created
});

// Only some tests use these objects, but all pay the creation cost
```

**With Lazy Initialization:**
```php
// ✅ Good: Creates objects only when needed
beforeEach(function () {
    $this->let('expensiveService', fn() => new ExpensiveService());
    $this->let('largeDataset', fn() => $this->generateLargeDataset());
    $this->let('databaseConnection', fn() => $this->createConnection());
});

// Objects created only in tests that actually use them
```

### Performance Metrics

```php
it_should_demonstrate_performance_benefits()
    ->group('performance')
    ->expect(function () {
        $startMemory = memory_get_usage();
        
        // Define lazy objects (no creation cost)
        $this->let('heavyObject', fn() => new HeavyObject());
        
        $afterDefinition = memory_get_usage();
        
        // Access object (creation happens here)
        $object = $this->lazy('heavyObject');
        
        $afterCreation = memory_get_usage();
        
        return [
            'definition_cost' => $afterDefinition - $startMemory,
            'creation_cost' => $afterCreation - $afterDefinition,
        ];
    })->toMatchArray([
        'definition_cost' => expect()->toBeLessThan(1024), // Minimal cost
        'creation_cost' => expect()->toBeGreaterThan(1024), // Actual cost
    ]);
```

## Integration with Other Features

### With Dependency Injection

Combine lazy initialization with dependency injection:

```php
beforeEach(function () {
    $this->let('mockService', fn() => $this->bindMock(ServiceInterface::class, [
        'process' => 'mocked_result',
    ]));
});

it_should_integrate_with_dependency_injection()
    ->expect(function () {
        $mock = $this->lazy('mockService');
        return $mock->process('test');
    })->toBe('mocked_result');
```

### With Builder Pattern

Use lazy initialization with builders:

```php
beforeEach(function () {
    $this->let('configBuilder', fn() => ValidationConfigBuilder::create());
    $this->let('fastConfig', fn() => $this->lazy('configBuilder')->forFastTesting()->build());
});
```

### With Performance Monitoring

Monitor lazy object creation performance:

```php
it_should_monitor_lazy_object_performance()
    ->group('performance')
    ->expect(function () {
        return $this->measurePerformance(function () {
            return $this->lazy('expensiveService');
        }, 'lazy_creation');
    })->toCompleteWithin(0.1);
```

## Best Practices

### 1. Use for Expensive Operations

```php
// ✅ Good: Expensive operations
$this->let('databaseConnection', fn() => $this->createDatabaseConnection());
$this->let('largeDataset', fn() => $this->generateLargeDataset());
$this->let('externalApiClient', fn() => new ExternalApiClient());

// ❌ Avoid: Simple objects
$this->let('simpleString', fn() => 'hello'); // Unnecessary overhead
$this->let('simpleArray', fn() => []); // Just use regular properties
```

### 2. Leverage Magic Method Access

```php
// ✅ Good: Clean syntax
$service = $this->expensiveService;
$data = $this->testData;

// ✅ Also good: Explicit access
$service = $this->lazy('expensiveService');
$data = $this->lazy('testData');
```

### 3. Group Related Lazy Objects

```php
beforeEach(function () {
    // Group validation-related objects
    $this->letMany([
        'validationConfig' => fn() => ValidationConfigBuilder::create()->build(),
        'validationService' => fn() => new ValidationService($this->lazy('validationConfig')),
        'validationResults' => fn() => new ValidationResultCollection(),
    ]);
    
    // Group reporting-related objects
    $this->letMany([
        'reportConfig' => fn() => new ReportConfig(),
        'reportGenerator' => fn() => new ReportGenerator($this->lazy('reportConfig')),
        'reportFormatter' => fn() => new ReportFormatter(),
    ]);
});
```

### 4. Use Descriptive Names

```php
// ✅ Good: Descriptive names
$this->let('githubApiClient', fn() => new GitHubApiClient());
$this->let('validationConfigForFastTesting', fn() => ValidationConfigBuilder::create()->forFastTesting()->build());

// ❌ Avoid: Generic names
$this->let('client', fn() => new GitHubApiClient());
$this->let('config', fn() => ValidationConfigBuilder::create()->build());
```

## Troubleshooting

### Common Issues

1. **Circular Dependencies**
   ```php
   // ❌ Problem: Circular dependency
   $this->let('serviceA', fn() => new ServiceA($this->lazy('serviceB')));
   $this->let('serviceB', fn() => new ServiceB($this->lazy('serviceA')));
   
   // ✅ Solution: Break the cycle
   $this->let('config', fn() => new Config());
   $this->let('serviceA', fn() => new ServiceA($this->lazy('config')));
   $this->let('serviceB', fn() => new ServiceB($this->lazy('config')));
   ```

2. **Memory Leaks**
   ```php
   // ✅ Ensure proper cleanup
   afterEach(function () {
       $this->cleanupLazyInstances(); // Called automatically
   });
   ```

3. **Undefined Lazy Properties**
   ```php
   // ❌ Problem: Accessing undefined property
   $service = $this->undefinedService; // Throws exception
   
   // ✅ Solution: Check existence
   if ($this->hasLazy('undefinedService')) {
       $service = $this->lazy('undefinedService');
   }
   ```

### Debugging

Enable debug mode to track lazy object creation:

```php
// In test
$this->let('debugService', function () {
    echo "Creating expensive service...\n";
    return new ExpensiveService();
});
```

## Configuration Options

Configure lazy initialization in [`pest.config.php`](../../../pest.config.php:139):

```php
'lazy_initialization' => [
    'enabled' => true,              // Enable/disable lazy initialization
    'cache_instances' => true,      // Cache created instances
    'auto_cleanup' => true,         // Automatic cleanup after tests
    'memory_threshold' => '50M',    // Clear cache when memory exceeds threshold
],
```

## Examples

See [`AdvancedPestPatternsExample.php`](../../Support/Examples/AdvancedPestPatternsExample.php) for comprehensive usage examples.

## Related Documentation

- [Dependency Injection Guide](dependency-injection.md)
- [Performance Testing Guide](performance-testing.md)
- [Test Organization Guide](test-organization.md)
- [Configuration Reference](../reference/configuration.md)