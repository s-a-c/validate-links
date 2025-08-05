<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

/**
 * Value object representing a complete validation summary for a file.
 *
 * This class combines validation results, statistics, and file information
 * to provide a comprehensive overview of validation outcomes for a specific file.
 */
final readonly class ValidationSummary
{
    /**
     * @param string $filePath Path to the validated file
     * @param ValidationResultCollection $results Collection of validation results
     * @param ValidationStatistics $statistics Aggregated statistics for the validation
     */
    public function __construct(
        private string $filePath,
        private ValidationResultCollection $results,
        private ValidationStatistics $statistics
    ) {}

    /**
     * Create a new ValidationSummary instance.
     *
     * @param string $filePath Path to the validated file
     * @param ValidationResultCollection $results Collection of validation results
     * @param ValidationStatistics $statistics Aggregated statistics for the validation
     * @return self New ValidationSummary instance
     */
    public static function create(
        string $filePath,
        ValidationResultCollection $results,
        ValidationStatistics $statistics
    ): self {
        return new self(
            filePath: $filePath,
            results: $results,
            statistics: $statistics
        );
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getResults(): ValidationResultCollection
    {
        return $this->results;
    }

    public function getStatistics(): ValidationStatistics
    {
        return $this->statistics;
    }

    /**
     * Check if the validation is considered valid (no issues found).
     *
     * @return bool True if no broken links, timeouts, or security risks were found
     */
    public function isValid(): bool
    {
        return ! $this->statistics->hasIssues();
    }

    /**
     * Get the number of broken links found during validation.
     *
     * @return int Number of broken links
     */
    public function getBrokenCount(): int
    {
        return $this->statistics->getBroken();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file_path' => $this->filePath,
            'valid' => $this->isValid(),
            'broken_count' => $this->getBrokenCount(),
            'statistics' => [
                'total' => $this->statistics->getTotal(),
                'valid' => $this->statistics->getValid(),
                'broken' => $this->statistics->getBroken(),
                'timeouts' => $this->statistics->getTimeouts(),
                'security_risks' => $this->statistics->getSecurityRisks(),
                'redirects' => $this->statistics->getRedirects(),
                'success_rate' => $this->statistics->getSuccessRate(),
            ],
        ];
    }
}
