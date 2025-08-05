<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;

describe('ValidationResultCollection', function () {
    /** @var ValidationResult|null */
    $validResult = null;
    /** @var ValidationResult|null */
    $brokenResult = null;
    /** @var ValidationResultCollection|null */
    $collection = null;

    beforeEach(function () use (&$validResult, &$brokenResult, &$collection) {
        $validResult = ValidationResult::create(
            url: 'https://example.com',
            status: LinkStatus::VALID,
            message: 'Link is valid'
        );

        $brokenResult = ValidationResult::create(
            url: 'https://broken.com',
            status: LinkStatus::BROKEN,
            message: 'Link is broken'
        );

        $collection = new ValidationResultCollection([
            $validResult,
            $brokenResult,
        ]);
    });

    it('counts results correctly', function () use (&$collection) {
        expect($collection->count())->toBe(2);
    });

    it('filters successful results', function () use (&$collection, &$validResult) {
        $successful = $collection->getSuccessful();

        expect($successful->count())->toBe(1)
            ->and($successful->first())->toBe($validResult);
    });

    it('filters failed results', function () use (&$collection, &$brokenResult) {
        $failed = $collection->getFailed();

        expect($failed->count())->toBe(1)
            ->and($failed->first())->toBe($brokenResult);
    });

    it('is iterable', function () use (&$collection, &$validResult, &$brokenResult) {
        $results = [];
        foreach ($collection as $result) {
            $results[] = $result;
        }

        expect($results)->toHaveCount(2)
            ->and($results[0])->toBe($validResult)
            ->and($results[1])->toBe($brokenResult);
    });

    it('validates result types on construction', function () {
        expect(fn () => new ValidationResultCollection(['invalid']))
            ->toThrow(InvalidArgumentException::class);
    });

    it('can be created from array', function () use (&$validResult, &$brokenResult) {
        $collection = ValidationResultCollection::fromArray([
            $validResult,
            $brokenResult,
        ]);

        expect($collection->count())->toBe(2);
    });

    it('provides array conversion', function () use (&$collection) {
        $array = $collection->toArray();

        expect($array)->toBeArray()
            ->and($array)->toHaveCount(2);
    });

    it('checks if collection is empty', function () use (&$collection) {
        $emptyCollection = new ValidationResultCollection([]);
        $nonEmptyCollection = $collection;

        expect($emptyCollection->isEmpty())->toBeTrue()
            ->and($nonEmptyCollection->isEmpty())->toBeFalse();
    });

    it('provides first and last result access', function () use (&$collection, &$validResult, &$brokenResult) {
        expect($collection->first())->toBe($validResult)
            ->and($collection->last())->toBe($brokenResult);
    });

    it('handles empty collection for first and last', function () {
        $emptyCollection = new ValidationResultCollection([]);

        expect($emptyCollection->first())->toBeNull()
            ->and($emptyCollection->last())->toBeNull();
    });
});
