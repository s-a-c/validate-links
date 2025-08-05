# Tags Reference

This reference provides comprehensive documentation for all 25+ tags available in the PEST test suite, organized by category with detailed descriptions, usage examples, and filtering strategies.

## Overview

The tagging system provides 25+ predefined tags across 5 categories for precise test organization, filtering, and conditional execution:

- **Performance Tags** (6 tags) - For execution time and resource management
- **Functional Tags** (6 tags) - For test type and scope categorization  
- **Component Tags** (6 tags) - For domain-specific functionality
- **Environment Tags** (7 tags) - For execution environment and context
- **Quality Tags** (6 tags) - For test quality and maintenance categorization

## Performance Tags

### `performance`
**Description**: General performance tests that measure execution time, memory usage, or throughput.

**Usage**:
```php
uses()->group('performance')->in('Performance');

it_should_validate_efficiently()
    ->group('performance')
    ->expect(fn() => $this->operation())->toBePerformant();
```

**Excluded by default**: Yes  
**Environment variable**: `INCLUDE_PERFORMANCE_TESTS=true`

---

### `benchmark`
**Description**: Benchmarking and comparison tests that measure relative performance between different implementations.

**Usage**:
```php
it_should_be_faster_than_baseline()
    ->group('benchmark', 'performance')
    ->expect(fn() => $this->optimizedAlgorithm())
    ->toBeFasterThan(fn() => $this->baselineAlgorithm());
```

**Excluded by default**: Yes  
**Environment variable**: `INCLUDE_PERFORMANCE_TESTS=true`

---

### `memory`
**Description**: Memory usage and leak tests that monitor memory consumption patterns.

**Usage**:
```php
it_should_use_memory_efficiently()
    ->group('memory', 'performance')
    ->expect(fn() => $this->processLargeDataset())
    ->toUseMemoryLessThan(50 * 1024 * 1024); // 50MB
```

**Excluded by default**: No  
**Alias**: `mem`

---

### `load`
**Description**: Load and stress testing that validates system behavior under high load conditions.

**Usage**:
```php
it_should_handle_concurrent_requests()
    ->group('load', 'performance')
    ->skip(!env('INCLUDE_PERFORMANCE_TESTS'))
    ->expect(fn() => $this->simulateHighLoad())->toBeSuccessful();
```

**Excluded by default**: Yes  
**Environment variable**: `INCLUDE_PERFORMANCE_TESTS=true`

---

### `slow`
**Description**: Tests that take longer than normal execution time (typically > 1 second).

**Usage**:
```php
it_should_process_large_dataset()
    ->group('slow', 'integration')
    ->skip(!env('INCLUDE_SLOW_TESTS'))
    ->expect(fn() => $this->processMillionRecords())->toBeSuccessful();
```

**Excluded by default**: Yes  
**Environment variable**: `INCLUDE_SLOW_TESTS=true`

---

### `fast`
**Description**: Quick tests for rapid feedback (typically < 100ms).

**Usage**:
```php
uses()->group('fast')->in('Unit');

it_should_validate_quickly()
    ->group('fast', 'unit')
    ->expect(fn() => $this->quickValidation())->toCompleteWithin(0.1);
```

**Excluded by default**: No

---

## Functional Tags

### `unit`
**Description**: Unit tests for individual components, classes, or methods in isolation.

**Usage**:
```php
uses()->group('unit', 'fast', 'parallel')->in('Unit');

it_should_validate_single_method()
    ->group('unit')
    ->expect(fn() => $this->validator->isValid('input'))->toBeTrue();
```

**Excluded by default**: No  
**Recommended for**: Individual component testing

---

### `integration`
**Description**: Integration tests between components, services, or external systems.

**Usage**:
```php
uses()->group('integration', 'sequential')->in('Integration');

it_should_integrate_with_database()
    ->group('integration', 'database')
    ->expect(fn() => $this->service->saveToDatabase($data))->toBeSuccessful();
```

