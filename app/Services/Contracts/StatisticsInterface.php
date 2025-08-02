<?php

declare(strict_types=1);

namespace App\Services\Contracts;

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
     * Get validation statistics.
     *
     * @return array<string, mixed>
     */
    public function getStatistics(): array;

    /**
     * Get broken links.
     *
     * @return array<string, mixed>
     */
    public function getBrokenLinks(): array;

    /**
     * Get processed files.
     *
     * @return array<string, mixed>
     */
    public function getProcessedFiles(): array;

    /**
     * Reset statistics.
     */
    public function reset(): void;

    /**
     * Get success rate.
     */
    public function getSuccessRate(): float;
}
