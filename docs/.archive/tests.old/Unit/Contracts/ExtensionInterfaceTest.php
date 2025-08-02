<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Contracts\ExtensionInterface;
use old\Support\Doubles\Contracts\AlternativeExtensionImplementation;
use old\Support\Doubles\Contracts\TestExtensionImplementation;
use old\Support\Traits\AssertionHelpers;
use ReflectionClass;
use ReflectionNamedType;

uses(AssertionHelpers::class);

describe('ExtensionInterface', function (): void {
    test('interface exists and is interface', function (): void {
        expect(interface_exists(ExtensionInterface::class))->toBeTrue();

        $reflection = new ReflectionClass(ExtensionInterface::class);
        expect($reflection->isInterface())->toBeTrue();
    });

    test('interface has required methods', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method): string => $method->getName(), $methods);

        $requiredMethods = [
            'getName',
            'getVersion',
            'getDescription',
            'register',
            'boot',
        ];

        foreach ($requiredMethods as $method) {
            expect($methodNames)->toContain($method, "Interface should have method: {$method}");
        }
    });

    test('get name method has correct signature', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $method = $reflection->getMethod('getName');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(0);

        $returnType = $method->getReturnType();
        expect($returnType)->not->toBeNull();
        if ($returnType !== null && $returnType instanceof ReflectionNamedType) {
            expect($returnType->getName())->toBe('string');
        }
    });

    test('get version method has correct signature', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $method = $reflection->getMethod('getVersion');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(0);

        $returnType = $method->getReturnType();
        expect($returnType)->not->toBeNull();
        if ($returnType !== null && $returnType instanceof ReflectionNamedType) {
            expect($returnType->getName())->toBe('string');
        }
    });

    test('get description method has correct signature', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $method = $reflection->getMethod('getDescription');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(0);

        $returnType = $method->getReturnType();
        expect($returnType)->not->toBeNull();
        if ($returnType !== null && $returnType instanceof ReflectionNamedType) {
            expect($returnType->getName())->toBe('string');
        }
    });

    test('register method has correct signature', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $method = $reflection->getMethod('register');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(0);
        expect($method->getReturnType())->toBeNull();
    });

    test('boot method has correct signature', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $method = $reflection->getMethod('boot');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(0);
        expect($method->getReturnType())->toBeNull();
    });

    test('interface can be implemented', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestExtensionImplementation();

        expect($implementation)->toBeInstanceOf(ExtensionInterface::class);
    });

    test('implementation returns expected types', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestExtensionImplementation();

        expect($implementation->getName())->toBeString();
        expect($implementation->getVersion())->toBeString();
        expect($implementation->getDescription())->toBeString();

        // register() and boot() should not throw exceptions
        // No assertions needed;
        $implementation->register();
        $implementation->boot();
    });

    test('implementation provides meaningful data', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestExtensionImplementation();

        $name = $implementation->getName();
        $version = $implementation->getVersion();
        $description = $implementation->getDescription();

        expect($name)->not->toBeEmpty();
        expect($version)->not->toBeEmpty();
        expect($description)->not->toBeEmpty();

        // Version should follow semantic versioning pattern
        expect($version)->toMatch('/^\d+\.\d+\.\d+/');
    });

    test('interface supports multiple implementations', function (): void {
        $implementation1 = new old\Support\Doubles\Contracts\TestExtensionImplementation();
        $implementation2 = new old\Support\Doubles\Contracts\AlternativeExtensionImplementation();

        expect($implementation1)->toBeInstanceOf(ExtensionInterface::class);
        expect($implementation2)->toBeInstanceOf(ExtensionInterface::class);

        // Different implementations should have different names
        expect($implementation1->getName())->not->toBe($implementation2->getName());
    });

    test('interface methods are callable', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestExtensionImplementation();

        // Test that methods exist and can be called (first-class callables are always callable)
        expect($implementation->getName(...))->toBeCallable();
        expect($implementation->getVersion(...))->toBeCallable();
        expect($implementation->getDescription(...))->toBeCallable();
        expect($implementation->register(...))->toBeCallable();
        expect($implementation->boot(...))->toBeCallable();
    });

    test('interface has proper namespace', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);

        expect($reflection->getNamespaceName())->toBe('App\Contracts');
    });

    test('interface file uses strict types', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $filename = $reflection->getFileName();

        expect($filename)->not->toBeFalse();
        if ($filename !== false) {
            $content = file_get_contents($filename);
            expect($content)->toContain('declare(strict_types=1);');
        }
    });

    test('interface methods have no default implementations', function (): void {
        $reflection = new ReflectionClass(ExtensionInterface::class);
        $methods = $reflection->getMethods();

        foreach ($methods as $method) {
            expect($method->isAbstract())->toBeTrue("Interface method {$method->getName()} should be abstract");
        }
    });

    test('interface can be used for type hinting', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestExtensionImplementation();

        expect($implementation)->toBeInstanceOf(ExtensionInterface::class);
        expect($implementation->getName())->toBeString();
        expect($implementation->getVersion())->toBeString();
        expect($implementation->getDescription())->toBeString();
    });

});
