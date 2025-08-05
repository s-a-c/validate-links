<?php

declare(strict_types=1);

use App\Enums\ValidationScope;

describe('ValidationScope enum', function () {
    let('internal', fn () => ValidationScope::INTERNAL);
    let('external', fn () => ValidationScope::EXTERNAL);
    let('anchor', fn () => ValidationScope::ANCHOR);
    let('image', fn () => ValidationScope::IMAGE);
    let('all', fn () => ValidationScope::ALL);

    beforeEach(function () {
        // no-op setup for determinism
    });

    afterEach(function () {
        // no-op teardown
    });

    it('it_should_list_all_names_and_values', function () {
        expect(ValidationScope::values())
            ->toContain('internal', 'external', 'anchor', 'image', 'all');

        expect(ValidationScope::names())
            ->toContain('INTERNAL', 'EXTERNAL', 'ANCHOR', 'IMAGE', 'ALL');
    });

    it('it_should_validate_scope_strings', function () {
        expect(ValidationScope::isValid('internal'))->toBeTrue()
            ->and(ValidationScope::isValid('INTERNAL'))->toBeFalse()
            ->and(ValidationScope::isValid('unknown'))->toBeFalse();
    });

    it('it_should_convert_from_string_normally', function () {
        expect(ValidationScope::fromString('INTERNAL'))->toBe(ValidationScope::INTERNAL)
            ->and(ValidationScope::fromString('external'))->toBe(ValidationScope::EXTERNAL);
    });

    it('it_should_report_inclusion_flags', function () {
        expect(($this->internal)()->includesInternal())->toBeTrue()
            ->and(($this->external)()->includesExternal())->toBeTrue()
            ->and(($this->anchor)()->includesAnchor())->toBeTrue()
            ->and(($this->image)()->includesImage())->toBeTrue()
            ->and(($this->all)()->includesExternal())->toBeTrue()
            ->and(($this->all)()->includesInternal())->toBeTrue()
            ->and(($this->all)()->includesAnchor())->toBeTrue()
            ->and(($this->all)()->includesImage())->toBeTrue();
    });

    it('it_should_expand_included_scopes', function () {
        $included = ($this->all)()->getIncludedScopes();
        expect($included)->toBeArray()
            ->and(array_map(fn ($s) => $s->value, $included))
            ->toContain('internal', 'external', 'anchor', 'image');
    });

    it('it_should_define_priority_order', function () {
        expect(($this->internal)()->getPriority())->toBeLessThan(($this->external)()->getPriority())
            ->and(($this->anchor)()->getPriority())->toBeLessThan(($this->external)()->getPriority());
    });
});