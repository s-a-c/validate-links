# Parallel Testing Guide

The PEST test suite includes comprehensive parallel testing support with proper test isolation, database transaction handling, and parallel-safe test data management. This enables faster test execution while maintaining test reliability and consistency.

## Overview

Parallel testing features include:

- **Process isolation** with unique worker environments
- **Database transaction handling** for data consistency
- **Shared resource locking** for exclusive access
- **Memory-efficient worker management**
- **Automatic cleanup** and resource management
- **Parallel safety validation** for test categorization

## Basic Usage

### Enabling Parallel Execution

```bash
# Run tests in parallel
pest --parallel

# Specify number of processes
pest --parallel --processes=8

# Run specific test suites in parallel
pest tests/Unit --parallel
pest tests/Feature --parallel
```

### Configuration

Parallel testing is configured in [`pest.config.php`](../../../pest.config.php:95):

```php
'parallel' => [
    'processes' => min(8, (int) shell_exec('nproc') ?: 4),
    'timeout' => 60,
    'memory_limit' => '512M',
    'isolation_mode' => 'process',
    'database_strategy' => 'transaction',
    'worker_configuration' => [
        'separate_temp_dirs' => true,
        'isolated_cache' => true,
        'unique_session_storage' => true,
        'process_specific_seeds' => true,
    ],
],
```

## Test Safety Classification

### Parallel-Safe Tests

Tests that can run concurrently without interference:

```php
// Unit tests - typically parallel-safe
uses()->group('unit', 'fast', 'parallel')->in('Unit');

it_should_validate_url_format()
    ->group('unit', 'parallel', 'validation')
    ->expect(function () {
        return $this->validator->isValidUrl('https://example.com');
    })->toBeTrue();

it_should_calculate_statistics()
    ->group('unit', 'parallel', 'fast')
    ->expect(function () {
        $stats = new ValidationStatistics([
            'total' => 100,
            'valid' => 95,
            'broken' => 5,
        ]);
        return $stats->getSuccessRate();
    })->toBe(0.95);
```

### Sequential Tests

Tests that must run sequentially due to shared resources:

```php
// Integration tests - often sequential
uses()->group('integration', 'sequential')->in('Integration');

it_should_migrate_database_schema()
    ->group('integration', 'sequential', 'database')
    ->expect(function () {
        return $this->migrationService->migrate();
    })->toBeSuccessful();

it_should_write_to_shared_file()
    ->group('integration', 'sequential', 'file_system')
    ->expect(function () {
        return $this->fileService->writeToSharedFile('test data');
    })->toBeSuccessful();
```

### Unsafe Tags

Tests with these tags are automatically excluded from parallel execution:

```php
// Tags that prevent parallel execution
'sequential',           // Must run sequentially
'external',            // External dependencies
'database_migration',  // Database schema changes
'file_system',        // File system operations
'global_state',       // Global state modifications
```

## Worker Management

### Process Isolation

Each worker process has isolated resources:

```php
use Tests\Support\Parallel\ParallelTestManager;

// Get unique worker identifier
$workerId = ParallelTestManager::getWorkerIdentifier();
// Returns: "worker_1_12345" (worker_id + process_id)

// Create worker-specific test data
$testData = ParallelTestManager::createTestData('validation', [
    'urls' => ['https://example.com'],
    'config' => ['timeout' => 30],
]);
// Includes worker_id, process_id, and unique_suffix
```

### Database Isolation

#### Transaction-Based Isolation (Default)

```php
// Automatic transaction management
beforeEach(function () {
    ParallelTestManager::beginTestTransaction();
});

afterEach(function () {
    ParallelTestManager::rollbackTestTransaction();
});

// Manual transaction control
it_should_handle_database_operations()
    ->group('integration', 'database')
    ->expect(function () {
        ParallelTestManager::beginTestTransaction();
        
        try {
            // Database operations
            $result = $this->databaseService->createRecord(['name' => 'test']);
            return $result;
        } finally {
            ParallelTestManager::rollbackTestTransaction();
        }
    })->toBeSuccessful();
```

