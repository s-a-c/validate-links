<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

use InvalidArgumentException;
use ValueError;

/**
 * Test implementation extracted from tests.old/Traits/EnumTestHelpers.php
 */
trait EnumTestHelpers
{
    /**
     * Test that all enum cases are properly defined with expected values.
     */
    protected function assertEnumCasesMatch(string $enumClass, array $expectedCases): void
    {
        $actualCases = $enumClass::cases();

        expect($actualCases)->toHaveCount(count($expectedCases));

        foreach ($expectedCases as $name => $value) {
            $case = $enumClass::tryFrom($value);
            expect($case)->not->toBeNull("Case {$name} with value '{$value}' should exist");
            expect($case->name)->toBe($name);
            expect($case->value)->toBe($value);
        }
    }

    /**
     * Test that enum values() static method returns correct array.
     */
    protected function assertEnumValuesMethod(string $enumClass, array $expectedValues): void
    {
        $values = $enumClass::values();

        expect($values)->toBeArray();
        expect($values)->toHaveCount(count($expectedValues));
        expect($values)->toEqual(array_values($expectedValues));
    }

    /**
     * Test that enum names() static method returns correct array (if it exists).
     */
    protected function assertEnumNamesMethod(string $enumClass, array $expectedNames): void
    {
        if (! method_exists($enumClass, 'names')) {
            return;
        }

        $names = $enumClass::names();

        expect($names)->toBeArray();
        expect($names)->toHaveCount(count($expectedNames));
        expect($names)->toEqual(array_keys($expectedNames));
    }

    /**
     * Test that isValid() static method works correctly.
     */
    protected function assertEnumIsValidMethod(string $enumClass, array $validValues, array $invalidValues): void
    {
        if (! method_exists($enumClass, 'isValid')) {
            return;
        }

        foreach ($validValues as $value) {
            expect($enumClass::isValid($value))->toBeTrue("'{$value}' should be valid");
        }

        foreach ($invalidValues as $value) {
            expect($enumClass::isValid($value))->toBeFalse("'{$value}' should be invalid");
        }
    }

    /**
     * Test that tryFrom() works correctly for valid and invalid values.
     */
    protected function assertEnumTryFromMethod(string $enumClass, array $validValues, array $invalidValues): void
    {
        foreach ($validValues as $value) {
            $case = $enumClass::tryFrom($value);
            expect($case)->not->toBeNull("tryFrom('{$value}') should return a case");
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            $case = $enumClass::tryFrom($value);
            expect($case)->toBeNull("tryFrom('{$value}') should return null");
        }
    }

    /**
     * Test that from() works correctly and throws for invalid values.
     */
    protected function assertEnumFromMethod(string $enumClass, array $validValues, array $invalidValues): void
    {
        foreach ($validValues as $value) {
            $case = $enumClass::from($value);
            expect($case)->toBeInstanceOf($enumClass);
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            expect(fn () => $enumClass::from($value))
                ->toThrow(ValueError::class);
        }
    }

    /**
     * Test that getSelectOptions() returns properly formatted array.
     */
    protected function assertEnumSelectOptionsMethod(string $enumClass): void
    {
        if (! method_exists($enumClass, 'getSelectOptions')) {
            return;
        }

        $options = $enumClass::getSelectOptions();

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();

        foreach ($options as $option) {
            expect($option)->toBeArray();
            expect($option)->toHaveKeys(['value', 'label']);
            expect($option['value'])->toBeString();
            expect($option['label'])->toBeString();
        }
    }

