<?php

declare(strict_types=1);

namespace Tests\Unit\Services\ValueObjects;

use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationSummary;
use Tests\TestCase;

abstract class ValueObjectTestCase extends TestCase
{
    protected ValidationResult $validResult;

    protected ValidationResult $brokenResult;

    protected ValidationResultCollection $collection;

    protected ValidationSummary $summary;

    protected ValidationConfig $mockConfig;

    /** @var array<string, mixed> */
    protected array $mockLinks;

    /** @var array<ValidationResult> */
    protected array $results;

    /** @var array<string, int> */
    protected array $statistics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validResult = ValidationResult::create(
            url: 'https://example.com',
            status: \App\Enums\LinkStatus::VALID,
            message: 'Link is valid',
            scope: \App\Enums\ValidationScope::EXTERNAL,
            httpStatusCode: 200,
            responseTime: 0.5,
            metadata: []
        );

        $this->brokenResult = ValidationResult::create(
            url: 'https://broken.com',
            status: \App\Enums\LinkStatus::BROKEN,
            message: 'Connection timeout',
            scope: \App\Enums\ValidationScope::EXTERNAL,
            httpStatusCode: null,
            responseTime: 5.0,
            metadata: []
        );

        $this->collection = new ValidationResultCollection([
            $this->validResult,
            $this->brokenResult,
        ]);

        // Initialize other properties...
    }
}
