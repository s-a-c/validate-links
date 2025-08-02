<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ValidationLevel;
use Exception;
use ValueError;

/**
 * Comprehensive unit tests for ValidationLevel enum.
 *
 * Tests all enum cases, methods, and edge cases with 100% coverage.
 * Validates validation level behavior and timeout multipliers.
 */
describe('ValidationLevel Enum', function () {

    describe('Enum Cases', function () {
        it('has all expected enum cases', function () {
            $expectedCases = [
                'STRICT',
                'NORMAL',
                'LENIENT',
            ];

            $actualCases = array_map(fn ($case) => $case->name, ValidationLevel::cases());

            expect($actualCases)->toBe($expectedCases);
        });

        it('has correct string values for all cases', function () {
            expect(ValidationLevel::STRICT->value)->toBe('strict');
            expect(ValidationLevel::NORMAL->value)->toBe('normal');
            expect(ValidationLevel::LENIENT->value)->toBe('lenient');
        });
    });

    describe('getTimeoutMultiplier() method', function () {
        it('returns correct timeout multipliers for all levels', function () {
            expect(ValidationLevel::STRICT->getTimeoutMultiplier())->toBe(2.0);
            expect(ValidationLevel::NORMAL->getTimeoutMultiplier())->toBe(1.0);
            expect(ValidationLevel::LENIENT->getTimeoutMultiplier())->toBe(0.8);
        });

        it('returns positive float values for all levels', function () {
            foreach (ValidationLevel::cases() as $level) {
                $multiplier = $level->getTimeoutMultiplier();

                expect($multiplier)->toBeFloat()
                    ->and($multiplier)->toBeGreaterThan(0.0);
            }
        });

        it('maintains logical ordering of timeout multipliers', function () {
            $strictMultiplier = ValidationLevel::STRICT->getTimeoutMultiplier();
            $normalMultiplier = ValidationLevel::NORMAL->getTimeoutMultiplier();
            $lenientMultiplier = ValidationLevel::LENIENT->getTimeoutMultiplier();

            // Strict should have highest timeout (most thorough)
            expect($strictMultiplier)->toBeGreaterThan($normalMultiplier);
            // Normal should be baseline (1.0)
            expect($normalMultiplier)->toBe(1.0);
            // Lenient should have lowest timeout (fastest)
            expect($lenientMultiplier)->toBeLessThan($normalMultiplier);
        });
    });

    describe('Enum Creation from Values', function () {
        it('can be created from string values', function () {
            expect(ValidationLevel::from('strict'))->toBe(ValidationLevel::STRICT)
                ->and(ValidationLevel::from('normal'))->toBe(ValidationLevel::NORMAL)
                ->and(ValidationLevel::from('lenient'))->toBe(ValidationLevel::LENIENT);
        });

        it('provides tryFrom for safe conversion', function () {
            expect(ValidationLevel::tryFrom('strict'))->toBe(ValidationLevel::STRICT);

            // Test with dynamic invalid value to avoid static analysis issues
            $invalidValue = 'invalid_'.time();
            expect(ValidationLevel::tryFrom($invalidValue))->toBeNull();
        });

        it('throws exception for invalid values with from()', function () {
            expect(fn () => ValidationLevel::from('invalid'))->toThrow(ValueError::class);
        });
    });

    describe('Type Safety', function () {
        it('enum cases are of correct type', function () {
            foreach (ValidationLevel::cases() as $level) {
                expect($level)->toBeInstanceOf(ValidationLevel::class);
                expect($level->value)->toBeString();
            }
        });

        it('methods return correct types', function () {
            $level = ValidationLevel::NORMAL;

            expect($level->getTimeoutMultiplier())->toBeFloat();
        });
    });

    describe('Enum Completeness', function () {
        it('has exactly 3 enum cases', function () {
            expect(ValidationLevel::cases())->toHaveCount(3);
        });

        it('all cases have unique values', function () {
            $values = array_map(fn ($case) => $case->value, ValidationLevel::cases());
            $uniqueValues = array_unique($values);

            expect(count($values))->toBe(count($uniqueValues));
        });

        it('all cases have unique names', function () {
            $names = array_map(fn ($case) => $case->name, ValidationLevel::cases());
            $uniqueNames = array_unique($names);

            expect(count($names))->toBe(count($uniqueNames));
        });
    });

    describe('Business Logic Validation', function () {
        it('strict level has highest timeout multiplier', function () {
            $strictMultiplier = ValidationLevel::STRICT->getTimeoutMultiplier();

            foreach (ValidationLevel::cases() as $level) {
                if ($level !== ValidationLevel::STRICT) {
                    expect($strictMultiplier)->toBeGreaterThanOrEqual($level->getTimeoutMultiplier());
                }
            }
        });

        it('lenient level has lowest timeout multiplier', function () {
            $lenientMultiplier = ValidationLevel::LENIENT->getTimeoutMultiplier();

            foreach (ValidationLevel::cases() as $level) {
                if ($level !== ValidationLevel::LENIENT) {
                    expect($lenientMultiplier)->toBeLessThanOrEqual($level->getTimeoutMultiplier());
                }
            }
        });

        it('normal level serves as baseline', function () {
            expect(ValidationLevel::NORMAL->getTimeoutMultiplier())->toBe(1.0);
        });
    });

    describe('Edge Cases', function () {
        it('handles all enum cases without exceptions', function () {
            foreach (ValidationLevel::cases() as $level) {
                expect(fn () => $level->getTimeoutMultiplier())->not->toThrow(Exception::class);
            }
        });

        it('methods are deterministic', function () {
            foreach (ValidationLevel::cases() as $level) {
                $multiplier1 = $level->getTimeoutMultiplier();
                $multiplier2 = $level->getTimeoutMultiplier();

                expect($multiplier1)->toBe($multiplier2);
            }
        });

        it('timeout multipliers are within reasonable bounds', function () {
            foreach (ValidationLevel::cases() as $level) {
                $multiplier = $level->getTimeoutMultiplier();

                expect($multiplier)->toBeGreaterThan(0.0)
                    ->and($multiplier)->toBeLessThanOrEqual(5.0); // Reasonable upper bound
            }
        });
    });
});
