<?php

declare(strict_types=1);

/**
 * Advanced PEST Features Demonstration.
 *
 * This file demonstrates all the advanced PEST features implemented:
 * - Comprehensive tagging system
 * - Custom matchers for domain-specific assertions
 * - Enhanced test organization with naming conventions
 * - Lifecycle management with automatic cleanup
 * - Parallel testing capabilities
 * - Test execution utilities
 */

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Services\Contracts\LinkValidationInterface;
use Tests\Support\Execution\TestExecutionManager;
use Tests\Support\Lifecycle\EnhancedLifecycleManager;
use Tests\Support\Matchers\MatcherRegistry;
use Tests\Support\Organization\TestNamingConventions;
use Tests\Support\Parallel\ParallelTestManager;
use Tests\Support\PestTestCase;
use Tests\Support\Tags\TagFilter;
use Tests\Support\Tags\TagManager;

// Load all custom matchers
require_once __DIR__ . '/../Support/Tags/PestTagHelpers.php';
require_once __DIR__ . '/../Support/Matchers/LinkValidationMatchers.php';
require_once __DIR__ . '/../Support/Matchers/PerformanceMatchers.php';
require_once __DIR__ . '/../Support/Matchers/SecurityMatchers.php';
require_once __DIR__ . '/../Support/Matchers/ReportingMatchers.php';

uses(PestTestCase::class);

// Initialize advanced features
beforeAll(function (): void {
    MatcherRegistry::loadAll();
    EnhancedLifecycleManager::setupEnhancedBeforeEach();
    EnhancedLifecycleManager::setupEnhancedAfterEach();
    TestExecutionManager::initialize();
});

// =============================================================================
// COMPREHENSIVE TAGGING SYSTEM EXAMPLES
// =============================================================================

taggedAs('Comprehensive Tagging System', 'unit', 'fast', 'parallel')->describe('TagManager', function (): void {
    unit()->it_should_provide_all_available_tags_correctly(function (): void {
        $allTags = TagManager::getAllTags();

        expect($allTags)->toBeArray()
            ->and($allTags)->toContain('performance', 'unit', 'security', 'validation');
    });

    unit()->it_should_resolve_tag_aliases_correctly(function (): void {
        expect(TagManager::resolveAlias('perf'))->toBe('performance');
        expect(TagManager::resolveAlias('mem'))->toBe('memory');
        expect(TagManager::resolveAlias('int'))->toBe('integration');
    });

    unit()->it_should_validate_tag_exclusions_correctly(function (): void {
        expect(TagManager::isExcludedByDefault('performance'))->toBeTrue();
        expect(TagManager::isExcludedByDefault('unit'))->toBeFalse();
    });

    unit()->it_should_check_setup_requirements_correctly(function (): void {
        expect(TagManager::requiresSetup('external'))->toBeTrue();
        expect(TagManager::requiresSetup('unit'))->toBeFalse();

        $requirements = TagManager::getSetupRequirements('github');
        expect($requirements)->toContain('github_token');
    });
});

taggedAs('Tag Filtering', 'unit', 'fast')->describe('TagFilter', function (): void {
    unit()->it_should_filter_tests_by_include_tags_correctly(function (): void {
        $filter = new TagFilter();
        $filter->include(['unit', 'fast']);

        expect($filter->shouldExecute(['unit', 'fast']))->toBeTrue();
        expect($filter->shouldExecute(['integration', 'slow']))->toBeFalse();
    });

    unit()->it_should_filter_tests_by_exclude_tags_correctly(function (): void {
        $filter = new TagFilter();
        $filter->exclude(['slow', 'external']);

        expect($filter->shouldExecute(['unit', 'fast']))->toBeTrue();
        expect($filter->shouldExecute(['unit', 'slow']))->toBeFalse();
    });

    unit()->it_should_create_filter_from_environment_correctly(function (): void {
        $_ENV['PEST_TAGS'] = 'unit,fast';
        $_ENV['PEST_EXCLUDE_TAGS'] = 'slow';

        $filter = TagFilter::fromEnvironment();
        $config = $filter->getConfiguration();

        expect($config['include_tags'])->toContain('unit', 'fast');
        expect($config['exclude_tags'])->toContain('slow');
    });
});

// =============================================================================
// CUSTOM MATCHERS EXAMPLES
// =============================================================================

