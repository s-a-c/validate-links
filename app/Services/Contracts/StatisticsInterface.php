<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Services\ValueObjects\BrokenLinksCollection;
use App\Services\ValueObjects\ProcessedFilesCollection;
use App\Services\ValueObjects\ValidationStatistics;

interface StatisticsInterface
{
    /**
     * Record a validation result.
     */
    public function recordValidation(string $url, bool $isValid, ?string $error = null): void;

    /**
     * Record file processing.
     */
    public function recordFileProcessed(string $filePath, int $linkCount): void;

    /**
     * Get validation statistics as DTO.
     */
    public function getStatistics(): ValidationStatistics;

    /**
     * Get broken links as collection.
     */
    public function getBrokenLinks(): BrokenLinksCollection;

    /**
     * Get processed files as collection.
     */
    public function getProcessedFiles(): ProcessedFilesCollection;

    /**
     * Reset statistics.
     */
    public function reset(): void;

    /**
     * Get success rate.
     */
    public function getSuccessRate(): float;

    /**
     * Get status statistics as ValidationStatistics DTO.
     */
    public function getStatusStatistics(): ValidationStatistics;
}
