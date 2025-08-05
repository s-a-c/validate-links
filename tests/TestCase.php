<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

/**
 * High-Performance TestCase for Laravel Zero with Pest
 * 
 * Eliminates PHPUnit remnants, implements efficient trait composition,
 * optimized database handling, smart caching mechanisms, and clean
 * Laravel Zero integration for maximum test performance.
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions; // Use transactions instead of migrations for speed
    
    /**
     * Performance optimization flags
     */
    protected bool $enableDatabaseTransactions = true;
    protected bool $enableEventFaking = true;
    protected bool $enableQueueFaking = true;
    protected bool $enableCacheClear = true;
    
    /**
     * Memory management
     * @var array<string, mixed>
     */
    private static array $sharedInstances = [];
    private static bool $applicationBooted = false;
    
    /**
     * Boot the testing environment once per test run
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        if (!self::$applicationBooted) {
            self::bootTestingEnvironment();
            self::$applicationBooted = true;
        }
    }
    
    /**
     * Clean up after all tests in class
     */
    public static function tearDownAfterClass(): void
    {
        self::cleanupSharedResources();
        parent::tearDownAfterClass();
    }
    
    /**
     * Boot the testing environment with optimizations
     */
    private static function bootTestingEnvironment(): void
    {
        // Pre-warm commonly used services
        self::$sharedInstances['cache'] = app('cache');
        self::$sharedInstances['events'] = app('events');
        self::$sharedInstances['queue'] = app('queue');
        
        // Optimize database for testing
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA synchronous = OFF');
            DB::statement('PRAGMA journal_mode = MEMORY');
            DB::statement('PRAGMA temp_store = MEMORY');
        }
    }
    
    /**
     * Clean up shared resources
     */
    private static function cleanupSharedResources(): void
    {
        self::$sharedInstances = [];
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }
    
    /**
     * Refresh the application instance for each test
     * Replaces PHPUnit's setUp method with Pest-optimized approach
     */
    protected function refreshApplication(): void
    {
        // Only refresh if not already fresh
        if (!$this->app || $this->app->isDownForMaintenance()) {
            $this->app = $this->createApplication();
        }
        
        $this->optimizeTestEnvironment();
    }
    
    /**
     * Optimize the test environment for performance
     */
    private function optimizeTestEnvironment(): void
    {
        // Disable unnecessary services for testing
        $this->app->bind('log', function () {
            return new \Psr\Log\NullLogger();
        });
        
        // Use array cache for tests
        config(['cache.default' => 'array']);
        
        // Disable broadcasting
        config(['broadcasting.default' => 'null']);
        
        // Optimize session for tests
        config(['session.driver' => 'array']);
        
        // Fake common services if enabled
        if ($this->enableEventFaking) {
            Event::fake();
        }
        
        if ($this->enableQueueFaking) {
            Queue::fake();
        }
        
        if ($this->enableCacheClear) {
            Cache::flush();
        }
    }
    
    /**
     * Clean up test state efficiently
     * Replaces PHPUnit's tearDown method
     */
    protected function cleanupTestState(): void
    {
        // Clear only what's necessary
        if ($this->enableCacheClear && Cache::getStore() instanceof \Illuminate\Cache\ArrayStore) {
            Cache::flush();
        }
        
        // Reset event dispatcher if faked
        if ($this->enableEventFaking && Event::getFacadeRoot() instanceof \Illuminate\Support\Testing\Fakes\EventFake) {
            Event::clearResolvedInstances();
        }
        
        // Reset queue if faked
        if ($this->enableQueueFaking && Queue::getFacadeRoot() instanceof \Illuminate\Support\Testing\Fakes\QueueFake) {
            Queue::clearResolvedInstances();
        }
        
        // Clear resolved instances to prevent memory leaks
        $this->app->forgetInstance('cache');
        $this->app->forgetInstance('events');
        $this->app->forgetInstance('queue');
    }
    
    /**
     * Get shared instance for performance
     */
    protected function getSharedInstance(string $key): mixed
    {
        return self::$sharedInstances[$key] ?? null;
    }
    
    /**
     * Set shared instance for reuse
     */
    protected function setSharedInstance(string $key, mixed $instance): void
    {
        self::$sharedInstances[$key] = $instance;
    }
    
    
    /**
     * Efficient database state management
     */
    protected function refreshDatabase(): void
    {
        if ($this->enableDatabaseTransactions) {
            // Use transactions for speed
            DB::beginTransaction();
            
            // Register cleanup
            $this->beforeApplicationDestroyed(function () {
                DB::rollBack();
            });
        } else {
            // Fallback to migrations if needed
            $this->artisan('migrate:fresh');
        }
    }
    
    /**
     * Smart assertion helpers for common patterns
     */
    protected function assertValidationResult(mixed $result, string $expectedStatus = 'valid'): void
    {
        expect($result)
            ->toBeInstanceOf(\App\Services\ValueObjects\ValidationResult::class);
        
        if ($result instanceof \App\Services\ValueObjects\ValidationResult) {
            expect($result->getStatus()->value)->toBe($expectedStatus);
        }
    }
    
    protected function assertValidationCollection(mixed $collection, ?int $expectedCount = null): void
    {
        expect($collection)
            ->toBeInstanceOf(\App\Services\ValueObjects\ValidationResultCollection::class);
            
        if ($expectedCount !== null && $collection instanceof \App\Services\ValueObjects\ValidationResultCollection) {
            expect($collection->count())->toBe($expectedCount);
        }
    }
    
    protected function assertCommandSuccess(int $exitCode): void
    {
        expect($exitCode)->toBe(0);
    }
    
    protected function assertCommandFailure(int $exitCode): void
    {
        expect($exitCode)->not->toBe(0);
    }
    
    /**
     * Performance monitoring helpers
     */
    protected function measureExecutionTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();
        return microtime(true) - $start;
    }
    
    protected function measureMemoryUsage(callable $callback): int
    {
        $before = memory_get_usage(true);
        $callback();
        return memory_get_usage(true) - $before;
    }
    
    /**
     * Efficient file system helpers
     */
    protected function createTempFile(string $content = '', string $extension = '.tmp'): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_') . $extension;
        file_put_contents($tempFile, $content);
        
        // Register cleanup
        $this->beforeApplicationDestroyed(function () use ($tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        });
        
        return $tempFile;
    }
    
    protected function createTempDirectory(string $prefix = 'test_'): string
    {
        $tempDir = sys_get_temp_dir() . '/' . $prefix . uniqid();
        mkdir($tempDir, 0755, true);
        
        // Register cleanup
        $this->beforeApplicationDestroyed(function () use ($tempDir) {
            if (is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
        });
        
        return $tempDir;
    }
    
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $scanResult = scandir($dir);
        if ($scanResult === false) {
            return;
        }
        
        $files = array_diff($scanResult, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
    
    /**
     * Laravel Zero specific optimizations
     * @param array<string, mixed> $parameters
     */
    protected function artisanOptimized(string $command, array $parameters = []): int
    {
        // Disable output for performance
        $parameters['--quiet'] = true;
        
        $result = $this->artisan($command, $parameters);
        return $result instanceof \Illuminate\Testing\PendingCommand ? 0 : $result;
    }
    
    /**
     * Configuration helpers for test isolation
     * @param array<string, mixed> $config
     */
    protected function withConfig(array $config, callable $callback): mixed
    {
        /** @var array<string, mixed> $original */
        $original = [];
        
        // Store original values
        foreach ($config as $key => $value) {
            $original[$key] = config($key);
            config([$key => $value]);
        }
        
        try {
            return $callback();
        } finally {
            // Restore original values
            foreach ($original as $key => $value) {
                config([$key => $value]);
            }
        }
    }
    
    /**
     * Environment variable helpers
     * @param array<string, string> $env
     */
    protected function withEnv(array $env, callable $callback): mixed
    {
        /** @var array<string, string|null> $original */
        $original = [];
        
        // Store and set environment variables
        foreach ($env as $key => $value) {
            $original[$key] = $_ENV[$key] ?? null;
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
        
        try {
            return $callback();
        } finally {
            // Restore original environment
            foreach ($original as $key => $value) {
                if ($value === null) {
                    unset($_ENV[$key]);
                    putenv($key);
                } else {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
}