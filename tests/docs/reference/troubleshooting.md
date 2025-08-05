# Troubleshooting Guide

This guide provides solutions to common issues encountered when using the advanced PEST test suite, including performance problems, parallel testing issues, memory leaks, and configuration errors.

## Common Issues

### 1. Memory Issues

#### Symptoms
- Tests fail with "Fatal error: Allowed memory size exhausted"
- Slow test execution
- System becomes unresponsive during test runs

#### Solutions

**Increase Memory Limits**:
```bash
# Temporary increase
php -d memory_limit=2G ./vendor/bin/pest

# Or set in php.ini
memory_limit = 2G
```

**Configure Per-Suite Limits**:
```php
// In pest.config.php
'suites' => [
    'performance' => [
        'memory_limit' => '2G',
    ],
    'unit' => [
        'memory_limit' => '512M',
    ],
],
```

**Enable Aggressive Garbage Collection**:
```php
// In pest.config.php
'memory' => [
    'gc_enabled' => true,
    'gc_frequency' => 1, // GC after every test
    'memory_limit_warning' => '50%',
],
```

**Use Lazy Initialization**:
```php
// Instead of eager initialization
beforeEach(function () {
    $this->expensiveObject = new ExpensiveObject(); // Always created
});

// Use lazy initialization
beforeEach(function () {
    $this->let('expensiveObject', fn() => new ExpensiveObject()); // Created when needed
});
```

---

### 2. Parallel Testing Issues

#### Symptoms
- Tests fail only when run in parallel
- Inconsistent test results
- Database conflicts
- File system conflicts

#### Solutions

**Check Parallel Safety**:
```php
// Ensure tests are tagged correctly
uses()->group('unit', 'parallel')->in('Unit');        // Safe
uses()->group('integration', 'sequential')->in('Integration'); // Unsafe

// Check for unsafe operations
it_should_be_parallel_safe()
    ->group('unit', 'parallel')
    ->expect(function () {
        // No shared state, no file system operations
        return $this->pureFunction($input);
    })->toBeSuccessful();
```

**Fix Database Conflicts**:
```php
// Use transaction isolation
beforeEach(function () {
    ParallelTestManager::beginTestTransaction();
});

afterEach(function () {
    ParallelTestManager::rollbackTestTransaction();
});

// Or use worker-specific databases
'parallel' => [
    'database_strategy' => 'separate_db',
],
```

**Fix File System Conflicts**:
```php
// Use worker-specific paths
it_should_use_worker_specific_files()
    ->group('integration', 'parallel')
    ->expect(function () {
        $workerId = ParallelTestManager::getWorkerIdentifier();
        $tempFile = "/tmp/test_file_{$workerId}.txt";
        
        file_put_contents($tempFile, 'test data');
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    })->toBe('test data');
```

**Use Resource Locking**:
```php
// For shared resources
it_should_use_exclusive_access()
    ->group('integration', 'parallel')
    ->expect(function () {
        return ParallelTestManager::withExclusiveAccess('shared_resource', function () {
            // Critical section
            return $this->sharedResourceOperation();
        });
    })->toBeSuccessful();
```

**Disable Parallel Testing**:
```bash
# Temporary disable
export PEST_PARALLEL=false
./vendor/bin/pest

# Or reduce processes
export PEST_PROCESSES=1
./vendor/bin/pest --parallel
```

---

### 3. Performance Issues

#### Symptoms
- Tests take too long to execute
- High CPU usage
- Slow test discovery
- Memory usage grows over time

#### Solutions

**Optimize Test Discovery**:
```php
// In pest.config.php
'discovery' => [
    'cache_enabled' => true,
    'exclude_patterns' => [
        '*/Support/*',
        '*/Fixtures/*',
        '*/Examples/*',
        '*/Traits/*',
    ],
],
```

