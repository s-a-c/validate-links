<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * Generic base class for type-safe collections.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
abstract readonly class TypedCollection implements Countable, IteratorAggregate
{
    /**
     * @param array<T> $items
     */
    public function __construct(
        protected array $items = [],
    ) {
        $this->validate();
    }

    /**
     * Get the first item in the collection.
     *
     * @return T|null
     */
    final public function first(): mixed
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get the last item in the collection.
     *
     * @return T|null
     */
    final public function last(): mixed
    {
        $itemsCopy = $this->items;
        $lastItem = end($itemsCopy);

        return $lastItem !== false ? $lastItem : null;
    }

    /**
     * Filter the collection using a predicate.
     *
     * @param callable(T): bool $predicate
     *
     * @return static<T>
     */
    final public function filter(callable $predicate): static
    {
        $filteredItems = array_filter($this->items, $predicate);

        return $this->createNew($filteredItems);
    }

    /**
     * Map the collection to a new collection.
     *
     * @template U
     *
     * @param callable(T): U $mapper
     *
     * @return array<U>
     */
    final public function map(callable $mapper): array
    {
        return array_map($mapper, $this->items);
    }

    /**
     * Check if the collection is empty.
     */
    final public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Check if the collection is not empty.
     */
    final public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Get the count of items in the collection.
     */
    final public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get an iterator for the collection.
     *
     * @return ArrayIterator<int, T>
     */
    final public function getIterator(): ArrayIterator
    {
        return new ArrayIterator(array_values($this->items));
    }

    /**
     * Convert the collection to an array.
     *
     * @return array<T>
     */
    final public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Find an item in the collection using a predicate.
     *
     * @param callable(T): bool $predicate
     *
     * @return T|null
     */
    final public function find(callable $predicate): mixed
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Check if any item matches the predicate.
     *
     * @param callable(T): bool $predicate
     */
    final public function any(callable $predicate): bool
    {
        return $this->find($predicate) !== null;
    }

    /**
     * Check if all items match the predicate.
     *
     * @param callable(T): bool $predicate
     */
    final public function all(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if (! $predicate($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Take the first n items from the collection.
     *
     * @return static<T>
     */
    final public function take(int $count): static
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count must be non-negative');
        }

        $slicedItems = array_slice($this->items, 0, $count);

        return $this->createNew($slicedItems);
    }

    /**
     * Skip the first n items and return the rest.
     *
     * @return static<T>
     */
    final public function skip(int $count): static
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count must be non-negative');
        }

        $slicedItems = array_slice($this->items, $count);

        return $this->createNew($slicedItems);
    }

    /**
     * Validate the collection items.
     * Override in subclasses for specific validation.
     */
    protected function validate(): void
    {
        // Default implementation - no validation
        // Subclasses can override for specific type validation
    }

    /**
     * Get the expected item type for validation.
     * Override in subclasses to specify the expected type.
     */
    protected function getExpectedItemType(): string
    {
        return 'mixed';
    }

    /**
     * Create a new instance of the same collection type.
     * This method provides a safe way to create new instances without using new static().
     *
     * @param array<T> $items
     *
     * @return static<T>
     */
    private function createNew(array $items): static
    {
        $className = static::class;

        /** @var static<T> */
        return new $className($items);
    }
}
