<?php

declare(strict_types=1);

namespace Tests\Support\Integration;

use Tests\Support\Tags\TagManager;
use Tests\Support\Tags\TagFilter;
use Tests\Support\Matchers\MatcherRegistry;
use Tests\Support\Organization\TestNamingConventions;
use Tests\Support\Organization\TestOrganizer;
use Tests\Support\Lifecycle\EnhancedLifecycleManager;
use Tests\Support\Parallel\ParallelTestManager;
use Tests\Support\Execution\TestExecutionManager;

/**
 * Advanced Features Integration Validator
 * 
 * Validates that all advanced PEST features integrate correctly
 * with the existing infrastructure and work together seamlessly.
 */
class AdvancedFeaturesIntegration
{
    /**
     * Integration test results
     */
    private static array $integrationResults = [];

    /**
     * Validation errors
     */
    private static array $validationErrors = [];

    /**
     * Run comprehensive integration validation
     */
    public static function validateIntegration(): array
    {
        self::resetValidation();

        // Validate individual components
        self::validateTaggingSystem();
        self::validateCustomMatchers();
        self::validateTestOrganization();
        self::validateLifecycleManagement();
        self::validateParallelTesting();
        self::validateTestExecution();

        // Validate cross-component integration
        self::validateCrossComponentIntegration();

        // Validate configuration integration
        self::validateConfigurationIntegration();

        return [
            'success' => empty(self::$validationErrors),
            'results' => self::$integrationResults,
            'errors' => self::$validationErrors,
            'summary' => self::generateValidationSummary(),
        ];
    }

    /**
     * Validate tagging system integration
     */
    private static function validateTaggingSystem(): void
    {
        try {
            // Test tag manager functionality
            $allTags = TagManager::getAllTags();
            self::$integrationResults['tagging']['all_tags_count'] = count($allTags);

            // Test tag filtering
            $filter = new TagFilter();
            $filter->include(['unit', 'fast']);
            self::$integrationResults['tagging']['filter_created'] = true;

            // Test tag validation
            $invalidTags = TagManager::validateTags(['nonexistent_tag']);
            self::$integrationResults['tagging']['validation_works'] = !empty($invalidTags);

            // Test tag helpers loading
            if (function_exists('tags')) {
                self::$integrationResults['tagging']['helpers_loaded'] = true;
            } else {
                self::$validationErrors[] = 'Tag helper functions not loaded';
            }

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Tagging system error: " . $e->getMessage();
        }
    }

    /**
     * Validate custom matchers integration
     */
    private static function validateCustomMatchers(): void
    {
        try {
            // Load all matchers
            MatcherRegistry::loadAll();
            self::$integrationResults['matchers']['registry_loaded'] = true;

            // Validate matcher existence
            $missingMatchers = MatcherRegistry::validateMatchers();
            self::$integrationResults['matchers']['missing_matchers'] = $missingMatchers;

            if (!empty($missingMatchers)) {
                self::$validationErrors[] = "Missing matchers: " . implode(', ', $missingMatchers);
            }

            // Test matcher categories
            $categories = MatcherRegistry::getAllMatchers();
            self::$integrationResults['matchers']['categories'] = array_keys($categories);

            // Validate specific matcher functions exist
            $criticalMatchers = [
                'toBeValidLink',
                'toCompleteWithin',
                'toBeSecure',
                'toMatchReportFormat',
            ];

            foreach ($criticalMatchers as $matcher) {
                if (!function_exists($matcher)) {
                    self::$validationErrors[] = "Critical matcher function missing: {$matcher}";
                }
            }

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Custom matchers error: " . $e->getMessage();
        }
    }

    /**
     * Validate test organization integration
     */
    private static function validateTestOrganization(): void
    {
        try {
            // Test naming conventions
            $testName = TestNamingConventions::behaviorTest('validate_input', 'input_is_provided');
            self::$integrationResults['organization']['naming_works'] = 
                $testName === 'it_should_validate_input_when_input_is_provided';

            // Test validation
            $validationIssues = TestNamingConventions::validateTestName('invalid_test_name');
            self::$integrationResults['organization']['validation_works'] = !empty($validationIssues);

            // Test case conversion
            $snakeCase = TestNamingConventions::toSnakeCase('CamelCase');
            self::$integrationResults['organization']['case_conversion_works'] = 
                $snakeCase === 'camel_case';

            // Test organizer functionality
            $testStructure = [
                [
                    'name' => 'Test Suite',
                    'tests' => [
                        ['name' => 'it_should_work', 'body' => ['expect(true)->toBeTrue();']],
                    ],
                ],
            ];

            $organizationReport = TestOrganizer::generateOrganizationReport($testStructure);
            self::$integrationResults['organization']['organizer_works'] = 
                isset($organizationReport['total_tests']);

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Test organization error: " . $e->getMessage();
        }
    }