#### Separate Database Strategy

```php
// Configuration for separate databases per worker
'parallel' => [
    'database_strategy' => 'separate_db',
    // Each worker gets its own database: test_worker_1, test_worker_2, etc.
],
```

### Memory Management

```php
// Worker-specific memory limits
'parallel' => [
    'memory_limit' => '512M',  // Per worker process
],

// Monitor worker memory usage
$stats = ParallelTestManager::getStatistics();
/*
Returns:
[
    'worker_id' => 1,
    'process_id' => 12345,
    'uptime' => 45.2,
    'memory_usage' => 67108864,    // 64MB
    'peak_memory' => 83886080,     // 80MB
    'active_locks' => 0,
    'shared_resources' => 2,
]
*/
```

## Shared Resource Management

### Resource Locking

For operations requiring exclusive access:

```php
use Tests\Support\Parallel\ParallelTestManager;

// Acquire exclusive lock
it_should_access_shared_resource_exclusively()
    ->group('integration', 'parallel')
    ->expect(function () {
        return ParallelTestManager::withExclusiveAccess('shared_file', function () {
            // Critical section - only one worker can execute this
            $content = file_get_contents('/tmp/shared_file.txt');
            $content .= "Worker: " . ParallelTestManager::getWorkerIdentifier() . "\n";
            file_put_contents('/tmp/shared_file.txt', $content);
            return true;
        });
    })->toBeTrue();

// Manual lock management
it_should_manage_locks_manually()
    ->expect(function () {
        $acquired = ParallelTestManager::acquireLock('resource_name', 30); // 30s timeout
        
        if ($acquired) {
            try {
                // Critical section
                $result = $this->performExclusiveOperation();
                return $result;
            } finally {
                ParallelTestManager::releaseLock('resource_name');
            }
        }
        
        return false;
    })->toBeTrue();
```

### Shared Resource Registry

```php
// Register shared resources
ParallelTestManager::registerSharedResource('api_client', $apiClient);
ParallelTestManager::registerSharedResource('cache_manager', $cacheManager);

// Access shared resources
it_should_use_shared_resources()
    ->expect(function () {
        $apiClient = ParallelTestManager::getSharedResource('api_client');
        return $apiClient->makeRequest('/test');
    })->toBeSuccessful();
```

## Parallel-Safe Test Patterns

### 1. Immutable Test Data

```php
// ✅ Good: Immutable test data
it_should_process_immutable_data()
    ->group('unit', 'parallel')
    ->expect(function () {
        $data = [
            'url' => 'https://example.com',
            'timeout' => 30,
            'retries' => 3,
        ];
        return $this->processor->process($data);
    })->toBeSuccessful();

// ❌ Avoid: Shared mutable state
static $sharedCounter = 0; // Dangerous in parallel execution

it_should_avoid_shared_state()
    ->group('unit', 'sequential') // Must be sequential
    ->expect(function () {
        static $sharedCounter;
        $sharedCounter++;
        return $sharedCounter;
    })->toBe(1); // Will fail in parallel
```

### 2. Worker-Specific Resources

```php
// ✅ Good: Worker-specific resources
it_should_use_worker_specific_resources()
    ->group('integration', 'parallel')
    ->expect(function () {
        $workerId = ParallelTestManager::getWorkerIdentifier();
        $tempFile = "/tmp/test_file_{$workerId}.txt";
        
        file_put_contents($tempFile, 'test data');
        $content = file_get_contents($tempFile);
        unlink($tempFile);
        
        return $content;
    })->toBe('test data');

// ❌ Avoid: Shared file paths
it_should_avoid_shared_files()
    ->group('integration', 'sequential') // Must be sequential
    ->expect(function () {
        $sharedFile = '/tmp/shared_test_file.txt'; // Dangerous
        file_put_contents($sharedFile, 'test data');
        return file_get_contents($sharedFile);
    })->toBe('test data');
```

### 3. Database Transactions

