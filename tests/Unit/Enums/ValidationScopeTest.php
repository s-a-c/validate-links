<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ValidationScope;
use ValueError;

/**
 * Comprehensive unit tests for ValidationScope enum.
 *
 * Tests all enum cases, methods, and edge cases with 100% coverage.
 * Validates validation scope behavior and inclusion methods.
 */
describe('ValidationScope Enum', function () {

    describe('Enum Cases', function () {
        it('has all expected enum cases', function () {
            $expectedCases = [
                'INTERNAL',
                'EXTERNAL',
                'ANCHOR',
                'IMAGE',
                'ALL',
            ];

            $actualCases = array_map(fn ($case) => $case->name, ValidationScope::cases());

            expect($actualCases)->toBe($expectedCases);
        });

        it('has correct string values for all cases', function () {
            expect(ValidationScope::INTERNAL->value)->toBe('internal');
            expect(ValidationScope::EXTERNAL->value)->toBe('external');
            expect(ValidationScope::ANCHOR->value)->toBe('anchor');
            expect(ValidationScope::IMAGE->value)->toBe('image');
            expect(ValidationScope::ALL->value)->toBe('all');
        });
    });

    describe('values() method', function () {
        it('returns all enum values as array', function () {
            $values = ValidationScope::values();

            expect($values)->toBeArray()
                ->and($values)->toHaveCount(5)
                ->and($values)->toBe(['internal', 'external', 'anchor', 'image', 'all']);
        });

        it('returns values in correct order', function () {
            $expectedValues = ['internal', 'external', 'anchor', 'image', 'all'];

            expect(ValidationScope::values())->toBe($expectedValues);
        });
    });

    describe('names() method', function () {
        it('returns all enum names as array', function () {
            $names = ValidationScope::names();

            expect($names)->toBeArray()
                ->and($names)->toHaveCount(5)
                ->and($names)->toBe(['INTERNAL', 'EXTERNAL', 'ANCHOR', 'IMAGE', 'ALL']);
        });
    });

    describe('getSelectOptions() method', function () {
        it('returns associative array with value => description mapping', function () {
            $options = ValidationScope::getSelectOptions();

            expect($options)->toBeArray()
                ->and($options)->toHaveCount(5);

            foreach (ValidationScope::cases() as $scope) {
                expect($options)->toHaveKey($scope->value)
                    ->and($options[$scope->value])->toBe($scope->getDescription());
            }
        });
    });

    describe('isValid() method', function () {
        it('returns true for valid scope values', function () {
            foreach (ValidationScope::values() as $value) {
                expect(ValidationScope::isValid($value))->toBeTrue();
            }
        });

        it('returns false for invalid scope values', function () {
            expect(ValidationScope::isValid('invalid'))->toBeFalse();
            expect(ValidationScope::isValid('unknown'))->toBeFalse();
            expect(ValidationScope::isValid(''))->toBeFalse();
            expect(ValidationScope::isValid('INTERNAL'))->toBeFalse(); // case sensitive
        });
    });

    describe('fromString() method', function () {
        it('creates scope from lowercase string', function () {
            expect(ValidationScope::fromString('internal'))->toBe(ValidationScope::INTERNAL);
            expect(ValidationScope::fromString('external'))->toBe(ValidationScope::EXTERNAL);
            expect(ValidationScope::fromString('anchor'))->toBe(ValidationScope::ANCHOR);
            expect(ValidationScope::fromString('image'))->toBe(ValidationScope::IMAGE);
            expect(ValidationScope::fromString('all'))->toBe(ValidationScope::ALL);
        });

        it('creates scope from uppercase string', function () {
            expect(ValidationScope::fromString('INTERNAL'))->toBe(ValidationScope::INTERNAL);
            expect(ValidationScope::fromString('EXTERNAL'))->toBe(ValidationScope::EXTERNAL);
            expect(ValidationScope::fromString('ALL'))->toBe(ValidationScope::ALL);
        });

        it('creates scope from mixed case string', function () {
            expect(ValidationScope::fromString('Internal'))->toBe(ValidationScope::INTERNAL);
            expect(ValidationScope::fromString('ExTeRnAl'))->toBe(ValidationScope::EXTERNAL);
        });

        it('throws exception for invalid string', function () {
            expect(fn () => ValidationScope::fromString('invalid'))->toThrow(ValueError::class);
        });
    });

    describe('Inclusion Methods', function () {
        describe('includesExternal() method', function () {
            it('returns true for EXTERNAL and ALL scopes', function () {
                expect(ValidationScope::EXTERNAL->includesExternal())->toBeTrue();
                expect(ValidationScope::ALL->includesExternal())->toBeTrue();
            });

            it('returns false for other scopes', function () {
                expect(ValidationScope::INTERNAL->includesExternal())->toBeFalse();
                expect(ValidationScope::ANCHOR->includesExternal())->toBeFalse();
                expect(ValidationScope::IMAGE->includesExternal())->toBeFalse();
            });
        });

        describe('includesInternal() method', function () {
            it('returns true for INTERNAL and ALL scopes', function () {
                expect(ValidationScope::INTERNAL->includesInternal())->toBeTrue();
                expect(ValidationScope::ALL->includesInternal())->toBeTrue();
            });

            it('returns false for other scopes', function () {
                expect(ValidationScope::EXTERNAL->includesInternal())->toBeFalse();
                expect(ValidationScope::ANCHOR->includesInternal())->toBeFalse();
                expect(ValidationScope::IMAGE->includesInternal())->toBeFalse();
            });
        });

        describe('includesAnchor() method', function () {
            it('returns true for ANCHOR and ALL scopes', function () {
                expect(ValidationScope::ANCHOR->includesAnchor())->toBeTrue();
                expect(ValidationScope::ALL->includesAnchor())->toBeTrue();
            });

            it('returns false for other scopes', function () {
                expect(ValidationScope::INTERNAL->includesAnchor())->toBeFalse();
                expect(ValidationScope::EXTERNAL->includesAnchor())->toBeFalse();
                expect(ValidationScope::IMAGE->includesAnchor())->toBeFalse();
            });
        });

        describe('includesImage() method', function () {
            it('returns true for IMAGE and ALL scopes', function () {
                expect(ValidationScope::IMAGE->includesImage())->toBeTrue();
                expect(ValidationScope::ALL->includesImage())->toBeTrue();
            });

            it('returns false for other scopes', function () {
                expect(ValidationScope::INTERNAL->includesImage())->toBeFalse();
                expect(ValidationScope::EXTERNAL->includesImage())->toBeFalse();
                expect(ValidationScope::ANCHOR->includesImage())->toBeFalse();
            });
        });
    });

    describe('getDescription() method', function () {
        it('returns meaningful descriptions for all scopes', function () {
            expect(ValidationScope::INTERNAL->getDescription())->toBe('Validate internal file and directory links');
            expect(ValidationScope::EXTERNAL->getDescription())->toBe('Validate external HTTP/HTTPS links');
            expect(ValidationScope::ANCHOR->getDescription())->toBe('Validate anchor links within documents');
            expect(ValidationScope::IMAGE->getDescription())->toBe('Validate image links and embedded media');
            expect(ValidationScope::ALL->getDescription())->toBe('Validate all link types (comprehensive validation)');
        });

        it('returns non-empty descriptions for all scopes', function () {
            foreach (ValidationScope::cases() as $scope) {
                expect($scope->getDescription())->toBeString()
                    ->and($scope->getDescription())->not->toBeEmpty()
                    ->and(mb_strlen($scope->getDescription()))->toBeGreaterThan(10);
            }
        });
    });

    describe('getHelpText() method', function () {
        it('returns detailed help text for all scopes', function () {
            foreach (ValidationScope::cases() as $scope) {
                $helpText = $scope->getHelpText();

                expect($helpText)->toBeString()
                    ->and($helpText)->not->toBeEmpty()
                    ->and(mb_strlen($helpText))->toBeGreaterThan(20);
            }
        });

        it('provides specific help text for each scope', function () {
            expect(ValidationScope::INTERNAL->getHelpText())->toContain('files and directories');
            expect(ValidationScope::EXTERNAL->getHelpText())->toContain('HTTP/HTTPS');
            expect(ValidationScope::ANCHOR->getHelpText())->toContain('anchor');
            expect(ValidationScope::IMAGE->getHelpText())->toContain('image');
            expect(ValidationScope::ALL->getHelpText())->toContain('all link types');
        });
    });

    describe('getIncludedScopes() method', function () {
        it('returns all scopes for ALL scope', function () {
            $includedScopes = ValidationScope::ALL->getIncludedScopes();

            expect($includedScopes)->toBeArray()
                ->and($includedScopes)->toHaveCount(4)
                ->and($includedScopes)->toContain(ValidationScope::INTERNAL)
                ->and($includedScopes)->toContain(ValidationScope::EXTERNAL)
                ->and($includedScopes)->toContain(ValidationScope::ANCHOR)
                ->and($includedScopes)->toContain(ValidationScope::IMAGE);
        });

        it('returns self for specific scopes', function () {
            $specificScopes = [
                ValidationScope::INTERNAL,
                ValidationScope::EXTERNAL,
                ValidationScope::ANCHOR,
                ValidationScope::IMAGE,
            ];

            foreach ($specificScopes as $scope) {
                $includedScopes = $scope->getIncludedScopes();

                expect($includedScopes)->toBeArray()
                    ->and($includedScopes)->toHaveCount(1)
                    ->and($includedScopes[0])->toBe($scope);
            }
        });
    });

    describe('getPriority() method', function () {
        it('returns correct priority values for all scopes', function () {
            expect(ValidationScope::ALL->getPriority())->toBe(0);
            expect(ValidationScope::INTERNAL->getPriority())->toBe(1);
            expect(ValidationScope::ANCHOR->getPriority())->toBe(2);
            expect(ValidationScope::IMAGE->getPriority())->toBe(3);
            expect(ValidationScope::EXTERNAL->getPriority())->toBe(4);
        });

        it('returns integer priorities for all scopes', function () {
            foreach (ValidationScope::cases() as $scope) {
                expect($scope->getPriority())->toBeInt()
                    ->and($scope->getPriority())->toBeGreaterThanOrEqual(0);
            }
        });

        it('maintains logical priority ordering', function () {
            // ALL should have highest priority (lowest number)
            expect(ValidationScope::ALL->getPriority())->toBe(0);

            // Other priorities should be positive
            $otherScopes = [
                ValidationScope::INTERNAL,
                ValidationScope::EXTERNAL,
                ValidationScope::ANCHOR,
                ValidationScope::IMAGE,
            ];

            foreach ($otherScopes as $scope) {
                expect($scope->getPriority())->toBeGreaterThan(0);
            }
        });
    });
});
