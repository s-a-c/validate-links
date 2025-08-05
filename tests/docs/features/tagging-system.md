# Tagging System Guide

The PEST test suite includes a comprehensive tagging system with 25+ tags across 5 categories for precise test organization, filtering, and conditional execution. This system enables efficient test management and selective execution based on various criteria.

## Overview

The tagging system provides:

- **25+ predefined tags** across 5 categories
- **Tag aliases** for convenience
- **Conditional execution** based on environment
- **Hierarchical organization** with tag groups
- **Automatic filtering** for CI/CD environments
- **Performance-based segregation**

## Tag Categories

### 1. Performance Tags

Performance-related tags for execution time management:

```php
// Performance tags
'performance' => 'General performance tests',
'benchmark' => 'Benchmarking and comparison tests',
'memory' => 'Memory usage and leak tests',
'load' => 'Load and stress testing',
'slow' => 'Tests that take longer than normal',
'fast' => 'Quick tests for rapid feedback',
```

**Usage:**
```php
// Apply performance tags
uses()->group('performance', 'benchmark')->in('Performance');
uses()->group('fast', 'memory')->in('Unit');

// Individual test tagging
it_should_complete_validation_quickly()
    ->group('performance', 'fast')
    ->expect(fn() => $this->quickValidation())->toCompleteWithin(0.1);

it_should_handle_large_datasets()
    ->group('performance', 'memory', 'slow')
    ->expect(fn() => $this->processLargeDataset())->toUseMemoryLessThan(100 * 1024 * 1024);
```

### 2. Functional Tags

Test type and scope categorization:

```php
// Functional tags
'unit' => 'Unit tests for individual components',
'integration' => 'Integration tests between components',
'feature' => 'Feature-level tests',
'e2e' => 'End-to-end tests',
'acceptance' => 'User acceptance tests',
'regression' => 'Regression tests',
```

**Usage:**
```php
// Directory-level tagging
uses()->group('unit', 'fast')->in('Unit');
uses()->group('integration', 'medium')->in('Integration');
uses()->group('e2e', 'slow')->in('E2E');

// Test-specific tagging
it_should_validate_individual_links()
    ->group('unit', 'validation')
    ->expect(fn() => $this->validateSingleLink())->toBeValidLink();

it_should_integrate_with_external_apis()
    ->group('integration', 'external')
    ->expect(fn() => $this->testApiIntegration())->toBeSuccessful();
```

### 3. Component Tags

Domain-specific functionality tags:

```php
// Component tags
'validation' => 'Link validation functionality',
'reporting' => 'Report generation and formatting',
'security' => 'Security validation and checks',
'github' => 'GitHub-specific functionality',
'config' => 'Configuration management',
'cli' => 'Command-line interface tests',
```

**Usage:**
```php
// Component-specific test organization
uses()->group('validation', 'security')->in('Feature');

it_should_validate_link_security()
    ->group('validation', 'security')
    ->expect(fn() => $this->validateSecureLink())->toBeSecure();

it_should_generate_reports()
    ->group('reporting', 'feature')
    ->expect(fn() => $this->generateReport())->toMatchReportFormat('json');

it_should_handle_github_integration()
    ->group('github', 'integration', 'external')
    ->expect(fn() => $this->testGitHubApi())->toBeSuccessful();
```

### 4. Environment Tags

Execution environment and context tags:

```php
// Environment tags
'ci' => 'Continuous integration environment',
'local' => 'Local development environment',
'production' => 'Production-like environment',
'staging' => 'Staging environment',
'docker' => 'Docker container environment',
'parallel' => 'Safe for parallel execution',
'sequential' => 'Must run sequentially',
```

**Usage:**
```php
// Environment-specific execution
uses()->group('ci', 'parallel')->in('Unit');
uses()->group('sequential', 'external')->in('Integration');

it_should_run_in_ci_environment()
    ->group('ci', 'fast')
    ->skip(!env('CI'), 'Only runs in CI')
    ->expect(fn() => $this->ciSpecificTest())->toBeSuccessful();

it_should_require_sequential_execution()
    ->group('sequential', 'database')
    ->expect(fn() => $this->databaseMigrationTest())->toBeSuccessful();
```

### 5. Quality Tags

Test quality and maintenance categorization:

```php
// Quality tags
'smoke' => 'Smoke tests for basic functionality',
'critical' => 'Critical path tests',
'edge_case' => 'Edge case and boundary tests',
'error_handling' => 'Error handling and exception tests',
'cleanup' => 'Tests that require special cleanup',
'external' => 'Tests requiring external dependencies',
```

**Usage:**
```php
// Quality-based organization
uses()->group('smoke', 'critical')->in('Feature');

it_should_handle_basic_functionality()
    ->group('smoke', 'critical')
    ->expect(fn() => $this->basicValidation())->toBeSuccessful();

it_should_handle_edge_cases()
    ->group('edge_case', 'error_handling')
    ->expect(fn() => $this->testEdgeCase())->toHandleErrorsGracefully();

it_should_test_external_dependencies()
    ->group('external', 'cleanup')
    ->skip(!env('INCLUDE_EXTERNAL_TESTS'), 'External tests disabled')
    ->expect(fn() => $this->testExternalService())->toBeSuccessful();
```

