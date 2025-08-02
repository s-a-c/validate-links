# 4. DTO Implementation Roadmap

<details>
<summary>Table of Contents</summary>

- [4. DTO Implementation Roadmap](#4-dto-implementation-roadmap)
  - [4.1. Overview](#41-overview)
  - [4.2. Phase 1: Core DTOs](#42-phase-1-core-dtos)
  - [4.3. Phase 2: Service Integration](#43-phase-2-service-integration)
  - [4.4. Phase 3: Specialized DTOs](#44-phase-3-specialized-dtos)
  - [4.5. Testing Strategy](#45-testing-strategy)
  - [4.6. Migration Guidelines](#46-migration-guidelines)

</details>

## 4.1. Overview

This document provides a step-by-step implementation roadmap for DTO integration in the validate-links service layer. Based on the analysis and recommendations from previous documents, this roadmap follows the recommended phased migration approach with detailed implementation steps.

**Implementation Timeline:** 9 weeks total
**Estimated Effort:** 66 hours (48 development + 18 testing)
**Target Type Safety Improvement:** 45/100 → 85/100

## 4.2. Phase 1: Core DTOs (Weeks 1-4)

### 4.2.1. ReportSummary DTO Implementation

**Files to Create:**
- `app/Services/ValueObjects/ReportSummary.php`

**Files to Modify:**
- `app/Services/ReportingService.php`
- `app/Services/Contracts/ReportingInterface.php`
- All formatter classes in `app/Services/Formatters/`

**Complete ReportSummary Class:**
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

**Service Method Changes:**
```php
// ReportingService.php - Update generateSummary method
public function generateSummary(array $results): ReportSummary
{
    $total = count($results);
    $broken = 0;
    $statusBreakdown = [];

    foreach ($results as $result) {
        if ($result instanceof ValidationResult) {
            $status = $result->getStatus();
            $statusBreakdown[$status] = ($statusBreakdown[$status] ?? 0) + 1;

            if (!$result->isValid()) {
                $broken++;
            }
        }
    }

    return ReportSummary::create([
        'total_links' => $total,
        'broken_links' => $broken,
        'success_rate' => $total > 0 ? round((($total - $broken) / $total) * 100, 2) : 0.0,
        'status_breakdown' => $statusBreakdown,
        'execution_time' => 0.0, // Will be calculated elsewhere
        'files_processed' => 1, // Will be calculated elsewhere
    ]);
}
```

### 4.2.2. LinkExtractionResult DTO Implementation

**Complete LinkExtractionResult Class:**
```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\ValidationScope;
use InvalidArgumentException;

final readonly class LinkExtractionResult
{
    public function __construct(
        public string $url,
        public string $text,
        public int $lineNumber,
        public ValidationScope $scope,
        public ?string $anchor = null,
        public ?string $filePath = null
    ) {
        $this->validate();
    }

    public static function create(array $data): self
    {
        $url = $data['url'] ?? '';
        if (!is_string($url) || empty($url)) {
            throw new InvalidArgumentException('URL must be a non-empty string');
        }

        $text = $data['text'] ?? '';
        if (!is_string($text)) {
            throw new InvalidArgumentException('Text must be a string');
        }

        $lineNumber = (int) ($data['line'] ?? 0);
        
        $scope = $data['scope'] ?? ValidationScope::ALL;
        if (!$scope instanceof ValidationScope) {
            try {
                $scope = ValidationScope::from((string) $scope);
            } catch (ValueError) {
                $scope = ValidationScope::ALL;
            }
        }

        return new self(
            url: $url,
            text: $text,
            lineNumber: $lineNumber,
            scope: $scope,
            anchor: isset($data['anchor']) ? (string) $data['anchor'] : null,
            filePath: isset($data['file_path']) ? (string) $data['file_path'] : null
        );
    }

    private function validate(): void
    {
        if (empty($this->url)) {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if ($this->lineNumber < 0) {
            throw new InvalidArgumentException('Line number must be non-negative');
        }
    }

    public function isInternal(): bool
    {
        return $this->scope === ValidationScope::INTERNAL;
    }

    public function isExternal(): bool
    {
        return $this->scope === ValidationScope::EXTERNAL;
    }

    public function hasAnchor(): bool
    {
        return $this->anchor !== null;
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'text' => $this->text,
            'line' => $this->lineNumber,
            'scope' => $this->scope->value,
            'anchor' => $this->anchor,
            'file_path' => $this->filePath,
        ];
    }
}
```

## 4.3. Phase 2: Service Integration (Weeks 5-7)

### 4.3.1. ValidationBatch DTO

**Complete ValidationBatch Class:**
```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

final readonly class ValidationBatch
{
    /**
     * @param LinkExtractionResult[] $links
     */
    public function __construct(
        public array $links,
        public ValidationConfig $config,
        public int $batchId,
        public int $priority = 0
    ) {
        $this->validate();
    }

    public static function create(array $data): self
    {
        $links = [];
        $rawLinks = $data['links'] ?? [];
        
        if (is_array($rawLinks)) {
            foreach ($rawLinks as $linkData) {
                if ($linkData instanceof LinkExtractionResult) {
                    $links[] = $linkData;
                } elseif (is_array($linkData)) {
                    $links[] = LinkExtractionResult::create($linkData);
                }
            }
        }

        $config = $data['config'] ?? null;
        if (!$config instanceof ValidationConfig) {
            throw new InvalidArgumentException('Config must be a ValidationConfig instance');
        }

        return new self(
            links: $links,
            config: $config,
            batchId: (int) ($data['batch_id'] ?? 0),
            priority: (int) ($data['priority'] ?? 0)
        );
    }

    private function validate(): void
    {
        if (empty($this->links)) {
            throw new InvalidArgumentException('Batch must contain at least one link');
        }

        foreach ($this->links as $link) {
            if (!$link instanceof LinkExtractionResult) {
                throw new InvalidArgumentException('All links must be LinkExtractionResult instances');
            }
        }

        if ($this->batchId < 0) {
            throw new InvalidArgumentException('Batch ID must be non-negative');
        }
    }

    public function getLinkCount(): int
    {
        return count($this->links);
    }

    public function getUrls(): array
    {
        return array_map(fn(LinkExtractionResult $link) => $link->url, $this->links);
    }
}
```

## 4.4. Phase 3: Specialized DTOs (Weeks 8-9)

### 4.4.1. FormatterOptions DTO

**Complete FormatterOptions Class:**
```php
<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\OutputFormat;
use InvalidArgumentException;

final readonly class FormatterOptions
{
    public function __construct(
        public OutputFormat $format,
        public bool $includeTimestamps = false,
        public bool $colorOutput = true,
        public int $maxLineLength = 120,
        public bool $showProgress = true,
        public array $customOptions = []
    ) {
        $this->validate();
    }

    public static function create(array $data): self
    {
        $format = $data['format'] ?? OutputFormat::CONSOLE;
        if (!$format instanceof OutputFormat) {
            try {
                $format = OutputFormat::from((string) $format);
            } catch (ValueError) {
                $format = OutputFormat::CONSOLE;
            }
        }

        return new self(
            format: $format,
            includeTimestamps: (bool) ($data['include_timestamps'] ?? false),
            colorOutput: (bool) ($data['color_output'] ?? true),
            maxLineLength: (int) ($data['max_line_length'] ?? 120),
            showProgress: (bool) ($data['show_progress'] ?? true),
            customOptions: $data['custom_options'] ?? []
        );
    }

    private function validate(): void
    {
        if ($this->maxLineLength < 40) {
            throw new InvalidArgumentException('Max line length must be at least 40 characters');
        }

        if (!is_array($this->customOptions)) {
            throw new InvalidArgumentException('Custom options must be an array');
        }
    }

    public function withFormat(OutputFormat $format): self
    {
        return new self(
            format: $format,
            includeTimestamps: $this->includeTimestamps,
            colorOutput: $this->colorOutput,
            maxLineLength: $this->maxLineLength,
            showProgress: $this->showProgress,
            customOptions: $this->customOptions
        );
    }
}
```

## 4.5. Testing Strategy

### 4.5.1. Unit Tests for DTOs

**Test Structure:**
```php
// tests/Unit/Services/ValueObjects/ReportSummaryTest.php
<?php

namespace Tests\Unit\Services\ValueObjects;

use App\Services\ValueObjects\ReportSummary;
use App\Enums\LinkStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class ReportSummaryTest extends TestCase
{
    public function test_creates_valid_summary(): void
    {
        $data = [
            'total_links' => 100,
            'broken_links' => 5,
            'success_rate' => 95.0,
            'status_breakdown' => [
                'valid' => 95,
                'broken' => 5
            ],
            'execution_time' => 2.5,
            'files_processed' => 10
        ];

        $summary = ReportSummary::create($data);

        $this->assertEquals(100, $summary->totalLinks);
        $this->assertEquals(5, $summary->brokenLinks);
        $this->assertEquals(95.0, $summary->successRate);
        $this->assertEquals(2.5, $summary->executionTime);
        $this->assertEquals(10, $summary->filesProcessed);
    }

    public function test_validates_negative_total_links(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Total links must be non-negative');

        new ReportSummary(
            totalLinks: -1,
            brokenLinks: 0,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        );
    }

    public function test_validates_broken_links_exceed_total(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Broken links cannot exceed total links');

        new ReportSummary(
            totalLinks: 5,
            brokenLinks: 10,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        );
    }
}
```

### 4.5.2. Integration Tests

**Service Integration Tests:**
```php
// tests/Integration/Services/ReportingServiceTest.php
<?php

namespace Tests\Integration\Services;

use App\Services\ReportingService;
use App\Services\ValueObjects\ReportSummary;
use App\Services\ValueObjects\ValidationResult;
use App\Enums\OutputFormat;
use Tests\TestCase;

class ReportingServiceTest extends TestCase
{
    public function test_generates_summary_dto(): void
    {
        $service = new ReportingService();
        $results = [
            ValidationResult::success('https://example.com', ValidationScope::EXTERNAL),
            ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN)
        ];

        $summary = $service->generateSummary($results);

        $this->assertInstanceOf(ReportSummary::class, $summary);
        $this->assertEquals(2, $summary->totalLinks);
        $this->assertEquals(1, $summary->brokenLinks);
        $this->assertEquals(50.0, $summary->successRate);
    }
}
```

## 4.6. Migration Guidelines

### 4.6.1. Backward Compatibility Strategy

**Adapter Pattern Implementation:**
```php
// Temporary adapter during migration
public function generateSummary(array $results): ReportSummary|array
{
    $summary = $this->createSummaryDto($results);
    
    // Return array for backward compatibility during transition
    if ($this->isLegacyMode()) {
        return $summary->toArray();
    }
    
    return $summary;
}

private function isLegacyMode(): bool
{
    return config('app.dto_migration_mode', false) === 'legacy';
}
```

### 4.6.2. Step-by-Step Migration Process

**Week 1-2: ReportSummary DTO**
1. Create ReportSummary DTO class
2. Update ReportingService to return DTO
3. Update all formatter classes to accept DTO
4. Add backward compatibility adapter
5. Update unit tests
6. Update integration tests

**Week 3-4: LinkExtractionResult DTO**
1. Create LinkExtractionResult DTO class
2. Update LinkValidationService extractLinks method
3. Update all methods that process link arrays
4. Add backward compatibility adapters
5. Update tests

**Weeks 5-7: Service Integration DTOs**
1. Implement ValidationBatch DTO
2. Update ConcurrentValidationService
3. Implement remaining DTOs
4. Update service interactions
5. Comprehensive testing

**Weeks 8-9: Specialized DTOs and Cleanup**
1. Implement FormatterOptions DTO
2. Remove backward compatibility adapters
3. Final testing and validation
4. Documentation updates

---

**Cross-References:**
- [Current State Analysis](010-dto-service-layer-analysis.md) - Baseline analysis
- [Implementation Strategy](020-dto-implementation-strategy.md) - DTO design patterns
- [Impact Analysis](030-dto-impact-analysis.md) - Benefits and recommendations
