<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

final readonly class ValidationStatistics
{
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

    public function hasIssues(): bool
    {
        return $this->broken > 0 || $this->timeouts > 0 || $this->securityRisks > 0;
    }

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
