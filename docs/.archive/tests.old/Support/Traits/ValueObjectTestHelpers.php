<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionProperty;

/**
 * Trait providing shared functionality for testing value object classes.
 *
 * This trait follows the established preference for trait-based composition
 * over base class inheritance, providing reusable testing utilities for
 * all value object test classes.
 *
 * All methods use pure Pest syntax with expect() statements.
 */
trait ValueObjectTestHelpers
{
    /**
     * Assert that a value object is immutable.
     */
    protected function assertValueObjectImmutable(object $valueObject): void
    {
        $reflection = new ReflectionClass($valueObject);
        $properties = $reflection->getProperties();

        foreach ($properties as $property) {
            expect(
                $property->isReadOnly() || $this->isPropertyPrivateOrProtected($property)
            )->toBeTrue("Property {$property->getName()} should be readonly or private/protected for immutability");
        }
    }

    /**
     * Assert that value object has expected constructor parameters.
     */
    protected function assertValueObjectConstructor(
        string $className,
        array $expectedParameters
    ): void {
        $reflection = new ReflectionClass($className);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull('Value object should have a constructor');

        $parameters = $constructor->getParameters();
        $parameterNames = array_map(fn ($param): string => $param->getName(), $parameters);

        foreach ($expectedParameters as $expectedParam) {
            expect($parameterNames)->toContain(
                $expectedParam,
                "Constructor should have parameter: {$expectedParam}"
            );
        }
    }

    /**
     * Assert that value object has factory methods.
     */
    protected function assertValueObjectFactoryMethods(
        string $className,
        array $expectedMethods
    ): void {
        $reflection = new ReflectionClass($className);

        foreach ($expectedMethods as $method) {
            expect($reflection->hasMethod($method))->toBeTrue(
                "Value object should have factory method: {$method}"
            );

            $methodReflection = $reflection->getMethod($method);
            expect($methodReflection->isStatic())->toBeTrue(
                "Factory method {$method} should be static"
            );
        }
    }

    /**
     * Assert that value object has getter methods.
     */
    protected function assertValueObjectGetters(
        object $valueObject,
        array $expectedGetters
    ): void {
        $reflection = new ReflectionClass($valueObject);

        foreach ($expectedGetters as $getter) {
            expect($reflection->hasMethod($getter))->toBeTrue(
                "Value object should have getter method: {$getter}"
            );

            $method = $reflection->getMethod($getter);
            expect($method->isPublic())->toBeTrue(
                "Getter method {$getter} should be public"
            );
        }
    }

    /**
     * Assert that value object can be serialized and unserialized.
     */
    protected function assertValueObjectSerializable(object $valueObject): void
    {
        $serialized = serialize($valueObject);
        $unserialized = unserialize($serialized);

        expect($unserialized)->toBeInstanceOf($valueObject::class);
        expect($unserialized)->toEqual($valueObject);
    }

    /**
     * Assert that value object can be converted to array.
     */
    protected function assertValueObjectToArray(
        object $valueObject,
        array $expectedKeys = []
    ): void {
        expect(method_exists($valueObject, 'toArray'))->toBeTrue(
            'Value object should have toArray method'
        );

        $array = $valueObject->toArray();
        expect($array)->toBeArray();

        foreach ($expectedKeys as $key) {
            expect($array)->toHaveKey($key);
        }
    }

    /**
     * Assert that value object can be created from array.
     */
    protected function assertValueObjectFromArray(
        string $className,
        array $testData
    ): void {
        expect(
            method_exists($className, 'create') || method_exists($className, 'fromArray')
        )->toBeTrue('Value object should have create or fromArray factory method');

        $valueObject = method_exists($className, 'create') ? $className::create($testData) : $className::fromArray($testData);

        expect($valueObject)->toBeInstanceOf($className);
    }

    /**
     * Assert that value object validates input data.
     */
    protected function assertValueObjectValidation(
        string $className,
        array $invalidData,
        string $expectedExceptionClass = InvalidArgumentException::class
    ): void {
        if (method_exists($className, 'create')) {
            expect(fn () => $className::create($invalidData))
                ->toThrow($expectedExceptionClass);
        } else {
            expect(fn (): object => new $className(...array_values($invalidData)))
                ->toThrow($expectedExceptionClass);
        }
    }

    /**
     * Assert that value objects are equal when they have same data.
     */
    protected function assertValueObjectEquality(
        object $valueObject1,
        object $valueObject2,
        bool $shouldBeEqual = true
    ): void {
        if ($shouldBeEqual) {
            expect($valueObject1)->toEqual($valueObject2);
        } else {
            expect($valueObject1)->not->toEqual($valueObject2);
        }
    }

    /**
     * Create test data for value object testing.
     */
    protected function createValueObjectTestData(): array
    {
        return [
            'valid_minimal' => [
                'timeout' => 30,
                'concurrent_requests' => 5,
            ],
            'valid_complete' => [
                'timeout' => 60,
                'concurrent_requests' => 10,
                'max_redirects' => 5,
                'cache_enabled' => true,
                'follow_redirects' => true,
                'user_agent' => 'test-agent/1.0',
            ],
            'invalid_negative_timeout' => [
                'timeout' => -1,
                'concurrent_requests' => 5,
            ],
            'invalid_zero_concurrent' => [
                'timeout' => 30,
                'concurrent_requests' => 0,
            ],
            'invalid_excessive_redirects' => [
                'timeout' => 30,
                'concurrent_requests' => 5,
                'max_redirects' => 100,
            ],
        ];
    }

    /**
     * Test value object with different data scenarios.
     */
    protected function testValueObjectScenarios(
        string $className,
        array $scenarios
    ): void {
        foreach ($scenarios as $scenarioName => $data) {
            if (str_contains($scenarioName, 'invalid')) {
                $this->assertValueObjectValidation($className, $data);
            } else {
                $valueObject = $className::create($data);
                expect($valueObject)->toBeInstanceOf($className);
                $this->assertValueObjectImmutable($valueObject);
            }
        }
    }

    /**
     * Assert that value object has string representation.
     */
    protected function assertValueObjectStringRepresentation(object $valueObject): void
    {
        expect(
            method_exists($valueObject, '__toString') || method_exists($valueObject, 'toString')
        )->toBeTrue('Value object should have string representation method');

        $string = method_exists($valueObject, '__toString') ? (string) $valueObject : $valueObject->toString();

        expect($string)->toBeString();
        expect($string)->not->toBeEmpty();
    }

    /**
     * Assert that value object has JSON representation.
     */
    protected function assertValueObjectJsonRepresentation(object $valueObject): void
    {
        expect(
            method_exists($valueObject, 'jsonSerialize') || method_exists($valueObject, 'toArray')
        )->toBeTrue('Value object should be JSON serializable');

        $json = json_encode($valueObject);
        expect($json)->toBeString();
        expect($json)->not->toBeFalse();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
    }

    /**
     * Set up value object test environment.
     */
    protected function setUpValueObjectTest(): void
    {
        // Configure environment for value object testing
    }

    /**
     * Tear down value object test environment.
     */
    protected function tearDownValueObjectTest(): void
    {
        // Clean up value object test state
    }

    /**
     * Check if property is private or protected.
     */
    private function isPropertyPrivateOrProtected(ReflectionProperty $property): bool
    {
        if ($property->isPrivate()) {
            return true;
        }

        return $property->isProtected();
    }
}
