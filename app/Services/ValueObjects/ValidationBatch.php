<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

/**
 * Value object representing a batch of links to be validated together.
 *
 * This class groups links with their validation configuration and metadata
 * for efficient batch processing during concurrent validation operations.
 */
final readonly class ValidationBatch
{
    /**
     * @param LinkExtractionResult[] $links Array of links to validate in this batch
     * @param ValidationConfig $config Configuration for validation processing
     * @param int $batchId Unique identifier for this batch
     * @param int $priority Processing priority (higher numbers = higher priority)
     */
    public function __construct(
        public array $links,
        public ValidationConfig $config,
        public int $batchId,
        public int $priority = 0
    ) {
        $this->validate();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function create(array $data): self
    {
        $links = [];
        $rawLinks = $data['links'] ?? [];

        if (is_array($rawLinks)) {
            foreach ($rawLinks as $linkData) {
                if ($linkData instanceof LinkExtractionResult) {
                    $links[] = $linkData;
                } elseif (is_array($linkData)) {
                    /** @var array<string, mixed> $linkData */
                    $links[] = LinkExtractionResult::create($linkData);
                }
            }
        }

        $config = $data['config'] ?? null;
        if (! $config instanceof ValidationConfig) {
            throw new InvalidArgumentException('Config must be a ValidationConfig instance');
        }

        return new self(
            links: $links,
            config: $config,
            batchId: is_numeric($data['batch_id'] ?? null) ? (int) $data['batch_id'] : 0,
            priority: is_numeric($data['priority'] ?? null) ? (int) $data['priority'] : 0
        );
    }

    /**
     * Get the number of links in this batch.
     *
     * @return int Number of links to be validated
     */
    public function getLinkCount(): int
    {
        return count($this->links);
    }

    /**
     * Extract all URLs from the links in this batch.
     *
     * @return string[] Array of URLs to be validated
     */
    public function getUrls(): array
    {
        return array_map(fn (LinkExtractionResult $link) => $link->url, $this->links);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'links' => array_map(fn (LinkExtractionResult $link) => $link->toArray(), $this->links),
            'config' => $this->config->toArray(),
            'batch_id' => $this->batchId,
            'priority' => $this->priority,
            'link_count' => $this->getLinkCount(),
        ];
    }

    /**
     * Validate the batch data for consistency and completeness.
     *
     * @throws InvalidArgumentException If batch is empty, contains invalid links, or has invalid ID
     */
    private function validate(): void
    {
        if (empty($this->links)) {
            throw new InvalidArgumentException('Batch must contain at least one link');
        }

        foreach ($this->links as $link) {
            if (! $link instanceof LinkExtractionResult) {
                throw new InvalidArgumentException('All links must be LinkExtractionResult instances');
            }
        }

        if ($this->batchId < 0) {
            throw new InvalidArgumentException('Batch ID must be non-negative');
        }
    }
}
