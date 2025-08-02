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

    protected array $mockLinks;

    protected array $results;

    protected array $statistics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validResult = ValidationResult::create([
            'url' => 'https://example.com',
            'status' => 'success',
            'scope' => 'external',
            'error' => null,
            'http_status_code' => 200,
            'redirect_url' => null,
            'response_time' => 0.5,
            'metadata' => [],
        ]);

        $this->brokenResult = ValidationResult::create([
            'url' => 'https://broken.com',
            'status' => 'failed',
            'scope' => 'external',
            'error' => 'Connection timeout',
            'http_status_code' => null,
            'redirect_url' => null,
            'response_time' => 5.0,
            'metadata' => [],
        ]);

        $this->collection = new ValidationResultCollection([
            $this->validResult,
            $this->brokenResult,
        ]);

        // Initialize other properties...
    }
}
