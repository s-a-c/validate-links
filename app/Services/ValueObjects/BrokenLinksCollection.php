<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Value object representing a collection of broken links with their error information.
 *
 * @implements IteratorAggregate<int, array{url: string, error: string|null, timestamp: string}>
 */
final readonly class BrokenLinksCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<array{url: string, error: string|null, timestamp: string}> $brokenLinks
     */
    public function __construct(
        private array $brokenLinks = [],
    ) {
        $this->validate();
    }

    /**
     * Create a new BrokenLinksCollection from array data.
     *
     * @param array<array{url: string, error: string|null, timestamp?: string}> $data
     */
    public static function fromArray(array $data): self
    {
        $brokenLinks = [];

        foreach ($data as $item) {
            if (! is_array($item) || ! isset($item['url'])) {
                continue;
            }

            $brokenLinks[] = [
                'url' => (string) $item['url'],
                'error' => isset($item['error']) && is_string($item['error']) ? $item['error'] : null,
                'timestamp' => isset($item['timestamp']) && is_string($item['timestamp']) ? $item['timestamp'] : (now()->toISOString() ?? ''),
            ];
        }

        return new self($brokenLinks);
    }

    /**
     * Add a broken link to the collection.
     */
    public function add(string $url, ?string $error = null, ?string $timestamp = null): self
    {
        $newBrokenLinks = $this->brokenLinks;
        $newBrokenLinks[] = [
            'url' => $url,
            'error' => $error,
            'timestamp' => $timestamp ?? (now()->toISOString() ?? ''),
        ];

        return new self($newBrokenLinks);
    }

    /**
     * Get all broken links as array.
     *
     * @return array<array{url: string, error: string|null, timestamp: string}>
     */
    public function toArray(): array
    {
        return $this->brokenLinks;
    }

    /**
     * Get URLs only.
     *
     * @return array<string>
     */
    public function getUrls(): array
    {
        return array_map(fn (array $item): string => $item['url'], $this->brokenLinks);
    }

    /**
     * Check if collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->brokenLinks === [];
    }

    /**
     * Get count of broken links.
     */
    public function count(): int
    {
        return count($this->brokenLinks);
    }

    /**
     * @return ArrayIterator<int, array{url: string, error: string|null, timestamp: string}>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->brokenLinks));
    }

    /**
     * Validate the broken links data.
     */
    private function validate(): void
    {
        foreach ($this->brokenLinks as $index => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException("Item at index {$index} must be an array");
            }

            if (! isset($item['url']) || ! is_string($item['url'])) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'url' string");
            }

            if (array_key_exists('error', $item) && ! is_string($item['error']) && $item['error'] !== null) {
                throw new InvalidArgumentException("Item at index {$index} 'error' must be string or null");
            }

            if (! isset($item['timestamp']) || ! is_string($item['timestamp'])) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'timestamp' string");
            }
        }
    }
}
