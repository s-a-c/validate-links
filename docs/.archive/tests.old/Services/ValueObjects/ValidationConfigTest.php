<?php

declare(strict_types=1);

namespace Tests\Services\ValueObjects;

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;
use InvalidArgumentException;
use old\Support\Traits\ValueObjectTestHelpers;
use ReflectionClass;

uses(ValueObjectTestHelpers::class);

describe('ValidationConfig', function (): void {
    beforeEach(function (): void {
        $this->setUpValueObjectTest();
    });

    afterEach(function (): void {
        $this->tearDownValueObjectTest();
    });

    test('construct creates valid instance', function (): void {
        $config = ValidationConfig::create([
            'timeout' => 30,
            'concurrent_requests' => 5,
            'scopes' => [ValidationScope::INTERNAL],
        ]);

        expect($config)->toBeInstanceOf(ValidationConfig::class);
        expect($config->getTimeout())->toBe(30);
        expect($config->getConcurrentRequests())->toBe(5);
        expect($config->getScopes())->toContain(ValidationScope::INTERNAL);
    });

    test('create factory method works', function (): void {
        $data = [
            'timeout' => 60,
            'concurrent_requests' => 10,
            'scopes' => [ValidationScope::EXTERNAL],
        ];

        $config = ValidationConfig::create($data);

        expect($config)->toBeInstanceOf(ValidationConfig::class);

        // Assert immutability
        $reflection = new ReflectionClass($config);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            expect(
                $property->isReadOnly() || $property->isPrivate() || $property->isProtected()
            )->toBeTrue("Property {$property->getName()} should be readonly or private/protected for immutability");
        }
    });

    test('has expected constructor parameters', function (): void {
        $reflection = new ReflectionClass(ValidationConfig::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull('Value object should have a constructor');

        $parameters = $constructor->getParameters();
        $parameterNames = array_map(fn ($param): string => $param->getName(), $parameters);

        $expectedParameters = ['timeout', 'concurrent_requests', 'scopes'];
        foreach ($expectedParameters as $expectedParam) {
            expect($parameterNames)->toContain(
                $expectedParam,
                "Constructor should have parameter: {$expectedParam}"
            );
        }
    });

    test('has expected getter methods', function (): void {
        $config = ValidationConfig::create([
            'timeout' => 30,
            'concurrent_requests' => 5,
        ]);

        $reflection = new ReflectionClass($config);
        $expectedGetters = [
            'getTimeout',
            'getConcurrentRequests',
            'getScopes',
            'getOutputFormat',
            'shouldValidateInternal',
            'shouldValidateExternal',
            'shouldFollowRedirects',
        ];

        foreach ($expectedGetters as $getter) {
            expect($reflection->hasMethod($getter))->toBeTrue(
                "Value object should have getter method: {$getter}"
            );

            $method = $reflection->getMethod($getter);
            expect($method->isPublic())->toBeTrue(
                "Getter method {$getter} should be public"
            );
        }
    });

    test('to array returns expected structure', function (): void {
        $config = ValidationConfig::create([
            'timeout' => 30,
            'concurrent_requests' => 5,
            'scopes' => [ValidationScope::INTERNAL],
        ]);

        expect(method_exists($config, 'toArray'))->toBeTrue(
            'Value object should have toArray method'
        );

        $array = $config->toArray();
        expect($array)->toBeArray();

        $expectedKeys = ['timeout', 'concurrent_requests', 'scopes', 'output_format'];
        foreach ($expectedKeys as $key) {
            expect($array)->toHaveKey($key);
        }
    });

    test('is serializable', function (): void {
        $config = ValidationConfig::create([
            'timeout' => 30,
            'concurrent_requests' => 5,
        ]);

        $serialized = serialize($config);
        $unserialized = unserialize($serialized);

        expect($unserialized)->toBeInstanceOf($config::class);
        expect($unserialized)->toEqual($config);
    });

    test('validates input data', function (): void {
        $invalidData = [
            'timeout' => -1,
            'concurrent_requests' => 0,
        ];

        expect(fn (): ValidationConfig => ValidationConfig::create($invalidData))
            ->toThrow(InvalidArgumentException::class);
    });

    test('equality works correctly', function (): void {
        $config1 = ValidationConfig::create(['timeout' => 30]);
        $config2 = ValidationConfig::create(['timeout' => 30]);
        $config3 = ValidationConfig::create(['timeout' => 60]);

        expect($config1)->toEqual($config2);
        expect($config1)->not->toEqual($config3);
    });

    test('has scope method works', function (): void {
        $config = ValidationConfig::create([
            'scopes' => [ValidationScope::INTERNAL, ValidationScope::EXTERNAL],
        ]);

        expect($config->hasScope(ValidationScope::INTERNAL))->toBeTrue();
        expect($config->hasScope(ValidationScope::ANCHOR))->toBeFalse();
    });

    test('get effective scopes returns correct scopes', function (): void {
        $config = ValidationConfig::create([
            'scopes' => [ValidationScope::INTERNAL],
        ]);

        $effectiveScopes = $config->getEffectiveScopes();
        expect($effectiveScopes)->toBeArray();
        expect($effectiveScopes)->toContain(ValidationScope::INTERNAL);
    });

    test('with scopes creates new instance', function (): void {
        $original = ValidationConfig::create(['timeout' => 30]);
        $modified = $original->withScopes([ValidationScope::EXTERNAL]);

        expect($modified)->not->toBe($original);
        expect($modified->hasScope(ValidationScope::EXTERNAL))->toBeTrue();
        expect($original->hasScope(ValidationScope::EXTERNAL))->toBeFalse();
    });

    test('with output format creates new instance', function (): void {
        $original = ValidationConfig::create(['timeout' => 30]);
        $modified = $original->withOutputFormat(OutputFormat::JSON);

        expect($modified)->not->toBe($original);
        expect($modified->getOutputFormat())->toBe(OutputFormat::JSON);
    });

    test('with defaults applies default values', function (): void {
        $config = ValidationConfig::withDefaults();

        expect($config->getTimeout())->toBeGreaterThan(0);
        expect($config->getConcurrentRequests())->toBeGreaterThan(0);
        expect($config->getScopes())->not->toBeEmpty();
    });
});
