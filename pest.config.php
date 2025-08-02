<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest Configuration
|--------------------------------------------------------------------------
|
| Comprehensive Pest testing framework configuration for 100% coverage
| and strict type safety requirements.
|
*/

return [
    // Test suite definitions for organized execution
    'suites' => [
        'unit' => [
            'paths' => ['tests/Unit'],
            'testsuite' => 'Unit Tests',
        ],
        'feature' => [
            'paths' => ['tests/Feature'],
            'testsuite' => 'Feature Tests',
        ],
        'integration' => [
            'paths' => ['tests/Integration'],
            'testsuite' => 'Integration Tests',
        ],
        'e2e' => [
            'paths' => ['tests/E2E'],
            'testsuite' => 'End-to-End Tests',
        ],
        'architecture' => [
            'paths' => ['tests/Architecture'],
            'testsuite' => 'Architecture Tests',
        ],
    ],

    // Coverage configuration for 100% coverage targets
    'coverage' => [
        'include' => [
            'app/**/*.php',
        ],
        'exclude' => [
            'app/Exceptions/Handler.php',
            'bootstrap/**/*.php',
            'config/**/*.php',
            'vendor/**/*.php',
        ],
        'report' => [
            'html' => 'reports/coverage/html',
            'text' => true,
            'clover' => 'reports/coverage/clover.xml',
            'php' => 'reports/coverage/coverage.php',
        ],
        'thresholds' => [
            'lines' => 100,
            'functions' => 100,
            'branches' => 100,
        ],
    ],

    // Test environment configuration
    'environment' => [
        'APP_ENV' => 'testing',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'CACHE_DRIVER' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
    ],

    // Parallel execution configuration
    'parallel' => [
        'processes' => 8,
        'timeout' => 120,
    ],

    // Type coverage requirements
    'type_coverage' => [
        'min_coverage' => 100,
        'ignore_files' => [
            'app/Exceptions/Handler.php',
            'bootstrap/**/*.php',
            'config/**/*.php',
            'vendor/**/*.php',
            'tests/**/*.php',
        ],
    ],
];
