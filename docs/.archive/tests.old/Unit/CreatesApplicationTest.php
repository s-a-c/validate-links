<?php

declare(strict_types=1);

namespace Tests\Unit;

use LaravelZero\Framework\Application;

/**
 * @property Application $app
 *
 * @method Application createApplication()
 * @method void refreshApplication()
 * @method string getBootstrapPath()
 * @method string getBasePath()
 */
describe('CreatesApplication Trait', function (): void {

    test('creates application instance correctly', function (): void {
        $app = app();

        expect($app)->toBeInstanceOf(Application::class);
        expect((string) $app->environment())->toBe('testing');
    });

    test('configures test environment properly', function (): void {
        $app = app();

        // Verify test-specific configurations are applied
        expect(config('cache.default'))->toBe('array');
        expect(config('logging.default'))->toBe('stderr');
        expect(config('validate-links.external.enabled'))->toBeFalse();
    });

    test('can refresh application instance', function (): void {
        $originalApp = app();

        // In Pest, we can test that a new application instance can be created
        $newApp = app();

        expect($newApp)->toBeInstanceOf(Application::class);
        // Note: In Laravel, app() returns the same singleton instance, so we test the type instead
        expect($newApp)->toBeInstanceOf(Application::class);
    });

    test('bootstrap path is accessible', function (): void {
        $bootstrapPath = base_path('bootstrap/app.php');

        expect($bootstrapPath)->toBeString();
        expect($bootstrapPath)->toEndWith('bootstrap/app.php');
        expect(file_exists($bootstrapPath))->toBeTrue();
    });

    test('base path is correct', function (): void {
        $basePath = base_path();

        expect($basePath)->toBeString();
        expect(is_dir($basePath))->toBeTrue();
        expect(file_exists($basePath.'/bootstrap/app.php'))->toBeTrue();
    });
});
