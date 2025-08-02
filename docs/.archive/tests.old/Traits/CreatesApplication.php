<?php

declare(strict_types=1);

namespace .archive\tests.old\Traits;

use Illuminate\Contracts\Console\Kernel;
use LaravelZero\Framework\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * This implementation extends Laravel Zero's default behavior by:
     * - Ensuring proper application bootstrapping
     * - Setting up test-specific environment configurations
     * - Maintaining compatibility with Laravel Zero 12.0+
     */
    public function createApplication(): Application
    {
        // Load the Laravel Zero application
        $app = require $this->getBootstrapPath();

        // Bootstrap the console kernel for testing
        $app->make(Kernel::class)->bootstrap();

        // Set up test-specific configurations
        $this->configureTestEnvironment($app);

        return $app;
    }

    /**
     * Get the path to the application bootstrap file.
     *
     * This method allows for easy customization of the bootstrap path
     * while maintaining compatibility with standard Laravel Zero structure.
     */
    protected function getBootstrapPath(): string
    {
        return __DIR__.'/../../bootstrap/app.php';
    }

    /**
     * Configure the application for testing environment.
     *
     * This method can be overridden in test classes to provide
     * custom test environment configurations.
     */
    protected function configureTestEnvironment(Application $app): void
    {
        // Set testing environment
        $app['env'] = 'testing';

        // Ensure config service is available before accessing it
        if ($app->bound('config')) {
            // Disable caching for tests by default
            $app['config']->set('cache.default', 'array');

            // Set up test-specific logging
            $app['config']->set('logging.default', 'stderr');

            // Disable external HTTP calls by default in tests
            // This can be overridden in specific tests that need external calls
            $app['config']->set('validate-links.external.enabled', false);
        }
    }

    /**
     * Get the application base path.
     *
     * Provides compatibility with Laravel Zero's application structure.
     */
    protected function getBasePath(): string
    {
        return dirname(__DIR__, 2);
    }

    /**
     * Refresh the application instance.
     *
     * Useful for tests.old that need a fresh application state.
     */
    protected function refreshApplication(): void
    {
        $this->app = $this->createApplication();
    }
}