**Excluded by default**: No  
**Alias**: `int`  
**Recommended for**: Component interaction testing

---

### `feature`
**Description**: Feature-level tests that validate complete user-facing functionality.

**Usage**:
```php
uses()->group('feature')->in('Feature');

it_should_complete_user_workflow()
    ->group('feature', 'acceptance')
    ->expect(fn() => $this->completeUserJourney())->toBeSuccessful();
```

**Excluded by default**: No  
**Recommended for**: End-user functionality testing

---

### `e2e`
**Description**: End-to-end tests that validate complete system workflows.

**Usage**:
```php
uses()->group('e2e', 'slow', 'sequential')->in('E2E');

it_should_complete_full_workflow()
    ->group('e2e', 'external')
    ->expect(fn() => $this->fullSystemTest())->toBeSuccessful();
```

**Excluded by default**: No  
**Recommended for**: Complete system validation

---

### `acceptance`
**Description**: User acceptance tests that validate business requirements.

**Usage**:
```php
it_should_meet_business_requirements()
    ->group('acceptance', 'feature')
    ->expect(fn() => $this->businessScenario())->toMeetRequirements();
```

**Excluded by default**: No  
**Recommended for**: Business requirement validation

---

### `regression`
**Description**: Regression tests that prevent previously fixed bugs from reoccurring.

**Usage**:
```php
it_should_not_regress_bug_123()
    ->group('regression', 'critical')
    ->expect(fn() => $this->previouslyBuggyOperation())->toBeSuccessful();
```

**Excluded by default**: No  
**Recommended for**: Bug prevention

---

## Component Tags

### `validation`
**Description**: Link validation functionality and URL processing tests.

**Usage**:
```php
uses()->group('validation')->in('Feature');

it_should_validate_urls()
    ->group('validation', 'unit')
    ->expect(fn() => $this->linkValidator->validate('https://example.com'))
    ->toBeValidLink();
```

**Excluded by default**: No  
**Domain**: Link validation

---

### `reporting`
**Description**: Report generation and formatting functionality tests.

**Usage**:
```php
it_should_generate_json_report()
    ->group('reporting', 'feature')
    ->expect(fn() => $this->reportGenerator->generate('json'))
    ->toMatchReportFormat('json');
```

**Excluded by default**: No  
**Domain**: Report generation

---

### `security`
**Description**: Security validation and checks functionality tests.

**Usage**:
```php
it_should_validate_security()
    ->group('security', 'critical')
    ->expect(fn() => $this->securityValidator->check('https://site.com'))
    ->toBeSecure();
```

**Excluded by default**: No  
**Alias**: `sec`  
**Domain**: Security validation

---

### `github`
**Description**: GitHub-specific functionality and API integration tests.

**Usage**:
```php
it_should_integrate_with_github()
    ->group('github', 'external')
    ->skip(!env('GITHUB_TOKEN'))
    ->expect(fn() => $this->githubService->validateRepo())->toBeSuccessful();
```

**Excluded by default**: No  
**Alias**: `gh`  
**Requirements**: `GITHUB_TOKEN` environment variable  
**Domain**: GitHub integration

---

### `config`
**Description**: Configuration management and settings validation tests.

**Usage**:
```php
it_should_load_configuration()
    ->group('config', 'unit')
    ->expect(fn() => $this->configManager->load())->toBeValidConfig();
```

**Excluded by default**: No  
**Domain**: Configuration management

---

### `cli`
**Description**: Command-line interface and console command tests.

**Usage**:
```php
it_should_execute_cli_command()
    ->group('cli', 'feature')
    ->expect(fn() => $this->artisan('validate:links'))->toBeSuccessful();
```

**Excluded by default**: No  
**Domain**: CLI functionality

---

## Environment Tags

### `ci`
**Description**: Continuous integration environment specific tests.

