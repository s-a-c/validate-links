# Configuration Reference

This reference provides comprehensive documentation for all configuration options available in the advanced PEST test suite, including performance tuning, feature toggles, and environment-specific settings.

## Overview

The PEST test suite configuration is managed through several files:

- **[`pest.config.php`](../../../pest.config.php)** - Main PEST configuration
- **[`phpunit.xml`](../../../phpunit.xml)** - PHPUnit integration settings
- **[`.env.testing`](../../../.env.testing)** - Environment variables
- **[`composer.json`](../../../composer.json)** - Composer scripts and dependencies

## Main Configuration (`pest.config.php`)

### Test Suite Definitions

```php
'suites' => [
    'unit' => [
        'paths' => ['tests/Unit'],
        'testsuite' => 'Unit Tests',
        'parallel' => true,
        'memory_limit' => '256M',
    ],
    'feature' => [
        'paths' => ['tests/Feature'],
        'testsuite' => 'Feature Tests',
        'parallel' => true,
        'memory_limit' => '512M',
    ],
    'integration' => [
        'paths' => ['tests/Integration'],
        'testsuite' => 'Integration Tests',
        'parallel' => false, // Sequential for database integrity
        'memory_limit' => '512M',
    ],
    'e2e' => [
        'paths' => ['tests/E2E'],
        'testsuite' => 'End-to-End Tests',
        'parallel' => false, // Sequential for external dependencies
        'memory_limit' => '1G',
    ],
    'architecture' => [
        'paths' => ['tests/Architecture'],
        'testsuite' => 'Architecture Tests',
        'parallel' => true,
        'memory_limit' => '256M',
    ],
    'performance' => [
        'paths' => ['tests/Performance'],
        'testsuite' => 'Performance Tests',
        'parallel' => false, // Sequential for accurate measurements
        'memory_limit' => '1G',
        'excluded_by_default' => true, // Only run when explicitly requested
    ],
],
```

**Options**:
- `paths` (array): Test directories to include
- `testsuite` (string): Human-readable test suite name
- `parallel` (bool): Enable parallel execution for this suite
- `memory_limit` (string): Memory limit per process
- `excluded_by_default` (bool): Exclude from default test runs

### Coverage Configuration

```php
'coverage' => [
    'include' => [
        'app/**/*.php',
    ],
    'exclude' => [
        'app/Exceptions/Handler.php',
        'bootstrap/**/*.php',
        'config/**/*.php',
        'vendor/**/*.php',
        'tests/**/*.php',
    ],
    'report' => [
        'html' => 'reports/coverage/html',
        'text' => false, // Disable for performance
        'clover' => 'reports/coverage/clover.xml',
    ],
    'thresholds' => [
        'lines' => 90, // Realistic target
        'functions' => 90,
        'branches' => 85,
    ],
],
```

**Options**:
- `include` (array): Files/directories to include in coverage
- `exclude` (array): Files/directories to exclude from coverage
- `report` (array): Coverage report formats and locations
- `thresholds` (array): Minimum coverage percentages

### Environment Configuration

```php
'environment' => [
    'APP_ENV' => 'testing',
    'APP_DEBUG' => false, // Disable debug for performance
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'CACHE_DRIVER' => 'array',
    'SESSION_DRIVER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'LOG_CHANNEL' => 'null', // Disable logging for performance
    'MAIL_MAILER' => 'array',
],
```

**Options**:
- Standard Laravel environment variables
- Optimized for testing performance
- In-memory databases and caching

### Parallel Execution Configuration

