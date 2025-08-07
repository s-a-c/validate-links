<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ArrayKey;
use App\Services\Contracts\StatisticsInterface;
use App\Services\ValueObjects\BrokenLinksCollection;
use App\Services\ValueObjects\ProcessedFilesCollection;
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
        $this->statistics[ArrayKey::TOTAL->value]++;

        if ($isValid) {
            $this->statistics[ArrayKey::VALID->value]++;
        } else {
            $this->statistics[ArrayKey::INVALID->value]++;
            $this->brokenLinks[] = [
                ArrayKey::URL->value => $url,
                ArrayKey::ERROR->value => $error,
                ArrayKey::TIMESTAMP->value => now()->toISOString(),
            ];
        }
    }

    /**
     * Record file processing.
     */
    public function recordFileProcessed(string $filePath, int $linkCount): void
    {
        $this->processedFiles[] = [
            ArrayKey::FILE->value => $filePath,
            ArrayKey::LINK_COUNT->value => $linkCount,
            ArrayKey::TIMESTAMP->value => now()->toISOString() ?? '',
        ];

        $this->statistics[ArrayKey::FILES_PROCESSED->value]++;
        $this->statistics[ArrayKey::TOTAL_LINKS->value] += $linkCount;
    }

    /**
     * Get validation statistics as DTO.
     */
    public function getStatistics(): ValidationStatistics
    {
        return ValidationStatistics::fromCounts(
            total: $this->statistics[ArrayKey::TOTAL->value],
            valid: $this->statistics[ArrayKey::VALID->value],
            broken: $this->statistics[ArrayKey::INVALID->value],
            timeouts: 0, // Not currently tracked separately
            securityRisks: 0, // Not currently tracked separately
            redirects: 0, // Not currently tracked
        );
    }

    /**
     * Get broken links as collection.
     */
    public function getBrokenLinks(): BrokenLinksCollection
    {
        return BrokenLinksCollection::fromArray($this->brokenLinks);
    }

    /**
     * Get processed files as collection.
     */
    public function getProcessedFiles(): ProcessedFilesCollection
    {
        return ProcessedFilesCollection::fromArray($this->processedFiles);
    }

    /**
     * Reset statistics.
     */
    public function reset(): void
    {
        $this->statistics = [
            ArrayKey::TOTAL->value => 0,
            ArrayKey::VALID->value => 0,
            ArrayKey::INVALID->value => 0,
            ArrayKey::FILES_PROCESSED->value => 0,
            ArrayKey::TOTAL_LINKS->value => 0,
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
        if ($this->statistics[ArrayKey::TOTAL->value] === 0) {
            return 0.0;
        }

        return round(($this->statistics[ArrayKey::VALID->value] / $this->statistics[ArrayKey::TOTAL->value]) * 100, 2);
    }

    /**
     * Get status statistics as ValidationStatistics DTO.
     */
    public function getStatusStatistics(): ValidationStatistics
    {
        return ValidationStatistics::fromCounts(
            total: $this->statistics[ArrayKey::TOTAL->value],
            valid: $this->statistics[ArrayKey::VALID->value],
            broken: $this->statistics[ArrayKey::INVALID->value],
            timeouts: 0, // Not currently tracked separately
            securityRisks: 0, // Not currently tracked separately
            redirects: 0, // Not currently tracked
        );
    }
}
