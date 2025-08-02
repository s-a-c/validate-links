<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Enums\LinkStatus;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use old\Support\Doubles\Contracts\TestLinkValidationImplementation;
use old\Support\Traits\AssertionHelpers;
use old\Support\Traits\MockingHelpers;
use ReflectionClass;

uses(AssertionHelpers::class, MockingHelpers::class);

describe('LinkValidationInterface', function (): void {
    test('interface exists and is interface', function (): void {
        expect(interface_exists(LinkValidationInterface::class))->toBeTrue();

        $reflection = new ReflectionClass(LinkValidationInterface::class);
        expect($reflection->isInterface())->toBeTrue();
    });

    test('interface has required methods', function (): void {
        $reflection = new ReflectionClass(LinkValidationInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method): string => $method->getName(), $methods);

        $requiredMethods = [
            'validateLink',
            'validateLinks',
            'validateFile',
            'extractLinks',
            'isValidUrl',
            'normalizeUrl',
            'getSupportedScopes',
            'supportsScope',
            'validateByScopes',
            'getStatusStatistics',
            'setTimeout',
        ];

        foreach ($requiredMethods as $method) {
            expect($methodNames)->toContain($method, "Interface should have method: {$method}");
        }
    });

    test('validate links method has correct signature', function (): void {
        $reflection = new ReflectionClass(LinkValidationInterface::class);
        $method = $reflection->getMethod('validateLinks');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(2);

        $parameters = $method->getParameters();
        expect($parameters[0]->getName())->toBe('urls');
        expect($parameters[1]->getName())->toBe('config');
    });

    test('validate file method has correct signature', function (): void {
        $reflection = new ReflectionClass(LinkValidationInterface::class);
        $method = $reflection->getMethod('validateFile');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(2);

        $parameters = $method->getParameters();
        expect($parameters[0]->getName())->toBe('filePath');
        expect($parameters[1]->getName())->toBe('config');
    });

    test('validate link method has correct signature', function (): void {
        $reflection = new ReflectionClass(LinkValidationInterface::class);
        $method = $reflection->getMethod('validateLink');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(2);

        $parameters = $method->getParameters();
        expect($parameters[0]->getName())->toBe('url');
        expect($parameters[1]->getName())->toBe('config');
    });

    test('interface can be implemented', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();

        expect($implementation)->toBeInstanceOf(LinkValidationInterface::class);
    });

    test('implementation returns expected types', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();
        $config = ValidationConfig::create([]);

        $linksResult = $implementation->validateLinks(['https://example.com'], $config);
        $fileResult = $implementation->validateFile('test.md', $config);
        $linkResult = $implementation->validateLink('https://example.com', $config);

        expect($linksResult)->toBeArray();
        expect($fileResult)->toBeArray();
        expect($linkResult)->toBeInstanceOf(ValidationResult::class);
    });

    test('implementation handles multiple links', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();
        $config = ValidationConfig::create([]);
        $urls = ['https://example1.com', 'https://example2.com', 'https://example3.com'];

        $result = $implementation->validateLinks($urls, $config);

        expect($result)->toBeArray();
        expect($result)->toHaveCount(3);
        expect($result[0])->toBeInstanceOf(ValidationResult::class);
    });

    test('implementation validates single file', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();
        $config = ValidationConfig::create([]);

        $result = $implementation->validateFile('test.md', $config);

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toBeInstanceOf(ValidationResult::class);
    });

    test('implementation validates single link', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();
        $config = ValidationConfig::create([]);

        $result = $implementation->validateLink('https://example.com', $config);

        expect($result)->toBeInstanceOf(ValidationResult::class);
        expect($result->getUrl())->toBe('https://example.com');
        expect($result->getStatus())->toBe(LinkStatus::VALID);
    });

    test('interface methods are callable', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();

        // Test that methods exist and can be called (first-class callables are always callable)
        expect($implementation->validateLinks(...))->toBeCallable();
        expect($implementation->validateFile(...))->toBeCallable();
        expect($implementation->validateLink(...))->toBeCallable();
        expect($implementation->extractLinks(...))->toBeCallable();
        expect($implementation->isValidUrl(...))->toBeCallable();
    });

    test('interface has proper namespace', function (): void {
        $reflection = new ReflectionClass(LinkValidationInterface::class);

        expect($reflection->getNamespaceName())->toBe('App\Services\Contracts');
    });

    test('interface can be used for type hinting', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();

        expect($implementation)->toBeInstanceOf(LinkValidationInterface::class);

        // Test that all interface methods are implemented
        $reflection = new ReflectionClass(LinkValidationInterface::class);
        $interfaceMethods = $reflection->getMethods();

        foreach ($interfaceMethods as $method) {
            expect(method_exists($implementation, $method->getName()))->toBeTrue(
                "Implementation should have method: {$method->getName()}"
            );
        }
    });

    test('implementation extracts links correctly', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();

        $result = $implementation->extractLinks('Some content with [link](https://example.com)');

        expect($result)->toBeArray();
        expect($result)->not->toBeEmpty();
        expect($result[0])->toHaveKey('url');
        expect($result[0])->toHaveKey('text');
        expect($result[0])->toHaveKey('line');
    });

    test('implementation validates URLs correctly', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestLinkValidationImplementation();

        expect($implementation->isValidUrl('https://example.com'))->toBeTrue();
        expect($implementation->isValidUrl('http://example.com'))->toBeTrue();
        expect($implementation->isValidUrl('invalid-url'))->toBeFalse();
        expect($implementation->isValidUrl(''))->toBeFalse();
    });

});