taggedAs('Custom Matchers - Link Validation', 'unit', 'validation')->describe('Link Validation Matchers', function (): void {
    validation()->it_should_assert_valid_links_correctly(function (): void {
        $validResult = $this->createValidationResult(['status' => LinkStatus::VALID]);

        expect($validResult)->toBeValidLink();
        expect('https://example.com')->toBeValidLink();
    });

    validation()->it_should_assert_broken_links_correctly(function (): void {
        $brokenResult = $this->createValidationResult(['status' => LinkStatus::BROKEN]);

        expect($brokenResult)->toBeBrokenLink();
    });

    validation()->it_should_assert_http_status_codes_correctly(function (): void {
        $result = $this->createValidationResult(['http_status_code' => 200]);

        expect($result)->toHaveHttpStatus(200)
            ->and($result)->toBeSuccessfulHttpStatus();
    });

    validation()->it_should_assert_response_times_correctly(function (): void {
        $result = $this->createValidationResult(['response_time' => 0.5]);

        expect($result)->toHaveResponseTimeBelow(1.0);
    });

    validation()->it_should_assert_validation_collections_correctly(function (): void {
        $collection = $this->createValidationCollection(5);

        expect($collection)->toBeValidationCollection(5)
            ->and($collection)->toHaveValidResults(3)
            ->and($collection)->toHaveSuccessRate(0.6, 0.1);
    });
});

taggedAs('Custom Matchers - Performance', 'performance', 'slow')->describe('Performance Matchers', function (): void {
    performance()->it_should_assert_execution_time_correctly(function (): void {
        $fastOperation = fn () => usleep(50000); // 50ms

        expect($fastOperation)->toCompleteWithin(0.1);
    });

    performance()->it_should_assert_memory_usage_correctly(function (): void {
        $lightOperation = fn () => array_fill(0, 100, 'test');

        expect($lightOperation)->toUseMemoryLessThan(1024 * 1024); // 1MB
    });

    performance()->it_should_assert_performance_criteria_correctly(function (): void {
        $efficientOperation = fn () => array_sum(range(1, 1000));

        expect($efficientOperation)->toBePerformant(0.1, 512 * 1024);
    });

    performance()->it_should_detect_memory_leaks_correctly(function (): void {
        $cleanOperation = fn () => $temp = range(1, 100);

        expect($cleanOperation)->toNotLeakMemory(50, 1024);
    });
});

taggedAs('Custom Matchers - Security', 'security', 'critical')->describe('Security Matchers', function (): void {
    security()->it_should_assert_secure_urls_correctly(function (): void {
        expect('https://example.com')->toBeSecure()
            ->and('https://example.com')->toHaveSecureProtocol();

        expect('http://example.com')->toHaveInsecureProtocol();
    });

    security()->it_should_detect_suspicious_paths_correctly(function (): void {
        expect('https://example.com/admin/secret')->toHaveSuspiciousPath();
        expect('https://example.com/public/info')->not->toHaveSuspiciousPath();
    });

    security()->it_should_detect_malicious_content_correctly(function (): void {
        $maliciousContent = '<script>alert("xss")</script>';
        $safeContent = '<p>Hello world</p>';

        expect($maliciousContent)->toContainMaliciousContent();
        expect($safeContent)->not->toContainMaliciousContent();
    });

    security()->it_should_validate_security_headers_correctly(function (): void {
        $secureHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
        ];

        expect($secureHeaders)->toHaveSecurityHeaders()
            ->and($secureHeaders)->toPreventClickjacking()
            ->and($secureHeaders)->toPreventMimeSniffing()
            ->and($secureHeaders)->toHaveXSSProtection();
    });
});

taggedAs('Custom Matchers - Reporting', 'reporting', 'unit')->describe('Reporting Matchers', function (): void {
    reporting()->it_should_validate_report_formats_correctly(function (): void {
        $jsonReport = '{"results": [], "summary": {}}';
        $htmlReport = '<!DOCTYPE html><html><body></body></html>';
        $markdownReport = '# Report\n\n**Summary**\n\n- Item 1';

        expect($jsonReport)->toMatchReportFormat(OutputFormat::JSON);
        expect($htmlReport)->toMatchReportFormat(OutputFormat::HTML);
        expect($markdownReport)->toMatchReportFormat(OutputFormat::MARKDOWN);
    });

    reporting()->it_should_validate_report_structure_correctly(function (): void {
        $validReport = [
            'results' => [['url' => 'test', 'status' => 'valid']],
            'summary' => ['total' => 1, 'valid' => 1],
            'metadata' => ['generated_at' => '2023-01-01'],
        ];

        expect($validReport)->toBeValidReport()
            ->and($validReport)->toHaveReportSummary()
            ->and($validReport)->toContainValidationResults(1)
            ->and($validReport)->toHaveMetadata();
    });
});

// =============================================================================
// TEST ORGANIZATION EXAMPLES
// =============================================================================

