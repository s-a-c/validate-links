<?php

declare(strict_types=1);

namespace Tests\Support\CI;

use RuntimeException;

/**
 * Environment-specific configuration management for CI/CD pipelines.
 * 
 * Provides intelligent environment detection, configuration optimization,
 * and environment-specific test execution strategies for development,
 * staging, and production deployment pipelines.
 * 
 * @package Tests\Support\CI
 * @author Roo
 * @since 1.0.0
 */
final class EnvironmentConfiguration
{
    /**
     * Environment configuration profiles.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $environmentProfiles = [
        'development' => [
            'test_timeout' => 60,
            'parallel_processes' => 4,
            'memory_limit' => '512M',
            'enable_coverage' => true,
            'enable_profiling' => true,
            'enable_debugging' => true,
            'quality_threshold' => 80.0,
            'performance_threshold' => 180.0,
            'test_suites' => ['Unit', 'Feature', 'Integration'],
            'optimization_level' => 'development',
        ],
        'testing' => [
            'test_timeout' => 120,
            'parallel_processes' => 6,
            'memory_limit' => '1G',
            'enable_coverage' => true,
            'enable_profiling' => true,
            'enable_debugging' => false,
            'quality_threshold' => 85.0,
            'performance_threshold' => 150.0,
            'test_suites' => ['Unit', 'Feature', 'Integration', 'E2E'],
            'optimization_level' => 'testing',
        ],
        'staging' => [
            'test_timeout' => 180,
            'parallel_processes' => 8,
            'memory_limit' => '2G',
            'enable_coverage' => true,
            'enable_profiling' => false,
            'enable_debugging' => false,
            'quality_threshold' => 90.0,
            'performance_threshold' => 120.0,
            'test_suites' => ['Unit', 'Feature', 'Integration', 'E2E', 'Architecture'],
            'optimization_level' => 'production',
        ],
        'production' => [
            'test_timeout' => 300,
            'parallel_processes' => 12,
            'memory_limit' => '4G',
            'enable_coverage' => false,
            'enable_profiling' => false,
            'enable_debugging' => false,
            'quality_threshold' => 95.0,
            'performance_threshold' => 90.0,
            'test_suites' => ['Unit', 'Feature', 'Integration', 'E2E', 'Architecture'],
            'optimization_level' => 'production',
        ],
        'ci' => [
            'test_timeout' => 240,
            'parallel_processes' => 8,
            'memory_limit' => '2G',
            'enable_coverage' => true,
            'enable_profiling' => false,
            'enable_debugging' => false,
            'quality_threshold' => 90.0,
            'performance_threshold' => 120.0,
            'test_suites' => ['Unit', 'Feature', 'Integration', 'E2E', 'Architecture'],
            'optimization_level' => 'ci',
        ],
    ];
    
    /**
     * Current environment configuration.
     * 
     * @var array<string, mixed>
     */
    private static array $currentConfig = [];
    
    /**
     * Detected environment name.
     */
    private static string $detectedEnvironment = '';
    
