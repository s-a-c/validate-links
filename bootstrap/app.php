<?php

// bootstrap/app.php - Application initialization

declare(strict_types=1);

use App\Commands\ConfigCommand;
use App\Commands\FixCommand;
use App\Commands\ReportCommand;
use App\Commands\ValidateCommand;
use App\Providers\AppServiceProvider;
use App\Providers\ValidateLinksServiceProvider;

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Foundation\Configuration\Exceptions;
use LaravelZero\Framework\Application;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        AppServiceProvider::class,
        ValidateLinksServiceProvider::class,
    ])
    ->withCommands([
        ValidateCommand::class,
        FixCommand::class,
        ReportCommand::class,
        ConfigCommand::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        // Exception handling configuration
    })
    ->create();
