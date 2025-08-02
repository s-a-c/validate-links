<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use App\Services\ValueObjects\ValidationSummary;

describe('ValidationSummary', function () {
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

        $this->results = new ValidationResultCollection([
            $this->validResult,
            $this->brokenResult,
        ]);

        $this->statistics = ValidationStatistics::fromCounts(
            total: 2,
            valid: 1,
            broken: 1,
            timeouts: 0,
            securityRisks: 0,
            redirects: 0
        );

        $this->summary = ValidationSummary::create(
            filePath: '/path/to/test.md',
            results: $this->results,
            statistics: $this->statistics
        );
    });

    it('creates summary with factory method', function () {
        expect($this->summary)->toBeInstanceOf(ValidationSummary::class);
    });

    it('provides access to file path', function () {
        expect($this->summary->getFilePath())->toBe('/path/to/test.md');
    });

    it('provides access to results collection', function () {
        expect($this->summary->getResults())->toBe($this->results)
            ->and($this->summary->getResults()->count())->toBe(2);
    });

    it('provides access to statistics', function () {
        expect($this->summary->getStatistics())->toBe($this->statistics)
            ->and($this->summary->getStatistics()->getTotal())->toBe(2);
    });

    it('determines validity based on statistics', function () {
        expect($this->summary->isValid())->toBeFalse(); // Has broken links
    });

    it('provides broken count from statistics', function () {
        expect($this->summary->getBrokenCount())->toBe(1);
    });

    it('converts to array format', function () {
        $array = $this->summary->toArray();

        expect($array)->toBeArray()
            ->and($array['file_path'])->toBe('/path/to/test.md')
            ->and($array['valid'])->toBeFalse()
            ->and($array['broken_count'])->toBe(1)
            ->and($array['statistics'])->toBeArray()
            ->and($array['statistics']['total'])->toBe(2)
            ->and($array['statistics']['valid'])->toBe(1)
            ->and($array['statistics']['broken'])->toBe(1);
    });

    it('shows valid when no issues exist', function () {
        $validStatistics = ValidationStatistics::fromCounts(
            total: 2,
            valid: 2,
            broken: 0,
            timeouts: 0,
            securityRisks: 0,
            redirects: 0
        );

        $validSummary = ValidationSummary::create(
            filePath: '/path/to/valid.md',
            results: new ValidationResultCollection([$this->validResult, $this->validResult]),
            statistics: $validStatistics
        );

        expect($validSummary->isValid())->toBeTrue()
            ->and($validSummary->getBrokenCount())->toBe(0);
    });

    it('is readonly and immutable', function () {
        $reflection = new ReflectionClass(ValidationSummary::class);

        expect($reflection->isReadOnly())->toBeTrue();
    });
});