    /**
     * Environment-specific optimizations.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $optimizations = [
        'development' => [
            'enable_mock_pooling' => false,
            'enable_data_caching' => true,
            'cache_size_limit' => 50 * 1024 * 1024, // 50MB
            'enable_lazy_loading' => false,
            'enable_batch_operations' => false,
        ],
        'testing' => [
            'enable_mock_pooling' => true,
            'enable_data_caching' => true,
            'cache_size_limit' => 100 * 1024 * 1024, // 100MB
            'enable_lazy_loading' => true,
            'enable_batch_operations' => true,
        ],
        'staging' => [
            'enable_mock_pooling' => true,
            'enable_data_caching' => true,
            'cache_size_limit' => 200 * 1024 * 1024, // 200MB
            'enable_lazy_loading' => true,
            'enable_batch_operations' => true,
        ],
        'production' => [
            'enable_mock_pooling' => true,
            'enable_data_caching' => false, // Avoid memory issues in production
            'cache_size_limit' => 50 * 1024 * 1024, // 50MB
            'enable_lazy_loading' => true,
            'enable_batch_operations' => true,
        ],
        'ci' => [
            'enable_mock_pooling' => true,
            'enable_data_caching' => true,
            'cache_size_limit' => 150 * 1024 * 1024, // 150MB
            'enable_lazy_loading' => true,
            'enable_batch_operations' => true,
        ],
    ];
    
    /**
     * Initialize environment configuration.
     * 
     * @param string|null $environment Force specific environment (optional)
     * @return void
     */
    public static function initialize(?string $environment = null): void
    {
        if ($environment !== null) {
            self::$detectedEnvironment = $environment;
        } else {
            self::$detectedEnvironment = self::detectEnvironment();
        }
        
        self::$currentConfig = self::loadEnvironmentConfig(self::$detectedEnvironment);
        
        // Apply environment-specific optimizations
        self::applyEnvironmentOptimizations();
        
        // Configure PHP settings
        self::configurePHPSettings();
        
        // Set environment variables
        self::setEnvironmentVariables();
    }
    
    /**
     * Get current environment configuration.
     * 
     * @return array<string, mixed> Environment configuration
     */
    public static function getCurrentConfig(): array
    {
        return self::$currentConfig;
    }
    
    /**
     * Get detected environment name.
     * 
     * @return string Environment name
     */
    public static function getEnvironment(): string
    {
        return self::$detectedEnvironment;
    }
    
    /**
     * Get configuration for specific environment.
     * 
     * @param string $environment Environment name
     * @return array<string, mixed> Environment configuration
     * @throws RuntimeException If environment is not supported
     */
    public static function getEnvironmentConfig(string $environment): array
    {
        if (!isset(self::$environmentProfiles[$environment])) {
            throw new RuntimeException("Unsupported environment: {$environment}");
        }
        
        return self::$environmentProfiles[$environment];
    }
    
    /**
     * Get optimizations for current environment.
     * 
     * @return array<string, mixed> Optimization settings
     */
    public static function getOptimizations(): array
    {
        return self::$optimizations[self::$detectedEnvironment] ?? self::$optimizations['development'];
    }
    
    /**
     * Check if environment supports specific feature.
     * 
     * @param string $feature Feature name
     * @return bool Whether feature is supported
     */
    public static function supportsFeature(string $feature): bool
    {
        return match ($feature) {
            'coverage' => self::$currentConfig['enable_coverage'] ?? false,
            'profiling' => self::$currentConfig['enable_profiling'] ?? false,
            'debugging' => self::$currentConfig['enable_debugging'] ?? false,
            'parallel_execution' => (self::$currentConfig['parallel_processes'] ?? 1) > 1,
            default => false,
        };
    }
    
    /**
     * Get environment-specific test configuration.
     * 
     * @return array<string, mixed> Test configuration
     */
    public static function getTestConfig(): array
    {
        return [
            'timeout' => self::$currentConfig['test_timeout'],
            'parallel_processes' => self::$currentConfig['parallel_processes'],
            'memory_limit' => self::$currentConfig['memory_limit'],
            'test_suites' => self::$currentConfig['test_suites'],
            'quality_threshold' => self::$currentConfig['quality_threshold'],
            'performance_threshold' => self::$currentConfig['performance_threshold'],
        ];
    }
    
