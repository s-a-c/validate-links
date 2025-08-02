<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Contracts;

use App\Services\Contracts\StatisticsInterface;

/**
 * Test implementation of StatisticsInterface for testing purposes.
 *
 * Provides predictable responses for testing statistics functionality.
 */
final class TestStatisticsImplementation implements StatisticsInterface
{
    public function recordValidation(string $url, bool $isValid, ?string $error = null): void
    {
        // Record validation statistics
    }

    public function recordFileProcessed(string $filePath, int $linkCount): void
    {
        // Record file processing
    }

    public function getStatistics(): array
    {
        return [
            'total_validations' => 100,
            'successful_validations' => 85,
            'failed_validations' => 15,
            'files_processed' => 10,
        ];
    }

    public function getBrokenLinks(): array
    {
        return [
            'broken_link_1' => 'http://example.com/broken1',
            'broken_link_2' => 'http://example.com/broken2',
        ];
    }

    public function getProcessedFiles(): array
    {
        return [
            'processed_file_1' => 'file1.md',
            'processed_file_2' => 'file2.md',
        ];
    }

    public function reset(): void
    {
        // Reset all statistics
    }

    public function getSuccessRate(): float
    {
        return 0.85; // 85% success rate
    }
}
