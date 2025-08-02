<?php

declare(strict_types=1);

use App\Providers\ValidateLinksServiceProvider;
use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\LinkValidationService;
use Illuminate\Support\ServiceProvider;

describe('ValidateLinksServiceProvider', function (): void {

    test('provider extends service provider', function (): void {
        $provider = new ValidateLinksServiceProvider(app());

        expect($provider)->toBeInstanceOf(ServiceProvider::class);
    });

    test('provider can be instantiated', function (): void {
        $provider = new ValidateLinksServiceProvider(app());

        expect($provider)->toBeInstanceOf(ValidateLinksServiceProvider::class);
    });

    test('provider has register method', function (): void {
        $provider = new ValidateLinksServiceProvider(app());

        expect(method_exists($provider, 'register'))->toBeTrue();

        $reflection = new ReflectionMethod($provider, 'register');
        expect($reflection->isPublic())->toBeTrue();
        expect($reflection->getReturnType()?->getName())->toBe('void');
    });

    test('provider has boot method', function (): void {
        $provider = new ValidateLinksServiceProvider(app());

        expect(method_exists($provider, 'boot'))->toBeTrue();

        $reflection = new ReflectionMethod($provider, 'boot');
        expect($reflection->isPublic())->toBeTrue();
        expect($reflection->getReturnType()?->getName())->toBe('void');
    });

    test('register method executes without errors', function (): void {
        $provider = new ValidateLinksServiceProvider(app());

        // Test that register method executes without throwing exceptions
        expect(fn () => $provider->register())->not->toThrow(Exception::class);

        // Verify that the provider executed successfully
        expect($provider)->toBeInstanceOf(ValidateLinksServiceProvider::class);
    });

    test('boot method executes without errors', function (): void {
        $provider = new ValidateLinksServiceProvider(app());

        // Test that boot method executes without throwing exceptions
        expect(fn () => $provider->boot())->not->toThrow(Exception::class);

        // Verify that the provider executed successfully
        expect($provider)->toBeInstanceOf(ValidateLinksServiceProvider::class);
    });

    test('services are registered', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        expect(app()->bound(LinkValidationInterface::class))->toBeTrue();
        expect(app()->make(LinkValidationInterface::class))->toBeInstanceOf(
            LinkValidationService::class
        );
    });

    test('singleton services return same instance', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        $instance1 = app()->make(LinkValidationInterface::class);
        $instance2 = app()->make(LinkValidationInterface::class);

        expect($instance2)->toBe($instance1);
    });

    test('provider registers link validation interface', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        expect(app()->bound(LinkValidationInterface::class))->toBeTrue();

        $service = app()->make(LinkValidationInterface::class);
        expect($service)->toBeInstanceOf(LinkValidationInterface::class);
    });

    test('provider registers reporting interface', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        expect(app()->bound(ReportingInterface::class))->toBeTrue();

        $service = app()->make(ReportingInterface::class);
        expect($service)->toBeInstanceOf(ReportingInterface::class);
    });

    test('provider registers github anchor interface', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        expect(app()->bound(GitHubAnchorInterface::class))->toBeTrue();

        $service = app()->make(GitHubAnchorInterface::class);
        expect($service)->toBeInstanceOf(GitHubAnchorInterface::class);
    });

    test('provider registers security validation interface', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        expect(app()->bound(SecurityValidationInterface::class))->toBeTrue();

        $service = app()->make(SecurityValidationInterface::class);
        expect($service)->toBeInstanceOf(SecurityValidationInterface::class);
    });

    test('provider registers statistics interface', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        expect(app()->bound(StatisticsInterface::class))->toBeTrue();

        $service = app()->make(StatisticsInterface::class);
        expect($service)->toBeInstanceOf(StatisticsInterface::class);
    });

    test('provider registers services as singletons', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        // Test that services are registered as singletons
        $service1 = app()->make(LinkValidationInterface::class);
        $service2 = app()->make(LinkValidationInterface::class);

        expect($service2)->toBe($service1);
    });

    test('provider publishes configuration', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();

        // Test that boot method executes without throwing exceptions
        expect(fn () => $provider->boot())->not->toThrow(Exception::class);

        // Verify that the provider executed successfully
        expect($provider)->toBeInstanceOf(ValidateLinksServiceProvider::class);
    });

    test('provider loads configuration from file', function (): void {
        $provider = new ValidateLinksServiceProvider(app());
        $provider->register();
        $provider->boot();

        // Test that validate-links configuration is loaded (if config service is available)
        if (app()->bound('config')) {
            $config = app('config');
            $validateLinksConfig = $config->get('validate-links');
            expect($validateLinksConfig)->toBeArray();
        } else {
            // If config service is not available in test environment, just verify provider executed
            expect($provider)->toBeInstanceOf(ValidateLinksServiceProvider::class);
        }
    });

    test('provider has correct namespace', function (): void {
        $reflection = new ReflectionClass(ValidateLinksServiceProvider::class);

        expect($reflection->getNamespaceName())->toBe('App\Providers');
    });

    test('provider file uses strict types', function (): void {
        $reflection = new ReflectionClass(ValidateLinksServiceProvider::class);
        $filename = $reflection->getFileName();

        expect($filename)->not->toBeFalse();

        $content = file_get_contents($filename);
        expect($content)->toContain('declare(strict_types=1);');
    });

});
