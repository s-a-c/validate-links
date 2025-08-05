<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ReportSummary;

describe('ReportSummary', function () {
    beforeEach(function () {
        // deterministic, no external I/O
    });

    afterEach(function () {
        // no teardown needed
    });

    it('it_should_create_from_array_with_validation_and_accessors', function () {
        $summary = ReportSummary::create([
            'total_links' => 10,
            'broken_links' => 3,
            'success_rate' => 70.0,
            'status_breakdown' => [
                'valid' => 7,
                'not_found' => 2,
                'forbidden' => 1,
            ],
            'execution_time' => 1.234,
            'files_processed' => 4,
        ]);

        expect($summary->getTotalLinks())->toBe(10)
            ->and($summary->getBrokenLinks())->toBe(3)
            ->and($summary->getSuccessRate())->toBe(70.0)
            ->and($summary->hasErrors())->toBeTrue();

        $arr = $summary->toArray();
        expect($arr)->toHaveKeys(['total_links', 'broken_links', 'success_rate', 'status_breakdown', 'execution_time', 'files_processed']);
        expect($arr['status_breakdown'])->toHaveKeys(['valid', 'not_found', 'forbidden']);
    });

    it('it_should_ignore_invalid_status_keys_in_breakdown', function () {
        $summary = ReportSummary::create([
            'total_links' => 2,
            'broken_links' => 1,
            'success_rate' => 50.0,
            'status_breakdown' => [
                'valid' => 1,
                'not_found' => 1,
                'INVALID_STATUS' => 99, // should be ignored
            ],
            'execution_time' => 0.1,
            'files_processed' => 1,
        ]);

        expect(array_keys($summary->toArray()['status_breakdown']))
            ->not->toContain('INVALID_STATUS');
    });

    it('it_should_throw_on_inconsistent_values', function () {
        // broken > total
        expect(fn () => new ReportSummary(
            totalLinks: 1,
            brokenLinks: 2,
            successRate: 50.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class);

        // negative values
        expect(fn () => new ReportSummary(
            totalLinks: -1,
            brokenLinks: 0,
            successRate: 50.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class);

        // success rate out of range
        expect(fn () => new ReportSummary(
            totalLinks: 1,
            brokenLinks: 0,
            successRate: 1000.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class);
    });
});