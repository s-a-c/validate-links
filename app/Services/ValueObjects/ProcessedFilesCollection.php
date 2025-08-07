<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Value object representing a collection of processed files with their metadata.
 *
 * @implements IteratorAggregate<int, array{file: string, link_count: int, timestamp: string}>
 */
final readonly class ProcessedFilesCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<array{file: string, link_count: int, timestamp: string}> $processedFiles
     */
    public function __construct(
        private array $processedFiles = [],
    ) {
        $this->validate();
    }

    /**
     * Create a new ProcessedFilesCollection from array data.
     *
     * @param array<array{file: string, link_count: int, timestamp?: string}> $data
     */
    public static function fromArray(array $data): self
    {
        $processedFiles = [];

        foreach ($data as $item) {
            if (! is_array($item) || ! isset($item['file'])) {
                continue;
            }

            $processedFiles[] = [
                'file' => (string) $item['file'],
                'link_count' => isset($item['link_count']) && is_numeric($item['link_count']) ? (int) $item['link_count'] : 0,
                'timestamp' => isset($item['timestamp']) && is_string($item['timestamp']) ? $item['timestamp'] : (now()->toISOString() ?? ''),
            ];
        }

        return new self($processedFiles);
    }

    /**
     * Add a processed file to the collection.
     */
    public function add(string $filePath, int $linkCount, ?string $timestamp = null): self
    {
        $newProcessedFiles = $this->processedFiles;
        $newProcessedFiles[] = [
            'file' => $filePath,
            'link_count' => $linkCount,
            'timestamp' => $timestamp ?? (now()->toISOString() ?? ''),
        ];

        return new self($newProcessedFiles);
    }

    /**
     * Get all processed files as array.
     *
     * @return array<array{file: string, link_count: int, timestamp: string}>
     */
    public function toArray(): array
    {
        return $this->processedFiles;
    }

    /**
     * Get file paths only.
     *
     * @return array<string>
     */
    public function getFilePaths(): array
    {
        return array_map(fn (array $item): string => $item['file'], $this->processedFiles);
    }

    /**
     * Get total link count across all files.
     */
    public function getTotalLinkCount(): int
    {
        return array_sum(array_map(fn (array $item): int => $item['link_count'], $this->processedFiles));
    }

    /**
     * Check if collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->processedFiles === [];
    }

    /**
     * Get count of processed files.
     */
    public function count(): int
    {
        return count($this->processedFiles);
    }

    /**
     * @return ArrayIterator<int, array{file: string, link_count: int, timestamp: string}>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->processedFiles));
    }

    /**
     * Validate the processed files data.
     */
    private function validate(): void
    {
        foreach ($this->processedFiles as $index => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException("Item at index {$index} must be an array");
            }

            if (! isset($item['file']) || ! is_string($item['file'])) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'file' string");
            }

            if (! isset($item['link_count']) || ! is_int($item['link_count']) || $item['link_count'] < 0) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid non-negative 'link_count' integer");
            }

            if (! isset($item['timestamp']) || ! is_string($item['timestamp'])) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'timestamp' string");
            }
        }
    }
}