```php
'parallel' => [
    'processes' => min(8, (int) shell_exec('nproc') ?: 4),
    'timeout' => 60, // Reduced from 120
    'memory_limit' => '512M',
    'isolation_mode' => 'process',
    'database_strategy' => 'transaction',
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

**Options**:
- `processes` (int): Number of parallel processes (auto-detected)
- `timeout` (int): Maximum execution time per process
- `memory_limit` (string): Memory limit per worker process
- `isolation_mode` (string): Process isolation strategy
- `database_strategy` (string): Database isolation method
- `worker_configuration` (array): Worker-specific settings
- `resource_management` (array): Shared resource handling
- `safety_checks` (array): Parallel safety validation

### Test Discovery Configuration

```php
'discovery' => [
    'cache_enabled' => true,
    'cache_path' => 'storage/framework/cache/pest-discovery.cache',
    'exclude_patterns' => [
        '*/Fixtures/*',
        '*/Support/Performance/*', // Exclude heavy performance utilities
        '*/Support/CI/*',
        '*/Support/Traits/*', // Exclude trait files
        '*/Support/Builders/*', // Exclude builder files
        '*/Support/Doubles/*', // Exclude test double files
    ],
    'include_patterns' => [
        '*Test.php',
        '*Tests.php',
    ],
],
```

**Options**:
- `cache_enabled` (bool): Enable test discovery caching
- `cache_path` (string): Cache file location
- `exclude_patterns` (array): Patterns to exclude from discovery
- `include_patterns` (array): Patterns to include in discovery

### Lazy Initialization Configuration

```php
'lazy_initialization' => [
    'enabled' => true,
    'cache_instances' => true,
    'auto_cleanup' => true,
    'memory_threshold' => '50M', // Clear cache when memory usage exceeds threshold
],
```

**Options**:
- `enabled` (bool): Enable lazy initialization feature
- `cache_instances` (bool): Cache created instances
- `auto_cleanup` (bool): Automatic cleanup after tests
- `memory_threshold` (string): Memory threshold for cache clearing

### Test Doubles Configuration

```php
'test_doubles' => [
    'auto_cleanup' => true,
    'state_management' => true,
    'interaction_recording' => env('PEST_RECORD_INTERACTIONS', false),
    'realistic_behaviors' => true,
],
```

**Options**:
- `auto_cleanup` (bool): Automatic mock cleanup
- `state_management` (bool): Enable stateful test doubles
- `interaction_recording` (bool): Record mock interactions
- `realistic_behaviors` (bool): Enable realistic mock behaviors

### Builder Pattern Configuration

```php
'builders' => [
    'fluent_interface' => true,
    'lazy_evaluation' => true,
    'method_chaining' => true,
    'validation' => true,
],
```

**Options**:
- `fluent_interface` (bool): Enable fluent interface pattern
- `lazy_evaluation` (bool): Enable lazy evaluation in builders
- `method_chaining` (bool): Enable method chaining
- `validation` (bool): Enable builder validation

### Tagging System Configuration

```php
'tagging' => [
    'enabled' => true,
    'auto_discovery' => true,
    'tag_inheritance' => true,
    'default_exclusions' => [
        'performance',
        'benchmark',
        'load',
        'slow',
        'external',
        'production',
    ],
    'tag_groups' => [
        'performance' => ['performance', 'benchmark', 'memory', 'load'],
        'functional' => ['unit', 'integration', 'feature', 'e2e'],
        'component' => ['validation', 'reporting', 'security', 'github'],
        'environment' => ['ci', 'local', 'production', 'staging'],
        'quality' => ['smoke', 'critical', 'edge_case', 'error_handling'],
    ],
    'conditional_execution' => [
        'respect_environment' => true,
        'check_requirements' => true,
        'validate_setup' => true,
    ],
],
```

**Options**:
- `enabled` (bool): Enable tagging system
- `auto_discovery` (bool): Automatic tag discovery
- `tag_inheritance` (bool): Enable tag inheritance
- `default_exclusions` (array): Tags excluded by default
- `tag_groups` (array): Logical tag groupings
- `conditional_execution` (array): Conditional execution settings

### Custom Matchers Configuration

```php
'custom_matchers' => [
    'enabled' => true,
    'auto_load' => true,
    'categories' => [
        'link_validation' => 'Tests\\Support\\Matchers\\LinkValidationMatchers',
        'performance' => 'Tests\\Support\\Matchers\\PerformanceMatchers',
        'security' => 'Tests\\Support\\Matchers\\SecurityMatchers',
        'reporting' => 'Tests\\Support\\Matchers\\ReportingMatchers',
    ],
    'validation' => [
        'check_existence' => true,
        'validate_signatures' => true,
    ],
],
```

**Options**:
- `enabled` (bool): Enable custom matchers
- `auto_load` (bool): Automatically load matchers
- `categories` (array): Matcher category mappings
- `validation` (array): Matcher validation settings

### Test Organization Configuration

```php
'organization' => [
    'naming_conventions' => [
        'enforce' => true,
        'pattern' => 'it_should_{behavior}_when_{condition}',
        'validate_names' => true,
        'suggest_improvements' => true,
    ],
    'structure' => [
        'max_nesting_depth' => 3,
        'require_describe_blocks' => true,
        'flat_structure_preferred' => true,
    ],
    'file_organization' => [
        'group_by_domain' => true,
        'separate_performance_tests' => true,
        'isolate_external_tests' => true,
    ],
],
```

**Options**:
- `naming_conventions` (array): Test naming rules
- `structure` (array): Test structure requirements
- `file_organization` (array): File organization preferences

### Enhanced Lifecycle Management

```php
'lifecycle' => [
    'enhanced_hooks' => true,
    'automatic_cleanup' => true,
    'memory_leak_detection' => true,
    'test_isolation_verification' => true,
    'resource_tracking' => true,
    'performance_monitoring' => true,
    'cleanup_strategies' => [
        'files' => 'auto',
        'directories' => 'auto',
        'database' => 'transaction',
        'cache' => 'clear',
        'sessions' => 'reset',
    ],
],
```

**Options**:
- `enhanced_hooks` (bool): Enable enhanced lifecycle hooks
- `automatic_cleanup` (bool): Automatic resource cleanup
- `memory_leak_detection` (bool): Detect memory leaks
- `test_isolation_verification` (bool): Verify test isolation
- `resource_tracking` (bool): Track resource usage
- `performance_monitoring` (bool): Monitor performance
- `cleanup_strategies` (array): Resource cleanup strategies

### Memory Management

```php
'memory' => [
    'gc_enabled' => true,
    'gc_frequency' => 10, // Run garbage collection every 10 tests
    'memory_limit_warning' => '80%',
    'memory_limit_error' => '90%',
],
```

**Options**:
- `gc_enabled` (bool): Enable garbage collection
- `gc_frequency` (int): GC frequency (tests)
- `memory_limit_warning` (string): Warning threshold
- `memory_limit_error` (string): Error threshold

### Performance Monitoring

```php
'performance' => [
    'slow_test_threshold' => 1.0, // 1 second
    'memory_usage_tracking' => true,
    'execution_time_tracking' => true,
    'report_slow_tests' => true,
],
```

**Options**:
- `slow_test_threshold` (float): Slow test threshold (seconds)
- `memory_usage_tracking` (bool): Track memory usage
- `execution_time_tracking` (bool): Track execution time
- `report_slow_tests` (bool): Report slow tests

### Conditional Loading

```php
'conditional_loading' => [
    'performance_tests' => env('INCLUDE_PERFORMANCE_TESTS', false),
    'slow_tests' => env('INCLUDE_SLOW_TESTS', false),
    'external_dependencies' => env('INCLUDE_EXTERNAL_TESTS', false),
],
```

**Options**:
- `performance_tests` (bool): Include performance tests
- `slow_tests` (bool): Include slow tests
- `external_dependencies` (bool): Include external dependency tests

### Test Execution Management

```php
'execution' => [
    'conditional_loading' => [
        'check_environment' => true,
        'validate_requirements' => true,
        'respect_ci_settings' => true,
    ],
    'filtering' => [
        'tag_based' => true,
        'performance_based' => true,
        'file_pattern_based' => true,
        'custom_conditions' => true,
    ],
    'grouping' => [
        'by_tags' => true,
        'by_performance' => true,
        'by_file_location' => true,
        'by_test_suite' => true,
    ],
    'performance_thresholds' => [
        'fast' => 0.1,      // 100ms
        'normal' => 1.0,    // 1 second
        'slow' => 5.0,      // 5 seconds
        'very_slow' => 30.0, // 30 seconds
    ],
    'reporting' => [
        'aggregate_results' => true,
        'performance_analysis' => true,
        'failure_analysis' => true,
        'tag_analysis' => true,
        'export_formats' => ['json', 'csv'],
    ],
],
```

**Options**:
- `conditional_loading` (array): Conditional loading settings
- `filtering` (array): Test filtering options
- `grouping` (array): Test grouping options
- `performance_thresholds` (array): Performance categorization
- `reporting` (array): Result reporting options

### Feature Flags

```php
'features' => [
    'comprehensive_tagging' => true,
    'custom_matchers' => true,
    'enhanced_lifecycle' => true,
    'parallel_execution' => true,
    'test_organization' => true,
    'execution_management' => true,
    'performance_monitoring' => true,
    'automatic_cleanup' => true,
],
```

**Options**:
- Feature flags to enable/disable advanced capabilities

## Environment Variables

### Core Testing Variables

```bash
# Environment
APP_ENV=testing
APP_DEBUG=false

