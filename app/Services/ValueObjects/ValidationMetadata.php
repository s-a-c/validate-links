<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\ArrayKey;
use InvalidArgumentException;

/**
 * Value object representing validation metadata with structured data.
 *
 * This class replaces generic array metadata with strongly typed properties
 * for better type safety and IDE support.
 */
final readonly class ValidationMetadata
{
    /**
     * @param int $totalResults Total number of validation results
     * @param int $brokenCount Number of broken/failed validations
     * @param ValidationResultCollection $results Collection of validation results
     * @param float $duration Total execution time in seconds
     * @param array<string, mixed> $summary Summary statistics
     * @param array<string> $files List of processed files
     */
    public function __construct(
        public int $totalResults = 0,
        public int $brokenCount = 0,
        public ValidationResultCollection $results = new ValidationResultCollection(),
        public float $duration = 0.0,
        public array $summary = [],
        public array $files = [],
    ) {
        $this->validate();
    }

    /**
     * Create ValidationMetadata from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $totalResults = is_numeric($data[ArrayKey::TOTAL_LINKS->value] ?? null)
            ? (int) $data[ArrayKey::TOTAL_LINKS->value]
            : 0;

        $brokenCount = is_numeric($data[ArrayKey::BROKEN_LINKS->value] ?? null)
            ? (int) $data[ArrayKey::BROKEN_LINKS->value]
            : 0;

        $results = isset($data[ArrayKey::RESULTS->value]) && is_array($data[ArrayKey::RESULTS->value])
            ? ValidationResultCollection::fromArray($data[ArrayKey::RESULTS->value])
            : new ValidationResultCollection();

        $duration = isset($data[ArrayKey::DURATION->value]) && (is_float($data[ArrayKey::DURATION->value]) || is_int($data[ArrayKey::DURATION->value]))
            ? (float) $data[ArrayKey::DURATION->value]
            : 0.0;

        $summaryData = $data[ArrayKey::SUMMARY->value] ?? [];
        /** @var array<string, mixed> $summary */
        $summary = is_array($summaryData) ? $summaryData : [];

        $files = isset($data['files']) && is_array($data['files'])
            ? array_filter($data['files'], 'is_string')
            : [];

        return new self(
            totalResults: $totalResults,
            brokenCount: $brokenCount,
            results: $results,
            duration: $duration,
            summary: $summary,
            files: $files,
        );
    }

    /**
     * Check if there are any broken links.
     */
    public function hasBrokenLinks(): bool
    {
        return $this->brokenCount > 0;
    }

    /**
     * Get success rate as percentage.
     */
    public function getSuccessRate(): float
    {
        if ($this->totalResults === 0) {
            return 0.0;
        }

        return round((($this->totalResults - $this->brokenCount) / $this->totalResults) * 100, 2);
    }

    /**
     * Get broken links by type.
     *
     * @return array<string, array<array{url: string, file: string, reason: string}>>
     */
    public function getBrokenLinksByType(): array
    {
        $brokenByType = [];

        foreach ($this->results as $result) {
            if (! $result->isValid()) {
                $type = $result->getScope()->value;
                if (! isset($brokenByType[$type])) {
                    $brokenByType[$type] = [];
                }
                $brokenByType[$type][] = [
                    ArrayKey::URL->value => $result->getUrl(),
                    'file' => $result->getFilePath() ?? 'unknown',
                    ArrayKey::REASON->value => $result->getError() ?? 'Validation failed',
                ];
            }
        }

        return $brokenByType;
    }

    /**
     * Get all broken links as simple array.
     *
     * @return array<array{url: string, file: string, reason: string}>
     */
    public function getBrokenLinksArray(): array
    {
        $broken = [];

        foreach ($this->results as $result) {
            if (! $result->isValid()) {
                $broken[] = [
                    ArrayKey::URL->value => $result->getUrl(),
                    'file' => $result->getFilePath() ?? 'unknown',
                    ArrayKey::REASON->value => $result->getError() ?? 'Validation failed',
                ];
            }
        }

        return $broken;
    }

    /**
     * Get all links with their status.
     *
     * @return array<array{url: string, status: string, scope: string, is_valid: bool, response_time: float}>
     */
    public function getLinksArray(): array
    {
        $links = [];

        foreach ($this->results as $result) {
            $links[] = [
                ArrayKey::URL->value => $result->getUrl(),
                ArrayKey::STATUS->value => $result->getStatus()->value,
                ArrayKey::SCOPE->value => $result->getScope()->value,
                'is_valid' => $result->isValid(),
                'response_time' => $result->getResponseTime(),
            ];
        }

        return $links;
    }

    /**
     * Get summary data.
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        return $this->summary;
    }

    /**
     * Get broken count.
     */
    public function getBrokenCount(): int
    {
        return $this->brokenCount;
    }

    /**
     * Get total results.
     */
    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    /**
     * Get files array.
     *
     * @return array<string>
     */
    public function getFilesArray(): array
    {
        return $this->files;
    }

    /**
     * Get duration.
     */
    public function getDuration(): float
    {
        return $this->duration;
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            ArrayKey::TOTAL_LINKS->value => $this->totalResults,
            ArrayKey::BROKEN_LINKS->value => $this->brokenCount,
            ArrayKey::RESULTS->value => $this->results->toValidationResultArray(),
            ArrayKey::DURATION->value => $this->duration,
            ArrayKey::SUMMARY->value => $this->summary,
            'files' => $this->files,
        ];
    }

    /**
     * Validate the metadata values.
     */
    private function validate(): void
    {
        if ($this->totalResults < 0) {
            throw new InvalidArgumentException('Total results must be non-negative');
        }

        if ($this->brokenCount < 0) {
            throw new InvalidArgumentException('Broken count must be non-negative');
        }

        if ($this->brokenCount > $this->totalResults) {
            throw new InvalidArgumentException('Broken count cannot exceed total results');
        }

        if ($this->duration < 0.0) {
            throw new InvalidArgumentException('Duration must be non-negative');
        }
    }
}
