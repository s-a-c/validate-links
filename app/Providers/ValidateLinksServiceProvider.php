<?php

declare(strict_types=1);

namespace App\Providers;

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

final class ValidateLinksServiceProvider extends ServiceProvider
{
    /**
     * Register services into the container.
     *
     * This method is called FIRST, before boot().
     * Only bind services here - don't access other services.
     */
    public function register(): void
    {
        // Core service interfaces - Order matters for dependencies!

        // Statistics service (no dependencies)
        $this->app->singleton(StatisticsInterface::class, StatisticsService::class);

        // Security validation service (no dependencies)
        $this->app->singleton(SecurityValidationInterface::class, SecurityValidationService::class);

        // GitHub anchor service (no dependencies)
        $this->app->singleton(GitHubAnchorInterface::class, GitHubAnchorService::class);

        // Reporting service (no dependencies)
        $this->app->singleton(ReportingInterface::class, fn (Application $app): ReportingService => new ReportingService());

        // Link validation service (depends on security service)
        $this->app->singleton(LinkValidationInterface::class, fn (Application $app): LinkValidationService => new LinkValidationService(
            $app->make(SecurityValidationInterface::class)
        ));
    }

    /**
     * Bootstrap services.
     *
     * This method is called AFTER register().
     * You can access other services here.
     */
    public function boot(): void
    {
        // Skip all bootstrapping during testing to avoid binding issues
        // Configuration publishing and event listeners are not needed for tests
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            SecurityValidationInterface::class,
            ReportingInterface::class,
            GitHubAnchorInterface::class,
            StatisticsInterface::class,
            LinkValidationInterface::class,
        ];
    }
}
