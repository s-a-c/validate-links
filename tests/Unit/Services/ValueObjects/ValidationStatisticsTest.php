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
            total: 60,
            valid: 40,
            broken: 5,
            timeouts: 3,
            securityRisks: 2,
            redirects: 10
        );

        expect($stats->getSuccessRate())->toBe(83.33333333333334); // (40 + 10) / 60 * 100 ≈ 83.33
    });

    it('validates statistics on construction', function () {
        expect(fn () => ValidationStatistics::fromCounts(
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

    it('provides access to all count values', function () {
        $stats = ValidationStatistics::fromCounts(
            total: 100,
            valid: 70,
            broken: 20,
            timeouts: 5,
            securityRisks: 3,
            redirects: 2
        );

        expect($stats->getTotal())->toBe(100)
            ->and($stats->getValid())->toBe(70)
            ->and($stats->getBroken())->toBe(20)
            ->and($stats->getTimeouts())->toBe(5)
            ->and($stats->getSecurityRisks())->toBe(3)
            ->and($stats->getRedirects())->toBe(2);
    });

    it('validates that sum of counts does not exceed total', function () {
        expect(fn () => ValidationStatistics::fromCounts(
            total: 10,
            valid: 5,
            broken: 4,
            timeouts: 3,
            securityRisks: 2,
            redirects: 1
        ))->toThrow(InvalidArgumentException::class, 'Sum of individual counts cannot exceed total');
    });
});