**Use Performance Tags**:
```bash
# Run only fast tests
./vendor/bin/pest --group=fast

# Exclude slow tests
./vendor/bin/pest --exclude-group=slow

# Skip performance tests by default
./vendor/bin/pest --exclude-group=performance
```

**Optimize Database Operations**:
```php
// Use in-memory database
'environment' => [
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
],

// Disable foreign key constraints for speed
'database' => [
    'connections' => [
        'sqlite_testing' => [
            'foreign_key_constraints' => false,
        ],
    ],
],
```

**Disable Expensive Features**:
```php
// In pest.config.php
'features' => [
    'performance_monitoring' => false,
    'memory_leak_detection' => false,
    'test_isolation_verification' => false,
],

'coverage' => [
    'report' => [
        'html' => false, // Disable HTML coverage
        'text' => false,
    ],
],
```

**Use Lazy Initialization**:
```php
// Avoid expensive setup in beforeEach
beforeEach(function () {
    $this->let('expensiveService', fn() => new ExpensiveService());
    $this->let('largeDataset', fn() => $this->generateLargeDataset());
});
```

---

### 4. Custom Matcher Issues

#### Symptoms
- "Call to undefined method" errors
- Matchers not working as expected
- Matcher conflicts

#### Solutions

**Verify Matcher Loading**:
```php
// Check if matchers are loaded
use Tests\Support\Matchers\MatcherRegistry;

$matchers = MatcherRegistry::getAllMatchers();
$missing = MatcherRegistry::validateMatchers();

if (!empty($missing)) {
    throw new Exception('Missing matchers: ' . implode(', ', $missing));
}
```

**Enable Auto-Loading**:
```php
// In pest.config.php
'custom_matchers' => [
    'enabled' => true,
    'auto_load' => true,
],
```

**Check Matcher Syntax**:
```php
// Correct usage
expect($result)->toBeValidLink();
expect($operation)->toCompleteWithin(1.0);

// Incorrect usage
expect($result)->toBeValidLink; // Missing parentheses
expect($operation)->toCompleteWithin(); // Missing parameter
```

---

### 5. Tagging Issues

#### Symptoms
- Tests not running with expected tags
- Tag filtering not working
- Unexpected test exclusions

#### Solutions

**Verify Tag Configuration**:
```php
// Check tag setup
use Tests\Support\Tags\TagManager;

$allTags = TagManager::getAllTags();
$invalidTags = TagManager::validateTags(['your', 'tags']);
$excluded = TagManager::isExcludedByDefault('performance');
```

**Check Tag Application**:
```php
// File-level tags
uses()->group('validation', 'feature')->in(__FILE__);

// Test-level tags
it_should_validate_links()
    ->group('unit', 'fast', 'parallel')
    ->expect(fn() => $this->validation())->toBeSuccessful();
```

**Environment Variables**:
```bash
# Include excluded tags
export INCLUDE_PERFORMANCE_TESTS=true
export INCLUDE_SLOW_TESTS=true
export INCLUDE_EXTERNAL_TESTS=true

./vendor/bin/pest --group=performance
```

**Debug Tag Filtering**:
```bash
# List all available groups
./vendor/bin/pest --list-groups

# Run with verbose output
./vendor/bin/pest --group=your-tag -v
```

---

### 6. Configuration Issues

#### Symptoms
- Configuration not being applied
- Environment variables not working
- Feature flags not taking effect

#### Solutions

**Verify Configuration Loading**:
```php
// Check if configuration is loaded
$config = require 'pest.config.php';
var_dump($config['features']);
```

**Check Environment Variables**:
```bash
# Verify environment variables
env | grep PEST
env | grep INCLUDE

# Set variables explicitly
export INCLUDE_PERFORMANCE_TESTS=true
export PEST_PARALLEL=true
```

**Clear Configuration Cache**:
```bash
# Clear PEST cache
rm -f storage/framework/cache/pest-discovery.cache

# Clear PHPUnit cache
rm -rf storage/framework/cache/phpunit
```

