# 4. Implementation Roadmap

<details>
<summary>Table of Contents</summary>

- [4. Implementation Roadmap](#4-implementation-roadmap)
  - [4.1. Overview](#41-overview)
  - [4.2. File-by-File Change List](#42-file-by-file-change-list)
  - [4.3. Pure Pest Test Suite](#43-pure-pest-test-suite)
  - [4.4. PHPStan Max Compliance](#44-phpstan-max-compliance)
  - [4.5. Migration Strategy](#45-migration-strategy)
  - [4.6. Rollback Plan](#46-rollback-plan)

</details>

## 4.1. Overview

This document provides a comprehensive step-by-step implementation plan for enhancing type safety in the validate-links application. The roadmap includes specific file modifications, test implementations, PHPStan compliance measures, and risk mitigation strategies.

**Implementation Phases:**
1. Foundation Setup (Week 1)
2. Core DTO Implementation (Week 2)
3. Union Type Elimination (Week 3)
4. Interface Segregation (Week 4)

## 4.2. File-by-File Change List

### 4.2.1. Phase 1: Foundation Setup

#### New Enum Files

**app/Enums/LinkType.php**
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum LinkType: string
{
    case INTERNAL = 'internal';
    case EXTERNAL = 'external';
    case ANCHOR = 'anchor';
    case CROSS_REFERENCE = 'cross_reference';

    public function isLocal(): bool
    {
        return match ($this) {
            self::INTERNAL, self::ANCHOR, self::CROSS_REFERENCE => true,
            self::EXTERNAL => false,
        };
    }

    public function requiresNetworkAccess(): bool
    {
        return $this === self::EXTERNAL;
    }
}
```

**app/Enums/ValidationLevel.php**
```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum ValidationLevel: string
{
    case STRICT = 'strict';
    case NORMAL = 'normal';
    case LENIENT = 'lenient';

    public function getTimeoutMultiplier(): float
    {
        return match ($this) {
            self::STRICT => 2.0,
            self::NORMAL => 1.0,
            self::LENIENT => 0.8,
        };
    }
}
```

#### New DTO Files

**app/Services/ValueObjects/ValidationStatistics.php**
```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

final readonly class ValidationStatistics
{
    public function __construct(
        private int $total,
        private int $valid,
        private int $broken,
        private int $timeouts,
        private int $securityRisks,
        private int $redirects = 0,
        private float $successRate = 0.0
    ) {
        $this->validateStatistics();
    }

    public static function fromCounts(
        int $total,
        int $valid,
        int $broken,
        int $timeouts,
        int $securityRisks,
        int $redirects = 0
    ): self {
        $successRate = $total > 0 ? ($valid + $redirects) / $total * 100 : 0.0;
        
        return new self(
            total: $total,
            valid: $valid,
            broken: $broken,
            timeouts: $timeouts,
            securityRisks: $securityRisks,
            redirects: $redirects,
            successRate: $successRate
        );
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    public function hasIssues(): bool
    {
        return $this->broken > 0 || $this->timeouts > 0 || $this->securityRisks > 0;
    }

    private function validateStatistics(): void
    {
        if ($this->total < 0) {
            throw new \InvalidArgumentException('Total cannot be negative');
        }
        
        if ($this->valid + $this->broken + $this->timeouts + $this->securityRisks + $this->redirects > $this->total) {
            throw new \InvalidArgumentException('Sum of individual counts cannot exceed total');
        }
    }
}
```

### 4.2.2. Phase 2: Collection Implementation

**app/Services/ValueObjects/ValidationResultCollection.php**
```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use ArrayIterator;
use Countable;
use IteratorAggregate;

final readonly class ValidationResultCollection implements Countable, IteratorAggregate
{
    public function __construct(
        private array $results = []
    ) {
        $this->validateResults();
    }

    public static function fromArray(array $results): self
    {
        return new self(array_map(
            fn($result) => $result instanceof ValidationResult ? $result : ValidationResult::fromArray($result),
            $results
        ));
    }

    public function getSuccessful(): self
    {
        return new self(array_filter($this->results, fn($result) => $result->isSuccessful()));
    }

    public function getFailed(): self
    {
        return new self(array_filter($this->results, fn($result) => !$result->isSuccessful()));
    }

    public function count(): int
    {
        return count($this->results);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->results);
    }

    private function validateResults(): void
    {
        foreach ($this->results as $result) {
            if (!$result instanceof ValidationResult) {
                throw new \InvalidArgumentException('All items must be ValidationResult instances');
            }
        }
    }
}
```

### 4.2.3. Phase 3: Service Method Updates

#### app/Services/LinkValidationService.php

**Before (Lines 29, 81, 348):**
```php
public function validateFile(string $filePath, ValidationConfig|array $config): array
public function validateFileSummary(string $filePath, ValidationConfig|array $config): array
public function validateExternalLinks(array $urls, ValidationConfig|array $config): array
```

**After:**
```php
public function validateFile(string $filePath, ValidationConfig $config): ValidationResultCollection
{
    // Remove union type handling
    $results = [];
    
    if (!file_exists($filePath)) {
        return ValidationResultCollection::fromArray([
            ValidationResult::create(
                url: $filePath,
                status: LinkStatus::BROKEN,
                message: 'File not found'
            )
        ]);
    }

    $content = file_get_contents($filePath);
    $links = $this->extractLinks($content);
    
    foreach ($links as $link) {
        $results[] = $this->validateLink($link, $config);
    }
    
    return ValidationResultCollection::fromArray($results);
}

public function validateFileSummary(string $filePath, ValidationConfig $config): ValidationSummary
{
    $results = $this->validateFile($filePath, $config);
    
    return ValidationSummary::create(
        filePath: $filePath,
        results: $results,
        statistics: ValidationStatistics::fromResults($results)
    );
}

public function validateExternalLinks(array $urls, ValidationConfig $config): ValidationResultCollection
{
    $results = [];
    
    foreach ($urls as $url) {
        if ($this->isValidUrl($url)) {
            $results[] = $this->validateSingleExternalLink($url);
        }
    }
    
    return ValidationResultCollection::fromArray($results);
}
```

#### app/Services/StatisticsService.php

**Before:**
```php
public function getStatusStatistics(): array
{
    return [
        'total' => $this->totalLinks,
        'valid' => $this->validLinks,
        'broken' => $this->brokenLinks,
        'timeouts' => $this->timeoutLinks,
        'security_risks' => $this->securityRisks,
    ];
}
```

**After:**
```php
public function getStatusStatistics(): ValidationStatistics
{
    return ValidationStatistics::fromCounts(
        total: $this->totalLinks,
        valid: $this->validLinks,
        broken: $this->brokenLinks,
        timeouts: $this->timeoutLinks,
        securityRisks: $this->securityRisks,
        redirects: $this->redirectLinks
    );
}
```

### 4.2.4. Phase 4: Interface Updates

#### app/Services/Contracts/LinkValidationInterface.php

**Before:**
```php
interface LinkValidationInterface
{
    public function validateFile(string $filePath, ValidationConfig|array $config): array;
    public function validateLinks(array $urls, ValidationConfig $config): array;
    public function extractLinks(string $content): array;
}
```

**After:**
```php
interface LinkValidationInterface
{
    public function validateFile(string $filePath, ValidationConfig $config): ValidationResultCollection;
    public function validateLinks(array $urls, ValidationConfig $config): ValidationResultCollection;
    public function extractLinks(string $content): LinkCollection;
}
```

## 4.3. Pure Pest Test Suite

### 4.3.1. DTO Test Examples

**tests/Unit/ValueObjects/ValidationStatisticsTest.php**
```php
<?php

declare(strict_types=1);

use App\Services\ValueObjects\ValidationStatistics;

describe('ValidationStatistics', function () {
    it('creates statistics from counts', function () {
        $stats = ValidationStatistics::fromCounts(
            total: 100,
            valid: 80,
            broken: 15,
            timeouts: 3,
            securityRisks: 2
        );

        expect($stats->getTotal())->toBe(100)
            ->and($stats->getSuccessRate())->toBe(80.0)
            ->and($stats->hasIssues())->toBeTrue();
    });

    it('calculates success rate correctly', function () {
        $stats = ValidationStatistics::fromCounts(
            total: 50,
            valid: 40,
            broken: 5,
            timeouts: 3,
            securityRisks: 2,
            redirects: 10
        );

        expect($stats->getSuccessRate())->toBe(100.0); // (40 + 10) / 50 * 100
    });

    it('validates statistics on construction', function () {
        expect(fn() => ValidationStatistics::fromCounts(
            total: -1,
            valid: 0,
            broken: 0,
            timeouts: 0,
            securityRisks: 0
        ))->toThrow(InvalidArgumentException::class, 'Total cannot be negative');
    });

    it('detects when there are no issues', function () {
        $stats = ValidationStatistics::fromCounts(
            total: 10,
            valid: 8,
            broken: 0,
            timeouts: 0,
            securityRisks: 0,
            redirects: 2
        );

        expect($stats->hasIssues())->toBeFalse();
    });
});
```

**tests/Unit/ValueObjects/ValidationResultCollectionTest.php**
```php
<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;

describe('ValidationResultCollection', function () {
    beforeEach(function () {
        $this->validResult = ValidationResult::create(
            url: 'https://example.com',
            status: LinkStatus::VALID,
            message: 'Link is valid'
        );

        $this->brokenResult = ValidationResult::create(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            message: 'Link is broken'
        );

        $this->collection = new ValidationResultCollection([
            $this->validResult,
            $this->brokenResult
        ]);
    });

    it('counts results correctly', function () {
        expect($this->collection->count())->toBe(2);
    });

    it('filters successful results', function () {
        $successful = $this->collection->getSuccessful();
        
        expect($successful->count())->toBe(1);
    });

    it('filters failed results', function () {
        $failed = $this->collection->getFailed();
        
        expect($failed->count())->toBe(1);
    });

    it('is iterable', function () {
        $results = [];
        foreach ($this->collection as $result) {
            $results[] = $result;
        }

        expect($results)->toHaveCount(2)
            ->and($results[0])->toBe($this->validResult)
            ->and($results[1])->toBe($this->brokenResult);
    });

    it('validates result types on construction', function () {
        expect(fn() => new ValidationResultCollection(['invalid']))
            ->toThrow(InvalidArgumentException::class, 'All items must be ValidationResult instances');
    });
});
```

### 4.3.2. Enum Test Examples

**tests/Unit/Enums/LinkTypeTest.php**
```php
<?php

declare(strict_types=1);

use App\Enums\LinkType;

describe('LinkType', function () {
    it('identifies local link types', function () {
        expect(LinkType::INTERNAL->isLocal())->toBeTrue()
            ->and(LinkType::ANCHOR->isLocal())->toBeTrue()
            ->and(LinkType::CROSS_REFERENCE->isLocal())->toBeTrue()
            ->and(LinkType::EXTERNAL->isLocal())->toBeFalse();
    });

    it('identifies network access requirements', function () {
        expect(LinkType::EXTERNAL->requiresNetworkAccess())->toBeTrue()
            ->and(LinkType::INTERNAL->requiresNetworkAccess())->toBeFalse()
            ->and(LinkType::ANCHOR->requiresNetworkAccess())->toBeFalse()
            ->and(LinkType::CROSS_REFERENCE->requiresNetworkAccess())->toBeFalse();
    });

    it('has correct string values', function () {
        expect(LinkType::INTERNAL->value)->toBe('internal')
            ->and(LinkType::EXTERNAL->value)->toBe('external')
            ->and(LinkType::ANCHOR->value)->toBe('anchor')
            ->and(LinkType::CROSS_REFERENCE->value)->toBe('cross_reference');
    });
});
```

### 4.3.3. Service Integration Tests

**tests/Feature/LinkValidationServiceTest.php**
```php
<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\LinkValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResultCollection;

describe('LinkValidationService Integration', function () {
    beforeEach(function () {
        $this->service = app(LinkValidationService::class);
        $this->config = ValidationConfig::withDefaults();
    });

    it('validates file and returns collection', function () {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '[Valid Link](https://example.com)');

        $results = $this->service->validateFile($tempFile, $this->config);

        expect($results)->toBeInstanceOf(ValidationResultCollection::class)
            ->and($results->count())->toBeGreaterThan(0);

        unlink($tempFile);
    });

    it('handles non-existent files gracefully', function () {
        $results = $this->service->validateFile('/non/existent/file.md', $this->config);

        expect($results)->toBeInstanceOf(ValidationResultCollection::class)
            ->and($results->count())->toBe(1)
            ->and($results->getFailed()->count())->toBe(1);
    });

    it('validates external links', function () {
        $urls = ['https://httpbin.org/status/200', 'https://httpbin.org/status/404'];
        
        $results = $this->service->validateExternalLinks($urls, $this->config);

        expect($results)->toBeInstanceOf(ValidationResultCollection::class)
            ->and($results->count())->toBe(2);
    });
});
```

### 4.3.4. Architecture Tests

**tests/Architecture/TypeSafetyTest.php**
```php
<?php

declare(strict_types=1);

use App\Services\ValueObjects;

describe('Type Safety Architecture', function () {
    it('ensures all value objects are readonly', function () {
        expect('App\Services\ValueObjects')
            ->toHaveClasses(function ($class) {
                return $class->toBeReadonly();
            });
    });

    it('ensures all value objects are final', function () {
        expect('App\Services\ValueObjects')
            ->toHaveClasses(function ($class) {
                return $class->toBeFinal();
            });
    });

    it('ensures services do not use array return types', function () {
        expect('App\Services')
            ->toHaveClasses(function ($class) {
                return $class->toHaveMethods(function ($method) {
                    return $method->not->toReturnType('array');
                });
            });
    });

    it('ensures no union types with arrays', function () {
        expect('App\Services')
            ->toHaveClasses(function ($class) {
                return $class->toHaveMethods(function ($method) {
                    return $method->not->toHaveParameterType('ValidationConfig|array');
                });
            });
    });
});
```

## 4.4. PHPStan Max Compliance

### 4.4.1. Configuration Updates

**phpstan.neon (Enhanced)**
```neon
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    level: max
    
    paths:
        - app
        - bootstrap
        - config
        - tests

    excludePaths:
        - vendor
        - vendor/*
        - vendor/**/*

    errorFormat: table
    editorUrl: 'phpstorm://open?file=%%file%%&line=%%line%%'
    editorUrlTitle: '%%relFile%%:%%line%%'

    fileExtensions:
        - php

    tmpDir: reports/phpstan

    ignoreErrors:
        # Temporary ignores during migration - remove after completion
        # - '#Parameter .* expects .*, array given#'

    checkExplicitMixed: true
    checkImplicitMixed: true
    checkMissingCallableSignature: true
    treatPhpDocTypesAsCertain: true
    reportUnmatchedIgnoredErrors: true
    checkUninitializedProperties: true
    checkBenevolentUnionTypes: true
    checkTooWideReturnTypesInProtectedAndPublicMethods: true

    # Enhanced type checking
    strictRules:
        allRules: true
        disallowedLooseComparison: true
        booleansInConditions: true
        uselessCast: true
        requireParentConstructorCall: false
        disallowedConstructs: true
        overwriteVariablesWithLoop: true
        closureUsesThis: true
        matchingInheritedMethodNames: true
        numericOperandsInArithmeticOperators: true
        strictCalls: true

    # PHP 8.4 specific settings
    phpVersion: 80400

    # Performance settings
    parallel:
        maximumNumberOfProcesses: 8

    bootstrapFiles:
        - phpstan-bootstrap.php
```

### 4.4.2. Custom PHPStan Rules

**phpstan-custom-rules.php**
```php
<?php

declare(strict_types=1);

use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PhpParser\Node;

/**
 * Custom rule to prevent array return types in services
 */
final class NoArrayReturnTypeInServicesRule implements Rule
{
    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        $className = $scope->getClassReflection()?->getName();
        
        if (!$className || !str_contains($className, 'App\\Services\\')) {
            return [];
        }

        if ($node->returnType instanceof Node\Name && $node->returnType->toString() === 'array') {
            return [
                sprintf(
                    'Service method %s::%s() should not return generic array. Use specific DTO or collection instead.',
                    $className,
                    $node->name->toString()
                )
            ];
        }

        return [];
    }
}
```

### 4.4.3. Baseline Management

**phpstan-baseline.neon**
```neon
# PHPStan baseline - temporary ignores during migration
# This file should be empty after full implementation

parameters:
    ignoreErrors:
        # Phase 1: Union type ignores (remove after Phase 3)
        -
            message: '#Parameter \#2 \$config of method App\\Services\\LinkValidationService::validateFile\(\) expects App\\Services\\ValueObjects\\ValidationConfig, App\\Services\\ValueObjects\\ValidationConfig\|array given\.#'
            count: 3
            path: app/Services/LinkValidationService.php

        # Phase 2: Array return type ignores (remove after Phase 2)
        -
            message: '#Method App\\Services\\StatisticsService::getStatusStatistics\(\) should return App\\Services\\ValueObjects\\ValidationStatistics but returns array\.#'
            count: 1
            path: app/Services/StatisticsService.php
```

## 4.5. Migration Strategy

### 4.5.1. Step-by-Step Implementation Sequence

#### Week 1: Foundation (Non-Breaking Changes)

**Day 1-2: Enum Implementation**
1. Create `LinkType` enum
2. Create `ValidationLevel` enum  
3. Add enum usage in new code only
4. Run tests: `./vendor/bin/pest --group=enums`

**Day 3-4: DTO Creation**
1. Create `ValidationStatistics` DTO
2. Create `ValidationResultCollection` DTO
3. Add factory methods for backward compatibility
4. Run tests: `./vendor/bin/pest --group=dtos`

**Day 5: Testing and Documentation**
1. Write comprehensive Pest tests
2. Update PHPStan configuration
3. Generate baseline: `./vendor/bin/phpstan analyse --generate-baseline`

#### Week 2: Collection Integration (Gradual Breaking Changes)

**Day 1-2: Service Return Type Updates**
1. Update `StatisticsService::getStatusStatistics()` return type
2. Update formatter input types
3. Maintain backward compatibility with `toArray()` methods

**Day 3-4: Collection Method Implementation**
1. Add collection methods to services
2. Update internal service calls
3. Run integration tests: `./vendor/bin/pest --group=integration`

**Day 5: Validation and Rollback Testing**
1. Test rollback procedures
2. Performance benchmarking
3. Memory usage analysis

#### Week 3: Union Type Elimination (Breaking Changes)

**Day 1: Preparation**
1. Identify all union type usage
2. Create migration helpers
3. Update service provider bindings

**Day 2-3: Core Service Updates**
1. Remove union types from `LinkValidationService`
2. Update all method signatures
3. Add factory methods at boundaries

**Day 4: Command Layer Updates**
1. Update command classes to use factories
2. Ensure proper type conversion at input boundaries
3. Run feature tests: `./vendor/bin/pest --group=commands`

**Day 5: Integration Testing**
1. Full application testing
2. PHPStan max level validation
3. Performance regression testing

#### Week 4: Interface Segregation (Optional Enhancement)

**Day 1-2: Interface Design**
1. Create segregated interfaces
2. Maintain composite interface for compatibility
3. Update service implementations

**Day 3-4: Dependency Injection Updates**
1. Update service container bindings
2. Refactor dependent services
3. Run architecture tests: `./vendor/bin/pest --group=architecture`

**Day 5: Final Validation**
1. Complete test suite execution
2. Documentation updates
3. Performance optimization

### 4.5.2. Backward Compatibility Strategy

**Compatibility Layer Implementation**
```php
// app/Services/Compatibility/ArrayCompatibilityTrait.php
trait ArrayCompatibilityTrait
{
    /**
     * @deprecated Use ValidationResultCollection instead
     */
    public function validateFileAsArray(string $filePath, ValidationConfig|array $config): array
    {
        $config = is_array($config) ? ValidationConfig::create($config) : $config;
        $results = $this->validateFile($filePath, $config);
        
        return array_map(fn($result) => $result->toArray(), iterator_to_array($results));
    }
}
```

**Migration Helper Classes**
```php
// app/Services/Factories/ValidationConfigFactory.php
final class ValidationConfigFactory
{
    public static function fromArrayOrConfig(ValidationConfig|array $input): ValidationConfig
    {
        return is_array($input) ? ValidationConfig::create($input) : $input;
    }
}
```

### 4.5.3. Dependency Management

**Composer Updates**
```json
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0"
    },
    "require-dev": {
        "phpstan/phpstan": "^1.10",
        "pestphp/pest": "^2.0",
        "pestphp/pest-plugin-arch": "^2.0"
    }
}
```

**Service Provider Updates**
```php
// app/Providers/ValidateLinksServiceProvider.php
public function register(): void
{
    // Bind interfaces to implementations
    $this->app->bind(LinkValidationInterface::class, LinkValidationService::class);
    $this->app->bind(StatisticsInterface::class, StatisticsService::class);
    
    // Register factory classes
    $this->app->singleton(ValidationConfigFactory::class);
}
```

## 4.6. Rollback Plan

### 4.6.1. Immediate Rollback (< 24 hours)

**Git-Based Rollback**
```bash
# Identify last stable commit
git log --oneline -10

# Create rollback branch
git checkout -b rollback-type-safety-$(date +%Y%m%d)

# Revert to stable commit
git reset --hard <stable-commit-hash>

# Force push to main (emergency only)
git push origin main --force-with-lease
```

**Automated Deployment Rollback**
```yaml
# .github/workflows/emergency-rollback.yml
name: Emergency Rollback
on:
  workflow_dispatch:
    inputs:
      commit_hash:
        description: 'Commit hash to rollback to'
        required: true

jobs:
  rollback:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Rollback to stable commit
        run: |
          git reset --hard ${{ github.event.inputs.commit_hash }}
          git push origin main --force-with-lease
```

### 4.6.2. Partial Rollback (24-72 hours)

**Selective Component Rollback**

1. **Keep Beneficial Changes:**
   - Enum implementations (low risk)
   - Simple DTO additions
   - Test improvements

2. **Revert Problematic Changes:**
   - Union type eliminations
   - Collection implementations
   - Interface segregation

**Rollback Script**
```php
// scripts/partial-rollback.php
<?php

declare(strict_types=1);

class PartialRollback
{
    private array $filesToRevert = [
        'app/Services/LinkValidationService.php',
        'app/Services/Contracts/LinkValidationInterface.php',
        'app/Services/StatisticsService.php',
    ];

    public function execute(): void
    {
        foreach ($this->filesToRevert as $file) {
            $this->revertFile($file);
        }
        
        $this->updateComposerDependencies();
        $this->runTests();
    }

    private function revertFile(string $file): void
    {
        exec("git checkout HEAD~1 -- {$file}");
        echo "Reverted: {$file}\n";
    }
}
```

### 4.6.3. Monitoring and Alerts

**Performance Monitoring**
```php
// app/Monitoring/TypeSafetyMonitor.php
final class TypeSafetyMonitor
{
    public function checkMemoryUsage(): void
    {
        $currentUsage = memory_get_usage(true);
        $peakUsage = memory_get_peak_usage(true);
        
        if ($peakUsage > $this->getMemoryThreshold()) {
            $this->alertHighMemoryUsage($peakUsage);
        }
    }

    public function checkResponseTimes(): void
    {
        $averageResponseTime = $this->getAverageResponseTime();
        
        if ($averageResponseTime > $this->getResponseTimeThreshold()) {
            $this->alertSlowResponse($averageResponseTime);
        }
    }
}
```

**Health Check Endpoints**
```php
// routes/health.php
Route::get('/health/type-safety', function () {
    return response()->json([
        'status' => 'ok',
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
        'phpstan_level' => 'max',
        'type_safety_score' => 96,
        'timestamp' => now()->toISOString(),
    ]);
});
```

### 4.6.4. Communication Plan

**Rollback Communication Template**
```markdown
# Type Safety Enhancement Rollback Notice

**Date:** [DATE]
**Time:** [TIME]
**Status:** [PARTIAL/FULL] Rollback Initiated

## Reason for Rollback
- [ ] Performance degradation detected
- [ ] Memory usage exceeded threshold
- [ ] Critical functionality broken
- [ ] Test failures in production

## Actions Taken
- [ ] Reverted to commit: [COMMIT_HASH]
- [ ] Restored database state
- [ ] Cleared application caches
- [ ] Notified stakeholders

## Next Steps
- [ ] Root cause analysis
- [ ] Fix implementation issues
- [ ] Enhanced testing procedures
- [ ] Gradual re-deployment plan

## Contact
Technical Lead: [NAME]
DevOps Team: [CONTACT]
```

**Success Metrics for Re-deployment**
- PHPStan level max compliance: 100%
- Test suite pass rate: 100%
- Memory usage increase: < 20%
- Response time degradation: < 5%
- Zero critical bugs in 48 hours

---

## Navigation

**← Previous:** [Impact Analysis](030-impact-analysis.md)

**Next →** [Index](000-index.md)
