<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing validation statistics for link validation results.
 *
 * This class provides a comprehensive summary of validation outcomes including
 * counts for different types of link statuses and calculated success rates.
 */
final readonly class ValidationStatistics
{
    /**
     * @param int $total Total number of links validated
     * @param int $valid Number of valid links
     * @param int $broken Number of broken links
     * @param int $timeouts Number of links that timed out
     * @param int $securityRisks Number of links with security risks
     * @param int $redirects Number of redirected links
     * @param float $successRate Success rate as a percentage (0.0-100.0)
     */
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

    /**
     * Create ValidationStatistics from individual counts.
     *
     * Automatically calculates the success rate based on valid and redirect counts.
     *
     * @param int $total Total number of links validated
     * @param int $valid Number of valid links
     * @param int $broken Number of broken links
     * @param int $timeouts Number of links that timed out
     * @param int $securityRisks Number of links with security risks
     * @param int $redirects Number of redirected links (considered successful)
     * @return self New ValidationStatistics instance
     */
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

    public function getValid(): int
    {
        return $this->valid;
    }

    public function getBroken(): int
    {
        return $this->broken;
    }

    public function getTimeouts(): int
    {
        return $this->timeouts;
    }

    public function getSecurityRisks(): int
    {
        return $this->securityRisks;
    }

    public function getRedirects(): int
    {
        return $this->redirects;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    /**
     * Check if there are any validation issues.
     *
     * @return bool True if there are broken links, timeouts, or security risks
     */
    public function hasIssues(): bool
    {
        return $this->broken > 0 || $this->timeouts > 0 || $this->securityRisks > 0;
    }

    /**
     * Validate the statistics data for consistency.
     *
     * @throws InvalidArgumentException If statistics are invalid or inconsistent
     */
    private function validateStatistics(): void
    {
        if ($this->total < 0) {
            throw new InvalidArgumentException('Total cannot be negative');
        }

        if ($this->valid + $this->broken + $this->timeouts + $this->securityRisks + $this->redirects > $this->total) {
            throw new InvalidArgumentException('Sum of individual counts cannot exceed total');
        }
    }
}
