<?php

declare(strict_types=1);

namespace Tests\Support\Organization;

/**
 * Test Naming Conventions for PEST
 * 
 * Provides standardized naming conventions and organizational patterns
 * for PEST tests, ensuring consistency and readability across the test suite.
 */
class TestNamingConventions
{
    /**
     * Action-oriented naming patterns
     */
    public const NAMING_PATTERNS = [
        'behavior' => 'it_should_{expected_behavior}_when_{condition}',
        'validation' => 'it_validates_{what}_correctly',
        'error' => 'it_throws_{exception}_when_{condition}',
        'performance' => 'it_completes_{action}_within_{time_limit}',
        'integration' => 'it_integrates_with_{external_system}_successfully',
        'edge_case' => 'it_handles_{edge_case}_appropriately',
    ];

    /**
     * Describe block organization patterns
     */
    public const DESCRIBE_PATTERNS = [
        'class' => '{ClassName}',
        'method' => '{ClassName}::{methodName}()',
        'feature' => '{FeatureName} Feature',
        'component' => '{ComponentName} Component',
        'service' => '{ServiceName} Service',
        'validation' => '{EntityName} Validation',
        'integration' => '{SystemName} Integration',
        'performance' => '{OperationName} Performance',
    ];

    /**
     * Test file naming conventions
     */
    public const FILE_NAMING = [
        'unit' => '{ClassName}Test.php',
        'feature' => '{FeatureName}FeatureTest.php',
        'integration' => '{ComponentName}IntegrationTest.php',
        'performance' => '{OperationName}PerformanceTest.php',
        'e2e' => '{WorkflowName}E2ETest.php',
    ];

    /**
     * Generate test name using behavior pattern
     */
    public static function behaviorTest(string $expectedBehavior, string $condition): string
    {
        return "it_should_{$expectedBehavior}_when_{$condition}";
    }

    /**
     * Generate test name using validation pattern
     */
    public static function validationTest(string $what): string
    {
        return "it_validates_{$what}_correctly";
    }

    /**
     * Generate test name using error pattern
     */
    public static function errorTest(string $exception, string $condition): string
    {
        return "it_throws_{$exception}_when_{$condition}";
    }

    /**
     * Generate test name using performance pattern
     */
    public static function performanceTest(string $action, string $timeLimit): string
    {
        return "it_completes_{$action}_within_{$timeLimit}";
    }

    /**
     * Generate test name using integration pattern
     */
    public static function integrationTest(string $externalSystem): string
    {
        return "it_integrates_with_{$externalSystem}_successfully";
    }

    /**
     * Generate test name using edge case pattern
     */
    public static function edgeCaseTest(string $edgeCase): string
    {
        return "it_handles_{$edgeCase}_appropriately";
    }

    /**
     * Generate describe block name for class testing
     */
    public static function describeClass(string $className): string
    {
        return $className;
    }

    /**
     * Generate describe block name for method testing
     */
    public static function describeMethod(string $className, string $methodName): string
    {
        return "{$className}::{$methodName}()";
    }

    /**
     * Generate describe block name for feature testing
     */
    public static function describeFeature(string $featureName): string
    {
        return "{$featureName} Feature";
    }

    /**
     * Generate describe block name for component testing
     */
    public static function describeComponent(string $componentName): string
    {
        return "{$componentName} Component";
    }

    /**
     * Generate describe block name for service testing
     */
    public static function describeService(string $serviceName): string
    {
        return "{$serviceName} Service";
    }

    /**
     * Generate describe block name for validation testing
     */
    public static function describeValidation(string $entityName): string
    {
        return "{$entityName} Validation";
    }

    /**
     * Generate describe block name for integration testing
     */
    public static function describeIntegration(string $systemName): string
    {
        return "{$systemName} Integration";
    }

    /**
     * Generate describe block name for performance testing
     */
    public static function describePerformance(string $operationName): string
    {
        return "{$operationName} Performance";
    }

    /**
     * Generate file name for unit tests
     */
    public static function unitTestFile(string $className): string
    {
        return "{$className}Test.php";
    }

    /**
     * Generate file name for feature tests
     */
    public static function featureTestFile(string $featureName): string
    {
        return "{$featureName}FeatureTest.php";
    }

    /**
     * Generate file name for integration tests
     */
    public static function integrationTestFile(string $componentName): string
    {
        return "{$componentName}IntegrationTest.php";
    }

    /**
     * Generate file name for performance tests
     */
    public static function performanceTestFile(string $operationName): string
    {
        return "{$operationName}PerformanceTest.php";
    }

    /**
     * Generate file name for E2E tests
     */
    public static function e2eTestFile(string $workflowName): string
    {
        return "{$workflowName}E2ETest.php";
    }

    /**
     * Convert camelCase to snake_case
     */
    public static function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    /**
     * Convert snake_case to camelCase
     */
    public static function toCamelCase(string $input): string
    {
        return lcfirst(str_replace('_', '', ucwords($input, '_')));
    }

    /**
     * Convert snake_case to PascalCase
     */
    public static function toPascalCase(string $input): string
    {
        return str_replace('_', '', ucwords($input, '_'));
    }

