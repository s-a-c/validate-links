<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Value object representing a collection of extracted links with their metadata.
 *
 * @implements IteratorAggregate<int, array{url: string, text: string, line: int}>
 */
final readonly class LinkCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<array{url: string, text: string, line: int}> $links
     */
    public function __construct(
        private array $links = [],
    ) {
        $this->validate();
    }

    /**
     * Create a new LinkCollection from array data.
     *
     * @param array<array{url: string, text: string, line: int}> $data
     */
    public static function fromArray(array $data): self
    {
        $links = [];

        foreach ($data as $item) {
            if (! is_array($item) || ! isset($item['url'])) {
                continue;
            }

            $links[] = [
                'url' => (string) $item['url'],
                'text' => isset($item['text']) && is_string($item['text']) ? $item['text'] : '',
                'line' => isset($item['line']) && is_numeric($item['line']) ? (int) $item['line'] : 0,
            ];
        }

        return new self($links);
    }

    /**
     * Add a link to the collection.
     */
    public function add(string $url, string $text = '', int $line = 0): self
    {
        $newLinks = $this->links;
        $newLinks[] = [
            'url' => $url,
            'text' => $text,
            'line' => $line,
        ];

        return new self($newLinks);
    }

    /**
     * Get all links as array.
     *
     * @return array<array{url: string, text: string, line: int}>
     */
    public function toArray(): array
    {
        return $this->links;
    }

    /**
     * Get URLs only.
     *
     * @return array<string>
     */
    public function getUrls(): array
    {
        return array_map(fn (array $item): string => $item['url'], $this->links);
    }

    /**
     * Filter links by URL pattern.
     */
    public function filterByPattern(string $pattern): self
    {
        $filteredLinks = array_filter(
            $this->links,
            fn (array $link): bool => preg_match($pattern, $link['url']) === 1,
        );

        return new self(array_values($filteredLinks));
    }

    /**
     * Get links from a specific line.
     */
    public function getFromLine(int $lineNumber): self
    {
        $filteredLinks = array_filter(
            $this->links,
            fn (array $link): bool => $link['line'] === $lineNumber,
        );

        return new self(array_values($filteredLinks));
    }

    /**
     * Check if collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->links === [];
    }

    /**
     * Get count of links.
     */
    public function count(): int
    {
        return count($this->links);
    }

    /**
     * @return ArrayIterator<int, array{url: string, text: string, line: int}>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->links));
    }

    /**
     * Validate the links data.
     */
    private function validate(): void
    {
        foreach ($this->links as $index => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException("Item at index {$index} must be an array");
            }

            if (! isset($item['url']) || ! is_string($item['url'])) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'url' string");
            }

            if (! isset($item['text']) || ! is_string($item['text'])) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid 'text' string");
            }

            if (! isset($item['line']) || ! is_int($item['line']) || $item['line'] < 0) {
                throw new InvalidArgumentException("Item at index {$index} must have a valid non-negative 'line' integer");
            }
        }
    }
}