    /**
     * Validate lifecycle management integration
     */
    private static function validateLifecycleManagement(): void
    {
        try {
            // Test resource tracking
            $tempFile = EnhancedLifecycleManager::createTempFile('test');
            self::$integrationResults['lifecycle']['temp_file_created'] = file_exists($tempFile);

            // Test memory monitoring
            $memoryResult = EnhancedLifecycleManager::monitorMemory(function () {
                return array_fill(0, 100, 'test');
            });

            self::$integrationResults['lifecycle']['memory_monitoring_works'] = 
                isset($memoryResult['execution_time'], $memoryResult['memory_used']);

            // Test performance metrics
            EnhancedLifecycleManager::recordMetric('test_metric', 1.0);
            $metrics = EnhancedLifecycleManager::getPerformanceMetrics();
            self::$integrationResults['lifecycle']['metrics_recording_works'] = 
                isset($metrics['test_metric']);

            // Test cleanup
            EnhancedLifecycleManager::executeCleanup();
            self::$integrationResults['lifecycle']['cleanup_executed'] = true;

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Lifecycle management error: " . $e->getMessage();
        }
    }

    /**
     * Validate parallel testing integration
     */
    private static function validateParallelTesting(): void
    {
        try {
            // Test parallel safety checking
            $safeTags = ['unit', 'parallel'];
            $unsafeTags = ['sequential', 'external'];

            self::$integrationResults['parallel']['safety_check_works'] = 
                ParallelTestManager::isParallelSafe($safeTags) && 
                !ParallelTestManager::isParallelSafe($unsafeTags);

            // Test worker data creation
            $testData = ParallelTestManager::createTestData('test', ['key' => 'value']);
            self::$integrationResults['parallel']['test_data_creation_works'] = 
                isset($testData['worker_id'], $testData['key']);

            // Test configuration validation
            $configIssues = ParallelTestManager::validateConfiguration();
            self::$integrationResults['parallel']['config_validation_works'] = is_array($configIssues);

            // Test optimal process count calculation
            $processCount = ParallelTestManager::getOptimalProcessCount();
            self::$integrationResults['parallel']['process_count_calculated'] = $processCount > 0;

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Parallel testing error: " . $e->getMessage();
        }
    }

    /**
     * Validate test execution integration
     */
    private static function validateTestExecution(): void
    {
        try {
            // Initialize execution manager
            TestExecutionManager::initialize();
            self::$integrationResults['execution']['initialization_works'] = true;

            // Test conditional loading
            $shouldLoad = TestExecutionManager::shouldLoadTests([
                'environment' => 'testing',
            ]);
            self::$integrationResults['execution']['conditional_loading_works'] = is_bool($shouldLoad);

            // Test performance thresholds
            TestExecutionManager::setPerformanceThreshold('custom', 2.0);
            $threshold = TestExecutionManager::getPerformanceThreshold('custom');
            self::$integrationResults['execution']['threshold_management_works'] = $threshold === 2.0;

            // Test result recording
            TestExecutionManager::recordTestResult([
                'name' => 'test',
                'status' => 'passed',
                'execution_time' => 0.1,
            ]);

            $results = TestExecutionManager::getAggregatedResults();
            self::$integrationResults['execution']['result_aggregation_works'] = 
                isset($results['summary']);

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Test execution error: " . $e->getMessage();
        }
    }

    /**
     * Validate cross-component integration
     */
    private static function validateCrossComponentIntegration(): void
    {
        try {
            // Test tag filtering with execution manager
            $filter = TagFilter::forSuite('unit');
            $testTags = ['unit', 'fast'];
            $shouldExecute = $filter->shouldExecute($testTags);
            
            self::$integrationResults['cross_integration']['tag_execution_integration'] = $shouldExecute;

            // Test lifecycle with parallel manager
            $parallelSafeData = ParallelTestManager::createTestData('lifecycle_test');
            $tempFile = EnhancedLifecycleManager::createTempFile('parallel test');
            
            self::$integrationResults['cross_integration']['lifecycle_parallel_integration'] = 
                isset($parallelSafeData['worker_id']) && file_exists($tempFile);

            // Test organization with tagging
            $recommendedTags = TagManager::getRecommendedTags('unit');
            $testName = TestNamingConventions::behaviorTest('work', 'conditions_are_met');
            
            self::$integrationResults['cross_integration']['organization_tagging_integration'] = 
                !empty($recommendedTags) && str_contains($testName, 'it_should');

            // Test matchers with all systems
            if (function_exists('toBeValidLink') && function_exists('tags')) {
                self::$integrationResults['cross_integration']['matchers_integration'] = true;
            }

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Cross-component integration error: " . $e->getMessage();
        }
    }

