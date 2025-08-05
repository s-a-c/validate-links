<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;

describe('ValidationResultCollection', function () {
    let('ok', fn () => ValidationResult::success('a', ValidationScope::INTERNAL, 200, 0.001));
    let('broken', fn () => ValidationResult::failure('b', ValidationScope::EXTERNAL, LinkStatus::NOT_FOUND, 'nf', 404, null, 0.002));
    let('collection', fn () => ValidationResultCollection::create([($this->ok)(), ($this->broken)()]));

    beforeEach(function () {
        // deterministic environment; no IO
    });

    afterEach(function () {
        // no teardown necessary
    });

    it('it_should_construct_and_iterate_validation_results', function () {
        $c = ($this->collection)();

        expect($c->count())->toBe(2)
            ->and($c->isEmpty())->toBeFalse()
            ->and($c->first())->toBeInstanceOf(ValidationResult::class)
            ->and($c->last())->toBeInstanceOf(ValidationResult::class);

        $arr = iterator_to_array($c);
        expect($arr)->toHaveCount(2)
            ->and($arr[0])->toBeInstanceOf(ValidationResult::class);
    });

    it('it_should_filter_successful_and_failed_results', function () {
        $c = ($this->collection)();

        $ok = $c->getSuccessful();
        $bad = $c->getFailed();

        expect($ok->count())->toBe(1)
            ->and($bad->count())->toBe(1);

        foreach ($ok as $r) {
            expect($r->isValid())->toBeTrue();
        }
        foreach ($bad as $r) {
            expect($r->isValid())->toBeFalse();
        }
    });

    it('it_should_convert_from_mixed_array_and_to_arrays', function () {
        $c = ValidationResultCollection::fromArray([
            ['url' => 'x', 'status' => 'valid', 'scope' => 'internal'],
            ValidationResult::failure('y', ValidationScope::EXTERNAL, LinkStatus::BROKEN),
        ]);

        expect($c->count())->toBe(2);

        $vals = $c->toValidationResultArray();
        expect($vals[0])->toBeInstanceOf(ValidationResult::class);

        $plain = $c->toArray();
        expect($plain)->toBeArray()
            ->and($plain[0])->toHaveKeys(['url', 'status', 'scope', 'is_valid']);
    });

    it('it_should_throw_on_invalid_element_in_constructor', function () {
        expect(fn () => new ValidationResultCollection([new stdClass()]))->toThrow(InvalidArgumentException::class);
    });
});