taggedAs('Test Organization', 'unit', 'organization')->describe('TestNamingConventions', function (): void {
    unit()->it_should_generate_behavior_test_names_correctly(function (): void {
        $testName = TestNamingConventions::behaviorTest('validate_url', 'url_is_provided');

        expect($testName)->toBe('it_should_validate_url_when_url_is_provided');
    });

    unit()->it_should_validate_test_names_correctly(function (): void {
        $validName = 'it_should_work_correctly_when_input_is_valid';
        $invalidName = 'testSomething';

        expect(TestNamingConventions::validateTestName($validName))->toBeEmpty();
        expect(TestNamingConventions::validateTestName($invalidName))->not->toBeEmpty();
    });

    unit()->it_should_convert_case_formats_correctly(function (): void {
        expect(TestNamingConventions::toSnakeCase('CamelCase'))->toBe('camel_case');
        expect(TestNamingConventions::toCamelCase('snake_case'))->toBe('snakeCase');
        expect(TestNamingConventions::toPascalCase('snake_case'))->toBe('SnakeCase');
    });
});

// =============================================================================
// LIFECYCLE MANAGEMENT EXAMPLES
// =============================================================================

taggedAs('Lifecycle Management', 'unit', 'lifecycle')->describe('EnhancedLifecycleManager', function (): void {
    unit()->it_should_track_resources_automatically(function (): void {
        $tempFile = EnhancedLifecycleManager::createTempFile('test content');
        $tempDir = EnhancedLifecycleManager::createTempDirectory();

        expect(file_exists($tempFile))->toBeTrue();
        expect(is_dir($tempDir))->toBeTrue();

        // Resources will be automatically cleaned up after test
    });

    unit()->it_should_monitor_memory_usage_correctly(function (): void {
        $result = EnhancedLifecycleManager::monitorMemory(function () {
            return array_fill(0, 1000, 'test');
        }, 'memory_test');

        expect($result)->toHaveKey('execution_time')
            ->and($result)->toHaveKey('memory_used')
            ->and($result)->toHaveKey('memory_leak_detected');
    });

    unit()->it_should_record_performance_metrics_correctly(function (): void {
        EnhancedLifecycleManager::recordMetric('test_metric', 1.5, 'seconds');

        $metrics = EnhancedLifecycleManager::getPerformanceMetrics();

        expect($metrics)->toHaveKey('test_metric')
            ->and($metrics['test_metric'])->toHaveKey('avg', 'min', 'max');
    });
});

// =============================================================================
// PARALLEL TESTING EXAMPLES
// =============================================================================

taggedAs('Parallel Testing', 'parallel', 'unit')->describe('ParallelTestManager', function (): void {
    parallel()->it_should_check_parallel_safety_correctly(function (): void {
        $safeTags = ['unit', 'fast', 'parallel'];
        $unsafeTags = ['sequential', 'external', 'file_system'];

        expect(ParallelTestManager::isParallelSafe($safeTags))->toBeTrue();
        expect(ParallelTestManager::isParallelSafe($unsafeTags))->toBeFalse();
    });

    parallel()->it_should_create_parallel_safe_test_data_correctly(function (): void {
        $testData = ParallelTestManager::createTestData('user', ['name' => 'Test User']);

        expect($testData)->toHaveKey('worker_id')
            ->and($testData)->toHaveKey('unique_suffix')
            ->and($testData)->toHaveKey('name')
            ->and($testData['name'])->toBe('Test User');
    });

    parallel()->it_should_manage_exclusive_access_correctly(function (): void {
        $result = ParallelTestManager::withExclusiveAccess('test_resource', function () {
            return 'exclusive_operation_result';
        });

        expect($result)->toBe('exclusive_operation_result');
    });
});

// =============================================================================
// TEST EXECUTION EXAMPLES
// =============================================================================