## Tag Aliases

Convenient shortcuts for commonly used tags:

```php
// Tag aliases
'perf' => 'performance',
'mem' => 'memory',
'int' => 'integration',
'sec' => 'security',
'gh' => 'github',
'ext' => 'external',
'par' => 'parallel',
'seq' => 'sequential',
```

**Usage:**
```php
// Using aliases
it_should_be_performant()
    ->group('perf', 'mem')  // Equivalent to 'performance', 'memory'
    ->expect(fn() => $this->operation())->toBePerformant();

it_should_integrate_securely()
    ->group('int', 'sec')   // Equivalent to 'integration', 'security'
    ->expect(fn() => $this->secureIntegration())->toBeSecure();
```

## Conditional Execution

### Environment-Based Filtering

Tags are automatically filtered based on environment conditions:

```php
// Excluded by default
'performance', 'benchmark', 'load', 'slow', 'external', 'production'

// Environment variables control inclusion
INCLUDE_PERFORMANCE_TESTS=true    // Include performance tests
INCLUDE_SLOW_TESTS=true          // Include slow tests
INCLUDE_EXTERNAL_TESTS=true      // Include external dependency tests
```

### Setup Requirements

Some tags require specific environment setup:

```php
// Tags with requirements
'external' => ['network', 'api_keys'],
'github' => ['github_token'],
'load' => ['performance_environment'],
'production' => ['production_config'],
```

**Usage:**
```php
it_should_test_github_integration()
    ->group('github', 'external')
    ->skip(!env('GITHUB_TOKEN'), 'GitHub token required')
    ->expect(fn() => $this->testGitHubApi())->toBeSuccessful();
```

## Running Tests with Tags

### Basic Tag Filtering

```bash
# Run tests with specific tags
pest --group=fast
pest --group=unit
pest --group=validation

# Exclude specific tags
pest --exclude-group=slow
pest --exclude-group=external
pest --exclude-group=performance

# Combine multiple tags
pest --group=unit,fast
pest --group=validation,security
```

### Advanced Filtering

```bash
# Include performance tests
INCLUDE_PERFORMANCE_TESTS=true pest --group=performance

# Run only critical tests
pest --group=critical,smoke

# Run parallel-safe tests only
pest --group=parallel --parallel

# Exclude slow and external tests
pest --exclude-group=slow,external
```

### Environment-Specific Execution

```bash
# CI environment
CI=true pest --group=ci,fast --exclude-group=external

# Local development
pest --group=local,unit --exclude-group=slow

# Full test suite including external dependencies
INCLUDE_EXTERNAL_TESTS=true INCLUDE_PERFORMANCE_TESTS=true pest
```

## Tag Management

### Using TagManager

The [`TagManager`](../../Support/Tags/TagManager.php:14) class provides utilities for tag management:

```php
use Tests\Support\Tags\TagManager;

// Get all tags in a category
$performanceTags = TagManager::getTagsByCategory('performance');

// Resolve tag aliases
$resolvedTag = TagManager::resolveAlias('perf'); // Returns 'performance'

// Check if tag is excluded by default
$isExcluded = TagManager::isExcludedByDefault('performance'); // Returns true

// Get setup requirements
$requirements = TagManager::getSetupRequirements('github'); // Returns ['github_token']

// Validate tags
$invalidTags = TagManager::validateTags(['invalid_tag', 'unit']);

// Get tag description
$description = TagManager::getTagDescription('performance');
```

### Using TagFilter

The [`TagFilter`](../../Support/Tags/TagFilter.php) class provides advanced filtering:

```php
use Tests\Support\Tags\TagFilter;

// Create filter
$filter = new TagFilter();

// Include specific tags
$filter->include(['unit', 'fast']);

// Exclude specific tags
$filter->exclude(['slow', 'external']);

// Check if test should execute
$shouldRun = $filter->shouldExecute(['unit', 'validation']); // Returns true
$shouldSkip = $filter->shouldExecute(['slow', 'external']);  // Returns false
```

## Best Practices

### 1. Consistent Tagging Strategy

```php
// ✅ Good: Consistent tagging
uses()->group('unit', 'fast', 'parallel')->in('Unit');
uses()->group('feature', 'validation')->in('Feature');
uses()->group('integration', 'sequential')->in('Integration');

// ❌ Avoid: Inconsistent tagging
uses()->group('unit')->in('Unit');
uses()->group('feature', 'slow', 'external')->in('Feature'); // Mixed concerns
```

### 2. Meaningful Tag Combinations