**Usage**:
```php
it_should_run_in_ci()
    ->group('ci', 'smoke')
    ->skip(!env('CI'))
    ->expect(fn() => $this->ciSpecificTest())->toBeSuccessful();
```

**Excluded by default**: No  
**Environment check**: `CI` environment variable

---

### `local`
**Description**: Local development environment specific tests.

**Usage**:
```php
it_should_run_locally()
    ->group('local', 'development')
    ->skip(env('CI'))
    ->expect(fn() => $this->localOnlyTest())->toBeSuccessful();
```

**Excluded by default**: No  
**Environment check**: Not in CI environment

---

### `production`
**Description**: Production-like environment tests with production configurations.

**Usage**:
```php
it_should_work_in_production()
    ->group('production', 'critical')
    ->skip(!env('PRODUCTION_CONFIG'))
    ->expect(fn() => $this->productionTest())->toBeSuccessful();
```

**Excluded by default**: Yes  
**Requirements**: `PRODUCTION_CONFIG` environment variable

---

### `staging`
**Description**: Staging environment specific tests.

**Usage**:
```php
it_should_work_in_staging()
    ->group('staging', 'integration')
    ->skip(!env('STAGING_ENV'))
    ->expect(fn() => $this->stagingTest())->toBeSuccessful();
```

**Excluded by default**: No  
**Environment check**: `STAGING_ENV` environment variable

---

### `docker`
**Description**: Docker container environment specific tests.

**Usage**:
```php
it_should_work_in_docker()
    ->group('docker', 'container')
    ->skip(!env('DOCKER_ENV'))
    ->expect(fn() => $this->dockerTest())->toBeSuccessful();
```

**Excluded by default**: No  
**Environment check**: `DOCKER_ENV` environment variable

---

### `parallel`
**Description**: Tests that are safe for parallel execution.

**Usage**:
```php
uses()->group('parallel')->in('Unit');

it_should_be_parallel_safe()
    ->group('parallel', 'unit')
    ->expect(fn() => $this->parallelSafeOperation())->toBeSuccessful();
```

**Excluded by default**: No  
**Alias**: `par`  
**Safety**: Parallel execution safe

---

### `sequential`
**Description**: Tests that must run sequentially due to shared resources.

**Usage**:
```php
it_should_run_sequentially()
    ->group('sequential', 'database')
    ->expect(fn() => $this->databaseMigration())->toBeSuccessful();
```

**Excluded by default**: No  
**Alias**: `seq`  
**Safety**: Must run sequentially

---

## Quality Tags

### `smoke`
**Description**: Smoke tests for basic functionality verification.

**Usage**:
```php
it_should_pass_smoke_test()
    ->group('smoke', 'critical')
    ->expect(fn() => $this->basicFunctionality())->toBeSuccessful();
```

**Excluded by default**: No  
**Priority**: High  
**Purpose**: Basic functionality verification

---

### `critical`
**Description**: Critical path tests that must always pass.

**Usage**:
```php
it_should_handle_critical_path()
    ->group('critical', 'feature')
    ->expect(fn() => $this->criticalUserJourney())->toBeSuccessful();
```

**Excluded by default**: No  
**Priority**: Highest  
**Purpose**: Critical functionality protection

---

### `edge_case`
**Description**: Edge case and boundary condition tests.

**Usage**:
```php
it_should_handle_edge_cases()
    ->group('edge_case', 'validation')
    ->expect(fn() => $this->validator->validate(''))->toHandleGracefully();
```

**Excluded by default**: No  
**Purpose**: Boundary condition testing

---

### `error_handling`
**Description**: Error handling and exception tests.

**Usage**:
```php
it_should_handle_errors_gracefully()
    ->group('error_handling', 'resilience')
    ->expect(fn() => $this->service->processInvalidInput())
    ->toThrow(ValidationException::class);
```

**Excluded by default**: No  
**Purpose**: Error resilience testing

