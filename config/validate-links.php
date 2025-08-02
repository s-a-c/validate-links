<?php

declare(strict_types=1);

use App\Services\Formatters\ConsoleFormatter;
use App\Services\Formatters\HtmlFormatter;
use App\Services\Formatters\JsonFormatter;
use App\Services\Formatters\MarkdownFormatter;

return [
    'environment' => env('APP_ENV', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Validation Settings
    |--------------------------------------------------------------------------
    |
    | These configuration options control the default behavior of the
    | link validation engine. All values can be overridden via CLI options.
    |
    */
    'defaults' => [
        'case_sensitive' => env('VALIDATE_LINKS_CASE_SENSITIVE', false),
        'check_external' => env('VALIDATE_LINKS_CHECK_EXTERNAL', false),
        'concurrency' => env('VALIDATE_LINKS_CONCURRENCY', 10),
        'include_hidden' => env('VALIDATE_LINKS_INCLUDE_HIDDEN', false),
        'max_broken' => env('VALIDATE_LINKS_MAX_BROKEN', 50),
        'max_depth' => env('VALIDATE_LINKS_MAX_DEPTH', 0),
        'max_files' => env('VALIDATE_LINKS_MAX_FILES', 0),
        'timeout' => env('VALIDATE_LINKS_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Scopes
    |--------------------------------------------------------------------------
    |
    | Define the types of links that can be validated. Each scope
    | corresponds to a specific validation strategy.
    |
    */
    'scopes' => [
        'internal' => [
            'name' => 'Internal file links',
            'description' => 'Links to files within the same project',
            'enabled' => true,
        ],
        'anchor' => [
            'name' => 'Anchor/heading links',
            'description' => 'Links to headings within markdown files',
            'enabled' => true,
        ],
        'cross-reference' => [
            'name' => 'Cross-reference links',
            'description' => 'Links between different files in the project',
            'enabled' => true,
        ],
        'external' => [
            'name' => 'External HTTP/HTTPS links',
            'description' => 'Links to external websites and resources',
            'enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Formatters
    |--------------------------------------------------------------------------
    |
    | Register the available output formatters for validation results.
    | Each formatter handles a specific output format.
    |
    */
    'formatters' => [
        'console' => ConsoleFormatter::class,
        'json' => JsonFormatter::class,
        'markdown' => MarkdownFormatter::class,
        'html' => HtmlFormatter::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security constraints for file processing and link validation.
    |
    */
    'security' => [
        'allowed_protocols' => ['http', 'https'],
        'max_file_size' => 10 * 1024 * 1024, // 10MB
        'allowed_extensions' => ['.md', '.mdc', '.markdown', '.html', '.htm', '.txt', '.rst'],
        'blocked_domains' => env('APP_ENV') === 'testing' ? [] : [
            'localhost',
            '127.0.0.1',
            '0.0.0.0',
        ],
        'allow_private_ips' => env('APP_ENV') === 'testing',
        'allowed_base_paths' => [
            // Will be populated dynamically
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | External Link Validation
    |--------------------------------------------------------------------------
    |
    | Configuration for validating external HTTP/HTTPS links.
    |
    */
    'external' => [
        'user_agent' => 'validate-links/2.0 (+https://github.com/s-a-c/validate-links)',
        'follow_redirects' => true,
        'max_redirects' => 5,
        'verify_ssl' => true,
        'timeout' => 30,
        'retry_attempts' => 3,
        'retry_delay' => 1000, // milliseconds
        'concurrent_requests' => 10,
        'rate_limit' => [
            'enabled' => true,
            'requests_per_second' => 5,
            'burst_limit' => 20,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | File Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for file discovery and processing.
    |
    */
    'files' => [
        'default_patterns' => [
            '**/*.md',
            '**/*.mdc',
            '**/*.markdown',
            '**/*.html',
            '**/*.htm',
        ],
        'exclude_patterns' => [
            'node_modules/**',
            'vendor/**',
            '.git/**',
            'build/**',
            'dist/**',
            'coverage/**',
        ],
        'hidden_files' => [
            'include_by_default' => false,
            'specific_includes' => [
                '.ai/**/*.md',
                '.github/**/*.md',
                '.docs/**/*.md',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    |
    | Settings for GitHub-specific features like anchor generation.
    |
    */
    'github' => [
        'anchor_generation' => [
            'algorithm' => 'github-compatible',
            'case_sensitive' => false,
            'preserve_unicode' => true,
        ],
        'api' => [
            'base_url' => 'https://api.github.com',
            'timeout' => 30,
            'rate_limit_aware' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Reporting Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for report generation and output formatting.
    |
    */
    'reporting' => [
        'console' => [
            'colors' => env('VALIDATE_LINKS_COLORS', true),
            'progress_bar' => true,
            'show_statistics' => true,
            'verbose_errors' => false,
        ],
        'file_output' => [
            'create_directories' => true,
            'overwrite_existing' => true,
            'backup_existing' => false,
        ],
        'statistics' => [
            'track_performance' => true,
            'include_memory_usage' => true,
            'detailed_timing' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    |
    | Optimization settings for large documentation sets.
    |
    */

    'cache' => [
        'enabled' => env('VALIDATE_LINKS_CACHE_ENABLED', true),
        'store' => env('VALIDATE_LINKS_CACHE_STORE', 'file'),
        'ttl' => env('VALIDATE_LINKS_CACHE_TTL', 86400), // 24 hours
    ],

    'logging' => [
        'enabled' => env('VALIDATE_LINKS_LOGGING_ENABLED', true),
        'channel' => env('VALIDATE_LINKS_LOG_CHANNEL', 'single'),
        'level' => env('VALIDATE_LINKS_LOG_LEVEL', 'info'),
    ],

    'performance' => [
        'chunk_size' => env('VALIDATE_LINKS_FILES_PER_CHUNK', 100), // Files per chunk
        'max_execution_time' => env('VALIDATE_LINKS_MAX_EXECUTION_TIME', 300),
        'memory_limit' => env('VALIDATE_LINKS_MEMORY_LIMIT', '512M'),
        'parallel_processing' => [
            'enabled' => env('VALIDATE_LINKS_PARALLEL_ENABLED', true),
            'max_workers' => env('VALIDATE_LINKS_PARALLEL_WORKERS', 4),
            'batch_size' => env('VALIDATE_LINKS_PARALLEL_BATCH_SIZE', 25),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Link Remediation (Fix Mode)
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic link fixing capabilities.
    |
    */
    'remediation' => [
        'enabled' => false,
        'backup_files' => true,
        'backup_directory' => storage_path('app/validate-links/backups'),
        'strategies' => [
            'case_mismatch' => true,
            'extension_correction' => true,
            'path_normalization' => true,
            'anchor_generation' => true,
        ],
        'interactive_mode' => [
            'confirm_changes' => true,
            'show_diff' => true,
            'batch_approval' => false,
        ],
    ],
];