    /**
     * Test that a method returns expected values for all enum cases.
     */
    protected function assertEnumMethodReturns(string $enumClass, string $method, array $expectedReturns): void
    {
        foreach ($expectedReturns as $caseValue => $expectedReturn) {
            $case = $enumClass::from($caseValue);
            $actualReturn = $case->$method();

            expect($actualReturn)->toBe($expectedReturn,
                "Method {$method}() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    }

    /**
     * Test that a boolean method returns expected values for all enum cases.
     */
    protected function assertEnumBooleanMethod(string $enumClass, string $method, array $trueCases, array $falseCases): void
    {
        foreach ($trueCases as $caseValue) {
            $case = $enumClass::from($caseValue);
            expect($case->$method())->toBeTrue("Method {$method}() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = $enumClass::from($caseValue);
            expect($case->$method())->toBeFalse("Method {$method}() should return false for case {$caseValue}");
        }
    }

    /**
     * Test that all enum cases have non-empty string descriptions.
     */
    protected function assertEnumDescriptionsNotEmpty(string $enumClass): void
    {
        if (! method_exists($enumClass, 'getDescription')) {
            return;
        }

        foreach ($enumClass::cases() as $case) {
            $description = $case->getDescription();
            expect($description)->toBeString();
            expect($description)->not->toBeEmpty("Description for case {$case->name} should not be empty");
        }
    }

    /**
     * Test that all enum cases have non-empty help text.
     */
    protected function assertEnumHelpTextNotEmpty(string $enumClass): void
    {
        if (! method_exists($enumClass, 'getHelpText')) {
            return;
        }

        foreach ($enumClass::cases() as $case) {
            $helpText = $case->getHelpText();
            expect($helpText)->toBeString();
            expect($helpText)->not->toBeEmpty("Help text for case {$case->name} should not be empty");
        }
    }

    /**
     * Test that a method returns an array with expected structure.
     */
    protected function assertEnumArrayMethod(string $enumClass, string $method, array $expectedStructure): void
    {
        foreach ($expectedStructure as $caseValue => $expectedArray) {
            $case = $enumClass::from($caseValue);
            $actualArray = $case->$method();

            expect($actualArray)->toBeArray("Method {$method}() for case {$caseValue} should return an array");
            expect($actualArray)->toEqual($expectedArray);
        }
    }

    /**
     * Test that a method returns integer values within expected range.
     */
    protected function assertEnumIntegerMethod(string $enumClass, string $method, int $min, int $max): void
    {
        foreach ($enumClass::cases() as $case) {
            $value = $case->$method();
            expect($value)->toBeInt("Method {$method}() for case {$case->name} should return an integer");
            expect($value)->toBeGreaterThanOrEqual($min);
            expect($value)->toBeLessThanOrEqual($max);
        }
    }

    /**
     * Test that a method returns nullable integer values.
     */
    protected function assertEnumNullableIntegerMethod(string $enumClass, string $method, array $expectedValues): void
    {
        foreach ($expectedValues as $caseValue => $expectedValue) {
            $case = $enumClass::from($caseValue);
            $actualValue = $case->$method();

            if ($expectedValue === null) {
                expect($actualValue)->toBeNull("Method {$method}() for case {$caseValue} should return null");
            } else {
                expect($actualValue)->toBeInt("Method {$method}() for case {$caseValue} should return an integer");
                expect($actualValue)->toBe($expectedValue);
            }
        }
    }

    /**
     * Test that fromString() method works correctly (if it exists).
     */
    protected function assertEnumFromStringMethod(string $enumClass, array $validInputs, array $invalidInputs): void
    {
        if (! method_exists($enumClass, 'fromString')) {
            return;
        }

        foreach ($validInputs as $input => $expectedCase) {
            $case = $enumClass::fromString($input);
            expect($case)->toBeInstanceOf($enumClass);
            expect($case->value)->toBe($expectedCase);
        }

        foreach ($invalidInputs as $input) {
            expect(fn () => $enumClass::fromString($input))
                ->toThrow(ValueError::class);
        }
    }

    /**
     * Test that all enum cases return consistent data types for a method.
     */
    protected function assertEnumMethodConsistentType(string $enumClass, string $method, string $expectedType): void
    {
        foreach ($enumClass::cases() as $case) {
            $value = $case->$method();

            match ($expectedType) {
                'string' => expect($value)->toBeString("Method {$method}() for case {$case->name} should return a string"),
                'int' => expect($value)->toBeInt("Method {$method}() for case {$case->name} should return an integer"),
                'bool' => expect($value)->toBeBool("Method {$method}() for case {$case->name} should return a boolean"),
                'array' => expect($value)->toBeArray("Method {$method}() for case {$case->name} should return an array"),
                default => throw new InvalidArgumentException("Unsupported type: {$expectedType}")
            };
        }
    }

    /**
     * Test that enum serialization/deserialization works correctly.
     */
    protected function assertEnumSerialization(string $enumClass): void
    {
        foreach ($enumClass::cases() as $case) {
            // Test JSON serialization
            $json = json_encode($case);
            expect($json)->toBeString();
            expect($json)->toContain($case->value);

            // Test that we can recreate from value
            $recreated = $enumClass::from($case->value);
            expect($recreated)->toBe($case);
        }
    }

    /**
     * Test that enum comparison operations work correctly.
     */
    protected function assertEnumComparisons(string $enumClass): void
    {
        $cases = $enumClass::cases();

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
    }
}