```php
// ✅ Good: Meaningful combinations
it_should_validate_quickly()
    ->group('validation', 'performance', 'fast')
    ->expect(fn() => $this->quickValidation())->toCompleteWithin(0.1);

it_should_handle_security_edge_cases()
    ->group('security', 'edge_case', 'error_handling')
    ->expect(fn() => $this->securityEdgeCase())->toHandleErrorsGracefully();

// ❌ Avoid: Contradictory tags
it_should_be_fast_and_slow()
    ->group('fast', 'slow') // Contradictory
    ->expect(fn() => $this->operation())->toBeSuccessful();
```

### 3. Environment-Aware Tagging

```php
// ✅ Good: Environment-aware
it_should_test_external_api()
    ->group('external', 'integration')
    ->skip(!env('INCLUDE_EXTERNAL_TESTS'), 'External tests disabled')
    ->expect(fn() => $this->externalApiTest())->toBeSuccessful();

it_should_run_performance_benchmark()
    ->group('performance', 'benchmark')
    ->skip(!env('INCLUDE_PERFORMANCE_TESTS'), 'Performance tests disabled')
    ->expect(fn() => $this->benchmark())->toMeetPerformanceBenchmark();
```

### 4. Hierarchical Organization

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
    
    describe('Performance Validation', function () {
        uses()->group('performance');
        
        it_should_validate_quickly()
            ->group('fast', 'benchmark')
            ->expect(fn() => $this->quickValidation())->toCompleteWithin(0.1);
    });
});
```

## Tag Recommendations

### By Test Type

```php
// Unit tests
$unitTags = TagManager::getRecommendedTags('unit');
// Returns: ['unit', 'fast', 'parallel']

// Integration tests
$integrationTags = TagManager::getRecommendedTags('integration');
// Returns: ['integration', 'sequential']

// Performance tests
$performanceTags = TagManager::getRecommendedTags('performance');
// Returns: ['performance', 'benchmark', 'slow', 'sequential']

// Security tests
$securityTags = TagManager::getRecommendedTags('security');
// Returns: ['security', 'critical']

// E2E tests
$e2eTags = TagManager::getRecommendedTags('e2e');
// Returns: ['e2e', 'slow', 'sequential', 'external']
```

## Configuration

Configure the tagging system in [`pest.config.php`](../../../pest.config.php:164):

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

## Examples

### Complete Test File with Tags

```php
<?php

declare(strict_types=1);

use Tests\Support\PestTestCase;

uses(PestTestCase::class);
uses()->group('validation', 'feature')->in(__FILE__);

describe('Link Validation Feature', function () {
    
    describe('Basic Validation', function () {
        uses()->group('unit', 'fast');
        
        it_should_validate_valid_urls()
            ->group('smoke', 'critical')
            ->expect(function () {
                return $this->linkValidation->validateLink('https://example.com');
            })->toBeValidLink();
            
        it_should_identify_broken_links()
            ->group('error_handling')
            ->expect(function () {
                return $this->linkValidation->validateLink('https://broken-link.com');
            })->toBeBrokenLink();
    });
    
    describe('Security Validation', function () {
        uses()->group('security', 'critical');
        
        it_should_validate_secure_protocols()
            ->group('protocol', 'https')
            ->expect(function () {
                return $this->securityValidation->validateUrl('https://secure.com');
            })->toBeSecure();
            
        it_should_block_malicious_domains()
            ->group('domain', 'security')
            ->expect(function () {
                return $this->securityValidation->validateUrl('http://malicious.com');
            })->toFailSecurityCheck();
    });
    
    describe('Performance Validation', function () {
        uses()->group('performance', 'benchmark');
        
        it_should_validate_quickly()
            ->group('fast', 'memory')
            ->skip(!env('INCLUDE_PERFORMANCE_TESTS'), 'Performance tests disabled')
            ->expect(function () {
                return fn() => $this->linkValidation->validateLink('https://fast-site.com');
            })->toCompleteWithin(0.5);
            
        it_should_handle_batch_validation_efficiently()
            ->group('load', 'memory', 'slow')
            ->skip(!env('INCLUDE_PERFORMANCE_TESTS'), 'Performance tests disabled')
            ->expect(function () {
                $urls = array_fill(0, 100, 'https://example.com');
                return fn() => $this->linkValidation->validateLinks($urls);
            })->toUseMemoryLessThan(50 * 1024 * 1024);
    });
    
    describe('External Integration', function () {
        uses()->group('external', 'integration');
        
        it_should_integrate_with_github_api()
            ->group('github', 'api')
            ->skip(!env('GITHUB_TOKEN'), 'GitHub token required')
            ->skip(!env('INCLUDE_EXTERNAL_TESTS'), 'External tests disabled')
            ->expect(function () {
                return $this->githubService->validateRepositoryLinks();
            })->toBeSuccessful();
    });
});
```

## Related Documentation

- [Test Organization Guide](test-organization.md)
- [Performance Testing Guide](performance-testing.md)
- [Parallel Testing Guide](parallel-testing.md)
- [Configuration Reference](../reference/configuration.md)
- [Tags Reference](../reference/tags.md)