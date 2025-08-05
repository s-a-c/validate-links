<?php

declare(strict_types=1);

use App\Providers\ValidateLinksServiceProvider;
use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\GitHubAnchorService;
use App\Services\LinkValidationService;
use App\Services\ReportingService;
use App\Services\SecurityValidationService;
use App\Services\StatisticsService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

describe('ValidateLinksServiceProvider', function () {
    $app = null;
    $provider = null;

    beforeEach(function () use (&$app, &$provider) {
        $app = Mockery::mock(Application::class);
        $app->shouldIgnoreMissing(); // Allow any method calls that aren't explicitly mocked
        $provider = new ValidateLinksServiceProvider($app);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('class structure', function () use (&$provider) {
        it('extends ServiceProvider', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            expect($provider)->toBeInstanceOf(ServiceProvider::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(ValidateLinksServiceProvider::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has correct namespace', function () {
            $reflection = new ReflectionClass(ValidateLinksServiceProvider::class);
            expect($reflection->getNamespaceName())->toBe('App\Providers');
        });
    });

    describe('register method', function () use (&$provider, &$app) {
        it('exists and is public', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            expect(method_exists($provider, 'register'))->toBeTrue();

            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'register');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'register');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('void');
        });

        it('registers StatisticsInterface as singleton', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);

            $app->shouldReceive('singleton')
                ->once()
                ->with(StatisticsInterface::class, StatisticsService::class);

            $app->shouldReceive('singleton')
                ->with(SecurityValidationInterface::class, SecurityValidationService::class);

            $app->shouldReceive('singleton')
                ->with(GitHubAnchorInterface::class, GitHubAnchorService::class);

            $app->shouldReceive('singleton')
                ->with(ReportingInterface::class, Mockery::type('Closure'));

            $app->shouldReceive('singleton')
                ->with(LinkValidationInterface::class, Mockery::type('Closure'));

            $provider->register();
        });

        it('registers SecurityValidationInterface as singleton', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);

            $app->shouldReceive('singleton')
                ->once()
                ->with(SecurityValidationInterface::class, SecurityValidationService::class);

            $app->shouldReceive('singleton')->times(4); // Other services

            $provider->register();
        });

        it('registers GitHubAnchorInterface as singleton', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);

            $app->shouldReceive('singleton')
                ->once()
                ->with(GitHubAnchorInterface::class, GitHubAnchorService::class);

            $app->shouldReceive('singleton')->times(4); // Other services

            $provider->register();
        });

        it('registers ReportingInterface with closure', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);

            $app->shouldReceive('singleton')
                ->once()
                ->with(ReportingInterface::class, Mockery::type('Closure'));

            $app->shouldReceive('singleton')->times(4); // Other services

            $provider->register();
        });

        it('registers LinkValidationInterface with closure and dependency', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);

            $app->shouldReceive('singleton')
                ->once()
                ->with(LinkValidationInterface::class, Mockery::type('Closure'));

            $app->shouldReceive('singleton')->times(4); // Other services

            $provider->register();
        });

        it('registers all services in correct order', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);

            $app->shouldReceive('singleton')
                ->once()
                ->with(StatisticsInterface::class, StatisticsService::class)
                ->ordered();

            $app->shouldReceive('singleton')
                ->once()
                ->with(SecurityValidationInterface::class, SecurityValidationService::class)
                ->ordered();

            $app->shouldReceive('singleton')
                ->once()
                ->with(GitHubAnchorInterface::class, GitHubAnchorService::class)
                ->ordered();

            $app->shouldReceive('singleton')
                ->once()
                ->with(ReportingInterface::class, Mockery::type('Closure'))
                ->ordered();

            $app->shouldReceive('singleton')
                ->once()
                ->with(LinkValidationInterface::class, Mockery::type('Closure'))
                ->ordered();

            $provider->register();
        });
    });

    describe('boot method', function () use (&$provider, &$app) {
        it('exists and is public', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            expect(method_exists($provider, 'boot'))->toBeTrue();

            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'boot');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'boot');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('void');
        });

        it('can be called without errors', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });

        it('does not perform any operations during testing', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);
            // According to the comment, it skips bootstrapping during testing
            $app->shouldNotReceive('make');
            $app->shouldNotReceive('bind');

            $provider->boot();
        });
    });

    describe('provides method', function () use (&$provider) {
        it('exists and is public', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            expect(method_exists($provider, 'provides'))->toBeTrue();

            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'provides');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'provides');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType)->toBeInstanceOf(ReflectionNamedType::class);
            /** @var ReflectionNamedType $returnType */
            expect($returnType->getName())->toBe('array');
        });

        it('returns array of provided services', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            $services = $provider->provides();

            expect($services)->toBeArray()
                ->and($services)->toContain(SecurityValidationInterface::class)
                ->and($services)->toContain(ReportingInterface::class)
                ->and($services)->toContain(GitHubAnchorInterface::class)
                ->and($services)->toContain(StatisticsInterface::class)
                ->and($services)->toContain(LinkValidationInterface::class);
        });

        it('returns exactly 5 services', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            $services = $provider->provides();
            expect(count($services))->toBe(5);
        });

        it('returns services in correct order', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            $services = $provider->provides();
            $expected = [
                SecurityValidationInterface::class,
                ReportingInterface::class,
                GitHubAnchorInterface::class,
                StatisticsInterface::class,
                LinkValidationInterface::class,
            ];

            expect($services)->toBe($expected);
        });
    });

    describe('service resolution', function () use (&$app, &$provider) {
        it('ReportingInterface closure returns ReportingService', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);
            $closure = null;

            $app->shouldReceive('singleton')
                ->with(ReportingInterface::class, Mockery::capture($closure));

            $app->shouldReceive('singleton')->times(4); // Other services

            $provider->register();

            expect($closure)->toBeCallable();

            $result = $closure($app);
            expect($result)->toBeInstanceOf(ReportingService::class);
        });

        it('LinkValidationInterface closure returns LinkValidationService with dependency', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);
            $closure = null;

            $app->shouldReceive('singleton')
                ->with(LinkValidationInterface::class, Mockery::capture($closure));

            $app->shouldReceive('singleton')->times(4); // Other services

            $provider->register();

            expect($closure)->toBeCallable();

            /** @phpstan-ignore-next-line */
            $securityService = Mockery::mock(SecurityValidationInterface::class);
            $app->shouldReceive('make')
                ->once()
                ->with(SecurityValidationInterface::class)
                ->andReturn($securityService);

            $result = $closure($app);
            expect($result)->toBeInstanceOf(LinkValidationService::class);
        });
    });

    describe('method signatures', function () {
        it('register method has no parameters', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'register');
            expect($reflection->getNumberOfParameters())->toBe(0);
        });

        it('boot method has no parameters', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'boot');
            expect($reflection->getNumberOfParameters())->toBe(0);
        });

        it('provides method has no parameters', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'provides');
            expect($reflection->getNumberOfParameters())->toBe(0);
        });
    });

    describe('dependency management', function () use (&$app, &$provider) {
        it('follows dependency injection principles', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);
            // LinkValidationService depends on SecurityValidationInterface
            // This should be resolved through the container
            $app->shouldReceive('singleton')->times(5);

            expect(fn () => $provider->register())->not->toThrow(Exception::class);
        });

        it('uses singleton pattern for all services', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);
            $app->shouldReceive('singleton')->times(5);
            $app->shouldNotReceive('bind');
            $app->shouldNotReceive('instance');

            $provider->register();
        });
    });

    describe('integration', function () use (&$provider, &$app) {
        it('can be used with Laravel service container', function () use (&$provider) {
            assert($provider instanceof ValidateLinksServiceProvider);
            expect($provider)->toBeInstanceOf(ValidateLinksServiceProvider::class)
                ->and($provider)->toBeInstanceOf(ServiceProvider::class);
        });

        it('follows Laravel service provider lifecycle', function () use (&$app, &$provider) {
            assert($app instanceof Application);
            assert($provider instanceof ValidateLinksServiceProvider);
            $app->shouldReceive('singleton')->times(5);

            expect(fn () => $provider->register())->not->toThrow(Exception::class);
            expect(fn () => $provider->boot())->not->toThrow(Exception::class);
        });
    });
});