---

### `cleanup`
**Description**: Tests that require special cleanup procedures.

**Usage**:
```php
it_should_cleanup_resources()
    ->group('cleanup', 'integration')
    ->expect(function () {
        $resource = $this->createResource();
        try {
            return $this->useResource($resource);
        } finally {
            $this->cleanupResource($resource);
        }
    })->toBeSuccessful();
```

**Excluded by default**: No  
**Purpose**: Resource management testing

---

### `external`
**Description**: Tests requiring external dependencies or network access.

**Usage**:
```php
it_should_connect_to_external_api()
    ->group('external', 'integration')
    ->skip(!env('INCLUDE_EXTERNAL_TESTS'))
    ->expect(fn() => $this->externalApiCall())->toBeSuccessful();
```

**Excluded by default**: Yes  
**Alias**: `ext`  
**Environment variable**: `INCLUDE_EXTERNAL_TESTS=true`  
**Requirements**: Network access, API keys

---

## Tag Aliases

Convenient shortcuts for commonly used tags:

| Alias | Full Tag | Usage |
|-------|----------|-------|
| `perf` | `performance` | `->group('perf')` |
| `mem` | `memory` | `->group('mem')` |
| `int` | `integration` | `->group('int')` |
| `sec` | `security` | `->group('sec')` |
| `gh` | `github` | `->group('gh')` |
| `ext` | `external` | `->group('ext')` |
| `par` | `parallel` | `->group('par')` |
| `seq` | `sequential` | `->group('seq')` |

**Example using aliases**:
```php
it_should_be_fast_and_secure()
    ->group('perf', 'sec', 'par')  // performance, security, parallel
    ->expect(fn() => $this->operation())->toBePerformant();
```

## Tag Combinations

### Recommended Combinations

#### Unit Tests
```php
uses()->group('unit', 'fast', 'parallel')->in('Unit');
```

#### Integration Tests
```php
uses()->group('integration', 'sequential')->in('Integration');
```

#### Performance Tests
```php
uses()->group('performance', 'benchmark', 'slow', 'sequential')->in('Performance');
```

#### Security Tests
```php
uses()->group('security', 'critical')->in('Feature');
```

#### E2E Tests
```php
uses()->group('e2e', 'slow', 'sequential', 'external')->in('E2E');
```

### Advanced Combinations

#### CI-Optimized Tests
```php
it_should_run_in_ci_pipeline()
    ->group('ci', 'fast', 'parallel', 'smoke')
    ->expect(fn() => $this->ciOptimizedTest())->toBeSuccessful();
```

#### Critical Path Validation
```php
it_should_validate_critical_security()
    ->group('critical', 'security', 'validation', 'smoke')
    ->expect(fn() => $this->criticalSecurityCheck())->toBeSecure();
```

#### Performance Benchmarking
```php
it_should_benchmark_against_baseline()
    ->group('performance', 'benchmark', 'memory', 'load')
    ->skip(!env('INCLUDE_PERFORMANCE_TESTS'))
    ->expect(fn() => $this->performanceBenchmark())->toMeetBenchmark();
```

## Filtering Strategies

### Basic Filtering

```bash
# Run fast tests only
pest --group=fast

# Run unit tests
pest --group=unit

# Run validation tests
pest --group=validation

# Exclude slow tests
pest --exclude-group=slow

# Exclude external dependencies
pest --exclude-group=external
```

### Advanced Filtering

```bash
# Run critical and smoke tests
pest --group=critical,smoke

# Run parallel-safe tests only
pest --group=parallel --parallel

# Run security and validation tests
pest --group=security,validation

# Run everything except performance and external
pest --exclude-group=performance,external
```

### Environment-Based Filtering