    /**
     * Get environment-specific quality gates.
     * 
     * @return array<string, mixed> Quality gate configuration
     */
    public static function getQualityGates(): array
    {
        $environment = self::$detectedEnvironment;
        
        return match ($environment) {
            'development' => [
                'test_success_rate' => 80.0,
                'code_coverage' => 70.0,
                'performance_threshold' => 180.0,
                'static_analysis_level' => 8,
                'blocking_issues' => ['test_failures'],
            ],
            'testing' => [
                'test_success_rate' => 85.0,
                'code_coverage' => 80.0,
                'performance_threshold' => 150.0,
                'static_analysis_level' => 9,
                'blocking_issues' => ['test_failures', 'coverage_below_threshold'],
            ],
            'staging' => [
                'test_success_rate' => 90.0,
                'code_coverage' => 85.0,
                'performance_threshold' => 120.0,
                'static_analysis_level' => 10,
                'blocking_issues' => ['test_failures', 'coverage_below_threshold', 'performance_regression'],
            ],
            'production' => [
                'test_success_rate' => 95.0,
                'code_coverage' => 90.0,
                'performance_threshold' => 90.0,
                'static_analysis_level' => 10,
                'blocking_issues' => ['test_failures', 'coverage_below_threshold', 'performance_regression', 'static_analysis_errors'],
            ],
            'ci' => [
                'test_success_rate' => 90.0,
                'code_coverage' => 85.0,
                'performance_threshold' => 120.0,
                'static_analysis_level' => 10,
                'blocking_issues' => ['test_failures', 'coverage_below_threshold', 'static_analysis_errors'],
            ],
            default => self::getQualityGates()['development'] ?? [],
        };
    }
    