# Database
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# Cache and Sessions
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

# Logging
LOG_CHANNEL=null
```

### PEST-Specific Variables

```bash
# Performance Tests
INCLUDE_PERFORMANCE_TESTS=false
INCLUDE_SLOW_TESTS=false
INCLUDE_EXTERNAL_TESTS=false

# PEST Configuration
PEST_PERFORMANCE_REPORT=false
PEST_PARALLEL=false
PEST_PROCESSES=4
PEST_WORKER_MEMORY_LIMIT=512M
PEST_DEBUG=false
PEST_RECORD_INTERACTIONS=false

# Thresholds
PEST_FAST_THRESHOLD=0.1
PEST_NORMAL_THRESHOLD=1.0
PEST_SLOW_THRESHOLD=5.0
```

### External Service Variables

```bash
# GitHub Integration
GITHUB_TOKEN=your_github_token

# API Testing
TEST_API_ENDPOINT=https://api.example.com
TEST_API_KEY=your_api_key

# Environment Flags
CI=false
STAGING_ENV=false
PRODUCTION_CONFIG=false
DOCKER_ENV=false
```

## PHPUnit Integration (`phpunit.xml`)

### Basic Configuration

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false"
         cacheDirectory="storage/framework/cache/phpunit">
```

### Test Suites

