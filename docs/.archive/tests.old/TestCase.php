<?php

declare(strict_types=1);

namespace .archive\tests.old;

use LaravelZero\Framework\Application;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\ValidationTestHelpers;
use old\Traits\CreatesApplication;

/**
 * Base test functionality for validate-links application.
 *
 * This trait provides enhanced application bootstrapping and test-specific
 * environment configuration using pure Pest syntax and trait-based composition.
 *
 * Features:
 * - Enhanced application bootstrapping
 * - Test-specific environment configuration
 * - Extensible configuration methods
 * - Laravel Zero 12.0+ compatibility
 * - Pure Pest syntax compliance
 * - Complete type safety with strict declarations
 *
 * @phpstan-type TestConfig array{
 *     performance: array{memory_limit: string},
 *     external: array{timeout: int, max_redirects: int},
 *     cache: array{enabled: bool},
 *     logging: array{enabled: bool}
 * }
 */
trait TestCase
{
    use CreatesApplication;
    use FileTestHelpers;
    use ValidationTestHelpers;

    /**
     * The Laravel application instance.
     */
    protected Application $app;

    /**
     * Configure test-specific settings.
     *
     * This method can be overridden in specific test classes
     * to provide custom configurations.
     */
    protected function configureTestSpecificSettings(): void
    {
        // Performance settings for tests
        config(['validate-links.performance.memory_limit' => '512M']);
        config(['validate-links.external.timeout' => 5]);
        config(['validate-links.external.max_redirects' => 3]);

        // Disable features that might interfere with testing
        config(['validate-links.cache.enabled' => false]);
        config(['validate-links.logging.enabled' => false]);
    }

    /**
     * Set up the test environment.
     */
    protected function setUpTestCase(): void
    {
        $this->app = $this->createApplication();
        $this->configureTestSpecificSettings();
    }

    /**
     * Clean up after test execution.
     */
    protected function tearDownTestCase(): void
    {
        // Clean up any global state
        if (isset($this->app)) {
            $this->app->flush();
        }
    }

    /**
     * Get the application instance.
     */
    protected function getApplication(): Application
    {
        return $this->app;
    }

    /**
     * Refresh the application instance.
     */
    protected function refreshTestApplication(): void
    {
        $this->tearDownTestCase();
        $this->setUpTestCase();
    }
}
