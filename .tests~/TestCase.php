<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\LoggingTestHelper;
use Tests\Support\CommandTestHelper;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Clear any previous test state to prevent accumulation
        $this->clearTestState();
    }
    
    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown(): void
    {
        // Force cleanup to prevent resource accumulation
        $this->clearTestState();
        
        // Force garbage collection
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        parent::tearDown();
    }
    
    /**
     * Clear all test state to prevent accumulation between tests.
     */
    private function clearTestState(): void
    {
        try {
            // Clear logging test state
            LoggingTestHelper::resetLoggingEnvironment();
            
            // Clear command test state
            CommandTestHelper::clearResults();
            
            // Clear any Laravel bindings that might conflict
            if (app()->bound('log')) {
                app()->forgetInstance('log');
            }
        } catch (\Throwable $e) {
            // Ignore cleanup errors to prevent test failures
        }
    }
}