    /**
     * Generate test method name from description
     */
    public static function generateTestMethodName(string $description): string
    {
        // Remove common test prefixes
        $description = preg_replace('/^(test|it|should)\s+/i', '', $description);
        
        // Convert to snake_case
        $snakeCase = self::toSnakeCase($description);
        
        // Clean up and ensure it starts with 'it_'
        $cleaned = preg_replace('/[^a-z0-9_]/', '_', $snakeCase);
        $cleaned = preg_replace('/_+/', '_', $cleaned);
        $cleaned = trim($cleaned, '_');
        
        return 'it_' . $cleaned;
    }

    /**
     * Validate test name follows conventions
     */
    public static function validateTestName(string $testName): array
    {
        $issues = [];
        
        // Check if it starts with 'it_'
        if (!str_starts_with($testName, 'it_')) {
            $issues[] = 'Test name should start with "it_"';
        }
        
        // Check if it uses snake_case
        if (!preg_match('/^[a-z0-9_]+$/', $testName)) {
            $issues[] = 'Test name should use snake_case with only lowercase letters, numbers, and underscores';
        }
        
        // Check for double underscores
        if (str_contains($testName, '__')) {
            $issues[] = 'Test name should not contain double underscores';
        }
        
        // Check length
        if (strlen($testName) > 100) {
            $issues[] = 'Test name should be less than 100 characters';
        }
        
        if (strlen($testName) < 10) {
            $issues[] = 'Test name should be descriptive (at least 10 characters)';
        }
        
        return $issues;
    }

    /**
     * Suggest improvements for test name
     */
    public static function suggestImprovements(string $testName): array
    {
        $suggestions = [];
        
        // Check for common anti-patterns
        if (str_contains($testName, 'test_')) {
            $suggestions[] = 'Remove "test_" prefix - PEST tests should start with "it_"';
        }
        
        if (!str_contains($testName, '_when_') && !str_contains($testName, '_should_')) {
            $suggestions[] = 'Consider using behavior-driven naming: "it_should_X_when_Y"';
        }
        
        if (str_word_count(str_replace('_', ' ', $testName)) < 4) {
            $suggestions[] = 'Make the test name more descriptive';
        }
        
        return $suggestions;
    }

    /**
     * Generate test structure template
     */
    public static function generateTestStructure(string $className, array $methods = []): string
    {
        $template = "<?php\n\n";
        $template .= "declare(strict_types=1);\n\n";
        $template .= "use Tests\\Support\\PestTestCase;\n\n";
        $template .= "uses(PestTestCase::class);\n\n";
        $template .= "describe('" . self::describeClass($className) . "', function () {\n";
        
        if (empty($methods)) {
            $template .= "    it_should_be_instantiable();\n";
        } else {
            foreach ($methods as $method) {
                $template .= "\n    describe('" . self::describeMethod($className, $method) . "', function () {\n";
                $template .= "        it_should_work_correctly_with_valid_input();\n";
                $template .= "        it_should_handle_invalid_input_appropriately();\n";
                $template .= "    });\n";
            }
        }
        
        $template .= "});\n";
        
        return $template;
    }

    /**
     * Get naming convention examples
     */
    public static function getExamples(): array
    {
        return [
            'behavior_tests' => [
                'it_should_validate_url_when_url_is_valid',
                'it_should_return_error_when_url_is_invalid',
                'it_should_timeout_when_request_takes_too_long',
            ],
            'validation_tests' => [
                'it_validates_email_format_correctly',
                'it_validates_url_structure_correctly',
                'it_validates_required_fields_correctly',
            ],
            'error_tests' => [
                'it_throws_validation_exception_when_url_is_malformed',
                'it_throws_timeout_exception_when_request_exceeds_limit',
                'it_throws_security_exception_when_domain_is_blocked',
            ],
            'performance_tests' => [
                'it_completes_validation_within_one_second',
                'it_completes_batch_processing_within_five_seconds',
                'it_completes_report_generation_within_ten_seconds',
            ],
            'integration_tests' => [
                'it_integrates_with_github_api_successfully',
                'it_integrates_with_database_successfully',
                'it_integrates_with_cache_service_successfully',
            ],
            'edge_case_tests' => [
                'it_handles_empty_input_appropriately',
                'it_handles_null_values_appropriately',
                'it_handles_extremely_long_urls_appropriately',
            ],
        ];
    }

    /**
     * Get describe block examples
     */
    public static function getDescribeExamples(): array
    {
        return [
            'class_testing' => [
                'LinkValidationService',
                'SecurityValidationService',
                'ReportingService',
            ],
            'method_testing' => [
                'LinkValidationService::validateLink()',
                'SecurityValidationService::validateUrl()',
                'ReportingService::generateReport()',
            ],
            'feature_testing' => [
                'Link Validation Feature',
                'Report Generation Feature',
                'Security Scanning Feature',
            ],
            'component_testing' => [
                'Validation Component',
                'Reporting Component',
                'Security Component',
            ],
        ];
    }
}