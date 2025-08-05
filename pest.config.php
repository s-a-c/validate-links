<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| High-Performance Pest Configuration
|--------------------------------------------------------------------------
|
| Optimized Pest configuration for maximum performance and maintainability.
| Implements lazy loading, selective test discovery, efficient memory
| management, and streamlined configuration patterns.
|
*/

return [
    // Streamlined test suite definitions with lazy loading
    'suites' => [
        'unit' => [
            'paths' => ['tests/Unit'],
            'testsuite' => 'Unit Tests',
            'parallel' => true,
            'memory_limit' => '256M',
        ],
        'feature' => [
            'paths' => ['tests/Feature'],
            'testsuite' => 'Feature Tests',
            'parallel' => true,
            'memory_limit' => '512M',
        ],
        'integration' => [
            'paths' => ['tests/Integration'],
            'testsuite' => 'Integration Tests',
            'parallel' => false, // Sequential for database integrity
            'memory_limit' => '512M',
        ],
        'e2e' => [
            'paths' => ['tests/E2E'],
            'testsuite' => 'End-to-End Tests',
            'parallel' => false, // Sequential for external dependencies
            'memory_limit' => '1G',
        ],
        'architecture' => [
            'paths' => ['tests/Architecture'],
            'testsuite' => 'Architecture Tests',
            'parallel' => true,
            'memory_limit' => '256M',
        ],
        'performance' => [
            'paths' => ['tests/Performance'],
            'testsuite' => 'Performance Tests',
            'parallel' => false, // Sequential for accurate measurements
            'memory_limit' => '1G',
            'excluded_by_default' => true, // Only run when explicitly requested
        ],
    ],

    // Optimized coverage configuration
    'coverage' => [
        'include' => [
            'app/**/*.php',
        ],
        'exclude' => [
            'app/Exceptions/Handler.php',
            'bootstrap/**/*.php',
            'config/**/*.php',
            'vendor/**/*.php',
            'tests/**/*.php',
        ],
        'report' => [
            'html' => 'reports/coverage/html',
            'text' => false, // Disable for performance
            'clover' => 'reports/coverage/clover.xml',
        ],
        'thresholds' => [
            'lines' => 90, // Realistic target
            'functions' => 90,
            'branches' => 85,
        ],
    ],

    // Performance-optimized environment configuration
    'environment' => [
        'APP_ENV' => 'testing',
        'APP_DEBUG' => false, // Disable debug for performance
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => ':memory:',
        'CACHE_DRIVER' => 'array',
        'SESSION_DRIVER' => 'array',
        'QUEUE_CONNECTION' => 'sync',
        'LOG_CHANNEL' => 'null', // Disable logging for performance
        'MAIL_MAILER' => 'array',
    ],

    // Intelligent parallel execution configuration
    'parallel' => [
        'processes' => min(8, (int) shell_exec('nproc') ?: 4),
        'timeout' => 60, // Reduced from 120
        'memory_limit' => '512M',
        'isolation_mode' => 'process',
        'database_strategy' => 'transaction',
        'worker_configuration' => [
            'separate_temp_dirs' => true,
            'isolated_cache' => true,
            'unique_session_storage' => true,
            'process_specific_seeds' => true,
        ],
        'resource_management' => [
            'shared_resource_locking' => true,
            'exclusive_access_timeout' => 30,
            'cleanup_worker_resources' => true,
        ],
        'safety_checks' => [
            'validate_parallel_safety' => true,
            'exclude_unsafe_tags' => ['sequential', 'external', 'file_system'],
            'require_parallel_tag' => false,
        ],
    ],

    // Selective test discovery for faster startup
    'discovery' => [
        'cache_enabled' => true,
        'cache_path' => 'storage/framework/cache/pest-discovery.cache',
        'exclude_patterns' => [
            '*/Fixtures/*',
            '*/Support/Performance/*', // Exclude heavy performance utilities
            '*/Support/CI/*',
            '*/Support/Traits/*', // Exclude trait files
            '*/Support/Builders/*', // Exclude builder files
            '*/Support/Doubles/*', // Exclude test double files
        ],
        'include_patterns' => [
            '*Test.php',
            '*Tests.php',
        ],
    ],
    
    // Advanced PEST patterns and lazy initialization
    'lazy_initialization' => [
        'enabled' => true,
        'cache_instances' => true,
        'auto_cleanup' => true,
        'memory_threshold' => '50M', // Clear cache when memory usage exceeds threshold
    ],
    
    // Test double configuration
    'test_doubles' => [
        'auto_cleanup' => true,
        'state_management' => true,
        'interaction_recording' => env('PEST_RECORD_INTERACTIONS', false),
        'realistic_behaviors' => true,
    ],
    
    // Builder pattern configuration
    'builders' => [
        'fluent_interface' => true,
        'lazy_evaluation' => true,
        'method_chaining' => true,
        'validation' => true,
    ],

    // Advanced tagging system configuration
    'tagging' => [
        'enabled' => true,
        'auto_discovery' => true,
        'tag_inheritance' => true,
        'default_exclusions' => [
            'performance',
            'benchmark',
            'load',
            'slow',
            'external',
            'production',
        ],
        'tag_groups' => [
            'performance' => ['performance', 'benchmark', 'memory', 'load'],
            'functional' => ['unit', 'integration', 'feature', 'e2e'],
            'component' => ['validation', 'reporting', 'security', 'github'],
            'environment' => ['ci', 'local', 'production', 'staging'],
            'quality' => ['smoke', 'critical', 'edge_case', 'error_handling'],
        ],
        'conditional_execution' => [
            'respect_environment' => true,
            'check_requirements' => true,
            'validate_setup' => true,
        ],
    ],

    // Custom matchers configuration
    'custom_matchers' => [
        'enabled' => true,
        'auto_load' => true,
        'categories' => [
            'link_validation' => 'Tests\\Support\\Matchers\\LinkValidationMatchers',
            'performance' => 'Tests\\Support\\Matchers\\PerformanceMatchers',
            'security' => 'Tests\\Support\\Matchers\\SecurityMatchers',
            'reporting' => 'Tests\\Support\\Matchers\\ReportingMatchers',
        ],
        'validation' => [
            'check_existence' => true,
            'validate_signatures' => true,
        ],
    ],

    // Test organization configuration
    'organization' => [
        'naming_conventions' => [
            'enforce' => true,
            'pattern' => 'it_should_{behavior}_when_{condition}',
            'validate_names' => true,
            'suggest_improvements' => true,
        ],
        'structure' => [
            'max_nesting_depth' => 3,
            'require_describe_blocks' => true,
            'flat_structure_preferred' => true,
        ],
        'file_organization' => [
            'group_by_domain' => true,
            'separate_performance_tests' => true,
            'isolate_external_tests' => true,
        ],
    ],

    // Enhanced lifecycle management
    'lifecycle' => [
        'enhanced_hooks' => true,
        'automatic_cleanup' => true,
        'memory_leak_detection' => true,
        'test_isolation_verification' => true,
        'resource_tracking' => true,
        'performance_monitoring' => true,
        'cleanup_strategies' => [
            'files' => 'auto',
            'directories' => 'auto',
            'database' => 'transaction',
            'cache' => 'clear',
            'sessions' => 'reset',
        ],
    ],

    // Memory management optimization
    'memory' => [
        'gc_enabled' => true,
        'gc_frequency' => 10, // Run garbage collection every 10 tests
        'memory_limit_warning' => '80%',
        'memory_limit_error' => '90%',
    ],

    // Performance monitoring
    'performance' => [
        'slow_test_threshold' => 1.0, // 1 second
        'memory_usage_tracking' => true,
        'execution_time_tracking' => true,
        'report_slow_tests' => true,
    ],

    // Optimized mock configuration
    'mocking' => [
        'auto_cleanup' => true,
        'memory_efficient' => true,
        'lazy_loading' => true,
    ],

    // Database optimization for tests
    'database' => [
        'default_connection' => 'sqlite_testing',
        'connections' => [
            'sqlite_testing' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'foreign_key_constraints' => false, // Faster for tests
            ],
        ],
        'migrations' => [
            'run_in_memory' => true,
            'cache_schema' => true,
        ],
    ],

    // Streamlined logging for tests
    'logging' => [
        'default' => 'null',
        'channels' => [
            'null' => [
                'driver' => 'monolog',
                'handler' => 'Monolog\Handler\NullHandler',
            ],
            'testing' => [
                'driver' => 'single',
                'path' => 'storage/logs/testing.log',
                'level' => 'error', // Only log errors during tests
            ],
        ],
    ],

    // Optimized cache configuration
    'cache' => [
        'default' => 'array',
        'stores' => [
            'array' => [
                'driver' => 'array',
                'serialize' => false, // Faster for tests
            ],
        ],
    ],

    // Test isolation configuration
    'isolation' => [
        'enabled' => true,
        'reset_database' => false, // Use transactions instead
        'reset_cache' => true,
        'reset_events' => true,
        'reset_queue' => true,
    ],

    // Conditional loading for different environments
    'conditional_loading' => [
        'performance_tests' => env('INCLUDE_PERFORMANCE_TESTS', false),
        'slow_tests' => env('INCLUDE_SLOW_TESTS', false),
        'external_dependencies' => env('INCLUDE_EXTERNAL_TESTS', false),
    ],

    // Test execution management
    'execution' => [
        'conditional_loading' => [
            'check_environment' => true,
            'validate_requirements' => true,
            'respect_ci_settings' => true,
        ],
        'filtering' => [
            'tag_based' => true,
            'performance_based' => true,
            'file_pattern_based' => true,
            'custom_conditions' => true,
        ],
        'grouping' => [
            'by_tags' => true,
            'by_performance' => true,
            'by_file_location' => true,
            'by_test_suite' => true,
        ],
        'performance_thresholds' => [
            'fast' => 0.1,      // 100ms
            'normal' => 1.0,    // 1 second
            'slow' => 5.0,      // 5 seconds
            'very_slow' => 30.0, // 30 seconds
        ],
        'reporting' => [
            'aggregate_results' => true,
            'performance_analysis' => true,
            'failure_analysis' => true,
            'tag_analysis' => true,
            'export_formats' => ['json', 'csv'],
        ],
    ],

    // Advanced feature integration
    'integration' => [
        'load_custom_matchers' => true,
        'initialize_lifecycle_manager' => true,
        'setup_parallel_manager' => true,
        'configure_execution_manager' => true,
        'enable_tag_helpers' => true,
        'validate_configuration' => true,
    ],

    // Feature flags for advanced capabilities
    'features' => [
        'comprehensive_tagging' => true,
        'custom_matchers' => true,
        'enhanced_lifecycle' => true,
        'parallel_execution' => true,
        'test_organization' => true,
        'execution_management' => true,
        'performance_monitoring' => true,
        'automatic_cleanup' => true,
    ],
];