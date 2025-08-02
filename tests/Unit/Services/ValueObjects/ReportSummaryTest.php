<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ReportSummary;

describe('ReportSummary', function () {
    it('creates valid summary with all parameters', function () {
        $statusBreakdown = [
            LinkStatus::VALID->value => 95,
            LinkStatus::BROKEN->value => 5,
        ];

        $summary = new ReportSummary(
            totalLinks: 100,
            brokenLinks: 5,
            successRate: 95.0,
            statusBreakdown: $statusBreakdown,
            executionTime: 2.5,
            filesProcessed: 10
        );

        expect($summary->totalLinks)->toBe(100);
        expect($summary->brokenLinks)->toBe(5);
        expect($summary->successRate)->toBe(95.0);
        expect($summary->statusBreakdown)->toBe($statusBreakdown);
        expect($summary->executionTime)->toBe(2.5);
        expect($summary->filesProcessed)->toBe(10);
    });

    it('creates summary from array data', function () {
        $data = [
            'total_links' => 100,
            'broken_links' => 5,
            'success_rate' => 95.0,
            'status_breakdown' => [
                'valid' => 95,
                'broken' => 5,
            ],
            'execution_time' => 2.5,
            'files_processed' => 10,
        ];

        $summary = ReportSummary::create($data);

        expect($summary->totalLinks)->toBe(100);
        expect($summary->brokenLinks)->toBe(5);
        expect($summary->successRate)->toBe(95.0);
        expect($summary->statusBreakdown)->toBe([
            LinkStatus::VALID->value => 95,
            LinkStatus::BROKEN->value => 5,
        ]);
        expect($summary->executionTime)->toBe(2.5);
        expect($summary->filesProcessed)->toBe(10);
    });

    it('creates summary with default values when data is missing', function () {
        $summary = ReportSummary::create([]);

        expect($summary->totalLinks)->toBe(0);
        expect($summary->brokenLinks)->toBe(0);
        expect($summary->successRate)->toBe(0.0);
        expect($summary->statusBreakdown)->toBe([]);
        expect($summary->executionTime)->toBe(0.0);
        expect($summary->filesProcessed)->toBe(0);
    });

    it('skips invalid status values in breakdown', function () {
        $data = [
            'status_breakdown' => [
                'valid' => 95,
                'invalid_status' => 10,
                'broken' => 5,
            ],
        ];

        $summary = ReportSummary::create($data);

        expect($summary->statusBreakdown)->toBe([
            LinkStatus::VALID->value => 95,
            LinkStatus::BROKEN->value => 5,
        ]);
    });

    it('validates negative total links', function () {
        expect(fn () => new ReportSummary(
            totalLinks: -1,
            brokenLinks: 0,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class, 'Total links must be non-negative');
    });

    it('validates negative broken links', function () {
        expect(fn () => new ReportSummary(
            totalLinks: 10,
            brokenLinks: -1,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class, 'Broken links must be non-negative');
    });

    it('validates broken links exceeding total', function () {
        expect(fn () => new ReportSummary(
            totalLinks: 5,
            brokenLinks: 10,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class, 'Broken links cannot exceed total links');
    });

    it('validates success rate below zero', function () {
        expect(fn () => new ReportSummary(
            totalLinks: 10,
            brokenLinks: 0,
            successRate: -1.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class, 'Success rate must be between 0 and 100');
    });

    it('validates success rate above 100', function () {
        expect(fn () => new ReportSummary(
            totalLinks: 10,
            brokenLinks: 0,
            successRate: 101.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class, 'Success rate must be between 0 and 100');
    });

    it('validates negative execution time', function () {
        expect(fn () => new ReportSummary(
            totalLinks: 10,
            brokenLinks: 0,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: -1.0,
            filesProcessed: 0
        ))->toThrow(InvalidArgumentException::class, 'Execution time must be non-negative');
    });

    it('validates negative files processed', function () {
        expect(fn () => new ReportSummary(
            totalLinks: 10,
            brokenLinks: 0,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: -1
        ))->toThrow(InvalidArgumentException::class, 'Files processed must be non-negative');
    });

    it('calculates valid links correctly', function () {
        $summary = new ReportSummary(
            totalLinks: 100,
            brokenLinks: 15,
            successRate: 85.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        );

        expect($summary->getValidLinks())->toBe(85);
    });

    it('detects when there are errors', function () {
        $summaryWithErrors = new ReportSummary(
            totalLinks: 100,
            brokenLinks: 5,
            successRate: 95.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        );

        $summaryWithoutErrors = new ReportSummary(
            totalLinks: 100,
            brokenLinks: 0,
            successRate: 100.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        );

        expect($summaryWithErrors->hasErrors())->toBeTrue();
        expect($summaryWithoutErrors->hasErrors())->toBeFalse();
    });

    it('converts to array correctly', function () {
        $statusBreakdown = [
            LinkStatus::VALID->value => 95,
            LinkStatus::BROKEN->value => 5,
        ];

        $summary = new ReportSummary(
            totalLinks: 100,
            brokenLinks: 5,
            successRate: 95.0,
            statusBreakdown: $statusBreakdown,
            executionTime: 2.5,
            filesProcessed: 10
        );

        $expected = [
            'total_links' => 100,
            'broken_links' => 5,
            'success_rate' => 95.0,
            'status_breakdown' => $statusBreakdown,
            'execution_time' => 2.5,
            'files_processed' => 10,
        ];

        expect($summary->toArray())->toBe($expected);
    });

    it('handles empty status breakdown in toArray', function () {
        $summary = new ReportSummary(
            totalLinks: 0,
            brokenLinks: 0,
            successRate: 0.0,
            statusBreakdown: [],
            executionTime: 0.0,
            filesProcessed: 0
        );

        $result = $summary->toArray();

        expect($result['status_breakdown'])->toBe([]);
    });
});
