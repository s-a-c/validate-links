<?php

declare(strict_types=1);

namespace Tests\Services\ValueObjects;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationResult;
use old\Support\Traits\ValueObjectTestHelpers;
use ReflectionClass;

uses(ValueObjectTestHelpers::class);

describe('ValidationResult', function (): void {
    beforeEach(function (): void {
        $this->setUpValueObjectTest();
    });

    afterEach(function (): void {
        $this->tearDownValueObjectTest();
    });

    test('construct creates valid instance', function (): void {
        $result = new ValidationResult(
            'https://example.com',
            LinkStatus::VALID,
            ValidationScope::EXTERNAL
        );

        expect($result)->toBeInstanceOf(ValidationResult::class);
        expect($result->getUrl())->toBe('https://example.com');
        expect($result->getStatus())->toBe(LinkStatus::VALID);
        expect($result->getScope())->toBe(ValidationScope::EXTERNAL);
    });

    test('success factory method works', function (): void {
        $result = ValidationResult::success(
            'https://example.com',
            ValidationScope::EXTERNAL,
            200,
            150.0,
            ['custom_data' => 'test']
        );

        expect($result)->toBeInstanceOf(ValidationResult::class);
        expect($result->isValid())->toBeTrue();
        expect($result->getStatus())->toBe(LinkStatus::VALID);

        // Assert immutability
        $reflection = new ReflectionClass($result);
        $properties = $reflection->getProperties();
        foreach ($properties as $property) {
            expect(
                $property->isReadOnly() || $property->isPrivate() || $property->isProtected()
            )->toBeTrue("Property {$property->getName()} should be readonly or private/protected for immutability");
        }
    });

    test('failure factory method works', function (): void {
        $result = ValidationResult::failure(
            'https://broken.example',
            ValidationScope::EXTERNAL,
            LinkStatus::BROKEN,
            'Connection timeout',
            404,
            null,
            1500.0
        );

        expect($result)->toBeInstanceOf(ValidationResult::class);
        expect($result->isValid())->toBeFalse();
        expect($result->isBroken())->toBeTrue();
        expect($result->getError())->toBe('Connection timeout');
    });

    test('has expected getter methods', function (): void {
        $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);

        $reflection = new ReflectionClass($result);
        $expectedGetters = [
            'getUrl',
            'getStatus',
            'getScope',
            'getResponseTime',
            'getHttpStatusCode',
            'getRedirectUrl',
            'getMetadata',
            'getError',
            'getSeverity',
            'getRecommendedAction',
            'getConsoleColor',
            'getFormattedDisplay',
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
        $result = ValidationResult::success(
            'https://example.com',
            ValidationScope::EXTERNAL,
            200,
            150.0
        );

        expect(method_exists($result, 'toArray'))->toBeTrue(
            'Value object should have toArray method'
        );

        $array = $result->toArray();
        expect($array)->toBeArray();

        $expectedKeys = [
            'url',
            'status',
            'scope',
            'response_time',
            'http_status_code',
            'error',
            'metadata',
        ];
        foreach ($expectedKeys as $key) {
            expect($array)->toHaveKey($key);
        }
    });

    test('to json returns valid json', function (): void {
        $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);

        expect(
            method_exists($result, 'jsonSerialize') || method_exists($result, 'toArray')
        )->toBeTrue('Value object should be JSON serializable');

        $json = json_encode($result);
        expect($json)->toBeString();
        expect($json)->not->toBeFalse();

        $decoded = json_decode($json !== false ? $json : '{}', true);
        expect($decoded)->toBeArray();
    });

    test('is serializable', function (): void {
        $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);

        $serialized = serialize($result);
        $unserialized = unserialize($serialized);

        expect($unserialized)->toBeInstanceOf($result::class);
        expect($unserialized)->toEqual($result);
    });

    test('equality works correctly', function (): void {
        $result1 = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);
        $result2 = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);
        $result3 = ValidationResult::failure('https://example.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN);

        expect($result1)->toEqual($result2);
        expect($result1)->not->toEqual($result3);
    });

    test('status checking methods work', function (): void {
        $validResult = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);
        $brokenResult = ValidationResult::failure('https://broken.example', ValidationScope::EXTERNAL, LinkStatus::BROKEN);

        expect($validResult->isValid())->toBeTrue();
        expect($validResult->isBroken())->toBeFalse();
        expect($validResult->isTemporary())->toBeFalse();
        expect($validResult->isSecurityIssue())->toBeFalse();
        expect($validResult->shouldRetry())->toBeFalse();

        expect($brokenResult->isValid())->toBeFalse();
        expect($brokenResult->isBroken())->toBeTrue();
    });

    test('metadata handling works', function (): void {
        $metadata = [
            'response_time' => 150,
            'final_url' => 'https://example.com',
            'redirect_count' => 0,
        ];

        $result = ValidationResult::success(
            'https://example.com',
            ValidationScope::EXTERNAL,
            200,
            150.0,
            $metadata
        );

        expect($result->getMetadata())->toBe($metadata);
        expect($result->getResponseTime())->toBe(150);
    });

    test('error handling works', function (): void {
        $errorMessage = 'Connection timeout after 30 seconds';

        $result = ValidationResult::failure(
            'https://timeout.example',
            ValidationScope::EXTERNAL,
            LinkStatus::TIMEOUT,
            $errorMessage
        );

        expect($result->getError())->toBe($errorMessage);
        expect($result->isValid())->toBeFalse();
    });

    test('console formatting works', function (): void {
        $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);

        expect($result->getConsoleColor())->toBeString();
        expect($result->getFormattedDisplay())->toBeString();
        expect($result->getFormattedDisplay())->toContain('https://example.com');
    });

    test('has string representation', function (): void {
        $result = ValidationResult::success('https://example.com', ValidationScope::EXTERNAL);

        expect(method_exists($result, '__toString'))->toBeTrue(
            'Value object should have __toString method'
        );

        $string = (string) $result;
        expect($string)->toBeString();
        expect($string)->not->toBeEmpty();
    });
});
