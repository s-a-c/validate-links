<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

/**
 * Application Service Provider
 *
 * This provider handles the registration and bootstrapping of core application services.
 * It extends Laravel's ServiceProvider to integrate with the Laravel Zero framework.
 *
 * @author StandAloneComplex
 *
 * @since 1.0.0
 */
final class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * This method is called after all providers have been registered.
     * Use this method to configure services that depend on other services.
     */
    public function boot(): void
    {
        // Bootstrap application-specific services
        // This method is called after all providers have been registered

        // Set up any application-wide configurations
        $environment = $this->app->environment();
        if ($environment === 'local') {
            // Local environment specific bootstrapping
        }
    }

    /**
     * Register any application services.
     *
     * This method is called during the registration phase, before boot().
     * Only bind services here - don't access other services or perform initialization.
     */
    public function register(): void
    {
        // Register application-specific services
        // This method is called during the registration phase

        // Note: Service bindings are handled by ValidateLinksServiceProvider
        // to avoid conflicts and maintain proper dependency management
    }
}
