<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;

describe('ValidationResultCollection', function () {
    beforeEach(function () {
        $this->validResult = ValidationResult::create(
            url: 'https://example.com',
            status: LinkStatus::VALID,
            message: 'Link is valid'
        );

        $this->brokenResult = ValidationResult::create(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            message: 'Link is broken'
        );

        $this->collection = new ValidationResultCollection([
            $this->validResult,
            $this->brokenResult,
        ]);
    });

    it('counts results correctly', function () {
        expect($this->collection->count())->toBe(2);
    });

    it('filters successful results', function () {
        $successful = $this->collection->getSuccessful();

        expect($successful->count())->toBe(1)
            ->and($successful->first())->toBe($this->validResult);
    });

    it('filters failed results', function () {
        $failed = $this->collection->getFailed();

        expect($failed->count())->toBe(1)
            ->and($failed->first())->toBe($this->brokenResult);
    });

    it('is iterable', function () {
        $results = [];
        foreach ($this->collection as $result) {
            $results[] = $result;
        }

        expect($results)->toHaveCount(2)
            ->and($results[0])->toBe($this->validResult)
            ->and($results[1])->toBe($this->brokenResult);
    });

    it('validates result types on construction', function () {
        expect(fn () => new ValidationResultCollection(['invalid']))
            ->toThrow(InvalidArgumentException::class, 'All results must be ValidationResult instances. Invalid item at index 0');
    });

    it('can be created from array', function () {
        $collection = ValidationResultCollection::fromArray([
            $this->validResult,
            $this->brokenResult,
        ]);

        expect($collection->count())->toBe(2);
    });

    it('provides array conversion', function () {
        $array = $this->collection->toArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveCount(2);
    });

    it('checks if collection is empty', function () {
        $emptyCollection = new ValidationResultCollection([]);
        $nonEmptyCollection = $this->collection;

        expect($emptyCollection->isEmpty())->toBeTrue()
            ->and($nonEmptyCollection->isEmpty())->toBeFalse();
    });

    it('provides first and last result access', function () {
        expect($this->collection->first())->toBe($this->validResult)
            ->and($this->collection->last())->toBe($this->brokenResult);
    });

    it('handles empty collection for first and last', function () {
        $emptyCollection = new ValidationResultCollection([]);

        expect($emptyCollection->first())->toBeNull()
            ->and($emptyCollection->last())->toBeNull();
    });
});