```php
// ✅ Good: Transaction isolation
it_should_use_database_transactions()
    ->group('integration', 'parallel', 'database')
    ->expect(function () {
        // Each worker has isolated transactions
        $user = User::create(['name' => 'Test User', 'email' => 'test@example.com']);
        return $user->exists;
    })->toBeTrue();
    // Transaction automatically rolled back after test

// ❌ Avoid: Persistent database changes
it_should_avoid_persistent_changes()
    ->group('integration', 'sequential') // Must be sequential
    ->expect(function () {
        // This would persist across tests
        DB::statement('CREATE TABLE persistent_table (id INT)');
        return true;
    })->toBeTrue();
```

### 4. Time-Based Testing

```php
// ✅ Good: Relative time testing
it_should_handle_time_correctly()
    ->group('unit', 'parallel')
    ->expect(function () {
        $start = microtime(true);
        usleep(100000); // 100ms
        $duration = microtime(true) - $start;
        return $duration;
    })->toBeGreaterThan(0.1);

// ❌ Avoid: Absolute time dependencies
it_should_avoid_absolute_time()
    ->group('unit', 'sequential') // Must be sequential
    ->expect(function () {
        // This assumes specific execution time
        return date('H:i:s');
    })->toBe('14:30:00'); // Will fail at different times
```

## Performance Optimization

### Optimal Process Count

```php
// Get optimal number of processes
$optimalCount = ParallelTestManager::getOptimalProcessCount();

// Factors considered:
// - CPU core count
// - Available memory
// - Process memory limit
// - Maximum configured processes
```

### Memory Efficiency

```php
// Monitor memory usage per worker
it_should_monitor_memory_usage()
    ->group('performance', 'parallel', 'memory')
    ->expect(function () {
        $stats = ParallelTestManager::getStatistics();
        return $stats['memory_usage'];
    })->toUseMemoryLessThan(100 * 1024 * 1024); // 100MB

// Cleanup worker resources
afterEach(function () {
    // Automatic cleanup happens here
    ParallelTestManager::cleanup();
});
```

### Load Balancing

```php
// Tests are automatically distributed across workers
// based on execution time estimates and worker availability

// Tag tests appropriately for better distribution
uses()->group('fast', 'parallel')->in('Unit');      // Quick tests
uses()->group('medium', 'parallel')->in('Feature');  // Medium tests
uses()->group('slow', 'sequential')->in('E2E');      // Slow tests
```

## Debugging Parallel Tests

### Worker Identification

```php
it_should_identify_worker()
    ->expect(function () {
        $workerId = $_ENV['PEST_WORKER_ID'] ?? 0;
        $processId = getmypid();
        
        echo "Running on worker {$workerId}, process {$processId}\n";
        
        return ['worker_id' => $workerId, 'process_id' => $processId];
    })->toHaveKeys(['worker_id', 'process_id']);
```

### Isolation Verification

```php
it_should_verify_isolation()
    ->group('parallel', 'debug')
    ->expect(function () {
        $workerId = ParallelTestManager::getWorkerIdentifier();
        $tempDir = sys_get_temp_dir() . '/pest_worker_' . $_ENV['PEST_WORKER_ID'];
        
        return [
            'worker_id' => $workerId,
            'temp_dir' => $tempDir,
            'temp_dir_exists' => is_dir($tempDir),
            'process_id' => getmypid(),
        ];
    })->toMatchArray([
        'worker_id' => expect()->toBeString(),
        'temp_dir' => expect()->toBeString(),
        'temp_dir_exists' => true,
        'process_id' => expect()->toBeInt(),
    ]);
```

### Race Condition Detection

```php
it_should_detect_race_conditions()
    ->group('parallel', 'debug')
    ->expect(function () {
        $lockFile = '/tmp/race_condition_test.lock';
        
        // Try to detect race conditions
        $acquired = ParallelTestManager::acquireLock('race_test', 1);
        
        if ($acquired) {
            // Simulate work
            usleep(10000); // 10ms
            ParallelTestManager::releaseLock('race_test');
            return 'acquired';
        }
        
        return 'blocked';
    })->toBeIn(['acquired', 'blocked']);
```

