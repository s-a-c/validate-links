<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\LinkType;
use Exception;
use ValueError;

/**
 * Comprehensive unit tests for LinkType enum.
 *
 * Tests all enum cases, methods, and edge cases with 100% coverage.
 * Validates link type classification and behavior methods.
 */
describe('LinkType Enum', function () {

    describe('Enum Cases', function () {
        it('has all expected enum cases', function () {
            $expectedCases = [
                'INTERNAL',
                'EXTERNAL',
                'ANCHOR',
                'CROSS_REFERENCE',
            ];

            $actualCases = array_map(fn ($case) => $case->name, LinkType::cases());

            expect($actualCases)->toBe($expectedCases);
        });

        it('has correct string values for all cases', function () {
            expect(LinkType::INTERNAL->value)->toBe('internal');
            expect(LinkType::EXTERNAL->value)->toBe('external');
            expect(LinkType::ANCHOR->value)->toBe('anchor');
            expect(LinkType::CROSS_REFERENCE->value)->toBe('cross_reference');
        });
    });

    describe('isLocal() method', function () {
        it('returns true for local link types', function () {
            expect(LinkType::INTERNAL->isLocal())->toBeTrue();
            expect(LinkType::ANCHOR->isLocal())->toBeTrue();
            expect(LinkType::CROSS_REFERENCE->isLocal())->toBeTrue();
        });

        it('returns false for external link types', function () {
            expect(LinkType::EXTERNAL->isLocal())->toBeFalse();
        });

        it('correctly classifies all link types', function () {
            $localTypes = [
                LinkType::INTERNAL,
                LinkType::ANCHOR,
                LinkType::CROSS_REFERENCE,
            ];

            $externalTypes = [
                LinkType::EXTERNAL,
            ];

            foreach ($localTypes as $type) {
                expect($type->isLocal())->toBeTrue();
            }

            foreach ($externalTypes as $type) {
                expect($type->isLocal())->toBeFalse();
            }
        });
    });

    describe('requiresNetworkAccess() method', function () {
        it('returns true only for external links', function () {
            expect(LinkType::EXTERNAL->requiresNetworkAccess())->toBeTrue();
        });

        it('returns false for local link types', function () {
            expect(LinkType::INTERNAL->requiresNetworkAccess())->toBeFalse();
            expect(LinkType::ANCHOR->requiresNetworkAccess())->toBeFalse();
            expect(LinkType::CROSS_REFERENCE->requiresNetworkAccess())->toBeFalse();
        });

        it('correctly identifies network requirements for all types', function () {
            $networkRequiredTypes = [LinkType::EXTERNAL];
            $noNetworkTypes = [
                LinkType::INTERNAL,
                LinkType::ANCHOR,
                LinkType::CROSS_REFERENCE,
            ];

            foreach ($networkRequiredTypes as $type) {
                expect($type->requiresNetworkAccess())->toBeTrue();
            }

            foreach ($noNetworkTypes as $type) {
                expect($type->requiresNetworkAccess())->toBeFalse();
            }
        });
    });

    describe('Enum Creation from Values', function () {
        it('can be created from string values', function () {
            expect(LinkType::from('internal'))->toBe(LinkType::INTERNAL)
                ->and(LinkType::from('external'))->toBe(LinkType::EXTERNAL)
                ->and(LinkType::from('anchor'))->toBe(LinkType::ANCHOR)
                ->and(LinkType::from('cross_reference'))->toBe(LinkType::CROSS_REFERENCE);
        });

        it('provides tryFrom for safe conversion', function () {
            expect(LinkType::tryFrom('internal'))->toBe(LinkType::INTERNAL);

            // Test with dynamic invalid value to avoid static analysis issues
            $invalidValue = 'invalid_'.time();
            expect(LinkType::tryFrom($invalidValue))->toBeNull();
        });

        it('throws exception for invalid values with from()', function () {
            expect(fn () => LinkType::from('invalid'))->toThrow(ValueError::class);
        });
    });

    describe('Method Consistency', function () {
        it('maintains logical consistency between isLocal and requiresNetworkAccess', function () {
            foreach (LinkType::cases() as $linkType) {
                // If a link is local, it should not require network access
                if ($linkType->isLocal()) {
                    expect($linkType->requiresNetworkAccess())->toBeFalse();
                }

                // If a link requires network access, it should not be local
                if ($linkType->requiresNetworkAccess()) {
                    expect($linkType->isLocal())->toBeFalse();
                }
            }
        });

        it('covers all enum cases in both methods', function () {
            foreach (LinkType::cases() as $linkType) {
                // Both methods should return boolean values for all cases
                expect($linkType->isLocal())->toBeBool();
                expect($linkType->requiresNetworkAccess())->toBeBool();
            }
        });
    });

    describe('Type Safety', function () {
        it('enum cases are of correct type', function () {
            foreach (LinkType::cases() as $linkType) {
                expect($linkType)->toBeInstanceOf(LinkType::class);
                expect($linkType->value)->toBeString();
            }
        });

        it('methods return correct types', function () {
            $linkType = LinkType::INTERNAL;

            expect($linkType->isLocal())->toBeBool();
            expect($linkType->requiresNetworkAccess())->toBeBool();
        });
    });

    describe('Enum Completeness', function () {
        it('has exactly 4 enum cases', function () {
            expect(LinkType::cases())->toHaveCount(4);
        });

        it('all cases have unique values', function () {
            $values = array_map(fn ($case) => $case->value, LinkType::cases());
            $uniqueValues = array_unique($values);

            expect(count($values))->toBe(count($uniqueValues));
        });

        it('all cases have unique names', function () {
            $names = array_map(fn ($case) => $case->name, LinkType::cases());
            $uniqueNames = array_unique($names);

            expect(count($names))->toBe(count($uniqueNames));
        });
    });

    describe('Business Logic Validation', function () {
        it('correctly identifies internal links as local', function () {
            expect(LinkType::INTERNAL->isLocal())->toBeTrue();
            expect(LinkType::INTERNAL->requiresNetworkAccess())->toBeFalse();
        });

        it('correctly identifies external links as non-local', function () {
            expect(LinkType::EXTERNAL->isLocal())->toBeFalse();
            expect(LinkType::EXTERNAL->requiresNetworkAccess())->toBeTrue();
        });

        it('correctly identifies anchors as local', function () {
            expect(LinkType::ANCHOR->isLocal())->toBeTrue();
            expect(LinkType::ANCHOR->requiresNetworkAccess())->toBeFalse();
        });

        it('correctly identifies cross-references as local', function () {
            expect(LinkType::CROSS_REFERENCE->isLocal())->toBeTrue();
            expect(LinkType::CROSS_REFERENCE->requiresNetworkAccess())->toBeFalse();
        });
    });

    describe('Edge Cases', function () {
        it('handles all enum cases without exceptions', function () {
            foreach (LinkType::cases() as $linkType) {
                expect(fn () => $linkType->isLocal())->not->toThrow(Exception::class);
                expect(fn () => $linkType->requiresNetworkAccess())->not->toThrow(Exception::class);
            }
        });

        it('methods are deterministic', function () {
            foreach (LinkType::cases() as $linkType) {
                $isLocal1 = $linkType->isLocal();
                $isLocal2 = $linkType->isLocal();
                $requiresNetwork1 = $linkType->requiresNetworkAccess();
                $requiresNetwork2 = $linkType->requiresNetworkAccess();

                expect($isLocal1)->toBe($isLocal2);
                expect($requiresNetwork1)->toBe($requiresNetwork2);
            }
        });
    });
});