    /**
     * Validate configuration integration
     */
    private static function validateConfigurationIntegration(): void
    {
        try {
            // Check if pest.config.php has advanced features
            $configPath = base_path('pest.config.php');
            if (file_exists($configPath)) {
                $configContent = file_get_contents($configPath);
                
                $requiredSections = [
                    'tagging',
                    'custom_matchers',
                    'organization',
                    'lifecycle',
                    'parallel',
                    'execution',
                ];

                $foundSections = [];
                foreach ($requiredSections as $section) {
                    if (str_contains($configContent, "'{$section}'")) {
                        $foundSections[] = $section;
                    }
                }

                self::$integrationResults['configuration']['sections_found'] = $foundSections;
                self::$integrationResults['configuration']['all_sections_present'] = 
                    count($foundSections) === count($requiredSections);

                if (count($foundSections) !== count($requiredSections)) {
                    $missing = array_diff($requiredSections, $foundSections);
                    self::$validationErrors[] = "Missing configuration sections: " . implode(', ', $missing);
                }
            } else {
                self::$validationErrors[] = "pest.config.php file not found";
            }

        } catch (\Throwable $e) {
            self::$validationErrors[] = "Configuration integration error: " . $e->getMessage();
        }
    }

    /**
     * Generate validation summary
     */
    private static function generateValidationSummary(): array
    {
        $totalChecks = 0;
        $passedChecks = 0;

        foreach (self::$integrationResults as $component => $results) {
            foreach ($results as $check => $result) {
                $totalChecks++;
                if ($result === true || (is_array($result) && !empty($result))) {
                    $passedChecks++;
                }
            }
        }

        return [
            'total_checks' => $totalChecks,
            'passed_checks' => $passedChecks,
            'failed_checks' => $totalChecks - $passedChecks,
            'success_rate' => $totalChecks > 0 ? ($passedChecks / $totalChecks) * 100 : 0,
            'total_errors' => count(self::$validationErrors),
            'components_tested' => array_keys(self::$integrationResults),
        ];
    }

    /**
     * Reset validation state
     */
    private static function resetValidation(): void
    {
        self::$integrationResults = [];
        self::$validationErrors = [];
    }

    /**
     * Get detailed integration report
     */
    public static function getIntegrationReport(): array
    {
        $validation = self::validateIntegration();
        
        return [
            'validation_results' => $validation,
            'recommendations' => self::generateRecommendations($validation),
            'next_steps' => self::generateNextSteps($validation),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate recommendations based on validation results
     */
    private static function generateRecommendations(array $validation): array
    {
        $recommendations = [];

        if (!$validation['success']) {
            $recommendations[] = 'Fix validation errors before proceeding with advanced features';
        }

        $summary = $validation['summary'];
        if ($summary['success_rate'] < 90) {
            $recommendations[] = 'Improve integration success rate - currently at ' . 
                round($summary['success_rate'], 1) . '%';
        }

        if (!empty($validation['errors'])) {
            $recommendations[] = 'Address the following errors: ' . implode('; ', $validation['errors']);
        }

        // Component-specific recommendations
        $results = $validation['results'];
        
        if (!($results['matchers']['registry_loaded'] ?? false)) {
            $recommendations[] = 'Ensure custom matchers are properly loaded';
        }

        if (!($results['configuration']['all_sections_present'] ?? false)) {
            $recommendations[] = 'Complete pest.config.php configuration with all advanced sections';
        }

        if (empty($recommendations)) {
            $recommendations[] = 'All advanced PEST features are properly integrated and ready for use';
        }

        return $recommendations;
    }

    /**
     * Generate next steps based on validation results
     */
    private static function generateNextSteps(array $validation): array
    {
        $nextSteps = [];

        if ($validation['success']) {
            $nextSteps[] = 'Begin using advanced PEST features in your test suite';
            $nextSteps[] = 'Create tests using the new tagging system and custom matchers';
            $nextSteps[] = 'Configure parallel testing for improved performance';
            $nextSteps[] = 'Implement enhanced lifecycle management for better test isolation';
        } else {
            $nextSteps[] = 'Fix integration errors identified in the validation';
            $nextSteps[] = 'Re-run validation after fixes';
            $nextSteps[] = 'Review configuration and ensure all components are properly set up';
        }

        $nextSteps[] = 'Monitor test execution performance and adjust thresholds as needed';
        $nextSteps[] = 'Regularly review and update tag classifications';
        $nextSteps[] = 'Consider creating additional custom matchers for domain-specific needs';

        return $nextSteps;
    }

    /**
     * Quick validation check
     */
    public static function quickValidation(): bool
    {
        try {
            // Quick checks for critical functionality
            $checks = [
                function_exists('tags'),
                function_exists('toBeValidLink'),
                class_exists(TagManager::class),
                class_exists(MatcherRegistry::class),
                class_exists(EnhancedLifecycleManager::class),
                class_exists(ParallelTestManager::class),
                class_exists(TestExecutionManager::class),
            ];

            return !in_array(false, $checks, true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}