## Best Practices

### 1. Design for Parallelism

```php
// ✅ Good: Parallel-first design
class ParallelSafeTest extends PestTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Use worker-specific resources
        $this->workerId = ParallelTestManager::getWorkerIdentifier();
        $this->tempDir = sys_get_temp_dir() . "/test_{$this->workerId}";
        
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up worker-specific resources
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        
        parent::tearDown();
    }
}
```

### 2. Appropriate Tagging

```php
// ✅ Good: Clear parallel safety indication
uses()->group('unit', 'fast', 'parallel')->in('Unit');
uses()->group('integration', 'sequential')->in('Integration');

// ✅ Good: Explicit safety marking
it_should_be_parallel_safe()
    ->group('unit', 'parallel', 'validation')
    ->expect(fn() => $this->pureFunction())->toBeSuccessful();

it_should_require_sequential_execution()
    ->group('integration', 'sequential', 'database')
    ->expect(fn() => $this->databaseMigration())->toBeSuccessful();
```

### 3. Resource Management

```php
// ✅ Good: Proper resource cleanup
it_should_manage_resources_properly()
    ->group('integration', 'parallel')
    ->expect(function () {
        $resource = $this->createWorkerSpecificResource();
        
        try {
            return $this->useResource($resource);
        } finally {
            $this->cleanupResource($resource);
        }
    })->toBeSuccessful();
```

### 4. Error Handling

```php
// ✅ Good: Parallel-aware error handling
it_should_handle_parallel_errors()
    ->group('integration', 'parallel')
    ->expect(function () {
        try {
            return ParallelTestManager::withExclusiveAccess('shared_resource', function () {
                return $this->riskyOperation();
            });
        } catch (Exception $e) {
            // Log with worker context
            $workerId = ParallelTestManager::getWorkerIdentifier();
            error_log("Worker {$workerId}: {$e->getMessage()}");
            throw $e;
        }
    })->toBeSuccessful();
```

## Configuration Reference

Complete parallel testing configuration:

```php
'parallel' => [
    'processes' => min(8, (int) shell_exec('nproc') ?: 4),
    'timeout' => 60,
    'memory_limit' => '512M',
    'isolation_mode' => 'process',
    'database_strategy' => 'transaction', // or 'separate_db'
    
    'worker_configuration' => [
        'separate_temp_dirs' => true,
        'isolated_cache' => true,
        'unique_session_storage' => true,
        'process_specific_seeds' => true,
    ],
    
    'resource_management' => [
        'shared_resource_locking' => true,
        'exclusive_access_timeout' => 30,
        'cleanup_worker_resources' => true,
    ],
    
    'safety_checks' => [
        'validate_parallel_safety' => true,
        'exclude_unsafe_tags' => ['sequential', 'external', 'file_system'],
        'require_parallel_tag' => false,
    ],
],
```

## Troubleshooting

### Common Issues

1. **Race Conditions**
   ```php
   // Use proper locking
   ParallelTestManager::withExclusiveAccess('resource', $callback);
   ```

2. **Database Conflicts**
   ```php
   // Ensure transaction isolation
   ParallelTestManager::beginTestTransaction();
   ```

3. **File System Conflicts**
   ```php
   // Use worker-specific paths
   $workerId = ParallelTestManager::getWorkerIdentifier();
   $path = "/tmp/test_file_{$workerId}.txt";
   ```

4. **Memory Issues**
   ```php
   // Monitor and cleanup
   $stats = ParallelTestManager::getStatistics();
   if ($stats['memory_usage'] > $threshold) {
       ParallelTestManager::cleanup();
   }
   ```

## Related Documentation

- [Tagging System Guide](tagging-system.md)
- [Performance Testing Guide](performance-testing.md)
- [Test Organization Guide](test-organization.md)
- [Configuration Reference](../reference/configuration.md)