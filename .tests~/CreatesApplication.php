<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        // Force testing environment for all tests
        $app->detectEnvironment(function () {
            return 'testing';
        });

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
