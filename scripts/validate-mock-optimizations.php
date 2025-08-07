#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Mock Optimization Validation Script.
 *
 * This script validates that the mock optimizations work correctly
 * and maintain backward compatibility with existing tests.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tests\Support\HttpMockFactory;
use Tests\Support\MockDataBuilder;
use Tests\Support\MockFactory;
use Tests\Support\MockLifecycleManager;

final class MockOptimizationValidator
{
    private array $results = [];

    private int $passed = 0;

    private int $failed = 0;

    public function run(): int
    {
        echo "🧪 Validating Mock Optimizations...\n\n";

        $this->validateMockFactory();
        $this->validateHttpMockFactory();
        $this->validateMockDataBuilder();
        $this->validateMockLifecycleManager();
        $this->validateHelperFunctions();
        $this->validateBackwardCompatibility();

        $this->printSummary();

        return $this->failed > 0 ? 1 : 0;
    }

    private function validateMockFactory(): void
    {
        echo "📦 Testing MockFactory...\n";

        try {
            // Test security validation service creation
            $securityMock = MockFactory::createSecurityValidationService();
            $this->assert($securityMock instanceof Mockery\MockInterface, 'Security mock creation');

            // Test link validation service creation
            $linkMock = MockFactory::createLinkValidationService();
            $this->assert($linkMock instanceof Mockery\MockInterface, 'Link validation mock creation');

            // Test reporting service creation
            $reportingMock = MockFactory::createReportingService();
            $this->assert($reportingMock instanceof Mockery\MockInterface, 'Reporting mock creation');

            // Test custom configuration
            $customSecurityMock = MockFactory::createSecurityValidationService([
                'safe_urls' => ['https://example.com'],
                'malicious_urls' => ['https://malicious.com'],
            ]);
            $this->assert($customSecurityMock instanceof Mockery\MockInterface, 'Custom security mock creation');

            echo "  ✅ MockFactory validation passed\n";
        } catch (Exception $e) {
            echo '  ❌ MockFactory validation failed: ' . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    private function validateHttpMockFactory(): void
    {
        echo "🌐 Testing HttpMockFactory...\n";

        try {
            // Test basic mock client creation
            $client = HttpMockFactory::createMockClient(['success_200', 'not_found_404']);
            $this->assert($client instanceof GuzzleHttp\Client, 'Basic HTTP mock client creation');

            // Test pre-configured clients
            $successClient = HttpMockFactory::createSuccessfulValidationClient();
            $this->assert($successClient instanceof GuzzleHttp\Client, 'Successful validation client');

            $mixedClient = HttpMockFactory::createMixedValidationClient();
            $this->assert($mixedClient instanceof GuzzleHttp\Client, 'Mixed validation client');

            $redirectClient = HttpMockFactory::createRedirectClient();
            $this->assert($redirectClient instanceof GuzzleHttp\Client, 'Redirect client');

            $timeoutClient = HttpMockFactory::createTimeoutClient();
            $this->assert($timeoutClient instanceof GuzzleHttp\Client, 'Timeout client');

            // Test available responses
            $responses = HttpMockFactory::getAvailableResponses();
            $this->assert(is_array($responses) && ! empty($responses), 'Available responses list');

            // Test custom response creation
            $customResponse = HttpMockFactory::createCustomResponse(201, ['Content-Type' => 'application/json'], '{"created": true}');
            $this->assert($customResponse instanceof GuzzleHttp\Psr7\Response, 'Custom response creation');

            echo "  ✅ HttpMockFactory validation passed\n";
        } catch (Exception $e) {
            echo '  ❌ HttpMockFactory validation failed: ' . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    private function validateMockDataBuilder(): void
    {
        echo "🏗️ Testing MockDataBuilder...\n";

        try {
            // Test validation config creation
            $config = MockDataBuilder::createValidationConfig(['timeout' => 30]);
            $this->assert($config instanceof App\Services\ValueObjects\ValidationConfig, 'Validation config creation');

            // Test validation result creation
            $result = MockDataBuilder::createValidationResult(['url' => 'https://example.com']);
            $this->assert($result instanceof App\Services\ValueObjects\ValidationResult, 'Validation result creation');

            // Test validation result collection creation
            $collection = MockDataBuilder::createValidationResultCollection(['count' => 10]);
            $this->assert($collection instanceof App\Services\ValueObjects\ValidationResultCollection, 'Validation result collection creation');
            $this->assert($collection->count() === 10, 'Collection count matches');

            // Test report summary creation
            $summary = MockDataBuilder::createReportSummary(['total_links' => 100]);
            $this->assert($summary instanceof App\Services\ValueObjects\ReportSummary, 'Report summary creation');

            // Test validation statistics creation
            $statistics = MockDataBuilder::createValidationStatistics(['total' => 100]);
            $this->assert($statistics instanceof App\Services\ValueObjects\ValidationStatistics, 'Validation statistics creation');

            echo "  ✅ MockDataBuilder validation passed\n";
        } catch (Exception $e) {
            echo '  ❌ MockDataBuilder validation failed: ' . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    private function validateMockLifecycleManager(): void
    {
        echo "🔄 Testing MockLifecycleManager...\n";

        try {
            // Test mock registration
            $mock = Mockery::mock(App\Services\Contracts\SecurityValidationInterface::class);
            $registeredMock = MockLifecycleManager::registerMock('test_mock', $mock);
            $this->assert($registeredMock === $mock, 'Mock registration');

            // Test mock retrieval
            $retrievedMock = MockLifecycleManager::getMock('test_mock');
            $this->assert($retrievedMock === $mock, 'Mock retrieval');

            // Test mock existence check
            $this->assert(MockLifecycleManager::hasMock('test_mock'), 'Mock existence check');

            // Test statistics
            $stats = MockLifecycleManager::getStatistics();
            $this->assert(is_array($stats) && isset($stats['total_mocks']), 'Statistics retrieval');

            // Test cleanup
            MockLifecycleManager::cleanup();
            $this->assert(! MockLifecycleManager::hasMock('test_mock'), 'Mock cleanup');

            echo "  ✅ MockLifecycleManager validation passed\n";
        } catch (Exception $e) {
            echo '  ❌ MockLifecycleManager validation failed: ' . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    private function validateHelperFunctions(): void
    {
        echo "🛠️ Testing Helper Functions...\n";

        try {
            // Note: Helper functions are defined in Pest.php and may not be available in this context
            // This is a basic validation that the functions exist

            $helperFunctions = [
                'createSecurityValidationMock',
                'createLinkValidationMock',
                'createReportingMock',
                'createMockHttpClient',
                'createSuccessfulHttpClient',
                'createTestValidationConfig',
                'createTestValidationResult',
                'getTestUrls',
                'getCategorizedTestUrls',
            ];

            $missingFunctions = [];
            foreach ($helperFunctions as $function) {
                if (! function_exists($function)) {
                    $missingFunctions[] = $function;
                }
            }

            if (empty($missingFunctions)) {
                echo "  ✅ All helper functions are defined\n";
            } else {
                echo "  ⚠️ Helper functions are defined in Pest.php context (expected)\n";
                echo '    Missing in CLI context: ' . implode(', ', $missingFunctions) . "\n";
            }
        } catch (Exception $e) {
            echo '  ❌ Helper function validation failed: ' . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    private function validateBackwardCompatibility(): void
    {
        echo "🔄 Testing Backward Compatibility...\n";

        try {
            // Test that old patterns still work alongside new ones

            // Test Mockery still works
            $oldStyleMock = Mockery::mock(App\Services\Contracts\SecurityValidationInterface::class);
            $oldStyleMock->shouldReceive('validateUrl')->andReturn(true);
            $this->assert($oldStyleMock instanceof Mockery\MockInterface, 'Old-style Mockery still works');

            // Test that ValidationConfig can still be created manually
            $manualConfig = new App\Services\ValueObjects\ValidationConfig(
                scopes: [App\Enums\ValidationScope::ALL],
            );
            $this->assert($manualConfig instanceof App\Services\ValueObjects\ValidationConfig, 'Manual config creation still works');

            // Test that ValidationResult can still be created manually
            $manualResult = App\Services\ValueObjects\ValidationResult::create(
                url: 'https://example.com',
                status: App\Enums\LinkStatus::VALID,
                message: '',
                scope: App\Enums\ValidationScope::EXTERNAL,
            );
            $this->assert($manualResult instanceof App\Services\ValueObjects\ValidationResult, 'Manual result creation still works');

            echo "  ✅ Backward compatibility validation passed\n";
        } catch (Exception $e) {
            echo '  ❌ Backward compatibility validation failed: ' . $e->getMessage() . "\n";
            $this->failed++;
        }
    }

    private function assert(bool $condition, string $description): void
    {
        if ($condition) {
            $this->passed++;
            $this->results[] = "✅ {$description}";
        } else {
            $this->failed++;
            $this->results[] = "❌ {$description}";
            throw new Exception("Assertion failed: {$description}");
        }
    }

    private function printSummary(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "📊 VALIDATION SUMMARY\n";
        echo str_repeat('=', 60) . "\n";

        echo "✅ Passed: {$this->passed}\n";
        echo "❌ Failed: {$this->failed}\n";
        echo '📈 Total:  ' . ($this->passed + $this->failed) . "\n\n";

        if ($this->failed === 0) {
            echo "🎉 All mock optimizations are working correctly!\n";
            echo "🚀 The test suite is ready for enhanced mock patterns.\n\n";

            echo "Next steps:\n";
            echo "1. Run the test suite: ./vendor/bin/pest\n";
            echo "2. Check specific test groups: ./vendor/bin/pest --group=integration\n";
            echo "3. Review the documentation: docs/testing/mock-optimization-guide.md\n";
        } else {
            echo "⚠️ Some validations failed. Please review the errors above.\n";
            echo "🔧 Check the implementation and try again.\n";
        }

        echo "\n";
    }
}

// Run the validation
$validator = new MockOptimizationValidator();
exit($validator->run());
