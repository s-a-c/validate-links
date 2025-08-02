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
    beforeEach(function () {
        $this->app = Mockery::mock(Application::class);
        $this->app->shouldIgnoreMissing(); // Allow any method calls that aren't explicitly mocked
        $this->provider = new ValidateLinksServiceProvider($this->app);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('class structure', function () {
        it('extends ServiceProvider', function () {
            expect($this->provider)->toBeInstanceOf(ServiceProvider::class);
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

    describe('register method', function () {
        it('exists and is public', function () {
            expect(method_exists($this->provider, 'register'))->toBeTrue();

            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'register');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'register');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull()
                ->and($returnType->getName())->toBe('void');
        });

        it('registers StatisticsInterface as singleton', function () {
            $this->app->shouldReceive('singleton')
                ->once()
                ->with(StatisticsInterface::class, StatisticsService::class);

            $this->app->shouldReceive('singleton')
                ->with(SecurityValidationInterface::class, SecurityValidationService::class);

            $this->app->shouldReceive('singleton')
                ->with(GitHubAnchorInterface::class, GitHubAnchorService::class);

            $this->app->shouldReceive('singleton')
                ->with(ReportingInterface::class, Mockery::type('Closure'));

            $this->app->shouldReceive('singleton')
                ->with(LinkValidationInterface::class, Mockery::type('Closure'));

            $this->provider->register();
        });

        it('registers SecurityValidationInterface as singleton', function () {
            $this->app->shouldReceive('singleton')
                ->once()
                ->with(SecurityValidationInterface::class, SecurityValidationService::class);

            $this->app->shouldReceive('singleton')->times(4); // Other services

            $this->provider->register();
        });

        it('registers GitHubAnchorInterface as singleton', function () {
            $this->app->shouldReceive('singleton')
                ->once()
                ->with(GitHubAnchorInterface::class, GitHubAnchorService::class);

            $this->app->shouldReceive('singleton')->times(4); // Other services

            $this->provider->register();
        });

        it('registers ReportingInterface with closure', function () {
            $this->app->shouldReceive('singleton')
                ->once()
                ->with(ReportingInterface::class, Mockery::type('Closure'));

            $this->app->shouldReceive('singleton')->times(4); // Other services

            $this->provider->register();
        });

        it('registers LinkValidationInterface with closure and dependency', function () {
            $securityService = Mockery::mock(SecurityValidationInterface::class);

            $this->app->shouldReceive('singleton')
                ->once()
                ->with(LinkValidationInterface::class, Mockery::type('Closure'));

            $this->app->shouldReceive('singleton')->times(4); // Other services

            $this->provider->register();
        });

        it('registers all services in correct order', function () {
            $this->app->shouldReceive('singleton')
                ->once()
                ->with(StatisticsInterface::class, StatisticsService::class)
                ->ordered();

            $this->app->shouldReceive('singleton')
                ->once()
                ->with(SecurityValidationInterface::class, SecurityValidationService::class)
                ->ordered();

            $this->app->shouldReceive('singleton')
                ->once()
                ->with(GitHubAnchorInterface::class, GitHubAnchorService::class)
                ->ordered();

            $this->app->shouldReceive('singleton')
                ->once()
                ->with(ReportingInterface::class, Mockery::type('Closure'))
                ->ordered();

            $this->app->shouldReceive('singleton')
                ->once()
                ->with(LinkValidationInterface::class, Mockery::type('Closure'))
                ->ordered();

            $this->provider->register();
        });
    });

    describe('boot method', function () {
        it('exists and is public', function () {
            expect(method_exists($this->provider, 'boot'))->toBeTrue();

            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'boot');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'boot');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull()
                ->and($returnType->getName())->toBe('void');
        });

        it('can be called without errors', function () {
            expect(fn () => $this->provider->boot())->not->toThrow(Exception::class);
        });

        it('does not perform any operations during testing', function () {
            // According to the comment, it skips bootstrapping during testing
            $this->app->shouldNotReceive('make');
            $this->app->shouldNotReceive('bind');

            $this->provider->boot();
        });
    });

    describe('provides method', function () {
        it('exists and is public', function () {
            expect(method_exists($this->provider, 'provides'))->toBeTrue();

            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'provides');
            expect($reflection->isPublic())->toBeTrue();
        });

        it('has correct return type', function () {
            $reflection = new ReflectionMethod(ValidateLinksServiceProvider::class, 'provides');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull()
                ->and($returnType->getName())->toBe('array');
        });

        it('returns array of provided services', function () {
            $services = $this->provider->provides();

            expect($services)->toBeArray()
                ->and($services)->toContain(SecurityValidationInterface::class)
                ->and($services)->toContain(ReportingInterface::class)
                ->and($services)->toContain(GitHubAnchorInterface::class)
                ->and($services)->toContain(StatisticsInterface::class)
                ->and($services)->toContain(LinkValidationInterface::class);
        });

        it('returns exactly 5 services', function () {
            $services = $this->provider->provides();
            expect(count($services))->toBe(5);
        });

        it('returns services in correct order', function () {
            $services = $this->provider->provides();
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

    describe('service resolution', function () {
        it('ReportingInterface closure returns ReportingService', function () {
            $closure = null;

            $this->app->shouldReceive('singleton')
                ->with(ReportingInterface::class, Mockery::capture($closure));

            $this->app->shouldReceive('singleton')->times(4); // Other services

            $this->provider->register();

            expect($closure)->toBeCallable();

            $result = $closure($this->app);
            expect($result)->toBeInstanceOf(ReportingService::class);
        });

        it('LinkValidationInterface closure returns LinkValidationService with dependency', function () {
            $closure = null;
            $securityService = Mockery::mock(SecurityValidationInterface::class);

            $this->app->shouldReceive('singleton')
                ->with(LinkValidationInterface::class, Mockery::capture($closure));

            $this->app->shouldReceive('singleton')->times(4); // Other services

            $this->provider->register();

            expect($closure)->toBeCallable();

            $this->app->shouldReceive('make')
                ->once()
                ->with(SecurityValidationInterface::class)
                ->andReturn($securityService);

            $result = $closure($this->app);
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

    describe('dependency management', function () {
        it('follows dependency injection principles', function () {
            // LinkValidationService depends on SecurityValidationInterface
            // This should be resolved through the container
            $this->app->shouldReceive('singleton')->times(5);

            expect(fn () => $this->provider->register())->not->toThrow(Exception::class);
        });

        it('uses singleton pattern for all services', function () {
            $this->app->shouldReceive('singleton')->times(5);
            $this->app->shouldNotReceive('bind');
            $this->app->shouldNotReceive('instance');

            $this->provider->register();
        });
    });

    describe('integration', function () {
        it('can be used with Laravel service container', function () {
            expect($this->provider)->toBeInstanceOf(ValidateLinksServiceProvider::class)
                ->and($this->provider)->toBeInstanceOf(ServiceProvider::class);
        });

        it('follows Laravel service provider lifecycle', function () {
            $this->app->shouldReceive('singleton')->times(5);

            expect(fn () => $this->provider->register())->not->toThrow(Exception::class);
            expect(fn () => $this->provider->boot())->not->toThrow(Exception::class);
        });
    });
});
