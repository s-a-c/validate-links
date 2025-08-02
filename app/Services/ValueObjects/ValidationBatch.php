<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use InvalidArgumentException;

final readonly class ValidationBatch
{
    /**
     * @param  LinkExtractionResult[]  $links
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

    public function getLinkCount(): int
    {
        return count($this->links);
    }

    /**
     * @return string[]
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
