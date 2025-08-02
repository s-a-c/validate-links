<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\StatisticsInterface;
use old\Support\Doubles\Contracts\TestGitHubAnchorImplementation;
use old\Support\Doubles\Contracts\TestSecurityValidationImplementation;
use old\Support\Doubles\Contracts\TestStatisticsImplementation;
use old\Support\Traits\AssertionHelpers;
use old\Support\Traits\MockingHelpers;
use ReflectionClass;

uses(AssertionHelpers::class, MockingHelpers::class);

describe('ServiceContracts', function (): void {
    test('github anchor interface exists and is interface', function (): void {
        expect(interface_exists(GitHubAnchorInterface::class))->toBeTrue();

        $reflection = new ReflectionClass(GitHubAnchorInterface::class);
        expect($reflection->isInterface())->toBeTrue();
    });

    test('security validation interface exists and is interface', function (): void {
        expect(interface_exists(SecurityValidationInterface::class))->toBeTrue();

        $reflection = new ReflectionClass(SecurityValidationInterface::class);
        expect($reflection->isInterface())->toBeTrue();
    });

    test('statistics interface exists and is interface', function (): void {
        expect(interface_exists(StatisticsInterface::class))->toBeTrue();

        $reflection = new ReflectionClass(StatisticsInterface::class);
        expect($reflection->isInterface())->toBeTrue();
    });

    test('github anchor interface can be implemented', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestGitHubAnchorImplementation();

        expect($implementation)->toBeInstanceOf(GitHubAnchorInterface::class);
    });

    test('security validation interface can be implemented', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestSecurityValidationImplementation();

        expect($implementation)->toBeInstanceOf(SecurityValidationInterface::class);
    });

    test('statistics interface can be implemented', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestStatisticsImplementation();

        expect($implementation)->toBeInstanceOf(StatisticsInterface::class);
    });

    test('github anchor interface has proper namespace', function (): void {
        $reflection = new ReflectionClass(GitHubAnchorInterface::class);

        expect($reflection->getNamespaceName())->toBe('App\Services\Contracts');
    });

    test('security validation interface has proper namespace', function (): void {
        $reflection = new ReflectionClass(SecurityValidationInterface::class);

        expect($reflection->getNamespaceName())->toBe('App\Services\Contracts');
    });

    test('statistics interface has proper namespace', function (): void {
        $reflection = new ReflectionClass(StatisticsInterface::class);

        expect($reflection->getNamespaceName())->toBe('App\Services\Contracts');
    });

    test('github anchor interface can be used for type hinting', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestGitHubAnchorImplementation();

        expect($implementation)->toBeInstanceOf(GitHubAnchorInterface::class);
    });

    test('security validation interface can be used for type hinting', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestSecurityValidationImplementation();

        expect($implementation)->toBeInstanceOf(SecurityValidationInterface::class);
    });

    test('statistics interface can be used for type hinting', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestStatisticsImplementation();

        expect($implementation)->toBeInstanceOf(StatisticsInterface::class);
    });

    test('all interfaces are in correct namespace', function (): void {
        $interfaces = [
            GitHubAnchorInterface::class,
            SecurityValidationInterface::class,
            StatisticsInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $reflection = new ReflectionClass($interface);
            expect($reflection->getNamespaceName())->toBe('App\Services\Contracts');
        }
    });

    test('all interfaces are actually interfaces', function (): void {
        $interfaces = [
            GitHubAnchorInterface::class,
            SecurityValidationInterface::class,
            StatisticsInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $reflection = new ReflectionClass($interface);
            expect($reflection->isInterface())->toBeTrue("{$interface} should be an interface");
        }
    });

    test('interfaces have methods', function (): void {
        $interfaces = [
            GitHubAnchorInterface::class,
            SecurityValidationInterface::class,
            StatisticsInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $reflection = new ReflectionClass($interface);
            $methods = $reflection->getMethods();

            expect(count($methods))->toBeGreaterThan(0, "{$interface} should have at least one method");
        }
    });

    test('interface methods are public', function (): void {
        $interfaces = [
            GitHubAnchorInterface::class,
            SecurityValidationInterface::class,
            StatisticsInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $reflection = new ReflectionClass($interface);
            $methods = $reflection->getMethods();

            foreach ($methods as $method) {
                expect($method->isPublic())->toBeTrue(
                    "Method {$method->getName()} in {$interface} should be public"
                );
            }
        }
    });

    test('interface methods are abstract', function (): void {
        $interfaces = [
            GitHubAnchorInterface::class,
            SecurityValidationInterface::class,
            StatisticsInterface::class,
        ];

        foreach ($interfaces as $interface) {
            $reflection = new ReflectionClass($interface);
            $methods = $reflection->getMethods();

            foreach ($methods as $method) {
                expect($method->isAbstract())->toBeTrue(
                    "Method {$method->getName()} in {$interface} should be abstract"
                );
            }
        }
    });

});
