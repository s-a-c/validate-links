<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;

/**
 * @implements IteratorAggregate<int, ValidationResult>
 */
final readonly class ValidationResultCollection implements Countable, IteratorAggregate
{
    /**
     * @param  array<ValidationResult>  $results
     */
    /**
     * @param  array<ValidationResult>  $results
     */
    public function __construct(
        private array $results = [],
    ) {
        // Validate that all elements are ValidationResult instances
        foreach ($this->results as $index => $result) {
            if (! $result instanceof ValidationResult) {
                throw new InvalidArgumentException(
                    "All results must be ValidationResult instances. Invalid item at index {$index}",
                );
            }
        }
    }

    /**
     * @param  array<mixed>  $results
     */
    public static function fromArray(array $results): self
    {
        return new self(array_map(
            function ($result) {
                if ($result instanceof ValidationResult) {
                    return $result;
                }
                /** @var array<string, mixed> $data */
                $data = is_array($result) ? $result : [];

                return ValidationResult::fromArray($data);
            },
            $results
        ));
    }

    public function getSuccessful(): self
    {
        return new self(array_values(array_filter(
            $this->results,
            fn (ValidationResult $result) => $result->isSuccessful()
        )));
    }

    public function getFailed(): self
    {
        return new self(array_values(array_filter(
            $this->results,
            fn (ValidationResult $result) => ! $result->isSuccessful()
        )));
    }

    public function isEmpty(): bool
    {
        return empty($this->results);
    }

    public function first(): ?ValidationResult
    {
        return $this->results[0] ?? null;
    }

    public function last(): ?ValidationResult
    {
        return $this->results[array_key_last($this->results)] ?? null;
    }

    /**
     * Add method to convert to array of ValidationResult objects
     *
     * @return array<ValidationResult>
     */
    public function toValidationResultArray(): array
    {
        return $this->results;
    }

    /**
     * @return ArrayIterator<int, ValidationResult>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->results);
    }

    /**
     * Returns the number of validation results in the collection
     */
    public function count(): int
    {
        return count($this->results);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_values(array_map(fn ($result) => $result->toArray(), $this->results));
    }
}
