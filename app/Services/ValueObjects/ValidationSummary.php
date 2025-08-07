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
     * @param ValidationResultCollection $validationResultCollection Collection of validation results
     * @param ValidationStatistics $validationStatistics Aggregated statistics for the validation
     */
    public function __construct(
        private string $filePath,
        private ValidationResultCollection $validationResultCollection,
        private ValidationStatistics $validationStatistics,
    ) {}

    /**
     * Create a new ValidationSummary instance.
     *
     * @param string $filePath Path to the validated file
     * @param ValidationResultCollection $validationResultCollection Collection of validation results
     * @param ValidationStatistics $validationStatistics Aggregated statistics for the validation
     *
     * @return self New ValidationSummary instance
     */
    public static function create(
        string $filePath,
        ValidationResultCollection $validationResultCollection,
        ValidationStatistics $validationStatistics,
    ): self {
        return new self(
            filePath: $filePath,
            validationResultCollection: $validationResultCollection,
            validationStatistics: $validationStatistics,
        );
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getResults(): ValidationResultCollection
    {
        return $this->validationResultCollection;
    }

    public function getStatistics(): ValidationStatistics
    {
        return $this->validationStatistics;
    }

    /**
     * Check if the validation is considered valid (no issues found).
     *
     * @return bool True if no broken links, timeouts, or security risks were found
     */
    public function isValid(): bool
    {
        return ! $this->validationStatistics->hasIssues();
    }

    /**
     * Get the number of broken links found during validation.
     *
     * @return int Number of broken links
     */
    public function getBrokenCount(): int
    {
        return $this->validationStatistics->getBroken();
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
                'total' => $this->validationStatistics->getTotal(),
                'valid' => $this->validationStatistics->getValid(),
                'broken' => $this->validationStatistics->getBroken(),
                'timeouts' => $this->validationStatistics->getTimeouts(),
                'security_risks' => $this->validationStatistics->getSecurityRisks(),
                'redirects' => $this->validationStatistics->getRedirects(),
                'success_rate' => $this->validationStatistics->getSuccessRate(),
            ],
        ];
    }
}
