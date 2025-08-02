# 2. DTO Implementation Strategy

<details>
<summary>Table of Contents</summary>

- [2. DTO Implementation Strategy](#2-dto-implementation-strategy)
  - [2.1. Overview](#21-overview)
  - [2.2. DTO Design Principles](#22-dto-design-principles)
  - [2.3. Naming Conventions](#23-naming-conventions)
  - [2.4. DTO Class Structure](#24-dto-class-structure)
  - [2.5. Validation Requirements](#25-validation-requirements)
  - [2.6. Error Handling](#26-error-handling)
  - [2.7. Complete DTO Examples](#27-complete-dto-examples)

</details>

## 2.1. Overview

This document defines the comprehensive strategy for implementing Data Transfer Objects (DTOs) in the validate-links service layer. Based on the analysis in [docs/011-dto-service-layer-analysis.md](010-dto-service-layer-analysis.md), this strategy provides concrete patterns, naming conventions, and complete implementation examples.

**Strategy Goals:**
- Improve type safety from 45/100 to 85/100+
- Establish consistent data contracts between services
- Reduce manual array validation and casting
- Provide clear, immutable data structures
- Enable better IDE support and static analysis

## 2.2. DTO Design Principles

### 2.2.1. Core Principles

**1. Immutability**
- All DTOs must be `readonly` classes
- No mutable state after construction
- Use `with*` methods for updates (return new instances)

**2. Type Safety**
- Strong typing for all properties
- Use enums instead of string constants
- Validate all input data during construction

**3. Single Responsibility**
- Each DTO represents one specific data contract
- Clear, focused purpose
- Minimal dependencies

**4. Consistency**
- Follow established patterns from ValidationConfig/ValidationResult
- Consistent naming and structure across all DTOs
- Standardized factory methods and validation

### 2.2.2. Implementation Patterns

**Pattern 1: Simple Data Container**
```php
final readonly class ReportSummary
{
    public function __construct(
        public int $totalLinks,
        public int $brokenLinks,
        public float $successRate
    ) {
        $this->validate();
    }
}
```

**Pattern 2: Factory-Based Construction**
```php
public static function create(array $data): self
{
    return new self(
        totalLinks: (int) ($data['total_links'] ?? 0),
        brokenLinks: (int) ($data['broken_links'] ?? 0),
        successRate: (float) ($data['success_rate'] ?? 0.0)
    );
}
```

## 2.3. Naming Conventions

### 2.3.1. Class Names

**Format:** `{Domain}{Purpose}DTO` or `{Domain}{Purpose}`

**Examples:**
- `ReportSummary` (not `ReportSummaryDTO`)
- `LinkExtractionResult`
- `ValidationBatch`
- `FormatterOptions`

### 2.3.2. Property Names

**Format:** camelCase, descriptive, no abbreviations

**Good Examples:**
- `$totalLinks` (not `$total_links` or `$totLinks`)
- `$responseTime` (not `$resp_time`)
- `$httpStatusCode` (not `$status`)

### 2.3.3. Method Names

**Factory Methods:**
- `create()` - Primary factory method
- `fromArray()` - Create from associative array
- `withDefaults()` - Create with default values

**Accessor Methods:**
- `get{Property}()` - Explicit getters when needed
- `is{Property}()` - Boolean properties
- `has{Property}()` - Nullable properties

## 2.4. DTO Class Structure

### 2.4.1. Standard Template

```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

final readonly class ExampleDTO
{
    public function __construct(
        public string $property1,
        public int $property2,
        public ?string $optionalProperty = null
    ) {
        $this->validate();
    }

    public static function create(array $data): self
    {
        // Validation and casting logic
        return new self(
            property1: (string) ($data['property1'] ?? ''),
            property2: (int) ($data['property2'] ?? 0),
            optionalProperty: isset($data['optional_property']) 
                ? (string) $data['optional_property'] 
                : null
        );
    }

    private function validate(): void
    {
        if (empty($this->property1)) {
            throw new InvalidArgumentException('Property1 cannot be empty');
        }

        if ($this->property2 < 0) {
            throw new InvalidArgumentException('Property2 must be non-negative');
        }
    }

    public function toArray(): array
    {
        return [
            'property1' => $this->property1,
            'property2' => $this->property2,
            'optional_property' => $this->optionalProperty,
        ];
    }
}
```

## 2.5. Validation Requirements

### 2.5.1. Constructor Validation

All DTOs must validate their state in the constructor:

```php
private function validate(): void
{
    // Required field validation
    if (empty($this->url)) {
        throw new InvalidArgumentException('URL cannot be empty');
    }

    // Range validation
    if ($this->timeout < 1 || $this->timeout > 300) {
        throw new InvalidArgumentException('Timeout must be between 1 and 300 seconds');
    }

    // Enum validation
    if (!$this->status instanceof LinkStatus) {
        throw new InvalidArgumentException('Status must be a valid LinkStatus');
    }
}
```

### 2.5.2. Factory Method Validation

Factory methods must handle and validate mixed input:

```php
public static function create(array $data): self
{
    // Type casting with validation
    $url = $data['url'] ?? '';
    if (!is_string($url) || empty($url)) {
        throw new InvalidArgumentException('URL must be a non-empty string');
    }

    // Enum conversion with validation
    $status = $data['status'] ?? null;
    if ($status !== null && !$status instanceof LinkStatus) {
        try {
            $status = LinkStatus::from((string) $status);
        } catch (ValueError $e) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }
    }

    return new self(url: $url, status: $status);
}
```

## 2.6. Error Handling

### 2.6.1. Exception Strategy

**Use `InvalidArgumentException` for:**
- Invalid constructor parameters
- Failed validation rules
- Type conversion errors

**Use `ValueError` for:**
- Enum conversion failures (caught and re-thrown as InvalidArgumentException)

### 2.6.2. Error Messages

**Format:** Clear, specific, actionable

**Examples:**
```php
// Good: Specific and actionable
throw new InvalidArgumentException('Timeout must be between 1 and 300 seconds, got: ' . $this->timeout);

// Bad: Vague and unhelpful
throw new InvalidArgumentException('Invalid timeout');
```

## 2.7. Complete DTO Examples

### 2.7.1. ReportSummary DTO

```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\LinkStatus;
use InvalidArgumentException;

final readonly class ReportSummary
{
    /**
     * @param array<LinkStatus, int> $statusBreakdown
     */
    public function __construct(
        public int $totalLinks,
        public int $brokenLinks,
        public float $successRate,
        public array $statusBreakdown,
        public float $executionTime,
        public int $filesProcessed
    ) {
        $this->validate();
    }

    public static function create(array $data): self
    {
        $totalLinks = (int) ($data['total_links'] ?? 0);
        $brokenLinks = (int) ($data['broken_links'] ?? 0);
        $successRate = (float) ($data['success_rate'] ?? 0.0);
        $executionTime = (float) ($data['execution_time'] ?? 0.0);
        $filesProcessed = (int) ($data['files_processed'] ?? 0);

        // Validate and convert status breakdown
        $statusBreakdown = [];
        $rawBreakdown = $data['status_breakdown'] ?? [];
        if (is_array($rawBreakdown)) {
            foreach ($rawBreakdown as $status => $count) {
                try {
                    $linkStatus = LinkStatus::from((string) $status);
                    $statusBreakdown[$linkStatus] = (int) $count;
                } catch (ValueError) {
                    // Skip invalid statuses
                }
            }
        }

        return new self(
            totalLinks: $totalLinks,
            brokenLinks: $brokenLinks,
            successRate: $successRate,
            statusBreakdown: $statusBreakdown,
            executionTime: $executionTime,
            filesProcessed: $filesProcessed
        );
    }

    private function validate(): void
    {
        if ($this->totalLinks < 0) {
            throw new InvalidArgumentException('Total links must be non-negative');
        }

        if ($this->brokenLinks < 0) {
            throw new InvalidArgumentException('Broken links must be non-negative');
        }

        if ($this->brokenLinks > $this->totalLinks) {
            throw new InvalidArgumentException('Broken links cannot exceed total links');
        }

        if ($this->successRate < 0.0 || $this->successRate > 100.0) {
            throw new InvalidArgumentException('Success rate must be between 0 and 100');
        }

        if ($this->executionTime < 0.0) {
            throw new InvalidArgumentException('Execution time must be non-negative');
        }

        if ($this->filesProcessed < 0) {
            throw new InvalidArgumentException('Files processed must be non-negative');
        }
    }

    public function getValidLinks(): int
    {
        return $this->totalLinks - $this->brokenLinks;
    }

    public function hasErrors(): bool
    {
        return $this->brokenLinks > 0;
    }

    public function toArray(): array
    {
        return [
            'total_links' => $this->totalLinks,
            'broken_links' => $this->brokenLinks,
            'success_rate' => $this->successRate,
            'status_breakdown' => array_map(
                fn(LinkStatus $status, int $count) => [$status->value => $count],
                array_keys($this->statusBreakdown),
                $this->statusBreakdown
            ),
            'execution_time' => $this->executionTime,
            'files_processed' => $this->filesProcessed,
        ];
    }
}
```

---

**Cross-References:**
- [Current State Analysis](010-dto-service-layer-analysis.md) - Analysis of existing patterns
- [Impact Analysis](030-dto-impact-analysis.md) - Benefits and recommendations  
- [Implementation Roadmap](040-dto-implementation-roadmap.md) - Step-by-step implementation