**Validate Configuration**:
```php
// Add to pest.config.php for debugging
if (env('PEST_DEBUG')) {
    error_log('PEST Config loaded: ' . json_encode($config));
}
```

---

### 7. Dependency Injection Issues

#### Symptoms
- Mocks not being created
- Service binding failures
- Cleanup not working

#### Solutions

**Check Mock Creation**:
```php
// Verify mock creation
it_should_create_mocks_correctly()
    ->expect(function () {
        $mock = $this->bindMock(ServiceInterface::class, [
            'method' => 'return_value',
        ]);
        
        return $mock instanceof ServiceInterface;
    })->toBeTrue();
```

**Verify Service Binding**:
```php
// Check if service is bound
it_should_bind_services()
    ->expect(function () {
        $this->bindMock(ServiceInterface::class);
        return app()->bound(ServiceInterface::class);
    })->toBeTrue();
```

**Manual Cleanup**:
```php
// Force cleanup if automatic cleanup fails
afterEach(function () {
    $this->cleanupDependencyInjection();
    app()->forgetInstance(ServiceInterface::class);
});
```

---

### 8. Lazy Initialization Issues

#### Symptoms
- Objects created too early
- Memory not being freed
- Circular dependencies

#### Solutions

**Check Lazy Loading**:
```php
// Verify lazy loading works
it_should_load_lazily()
    ->expect(function () {
        $this->let('expensive', fn() => new ExpensiveObject());
        
        // Object should not be created yet
        $memoryBefore = memory_get_usage();
        
        // Access the object
        $object = $this->expensive;
        
        $memoryAfter = memory_get_usage();
        
        return $memoryAfter > $memoryBefore;
    })->toBeTrue();
```

**Fix Circular Dependencies**:
```php
// Avoid circular dependencies
// Bad:
$this->let('serviceA', fn() => new ServiceA($this->lazy('serviceB')));
$this->let('serviceB', fn() => new ServiceB($this->lazy('serviceA')));

// Good:
$this->let('config', fn() => new Config());
$this->let('serviceA', fn() => new ServiceA($this->lazy('config')));
$this->let('serviceB', fn() => new ServiceB($this->lazy('config')));
```

**Manual Cleanup**:
```php
// Force cleanup if needed
afterEach(function () {
    $this->cleanupLazyInstances();
    gc_collect_cycles();
});
```

---

## Debug Techniques

### 1. Enable Debug Mode

```bash
# Enable PEST debug mode
export PEST_DEBUG=true

# Enable verbose output
./vendor/bin/pest -v

# Enable very verbose output
./vendor/bin/pest -vv

# Enable PHPUnit debug
./vendor/bin/pest --debug
```

### 2. Memory Debugging

```php
// Add memory tracking to tests
it_should_track_memory_usage()
    ->expect(function () {
        $startMemory = memory_get_usage(true);
        
        // Your test code here
        $this->operation();
        
        $endMemory = memory_get_usage(true);
        $peakMemory = memory_get_peak_usage(true);
        
        echo "Memory used: " . ($endMemory - $startMemory) . " bytes\n";
        echo "Peak memory: " . $peakMemory . " bytes\n";
        
        return true;
    })->toBeTrue();
```

### 3. Performance Debugging

```php
// Add timing to tests
it_should_track_execution_time()
    ->expect(function () {
        $start = microtime(true);
        
        // Your test code here
        $this->operation();
        
        $end = microtime(true);
        $duration = $end - $start;
        
        echo "Execution time: " . $duration . " seconds\n";
        
        return $duration;
    })->toBeLessThan(1.0);
```

### 4. Parallel Testing Debug

```php
// Debug parallel execution
it_should_debug_parallel_execution()
    ->group('parallel', 'debug')
    ->expect(function () {
        $workerId = $_ENV['PEST_WORKER_ID'] ?? 'main';
        $processId = getmypid();
        
        echo "Worker: {$workerId}, Process: {$processId}\n";
        
        return ['worker' => $workerId, 'process' => $processId];
    })->toHaveKeys(['worker', 'process']);
```