taggedAs('Test Execution', 'unit', 'execution')->describe('TestExecutionManager', function (): void {
    unit()->it_should_check_loading_conditions_correctly(function (): void {
        $ciConditions = ['ci' => true, 'environment' => 'testing'];
        $localConditions = ['ci' => false, 'environment' => 'local'];

        TestExecutionManager::updateContext(['ci' => true, 'environment' => 'testing']);
        expect(TestExecutionManager::shouldLoadTests($ciConditions))->toBeTrue();
        expect(TestExecutionManager::shouldLoadTests($localConditions))->toBeFalse();
    });

    unit()->it_should_filter_test_suites_correctly(function (): void {
        $tests = [
            ['name' => 'test1', 'tags' => ['unit', 'fast']],
            ['name' => 'test2', 'tags' => ['integration', 'slow']],
            ['name' => 'test3', 'tags' => ['unit', 'parallel']],
        ];

        $criteria = ['tags' => ['unit']];
        $filtered = TestExecutionManager::filterTestSuite($tests, $criteria);

        expect($filtered)->toHaveCount(2);
    });

    unit()->it_should_group_tests_correctly(function (): void {
        $tests = [
            ['name' => 'test1', 'tags' => ['unit']],
            ['name' => 'test2', 'tags' => ['integration']],
            ['name' => 'test3', 'tags' => ['unit']],
        ];

        $grouped = TestExecutionManager::groupTests($tests, 'tags');

        expect($grouped)->toHaveKey('unit')
            ->and($grouped)->toHaveKey('integration')
            ->and($grouped['unit'])->toHaveCount(2);
    });

    unit()->it_should_manage_performance_thresholds_correctly(function (): void {
        TestExecutionManager::setPerformanceThreshold('custom', 2.0);

        expect(TestExecutionManager::getPerformanceThreshold('custom'))->toBe(2.0);
        expect(TestExecutionManager::meetsPerformanceThreshold(1.5, 'custom'))->toBeTrue();
        expect(TestExecutionManager::meetsPerformanceThreshold(2.5, 'custom'))->toBeFalse();
    });
});

// =============================================================================
// INTEGRATION EXAMPLES
// =============================================================================

taggedAs('Feature Integration', 'integration', 'feature')->describe('Advanced Features Integration', function (): void {
    integration()->it_should_integrate_all_features_seamlessly(function (): void {
        // Use lazy initialization
        $this->let('testConfig', fn () => [
            'timeout' => 30,
            'retries' => 3,
            'parallel_safe' => true,
        ]);

        // Use dependency injection
        $linkValidation = $this->bindMock(LinkValidationInterface::class, [
            'validateLink' => $this->createValidationResult(['status' => LinkStatus::VALID]),
        ]);

        // Use performance monitoring
        $result = $this->assertPerformant(function () use ($linkValidation) {
            return $linkValidation->validateLink('https://example.com');
        }, 0.5, 1024 * 1024);

        // Use custom matchers
        expect($result)->toBeValidLink()
            ->and($result)->toHaveHttpStatus(200);

        // Verify lazy property access
        expect($this->testConfig['timeout'])->toBe(30);
    });

    integration()->it_should_demonstrate_complete_workflow(function (): void {
        // Setup with enhanced lifecycle
        EnhancedLifecycleManager::takeMemorySnapshot('workflow_start');

        // Create test data with parallel safety
        $testData = ParallelTestManager::createTestData('validation_test', [
            'urls' => ['https://example.com', 'https://test.com'],
        ]);

        // Execute with performance monitoring
        $executionResult = TestExecutionManager::shouldLoadTests([
            'environment' => 'testing',
            'ci' => false,
        ]);

        // Validate with custom matchers
        expect($executionResult)->toBeTrue()
            ->and($testData)->toHaveKey('urls')
            ->and($testData['urls'])->toHaveCount(2);

        // Check memory usage
        EnhancedLifecycleManager::takeMemorySnapshot('workflow_end');
        $memoryOk = EnhancedLifecycleManager::checkMemoryLeaks('workflow_start', 'workflow_end');

        expect($memoryOk)->toBeTrue();
    });
});

// =============================================================================
// EDGE CASES AND ERROR HANDLING
// =============================================================================

taggedAs('Edge Cases', 'edge_case', 'error_handling')->describe('Edge Cases and Error Handling', function (): void {
    edge_case()->it_should_handle_invalid_tag_configurations_gracefully(function (): void {
        $invalidTags = ['nonexistent_tag', 'another_invalid'];
        $invalidTagsFound = TagManager::validateTags($invalidTags);

        expect($invalidTagsFound)->toContain('nonexistent_tag', 'another_invalid');
    });

    error_handling()->it_should_handle_matcher_loading_failures_gracefully(function (): void {
        $missingMatchers = MatcherRegistry::validateMatchers();

        // Should return empty array if all matchers exist
        expect($missingMatchers)->toBeArray();
    });

    edge_case()->it_should_handle_parallel_resource_conflicts_gracefully(function (): void {
        // Simulate resource conflict
        $acquired = ParallelTestManager::acquireLock('test_resource', 1); // 1 second timeout

        if ($acquired) {
            // Try to acquire same lock (should fail quickly)
            $conflicted = ParallelTestManager::acquireLock('test_resource', 1);
            expect($conflicted)->toBeFalse();

            // Release the lock
            ParallelTestManager::releaseLock('test_resource');
        }

        expect($acquired)->toBeTrue();
    });
});
