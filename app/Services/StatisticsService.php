<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\StatisticsInterface;
use App\Services\ValueObjects\ValidationStatistics;

final class StatisticsService implements StatisticsInterface
{
    /**
     * @var array{total: int, valid: int, invalid: int, files_processed: int, total_links: int, start_time: string}
     */
    private array $statistics;

    /**
     * @var array<array{url: string, error: string|null}>
     */
    private array $brokenLinks;

    /**
     * @var array<array{file: string, link_count: int, timestamp: string}>
     */
    private array $processedFiles;

    public function __construct()
    {
        $this->reset();
    }

    /**
     * Record a validation result.
     */
    public function recordValidation(string $url, bool $isValid, ?string $error = null): void
    {
        $this->statistics['total']++;

        if ($isValid) {
            $this->statistics['valid']++;
        } else {
            $this->statistics['invalid']++;
            $this->brokenLinks[] = [
                'url' => $url,
                'error' => $error,
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    /**
     * Record file processing.
     */
    public function recordFileProcessed(string $filePath, int $linkCount): void
    {
        $this->processedFiles[] = [
            'file' => $filePath,
            'link_count' => $linkCount,
            'timestamp' => now()->toISOString() ?? '',
        ];

        $this->statistics['files_processed']++;
        $this->statistics['total_links'] += $linkCount;
    }

    /**
     * Get validation statistics.
     */
    public function getStatistics(): array
    {
        return array_merge($this->statistics, [
            'success_rate' => $this->getSuccessRate(),
            'broken_links_count' => count($this->brokenLinks),
            'processed_files_count' => count($this->processedFiles),
        ]);
    }

    /**
     * Get broken links.
     *
     * @return array<string, mixed>
     */
    public function getBrokenLinks(): array
    {
        return ['broken_links' => $this->brokenLinks];
    }

    /**
     * Get processed files.
     *
     * @return array<string, mixed>
     */
    public function getProcessedFiles(): array
    {
        return ['processed_files' => $this->processedFiles];
    }

    /**
     * Reset statistics.
     */
    public function reset(): void
    {
        $this->statistics = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'files_processed' => 0,
            'total_links' => 0,
            'start_time' => now()->toISOString() ?? '',
        ];

        $this->brokenLinks = [];
        $this->processedFiles = [];
    }

    /**
     * Get success rate.
     */
    public function getSuccessRate(): float
    {
        if ($this->statistics['total'] === 0) {
            return 0.0;
        }

        return round(($this->statistics['valid'] / $this->statistics['total']) * 100, 2);
    }

    /**
     * Get status statistics as ValidationStatistics DTO.
     */
    public function getStatusStatistics(): ValidationStatistics
    {
        return ValidationStatistics::fromCounts(
            total: $this->statistics['total'],
            valid: $this->statistics['valid'],
            broken: $this->statistics['invalid'],
            timeouts: 0, // Not currently tracked separately
            securityRisks: 0, // Not currently tracked separately
            redirects: 0 // Not currently tracked
        );
    }
}
