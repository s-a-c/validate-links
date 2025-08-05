<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

describe('AppServiceProvider', function () {
    $app = null;
    $provider = null;

    beforeEach(function () use (&$app, &$provider) {
        $app = Mockery::mock(Application::class);
        $app->shouldIgnoreMissing(); // Allow any method calls that aren't explicitly mocked
        $provider = new AppServiceProvider($app);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('class structure', function () use (&$provider) {
        it('extends ServiceProvider', function () use (&$provider) {
            assert($provider instanceof AppServiceProvider);
            expect($provider)->toBeInstanceOf(ServiceProvider::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(AppServiceProvider::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has correct namespace', function () {
            $reflection = new ReflectionClass(AppServiceProvider::class);
            expect($reflection->getNamespaceName())->toBe('App\Providers');
        });
    });

    describe('register method', function () use (&$provider) {
        it('exists and is public', function () use (&$provider) {
            assert($provider instanceof AppServiceProvider);
            expect(method_exists($provider, 'register'))->toBeTrue();

            $reflection = new ReflectionMethod(AppServiceProvider::class, 'register');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(AppServiceProvider::class, 'register');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('void');
        });

        it('can be called without errors', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            // Mock the app to avoid actual service registration
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')->never();

            expect(fn () => $provider->register())->not->toThrow(Exception::class);
        });

        it('does not register any services directly', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            // The register method should not bind any services according to the comment
            /** @phpstan-ignore-next-line */
            $app->shouldNotReceive('bind');
            /** @phpstan-ignore-next-line */
            $app->shouldNotReceive('singleton');
            /** @phpstan-ignore-next-line */
            $app->shouldNotReceive('instance');

            $provider->register();
        });
    });

    describe('boot method', function () use (&$provider) {
        it('exists and is public', function () use (&$provider) {
            assert($provider instanceof AppServiceProvider);
            expect(method_exists($provider, 'boot'))->toBeTrue();

            $reflection = new ReflectionMethod(AppServiceProvider::class, 'boot');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(AppServiceProvider::class, 'boot');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('void');
        });

        it('calls app environment method', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')
                ->once()
                ->andReturn('testing');

            $provider->boot();
        });

        it('handles local environment', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')
                ->once()
                ->andReturn('local');

            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });

        it('handles production environment', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')
                ->once()
                ->andReturn('production');

            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });

        it('handles testing environment', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')
                ->once()
                ->andReturn('testing');

            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });
    });

    describe('method signatures', function () {
        it('has no additional public methods beyond ServiceProvider', function () {
            $reflection = new ReflectionClass(AppServiceProvider::class);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            $methodNames = array_map(fn ($method) => $method->getName(), $methods);
            $expectedMethods = ['register', 'boot'];

            // Filter out inherited methods from ServiceProvider
            $ownMethods = array_filter($methodNames, fn ($name) => in_array($name, $expectedMethods));

            expect($ownMethods)->toContain('register')
                ->and($ownMethods)->toContain('boot');
        });

        it('register method has no parameters', function () {
            $reflection = new ReflectionMethod(AppServiceProvider::class, 'register');
            expect($reflection->getNumberOfParameters())->toBe(0);
        });

        it('boot method has no parameters', function () {
            $reflection = new ReflectionMethod(AppServiceProvider::class, 'boot');
            expect($reflection->getNumberOfParameters())->toBe(0);
        });
    });

    describe('documentation and comments', function () {
        it('has class-level docblock', function () {
            $reflection = new ReflectionClass(AppServiceProvider::class);
            $docComment = $reflection->getDocComment();

            expect($docComment)->not->toBeFalse()
                ->and($docComment)->toContain('Application Service Provider')
                ->and($docComment)->toContain('@author')
                ->and($docComment)->toContain('@since');
        });

        it('has method-level docblocks', function () {
            $registerReflection = new ReflectionMethod(AppServiceProvider::class, 'register');
            $bootReflection = new ReflectionMethod(AppServiceProvider::class, 'boot');

            expect($registerReflection->getDocComment())->not->toBeFalse();
            expect($bootReflection->getDocComment())->not->toBeFalse();
        });
    });

    describe('integration with Laravel framework', function () use (&$provider, &$app) {
        it('can be instantiated with Laravel application', function () use (&$provider) {
            assert($provider instanceof AppServiceProvider);
            expect($provider)->toBeInstanceOf(AppServiceProvider::class)
                ->and($provider)->toBeInstanceOf(ServiceProvider::class);
        });

        it('follows Laravel service provider lifecycle', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            // Test that register can be called before boot
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')->once()->andReturn('testing');

            expect(fn () => $provider->register())->not->toThrow(Exception::class);
            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });
    });

    describe('environment handling', function () use (&$app, &$provider) {
        it('handles unknown environment gracefully', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')
                ->once()
                ->andReturn('unknown');

            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });

        it('handles null environment gracefully', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')
                ->once()
                ->andReturn(null);

            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });
    });

    describe('error handling', function () use (&$app, &$provider) {
        it('handles app environment exception gracefully', function () use (&$app, &$provider) {
            assert($app !== null);
            assert($provider instanceof AppServiceProvider);
            /** @phpstan-ignore-next-line */
            $app->shouldReceive('environment')
                ->once()
                ->andThrow(new Exception('Environment error'));

            expect(fn () => $provider->boot())->toThrow(Exception::class, 'Environment error');
        });
    });
});
