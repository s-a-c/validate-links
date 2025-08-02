<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\LinkStatus;
use InvalidArgumentException;
use ValueError;

final readonly class ReportSummary
{
    /**
     * @param  array<string, int>  $statusBreakdown  Array with LinkStatus values as keys
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

    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): self
    {
        $totalLinks = is_numeric($data['total_links'] ?? null) ? (int) $data['total_links'] : 0;
        $brokenLinks = is_numeric($data['broken_links'] ?? null) ? (int) $data['broken_links'] : 0;
        $rawSuccessRate = $data['success_rate'] ?? 0.0;
        $successRate = (is_float($rawSuccessRate) || is_int($rawSuccessRate)) ? (float) $rawSuccessRate : 0.0;

        $rawExecutionTime = $data['execution_time'] ?? 0.0;
        $executionTime = (is_float($rawExecutionTime) || is_int($rawExecutionTime)) ? (float) $rawExecutionTime : 0.0;
        $filesProcessed = is_numeric($data['files_processed'] ?? null) ? (int) $data['files_processed'] : 0;

        // Validate and convert status breakdown
        $statusBreakdown = [];
        $rawBreakdown = $data['status_breakdown'] ?? [];
        if (is_array($rawBreakdown)) {
            foreach ($rawBreakdown as $status => $count) {
                try {
                    $linkStatus = LinkStatus::from((string) $status);
                    $statusBreakdown[$linkStatus->value] = is_int($count) ? $count : 0;
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

    public function getValidLinks(): int
    {
        return $this->totalLinks - $this->brokenLinks;
    }

    public function getTotalLinks(): int
    {
        return $this->totalLinks;
    }

    public function getBrokenLinks(): int
    {
        return $this->brokenLinks;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    public function hasErrors(): bool
    {
        return $this->brokenLinks > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total_links' => $this->totalLinks,
            'broken_links' => $this->brokenLinks,
            'success_rate' => $this->successRate,
            'status_breakdown' => $this->statusBreakdown,
            'execution_time' => $this->executionTime,
            'files_processed' => $this->filesProcessed,
        ];
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
}
