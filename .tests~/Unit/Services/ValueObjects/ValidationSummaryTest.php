<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use App\Services\ValueObjects\ValidationSummary;

describe('ValidationSummary', function () {
    /** @var ValidationResult|null */
    $validResult = null;
    /** @var ValidationResult|null */
    $brokenResult = null;
    /** @var ValidationResultCollection|null */
    $results = null;
    /** @var ValidationStatistics|null */
    $statistics = null;
    /** @var ValidationSummary|null */
    $summary = null;

    beforeEach(function () use (&$validResult, &$brokenResult, &$results, &$statistics, &$summary) {
        $validResult = ValidationResult::create(
            url: 'https://example.com',
            status: LinkStatus::VALID,
            message: 'Link is valid'
        );

        $brokenResult = ValidationResult::create(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            message: 'Link is broken'
        );

        $results = new ValidationResultCollection([
            $validResult,
            $brokenResult,
        ]);

        $statistics = ValidationStatistics::fromCounts(
            total: 2,
            valid: 1,
            broken: 1,
            timeouts: 0,
            securityRisks: 0,
            redirects: 0
        );

        $summary = ValidationSummary::create(
            filePath: '/path/to/test.md',
            results: $results,
            statistics: $statistics
        );
    });

    it('creates summary with factory method', function () use (&$summary) {
        expect($summary)->toBeInstanceOf(ValidationSummary::class);
    });

    it('provides access to file path', function () use (&$summary) {
        expect($summary->getFilePath())->toBe('/path/to/test.md');
    });

    it('provides access to results collection', function () use (&$summary, &$results) {
        expect($summary->getResults())->toBe($results)
            ->and($summary->getResults()->count())->toBe(2);
    });

    it('provides access to statistics', function () use (&$summary, &$statistics) {
        expect($summary->getStatistics())->toBe($statistics)
            ->and($summary->getStatistics()->getTotal())->toBe(2);
    });

    it('determines validity based on statistics', function () use (&$summary) {
        expect($summary->isValid())->toBeFalse(); // Has broken links
    });

    it('provides broken count from statistics', function () use (&$summary) {
        expect($summary->getBrokenCount())->toBe(1);
    });

    it('converts to array format', function () use (&$summary) {
        $array = $summary->toArray();

        expect($array)->toBeArray()
            ->and($array['file_path'])->toBe('/path/to/test.md')
            ->and($array['valid'])->toBeFalse()
            ->and($array['broken_count'])->toBe(1)
            ->and($array['statistics'])->toBeArray();
        
        $statisticsArray = $array['statistics'];
        if (is_array($statisticsArray)) {
            expect($statisticsArray['total'])->toBe(2)
                ->and($statisticsArray['valid'])->toBe(1)
                ->and($statisticsArray['broken'])->toBe(1);
        }
    });

    it('shows valid when no issues exist', function () use (&$validResult) {
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
            results: new ValidationResultCollection([$validResult, $validResult]),
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