    /**
     * Validate environment compatibility.
     * 
     * @return array<string, mixed> Validation results
     */
    public static function validateEnvironment(): array
    {
        $validation = [
            'compatible' => true,
            'warnings' => [],
            'errors' => [],
            'recommendations' => [],
        ];
        
        // Check PHP version
        $phpVersion = PHP_VERSION;
        if (version_compare($phpVersion, '8.4.0', '<')) {
            $validation['warnings'][] = "PHP version {$phpVersion} is below recommended 8.4.0";
        }
        
        // Check memory limit
        $memoryLimit = ini_get('memory_limit');
        $requiredMemory = self::$currentConfig['memory_limit'];
        if (self::parseMemoryLimit($memoryLimit) < self::parseMemoryLimit($requiredMemory)) {
            $validation['errors'][] = "Memory limit {$memoryLimit} is below required {$requiredMemory}";
            $validation['compatible'] = false;
        }
        
        // Check extensions
        $requiredExtensions = ['mbstring', 'json', 'curl', 'dom'];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $validation['errors'][] = "Required PHP extension '{$extension}' is not loaded";
                $validation['compatible'] = false;
            }
        }
        
        // Check parallel execution support
        if (self::$currentConfig['parallel_processes'] > 1 && !function_exists('pcntl_fork')) {
            $validation['warnings'][] = 'Parallel execution requested but pcntl extension not available';
            $validation['recommendations'][] = 'Install pcntl extension for parallel test execution';
        }
        
        // Check disk space for artifacts
        $freeSpace = disk_free_space(getcwd());
        $requiredSpace = 100 * 1024 * 1024; // 100MB
        if ($freeSpace !== false && $freeSpace < $requiredSpace) {
            $validation['warnings'][] = 'Low disk space may affect artifact generation';
        }
        
        return $validation;
    }
    
    /**
     * Configure environment for specific deployment stage.
     * 
     * @param string $stage Deployment stage (dev, test, staging, prod)
     * @return void
     */
    public static function configureForStage(string $stage): void
    {
        $stageMapping = [
            'dev' => 'development',
            'test' => 'testing',
            'staging' => 'staging',
            'prod' => 'production',
        ];
        
        $environment = $stageMapping[$stage] ?? $stage;
        self::initialize($environment);
    }
    
    /**
     * Get environment-specific artifact configuration.
     * 
     * @return array<string, mixed> Artifact configuration
     */
    public static function getArtifactConfig(): array
    {
        $environment = self::$detectedEnvironment;
        
        return match ($environment) {
            'development' => [
                'generate_html_reports' => true,
                'generate_json_reports' => true,
                'generate_junit_xml' => false,
                'generate_coverage_reports' => true,
                'generate_performance_reports' => true,
                'retention_days' => 7,
            ],
            'testing' => [
                'generate_html_reports' => true,
                'generate_json_reports' => true,
                'generate_junit_xml' => true,
                'generate_coverage_reports' => true,
                'generate_performance_reports' => true,
                'retention_days' => 14,
            ],
            'staging' => [
                'generate_html_reports' => true,
                'generate_json_reports' => true,
                'generate_junit_xml' => true,
                'generate_coverage_reports' => true,
                'generate_performance_reports' => true,
                'retention_days' => 30,
            ],
            'production' => [
                'generate_html_reports' => false,
                'generate_json_reports' => true,
                'generate_junit_xml' => true,
                'generate_coverage_reports' => false,
                'generate_performance_reports' => true,
                'retention_days' => 90,
            ],
            'ci' => [
                'generate_html_reports' => true,
                'generate_json_reports' => true,
                'generate_junit_xml' => true,
                'generate_coverage_reports' => true,
                'generate_performance_reports' => true,
                'retention_days' => 30,
            ],
            default => [
                'generate_html_reports' => true,
                'generate_json_reports' => true,
                'generate_junit_xml' => false,
                'generate_coverage_reports' => true,
                'generate_performance_reports' => false,
                'retention_days' => 7,
            ],
        };
    }
    
    /**
     * Reset environment configuration.
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$currentConfig = [];
        self::$detectedEnvironment = '';
    }
    
    /**
     * Detect current environment.
     * 
     * @return string Detected environment name
     */
    private static function detectEnvironment(): string
    {
        // Check environment variables
        $envVar = getenv('APP_ENV') ?: getenv('ENVIRONMENT') ?: getenv('NODE_ENV');
        if ($envVar !== false) {
            return match (strtolower($envVar)) {
                'dev', 'development', 'local' => 'development',
                'test', 'testing' => 'testing',
                'stage', 'staging' => 'staging',
                'prod', 'production' => 'production',
                default => $envVar,
            };
        }
        
        // Check CI environment
        if (getenv('CI') === 'true' || getenv('CONTINUOUS_INTEGRATION') === 'true') {
            return 'ci';
        }
        
        // Check for specific CI providers
        if (getenv('GITHUB_ACTIONS') === 'true') {
            return 'ci';
        }
        
        if (getenv('GITLAB_CI') === 'true') {
            return 'ci';
        }
        
        if (getenv('JENKINS_URL') !== false) {
            return 'ci';
        }
        
        // Default to development
        return 'development';
    }
    
    /**
     * Load configuration for specific environment.
     * 
     * @param string $environment Environment name
     * @return array<string, mixed> Environment configuration
     */
    private static function loadEnvironmentConfig(string $environment): array
    {
        $config = self::$environmentProfiles[$environment] ?? self::$environmentProfiles['development'];
        
        // Override with environment-specific settings
        $overrides = self::loadEnvironmentOverrides($environment);
        
        return array_merge($config, $overrides);
    }
    
    /**
     * Load environment-specific overrides.
     * 
     * @param string $environment Environment name
     * @return array<string, mixed> Configuration overrides
     */
    private static function loadEnvironmentOverrides(string $environment): array
    {
        $overrides = [];
        
        // Load from environment variables
        if (($timeout = getenv('TEST_TIMEOUT')) !== false) {
            $overrides['test_timeout'] = (int) $timeout;
        }
        
        if (($processes = getenv('PARALLEL_PROCESSES')) !== false) {
            $overrides['parallel_processes'] = (int) $processes;
        }
        
        if (($memoryLimit = getenv('MEMORY_LIMIT')) !== false) {
            $overrides['memory_limit'] = $memoryLimit;
        }
        
        if (($qualityThreshold = getenv('QUALITY_THRESHOLD')) !== false) {
            $overrides['quality_threshold'] = (float) $qualityThreshold;
        }
        
        // Load from configuration file if exists
        $configFile = getcwd() . "/config/testing/{$environment}.php";
        if (file_exists($configFile)) {
            $fileConfig = include $configFile;
            if (is_array($fileConfig)) {
                $overrides = array_merge($overrides, $fileConfig);
            }
        }
        
        return $overrides;
    }
    
    /**
     * Apply environment-specific optimizations.
     * 
     * @return void
     */
    private static function applyEnvironmentOptimizations(): void
    {
        $optimizations = self::getOptimizations();
        
        // Configure test execution optimizer
        if (class_exists('Tests\Support\Performance\TestExecutionOptimizer')) {
            \Tests\Support\Performance\TestExecutionOptimizer::configure($optimizations);
        }
        
        // Configure performance benchmark
        if (class_exists('Tests\Support\Performance\PerformanceBenchmark')) {
            \Tests\Support\Performance\PerformanceBenchmark::configure([
                'warmup_iterations' => self::$detectedEnvironment === 'production' ? 1 : 3,
                'benchmark_iterations' => self::$detectedEnvironment === 'production' ? 3 : 10,
            ]);
        }
    }
    
    /**
     * Configure PHP settings for environment.
     * 
     * @return void
     */
    private static function configurePHPSettings(): void
    {
        // Set memory limit
        ini_set('memory_limit', self::$currentConfig['memory_limit']);
        
        // Set time limit
        set_time_limit(self::$currentConfig['test_timeout']);
        
        // Configure error reporting
        if (self::$currentConfig['enable_debugging']) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
            ini_set('display_errors', '0');
        }
        
        // Configure garbage collection
        if (self::$detectedEnvironment === 'production') {
            gc_enable();
            ini_set('zend.enable_gc', '1');
        }
    }
    
    /**
     * Set environment-specific variables.
     * 
     * @return void
     */
    private static function setEnvironmentVariables(): void
    {
        // Set test environment
        putenv("APP_ENV=" . self::$detectedEnvironment);
        $_ENV['APP_ENV'] = self::$detectedEnvironment;
        
        // Set testing flag
        putenv('TESTING=true');
        $_ENV['TESTING'] = 'true';
        
        // Set parallel execution flag
        if (self::$currentConfig['parallel_processes'] > 1) {
            putenv('PARALLEL_TESTING=true');
            $_ENV['PARALLEL_TESTING'] = 'true';
        }
        
        // Set coverage flag
        if (self::$currentConfig['enable_coverage']) {
            putenv('ENABLE_COVERAGE=true');
            $_ENV['ENABLE_COVERAGE'] = 'true';
        }
    }
    
    /**
     * Parse memory limit string to bytes.
     * 
     * @param string $memoryLimit Memory limit string (e.g., "512M")
     * @return int Memory limit in bytes
     */
    private static function parseMemoryLimit(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        
        if ($memoryLimit === '-1') {
            return PHP_INT_MAX;
        }
        
        $value = (int) $memoryLimit;
        $unit = strtolower(substr($memoryLimit, -1));
        
        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
    
    /**
     * Get environment configuration for specified environment.
     *
     * @param string $environment Environment name
     * @return array<string, mixed> Environment configuration
     */
    public static function getEnvironmentConfiguration(string $environment): array
    {
        $config = [
            'environment' => $environment,
            'configuration' => [],
            'optimizations' => [],
            'settings' => [],
        ];
        
        switch ($environment) {
            case 'testing':
                $config['configuration'] = [
                    'debug' => true,
                    'log_level' => 'debug',
                    'cache_driver' => 'array',
                    'database_connection' => 'sqlite',
                ];
                break;
            case 'development':
                $config['configuration'] = [
                    'debug' => true,
                    'log_level' => 'debug',
                    'cache_driver' => 'file',
                ];
                break;
            case 'staging':
                $config['configuration'] = [
                    'debug' => false,
                    'log_level' => 'info',
                    'cache_driver' => 'redis',
                ];
                break;
            case 'production':
                $config['configuration'] = [
                    'debug' => false,
                    'log_level' => 'error',
                    'cache_driver' => 'redis',
                ];
                break;
            default:
                $config['configuration'] = [
                    'debug' => true,
                    'log_level' => 'info',
                ];
        }
        
        return $config;
    }
}