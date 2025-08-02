<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ValidationScope;
use ValueError;

/**
 * Unit test suite for ValidationScope enum.
 *
 * Tests all enum cases, values, and scope filtering methods to ensure
 * proper functionality of validation scope selection and filtering logic.
 *
 * @group unit
 * @group enums
 *
 * @covers \App\Enums\ValidationScope
 */
describe('ValidationScope Enum', function (): void {

    test('has all expected cases with correct values', function (): void {
        $expectedCases = [
            'INTERNAL' => 'internal',
            'EXTERNAL' => 'external',
            'ANCHOR' => 'anchor',
            'IMAGE' => 'image',
            'ALL' => 'all',
        ];

        $actualCases = ValidationScope::cases();
        expect($actualCases)->toHaveCount(count($expectedCases));

        foreach ($expectedCases as $name => $value) {
            $case = ValidationScope::tryFrom($value);
            expect($case)->not->toBeNull("Case {$name} with value '{$value}' should exist");
            expect($case->name)->toBe($name);
            expect($case->value)->toBe($value);
        }
    });

    test('values() returns correct array', function (): void {
        $expectedValues = ['internal', 'external', 'anchor', 'image', 'all'];

        $values = ValidationScope::values();
        expect($values)->toBeArray();
        expect($values)->toHaveCount(count($expectedValues));
        expect($values)->toEqual($expectedValues);
    });

    test('names() returns correct array', function (): void {
        $expectedNames = ['INTERNAL', 'EXTERNAL', 'ANCHOR', 'IMAGE', 'ALL'];

        $names = ValidationScope::names();
        expect($names)->toBeArray();
        expect($names)->toHaveCount(count($expectedNames));
        expect($names)->toEqual($expectedNames);
    });

    test('cases() returns all enum cases', function (): void {
        $cases = ValidationScope::cases();

        expect($cases)->toHaveCount(5);
        expect($cases)->each->toBeInstanceOf(ValidationScope::class);
    });

    test('tryFrom() works correctly', function (): void {
        $validValues = ['internal', 'external', 'anchor', 'image', 'all'];
        $invalidValues = ['invalid', 'unknown', '', 'INTERNAL', 'Internal', 'external_links'];

        foreach ($validValues as $value) {
            $case = ValidationScope::tryFrom($value);
            expect($case)->not->toBeNull("tryFrom('{$value}') should return a case");
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            $case = ValidationScope::tryFrom($value);
            expect($case)->toBeNull("tryFrom('{$value}') should return null");
        }
    });

    test('from() works correctly and throws for invalid values', function (): void {
        $validValues = ['internal', 'external', 'anchor', 'image', 'all'];
        $invalidValues = ['invalid', 'unknown', '', 'INTERNAL', 'Internal', 'external_links'];

        foreach ($validValues as $value) {
            $case = ValidationScope::from($value);
            expect($case)->toBeInstanceOf(ValidationScope::class);
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            expect(fn () => ValidationScope::from($value))
                ->toThrow(ValueError::class);
        }
    });

    test('isValid() static method works correctly', function (): void {
        $validValues = ['internal', 'external', 'anchor', 'image', 'all'];
        $invalidValues = ['invalid', 'unknown', '', 'INTERNAL', 'Internal', 'external_links'];

        foreach ($validValues as $value) {
            expect(ValidationScope::isValid($value))->toBeTrue("'{$value}' should be valid");
        }

        foreach ($invalidValues as $value) {
            expect(ValidationScope::isValid($value))->toBeFalse("'{$value}' should be invalid");
        }
    });

    test('fromString() works correctly with case conversion', function (): void {
        $validInputs = [
            'internal' => 'internal',
            'INTERNAL' => 'internal',
            'Internal' => 'internal',
            'EXTERNAL' => 'external',
            'external' => 'external',
            'External' => 'external',
            'ALL' => 'all',
            'all' => 'all',
            'All' => 'all',
        ];
        $invalidInputs = ['invalid', 'unknown', '', 'external_links', 'internal_only'];

        foreach ($validInputs as $input => $expectedCase) {
            $case = ValidationScope::fromString($input);
            expect($case)->toBeInstanceOf(ValidationScope::class);
            expect($case->value)->toBe($expectedCase);
        }

        foreach ($invalidInputs as $input) {
            expect(fn (): ValidationScope => ValidationScope::fromString($input))
                ->toThrow(ValueError::class);
        }
    });

    test('getSelectOptions() returns properly formatted array', function (): void {
        $options = ValidationScope::getSelectOptions();

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();
        expect($options)->toHaveCount(5);

        // The actual implementation returns an associative array: value => description
        foreach ($options as $value => $description) {
            expect($value)->toBeString();
            expect($description)->toBeString();
            expect($description)->not->toBeEmpty();
            expect(ValidationScope::isValid($value))->toBeTrue();
        }
    });

    test('includesInternal() returns correct values', function (): void {
        $trueCases = ['internal', 'all'];
        $falseCases = ['external', 'anchor', 'image'];

        foreach ($trueCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesInternal())->toBeTrue("Method includesInternal() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesInternal())->toBeFalse("Method includesInternal() should return false for case {$caseValue}");
        }
    });

    test('includesExternal() returns correct values', function (): void {
        $trueCases = ['external', 'all'];
        $falseCases = ['internal', 'anchor', 'image'];

        foreach ($trueCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesExternal())->toBeTrue("Method includesExternal() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesExternal())->toBeFalse("Method includesExternal() should return false for case {$caseValue}");
        }
    });

    test('includesAnchor() returns correct values', function (): void {
        $trueCases = ['anchor', 'all'];
        $falseCases = ['internal', 'external', 'image'];

        foreach ($trueCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesAnchor())->toBeTrue("Method includesAnchor() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesAnchor())->toBeFalse("Method includesAnchor() should return false for case {$caseValue}");
        }
    });

    test('includesImage() returns correct values', function (): void {
        $trueCases = ['image', 'all'];
        $falseCases = ['internal', 'external', 'anchor'];

        foreach ($trueCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesImage())->toBeTrue("Method includesImage() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = ValidationScope::from($caseValue);
            expect($case->includesImage())->toBeFalse("Method includesImage() should return false for case {$caseValue}");
        }
    });

    test('getDescription() returns non-empty strings', function (): void {
        foreach (ValidationScope::cases() as $case) {
            $description = $case->getDescription();
            expect($description)->toBeString();
            expect($description)->not->toBeEmpty("Description for case {$case->name} should not be empty");
        }
    });

    test('getHelpText() returns non-empty strings', function (): void {
        foreach (ValidationScope::cases() as $case) {
            $helpText = $case->getHelpText();
            expect($helpText)->toBeString();
            expect($helpText)->not->toBeEmpty("Help text for case {$case->name} should not be empty");
        }
    });

    test('getIncludedScopes() returns correct arrays', function (): void {
        $expectedIncludedScopes = [
            'internal' => [ValidationScope::INTERNAL],
            'external' => [ValidationScope::EXTERNAL],
            'anchor' => [ValidationScope::ANCHOR],
            'image' => [ValidationScope::IMAGE],
            'all' => [ValidationScope::INTERNAL, ValidationScope::EXTERNAL, ValidationScope::ANCHOR, ValidationScope::IMAGE],
        ];

        foreach ($expectedIncludedScopes as $caseValue => $expectedReturn) {
            $case = ValidationScope::from($caseValue);
            $actualReturn = $case->getIncludedScopes();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getIncludedScopes() for case {$caseValue} should return expected array");
        }
    });

    test('getPriority() returns correct integer values', function (): void {
        $expectedPriorities = [
            'internal' => 1,
            'anchor' => 2,
            'image' => 3,
            'external' => 4,
            'all' => 0,
        ];

        foreach ($expectedPriorities as $caseValue => $expectedReturn) {
            $case = ValidationScope::from($caseValue);
            $actualReturn = $case->getPriority();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getPriority() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getPriority() returns values in valid range', function (): void {
        foreach (ValidationScope::cases() as $case) {
            $value = $case->getPriority();
            expect($value)->toBeInt("Method getPriority() for case {$case->name} should return an integer");
            expect($value)->toBeGreaterThanOrEqual(0);
            expect($value)->toBeLessThanOrEqual(4);
        }
    });

    test('serialization works correctly', function (): void {
        foreach (ValidationScope::cases() as $case) {
            // Test JSON serialization
            $json = json_encode($case);
            expect($json)->toBeString();
            expect($json)->toContain($case->value);

            // Test that we can recreate from value
            $recreated = ValidationScope::from($case->value);
            expect($recreated)->toBe($case);
        }
    });

    test('comparison operations work correctly', function (): void {
        $cases = ValidationScope::cases();

        foreach ($cases as $case1) {
            foreach ($cases as $case2) {
                if ($case1 === $case2) {
                    expect($case1)->toBe($case2);
                    expect($case1->value)->toBe($case2->value);
                } else {
                    expect($case1)->not->toBe($case2);
                }
            }
        }
    });

    test('all methods return consistent types', function (): void {
        // Test optional string methods
        $optionalStringMethods = ['getDescription', 'getHelpText'];
        foreach ($optionalStringMethods as $method) {
            if (method_exists(ValidationScope::class, $method)) {
                foreach (ValidationScope::cases() as $case) {
                    $value = $case->$method();
                    expect($value)->toBeString("Method {$method}() for case {$case->name} should return a string");
                }
            }
        }

        // Test array methods
        foreach (ValidationScope::cases() as $case) {
            $value = $case->getIncludedScopes();
            expect($value)->toBeArray("Method getIncludedScopes() for case {$case->name} should return an array");
        }

        // Test integer methods
        foreach (ValidationScope::cases() as $case) {
            $value = $case->getPriority();
            expect($value)->toBeInt("Method getPriority() for case {$case->name} should return an integer");
        }

        // Test boolean methods
        $boolMethods = ['includesInternal', 'includesExternal', 'includesAnchor', 'includesImage'];
        foreach ($boolMethods as $method) {
            foreach (ValidationScope::cases() as $case) {
                $value = $case->$method();
                expect($value)->toBeBool("Method {$method}() for case {$case->name} should return a boolean");
            }
        }
    });

    // Business logic and edge case tests
    test('ALL scope includes all other scopes', function (): void {
        $allScope = ValidationScope::ALL;

        expect($allScope->includesInternal())->toBeTrue();
        expect($allScope->includesExternal())->toBeTrue();
        expect($allScope->includesAnchor())->toBeTrue();
        expect($allScope->includesImage())->toBeTrue();

        $includedScopes = $allScope->getIncludedScopes();
        expect($includedScopes)->toHaveCount(4);
        expect($includedScopes)->toContain(ValidationScope::INTERNAL);
        expect($includedScopes)->toContain(ValidationScope::EXTERNAL);
        expect($includedScopes)->toContain(ValidationScope::ANCHOR);
        expect($includedScopes)->toContain(ValidationScope::IMAGE);
    });

    test('individual scopes only include themselves', function (): void {
        $individualScopes = [
            ValidationScope::INTERNAL, ValidationScope::EXTERNAL,
            ValidationScope::ANCHOR, ValidationScope::IMAGE,
        ];

        foreach ($individualScopes as $scope) {
            $includedScopes = $scope->getIncludedScopes();
            expect($includedScopes)->toHaveCount(1);
            expect($includedScopes[0])->toBe($scope);
        }
    });

    test('priority ordering is logical', function (): void {
        // ALL should have highest priority (lowest number)
        expect(ValidationScope::ALL->getPriority())->toBe(0);

        // INTERNAL should have higher priority than EXTERNAL
        expect(ValidationScope::INTERNAL->getPriority())
            ->toBeLessThan(ValidationScope::EXTERNAL->getPriority());

        // ANCHOR should have higher priority than IMAGE
        expect(ValidationScope::ANCHOR->getPriority())
            ->toBeLessThan(ValidationScope::IMAGE->getPriority());

        // EXTERNAL should have lowest priority among individual scopes
        expect(ValidationScope::EXTERNAL->getPriority())->toBe(4);
    });

    test('scope inclusion methods are mutually exclusive for individual scopes', function (): void {
        $testCases = [
            ValidationScope::INTERNAL->value => ['scope' => ValidationScope::INTERNAL, 'includesInternal' => true, 'includesExternal' => false, 'includesAnchor' => false, 'includesImage' => false],
            ValidationScope::EXTERNAL->value => ['scope' => ValidationScope::EXTERNAL, 'includesInternal' => false, 'includesExternal' => true, 'includesAnchor' => false, 'includesImage' => false],
            ValidationScope::ANCHOR->value => ['scope' => ValidationScope::ANCHOR, 'includesInternal' => false, 'includesExternal' => false, 'includesAnchor' => true, 'includesImage' => false],
            ValidationScope::IMAGE->value => ['scope' => ValidationScope::IMAGE, 'includesInternal' => false, 'includesExternal' => false, 'includesAnchor' => false, 'includesImage' => true],
        ];

        foreach ($testCases as $scopeValue => $testData) {
            $scope = $testData['scope'];
            unset($testData['scope']); // Remove scope from expectations array

            foreach ($testData as $method => $expected) {
                $result = $scope->$method();
                expect($result)->toBe($expected,
                    "Scope {$scope->value} method {$method}() should return ".($expected ? 'true' : 'false')
                );
            }
        }
    });

    test('fromString() handles case insensitivity correctly', function (): void {
        $testCases = [
            'internal' => ValidationScope::INTERNAL,
            'INTERNAL' => ValidationScope::INTERNAL,
            'Internal' => ValidationScope::INTERNAL,
            'iNtErNaL' => ValidationScope::INTERNAL,
            'external' => ValidationScope::EXTERNAL,
            'EXTERNAL' => ValidationScope::EXTERNAL,
            'all' => ValidationScope::ALL,
            'ALL' => ValidationScope::ALL,
        ];

        foreach ($testCases as $input => $expectedScope) {
            $actualScope = ValidationScope::fromString($input);
            expect($actualScope)->toBe($expectedScope,
                "fromString('{$input}') should return {$expectedScope->name}"
            );
        }
    });

    test('descriptions and help text are distinct and informative', function (): void {
        foreach (ValidationScope::cases() as $scope) {
            $description = $scope->getDescription();
            $helpText = $scope->getHelpText();

            expect($description)->not->toBe($helpText,
                "Description and help text for {$scope->name} should be different"
            );

            expect(str_contains($description, 'Validate'))->toBe(true,
                "Description for {$scope->name} should mention validation"
            );

            expect(str_contains($helpText, 'Validates'))->toBe(true,
                "Help text for {$scope->name} should mention validation action"
            );
        }
    });
});
