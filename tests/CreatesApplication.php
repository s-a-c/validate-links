<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * Creates Application Trait for Laravel Zero Testing
 * 
 * Optimized application creation for PEST testing with Laravel Zero,
 * implementing lazy initialization and performance optimizations.
 */
trait CreatesApplication
{
    /**
     * Cached application instance for performance
     */
    private static ?Application $cachedApp = null;
    
    /**
     * Creates the application with performance optimizations
     */
    public function createApplication(): Application
    {
        // Use cached application if available and not in maintenance mode
        if (self::$cachedApp && !self::$cachedApp->isDownForMaintenance()) {
            return self::$cachedApp;
        }
        
        /** @var Application $app */
        $app = require __DIR__.'/../bootstrap/app.php';
        
        $app->make(Kernel::class)->bootstrap();
        
        // Cache for reuse
        self::$cachedApp = $app;
        
        return $app;
    }
    
    /**
     * Clear cached application (useful for tests that modify app state)
     */
    protected function clearApplicationCache(): void
    {
        self::$cachedApp = null;
    }
}