### 5. Configuration Debug

```php
// Debug configuration loading
it_should_debug_configuration()
    ->expect(function () {
        $config = require 'pest.config.php';
        
        echo "Lazy initialization enabled: " . 
             ($config['lazy_initialization']['enabled'] ? 'yes' : 'no') . "\n";
        echo "Parallel processes: " . $config['parallel']['processes'] . "\n";
        echo "Default exclusions: " . 
             implode(', ', $config['tagging']['default_exclusions']) . "\n";
        
        return $config;
    })->toBeArray();
```

## Performance Optimization

### 1. Test Execution Optimization

```bash
# Run only fast tests for quick feedback
./vendor/bin/pest --group=fast

# Use parallel execution
./vendor/bin/pest --parallel --processes=8

# Skip coverage for faster execution
./vendor/bin/pest --no-coverage

# Run specific test suites
./vendor/bin/pest tests/Unit tests/Feature
```

### 2. Memory Optimization

```php
// In pest.config.php
'memory' => [
    'gc_enabled' => true,
    'gc_frequency' => 5, // More frequent GC
    'memory_limit_warning' => '70%',
],

'lazy_initialization' => [
    'memory_threshold' => '100M', // Lower threshold
],
```

### 3. Database Optimization

```php
// Use faster database configuration
'environment' => [
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
],

'database' => [
    'connections' => [
        'sqlite_testing' => [
            'foreign_key_constraints' => false,
            'synchronous' => 'OFF',
            'journal_mode' => 'MEMORY',
        ],
    ],
],
```

## Getting Help

### 1. Check Documentation

- [Main README](../README.md)
- [Feature Guides](../features/)
- [Usage Examples](../examples/)
- [Configuration Reference](configuration.md)

### 2. Enable Logging

```php
// Add logging to debug issues
it_should_log_debug_info()
    ->expect(function () {
        error_log('Debug: Starting test operation');
        
        try {
            $result = $this->operation();
            error_log('Debug: Operation completed successfully');
            return $result;
        } catch (Exception $e) {
            error_log('Debug: Operation failed - ' . $e->getMessage());
            throw $e;
        }
    })->toBeSuccessful();
```

### 3. Use Profiling Tools

```bash
# Profile with Xdebug
php -d xdebug.mode=profile ./vendor/bin/pest

# Profile memory usage
php -d memory_limit=2G ./vendor/bin/pest --group=memory

# Time execution
time ./vendor/bin/pest
```

### 4. Check System Resources

```bash
# Check available memory
free -h

# Check CPU usage
top

# Check disk space
df -h

# Check PHP configuration
php -i | grep memory_limit
php -i | grep max_execution_time
```

## Common Error Messages

### "Call to undefined method"
- **Cause**: Custom matcher not loaded
- **Solution**: Enable auto-loading in configuration

### "Allowed memory size exhausted"
- **Cause**: Memory limit too low
- **Solution**: Increase memory limit or optimize tests

### "Maximum execution time exceeded"
- **Cause**: Test taking too long
- **Solution**: Optimize test or increase timeout

### "Database connection failed"
- **Cause**: Database configuration issue
- **Solution**: Check database settings in configuration

### "Worker process failed"
- **Cause**: Parallel testing issue
- **Solution**: Check parallel safety or disable parallel execution

### "Class not found"
- **Cause**: Autoloading issue
- **Solution**: Run `composer dump-autoload`

### "Permission denied"
- **Cause**: File system permissions
- **Solution**: Check file/directory permissions

## Related Documentation

- [Setup Guide](../guides/setup.md)
- [Configuration Reference](configuration.md)
- [Performance Testing Guide](../features/performance-testing.md)
- [Parallel Testing Guide](../features/parallel-testing.md)