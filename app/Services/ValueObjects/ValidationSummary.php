<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

final readonly class ValidationSummary
{
    public function __construct(
        private string $filePath,
        private ValidationResultCollection $results,
        private ValidationStatistics $statistics
    ) {}

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

    public function isValid(): bool
    {
        return ! $this->statistics->hasIssues();
    }

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