```bash
# CI environment (fast, parallel-safe tests)
CI=true pest --group=ci,fast,parallel --exclude-group=external,slow

# Local development (include slow tests)
INCLUDE_SLOW_TESTS=true pest --group=local,unit,feature

# Full test suite (include everything)
INCLUDE_PERFORMANCE_TESTS=true \
INCLUDE_SLOW_TESTS=true \
INCLUDE_EXTERNAL_TESTS=true \
pest
```

### Performance-Based Filtering

```bash
# Fast feedback loop
pest --group=fast,smoke,critical

# Performance testing session
INCLUDE_PERFORMANCE_TESTS=true pest --group=performance,benchmark,memory

# Load testing
INCLUDE_PERFORMANCE_TESTS=true pest --group=load,stress
```

## Tag Management

### Using TagManager

```php
use Tests\Support\Tags\TagManager;

// Get all tags in a category
$performanceTags = TagManager::getTagsByCategory('performance');
// Returns: ['performance', 'benchmark', 'memory', 'load', 'slow', 'fast']

// Resolve aliases
$resolved = TagManager::resolveAlias('perf'); // Returns: 'performance'

// Check if excluded by default
$excluded = TagManager::isExcludedByDefault('performance'); // Returns: true

// Get setup requirements
$requirements = TagManager::getSetupRequirements('github');
// Returns: ['github_token']

// Validate tags
$invalid = TagManager::validateTags(['invalid_tag', 'unit']);
// Returns: ['invalid_tag']

// Get recommendations for test type
$unitTags = TagManager::getRecommendedTags('unit');
// Returns: ['unit', 'fast', 'parallel']
```

### Using TagFilter

```php
use Tests\Support\Tags\TagFilter;

// Create filter
$filter = new TagFilter();

// Include specific tags
$filter->include(['unit', 'fast']);

// Exclude specific tags
$filter->exclude(['slow', 'external']);

// Check if test should execute
$shouldRun = $filter->shouldExecute(['unit', 'validation']); // true
$shouldSkip = $filter->shouldExecute(['slow', 'external']);  // false
```

## Configuration

Tags are configured in [`pest.config.php`](../../../pest.config.php:164):

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

## Best Practices

### 1. Consistent Tagging Strategy

```php
// ✅ Good: Consistent and meaningful
uses()->group('unit', 'fast', 'parallel')->in('Unit');
uses()->group('feature', 'validation')->in('Feature');
uses()->group('integration', 'sequential')->in('Integration');

// ❌ Avoid: Inconsistent or contradictory
uses()->group('unit', 'slow')->in('Unit'); // Contradictory
uses()->group('fast', 'external')->in('Feature'); // Usually contradictory
```

### 2. Hierarchical Organization

```php
// ✅ Good: Hierarchical tagging
describe('Link Validation', function () {
    uses()->group('validation');
    
    describe('Security Validation', function () {
        uses()->group('security');
        
        it_should_validate_secure_protocols()
            ->group('protocol', 'https')
            ->expect(fn() => $this->validateHttps())->toBeSecure();
    });
});
```

### 3. Environment Awareness

```php
// ✅ Good: Environment-aware tagging
it_should_test_external_api()
    ->group('external', 'integration')
    ->skip(!env('INCLUDE_EXTERNAL_TESTS'), 'External tests disabled')
    ->expect(fn() => $this->externalApiTest())->toBeSuccessful();
```

### 4. Performance Segregation

```php
// ✅ Good: Clear performance segregation
it_should_be_fast()
    ->group('fast', 'unit')
    ->expect(fn() => $this->quickOperation())->toCompleteWithin(0.1);

it_should_handle_load()
    ->group('slow', 'load', 'performance')
    ->skip(!env('INCLUDE_PERFORMANCE_TESTS'))
    ->expect(fn() => $this->loadTest())->toHandleLoad();
```

## Related Documentation

- [Tagging System Guide](../features/tagging-system.md)
- [Test Organization Guide](../features/test-organization.md)
- [Running Tests Guide](../guides/running-tests.md)
- [Configuration Reference](configuration.md)