```xml
<testsuites>
    <testsuite name="Unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="Feature">
        <directory>tests/Feature</directory>
    </testsuite>
    <testsuite name="Integration">
        <directory>tests/Integration</directory>
    </testsuite>
    <testsuite name="E2E">
        <directory>tests/E2E</directory>
    </testsuite>
    <testsuite name="Architecture">
        <directory>tests/Architecture</directory>
    </testsuite>
    <testsuite name="Performance">
        <directory>tests/Performance</directory>
    </testsuite>
</testsuites>
```

### Coverage Configuration

```xml
<source>
    <include>
        <directory>app</directory>
    </include>
    <exclude>
        <directory>app/Exceptions</directory>
        <file>app/Exceptions/Handler.php</file>
    </exclude>
</source>
```

### Environment Variables

```xml
<php>
    <env name="APP_ENV" value="testing"/>
    <env name="APP_DEBUG" value="false"/>
    <env name="DB_CONNECTION" value="sqlite"/>
    <env name="DB_DATABASE" value=":memory:"/>
    <env name="CACHE_DRIVER" value="array"/>
    <env name="SESSION_DRIVER" value="array"/>
    <env name="QUEUE_CONNECTION" value="sync"/>
    <env name="LOG_CHANNEL" value="null"/>
</php>
```

## Performance Tuning

### Memory Optimization

```php
// Increase memory limits for heavy tests
'memory' => [
    'gc_enabled' => true,
    'gc_frequency' => 5, // More frequent GC
    'memory_limit_warning' => '70%', // Earlier warning
],

// Per-suite memory limits
'suites' => [
    'performance' => [
        'memory_limit' => '2G', // Higher for performance tests
    ],
],
```

### Parallel Execution Optimization

```php
// Optimize for your hardware
'parallel' => [
    'processes' => min(16, (int) shell_exec('nproc') ?: 8), // More processes
    'timeout' => 30, // Shorter timeout
    'memory_limit' => '256M', // Lower per-process limit
],
```

### Test Discovery Optimization

```php
// Aggressive caching
'discovery' => [
    'cache_enabled' => true,
    'exclude_patterns' => [
        '*/Support/*', // Exclude all support files
        '*/Fixtures/*',
        '*/Examples/*',
    ],
],
```

### Database Optimization

```php
// Faster database strategy
'parallel' => [
    'database_strategy' => 'separate_db', // Separate DBs for isolation
],

// Or in-memory with transactions
'environment' => [
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
],
```

## Troubleshooting Configuration

### Debug Mode

```bash
# Enable debug mode
export PEST_DEBUG=true

# Verbose output
./vendor/bin/pest -v

# Very verbose output
./vendor/bin/pest -vv
```

### Memory Issues

```php
// Increase memory limits
'memory' => [
    'gc_frequency' => 1, // GC after every test
    'memory_limit_warning' => '50%',
],

// Per-suite limits
'suites' => [
    'unit' => ['memory_limit' => '512M'],
    'feature' => ['memory_limit' => '1G'],
],
```

### Parallel Testing Issues

```php
// Disable parallel testing
'parallel' => [
    'processes' => 1, // Sequential execution
],

// Or reduce processes
'parallel' => [
    'processes' => 2, // Minimal parallelism
],
```

### Performance Issues

```php
// Disable expensive features
'features' => [
    'performance_monitoring' => false,
    'memory_leak_detection' => false,
    'test_isolation_verification' => false,
],

// Reduce coverage overhead
'coverage' => [
    'report' => [
        'html' => false,
        'text' => false,
    ],
],
```

## Environment-Specific Configurations

### CI/CD Environment

```php
// Optimized for CI
'environment' => [
    'APP_DEBUG' => false,
    'LOG_CHANNEL' => 'null',
],

'parallel' => [
    'processes' => min(4, (int) shell_exec('nproc') ?: 2),
    'timeout' => 120, // Longer timeout for CI
],

'tagging' => [
    'default_exclusions' => [
        'performance', 'slow', 'external', 'load',
    ],
],
```

### Local Development

```php
// Optimized for development
'features' => [
    'performance_monitoring' => true,
    'memory_leak_detection' => true,
],

'tagging' => [
    'default_exclusions' => [
        'performance', 'external',
    ],
],
```

### Production Testing

```php
// Production-like testing
'environment' => [
    'APP_ENV' => 'production',
    'APP_DEBUG' => false,
],

'tagging' => [
    'default_exclusions' => [], // Include all tests
},
```

## Related Documentation

- [Setup Guide](../guides/setup.md)
- [Performance Testing Guide](../features/performance-testing.md)
- [Parallel Testing Guide](../features/parallel-testing.md)
- [Troubleshooting Guide](troubleshooting.md)