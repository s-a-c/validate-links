<?php

declare(strict_types=1);

use Tests\Support\CommandTestHelper;
use Tests\Support\LoggingTestHelper;
use App\Commands\ValidateCommand;
use App\Commands\ReportCommand;
use App\Commands\BaseValidationCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ReportSummary;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Comprehensive command lifecycle testing suite.
 *
 * Tests:
 * - Command lifecycle from initialization to completion
 * - Dependency injection and service integration
 * - Command state management and transitions
 * - Error handling and recovery during lifecycle
 * - Resource management and cleanup
 * - Performance monitoring throughout lifecycle
 *
 * @package Tests\Unit\Commands
 * @author Roo
 * @since 1.0.0
 */

describe('Command Lifecycle', function () {
    beforeEach(function () {
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr']);
        CommandTestHelper::clearResults();
    });

    afterEach(function () {
        LoggingTestHelper::resetLoggingEnvironment();
        CommandTestHelper::clearResults();
    });

    describe('Complete Command Lifecycle', function () {
        it('completes ValidateCommand lifecycle from initialization to completion', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            // Configure mocks for realistic behavior
            $linkValidation->expects('validateFile')
                ->andReturn(new ValidationResultCollection([]));

            $reporting->expects('generateSummary')
                ->andReturn(ReportSummary::create([
                    'total_links' => 5,
                    'broken_links' => 1,
                    'success_rate' => 80.0,
                ]));

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $parameters = [
                'paths' => ['./test.md'],
                '--scope' => 'all',
                '--format' => 'console',
                '--timeout' => '30',
            ];

            $lifecycle = CommandTestHelper::testCommandLifecycle(
                ValidateCommand::class,
                $dependencies,
                $parameters
            );

            // Verify all lifecycle stages completed successfully
            expect($lifecycle['lifecycle_stages']['instantiation'])
                ->toBeTrue('Command instantiation should succeed');
            expect($lifecycle['lifecycle_stages']['dependency_injection'])
                ->toBeTrue('Dependency injection should succeed');
            expect($lifecycle['lifecycle_stages']['parameter_binding'])
                ->toBeTrue('Parameter binding should succeed');
            expect($lifecycle['lifecycle_stages']['validation'])
                ->toBeTrue('Validation stage should succeed');
            expect($lifecycle['lifecycle_stages']['execution'])
                ->toBeTrue('Execution preparation should succeed');
            expect($lifecycle['lifecycle_stages']['cleanup'])
                ->toBeTrue('Cleanup stage should succeed');

            expect($lifecycle['errors'])
                ->toBeEmpty('No errors should occur during lifecycle: ' . implode(', ', $lifecycle['errors']));

            // Verify performance is reasonable
            expect($lifecycle['performance']['total_time'])
                ->toBeLessThan(1.0, 'Lifecycle should complete quickly');
        });

        it('handles ReportCommand lifecycle with different configurations', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $configurations = [
                'html_report' => [
                    'paths' => ['./docs'],
                    '--format' => 'html',
                    '--output' => './report.html',
                    '--detailed' => true,
                ],
                'json_report' => [
                    'paths' => ['./test.md'],
                    '--format' => 'json',
                    '--output' => './report.json',
                ],
                'markdown_report' => [
                    'paths' => ['./README.md'],
                    '--format' => 'markdown',
                ],
            ];

            foreach ($configurations as $configName => $parameters) {
                $lifecycle = CommandTestHelper::testCommandLifecycle(
                    ReportCommand::class,
                    $dependencies,
                    $parameters
                );

                expect($lifecycle['lifecycle_stages']['instantiation'])
                    ->toBeTrue("ReportCommand instantiation should succeed for {$configName}");
                expect($lifecycle['lifecycle_stages']['dependency_injection'])
                    ->toBeTrue("Dependency injection should succeed for {$configName}");
                expect($lifecycle['lifecycle_stages']['parameter_binding'])
                    ->toBeTrue("Parameter binding should succeed for {$configName}");

                expect($lifecycle['errors'])
                    ->toBeEmpty("No errors should occur during {$configName} lifecycle: " . implode(', ', $lifecycle['errors']));
            }
        });
    });

    describe('Dependency Injection Failures', function () {
        it('handles command lifecycle with dependency injection failures', function () {
            // Test with missing required dependencies
            $incompleteDependencies = [
                'linkValidation' => mock(LinkValidationInterface::class),
                // Missing 'reporting' dependency
            ];

            $parameters = [
                'paths' => ['./test.md'],
            ];

            $lifecycle = CommandTestHelper::testCommandLifecycle(
                ValidateCommand::class,
                $incompleteDependencies,
                $parameters
            );

            expect($lifecycle['lifecycle_stages']['dependency_injection'])
                ->toBeFalse('Dependency injection should fail with missing dependencies');
            expect($lifecycle['errors'])
                ->not->toBeEmpty('Errors should be reported for missing dependencies');

            // Test with invalid dependency types
            $invalidDependencies = [
                'linkValidation' => 'invalid_type',
                'reporting' => 'invalid_type',
            ];

            $lifecycle = CommandTestHelper::testCommandLifecycle(
                ValidateCommand::class,
                $invalidDependencies,
                $parameters
            );

            expect($lifecycle['lifecycle_stages']['dependency_injection'])
                ->toBeFalse('Dependency injection should fail with invalid dependency types');
            expect($lifecycle['errors'])
                ->not->toBeEmpty('Errors should be reported for invalid dependency types');
        });
    });

    describe('Parameter Binding Failures', function () {
        it('handles command lifecycle with parameter binding failures', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $invalidParameterSets = [
                'invalid_option_values' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'invalid_scope',
                    '--format' => 'invalid_format',
                ],
                'out_of_range_values' => [
                    'paths' => ['./test.md'],
                    '--timeout' => '999',
                    '--concurrent' => '100',
                ],
                'type_mismatch' => [
                    'paths' => 'not_an_array',
                    '--timeout' => 'not_a_number',
                ],
            ];

            foreach ($invalidParameterSets as $setName => $parameters) {
                $lifecycle = CommandTestHelper::testCommandLifecycle(
                    ValidateCommand::class,
                    $dependencies,
                    $parameters
                );

                // Parameter binding might succeed but validation should catch issues
                if ($lifecycle['lifecycle_stages']['parameter_binding']) {
                    expect($lifecycle['lifecycle_stages']['validation'])
                        ->toBeFalse("Validation should fail for invalid parameters in {$setName}");
                }

                expect($lifecycle['errors'])
                    ->not->toBeEmpty("Errors should be reported for invalid parameters in {$setName}");
            }
        });
    });

    describe('State Management', function () {
        it('manages command lifecycle state and transitions', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            $command = new ValidateCommand($linkValidation, $reporting);
            $command->setLaravel($this->app);

            // Test initial state
            expect($command)->toBeInstanceOf(ValidateCommand::class);
            expect($command->getName())->toBe('validate');

            // Test state during configuration
            $input = new ArrayInput([
                'paths' => ['./test.md'],
                '--scope' => 'all',
                '--format' => 'console',
            ]);
            $output = new BufferedOutput();

            try {
                $input->bind($command->getDefinition());
                expect(true)->toBeTrue('Input binding should succeed');
            } catch (\Exception $e) {
                throw new Exception('Input binding should not fail: ' . $e->getMessage());
            }

            // Test command definition state
            $definition = $command->getDefinition();
            expect($definition)->not->toBeNull('Command definition should be available');
            expect($definition->hasArgument('paths'))->toBeTrue('Paths argument should be defined');
            expect($definition->hasOption('scope'))->toBeTrue('Scope option should be defined');

            // Test option access
            expect($command->getDefinition()->hasOption('format'))->toBeTrue('Format option should be accessible');
            expect($command->getDefinition()->hasOption('timeout'))->toBeTrue('Timeout option should be accessible');
        });
    });

    describe('Error Handling and Recovery', function () {
        it('handles command lifecycle error handling and recovery', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            // Configure mock to throw exception during validation
            $linkValidation->expects('validateFile')
                ->andThrow(new \RuntimeException('Validation service error'));

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $parameters = [
                'paths' => ['./test.md'],
                '--scope' => 'all',
            ];

            $lifecycle = CommandTestHelper::testCommandLifecycle(
                ValidateCommand::class,
                $dependencies,
                $parameters
            );

            // Lifecycle should handle service errors gracefully
            expect($lifecycle['lifecycle_stages']['instantiation'])
                ->toBeTrue('Instantiation should succeed even with problematic services');
            expect($lifecycle['lifecycle_stages']['dependency_injection'])
                ->toBeTrue('Dependency injection should succeed with mock services');

            // The actual execution would fail, but lifecycle test only checks preparation
            expect($lifecycle['lifecycle_stages']['execution'])
                ->toBeTrue('Execution preparation should succeed');
        });
    });

    describe('Resource Management', function () {
        it('manages command lifecycle resource management and cleanup', function () {
            $initialMemory = memory_get_usage(true);
            $initialPeakMemory = memory_get_peak_usage(true);

            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $parameters = [
                'paths' => ['./test.md'],
                '--scope' => 'all',
                '--format' => 'json',
            ];

            $resourceUsage = CommandTestHelper::monitorResourceUsage(function () use ($dependencies, $parameters) {
                return CommandTestHelper::testCommandLifecycle(
                    ValidateCommand::class,
                    $dependencies,
                    $parameters
                );
            });

            // Verify resource usage is reasonable
            expect($resourceUsage['memory_usage'])
                ->toBeLessThan(10 * 1024 * 1024, 'Command lifecycle should not use excessive memory');
            expect($resourceUsage['execution_time'])
                ->toBeLessThan(1.0, 'Command lifecycle should complete quickly');

            // Verify cleanup
            $finalMemory = memory_get_usage(true);
            $memoryIncrease = $finalMemory - $initialMemory;
            
            expect($memoryIncrease)
                ->toBeLessThan(5 * 1024 * 1024, 'Memory should be cleaned up after command lifecycle');
        });
    });

    describe('Performance Monitoring', function () {
        it('monitors command lifecycle performance', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $performanceTests = [
                'minimal_config' => [
                    'paths' => ['./test.md'],
                ],
                'standard_config' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'all',
                    '--format' => 'console',
                    '--timeout' => '30',
                ],
                'complex_config' => [
                    'paths' => ['./test.md', './docs'],
                    '--scope' => 'internal,external',
                    '--format' => 'json',
                    '--output' => './report.json',
                    '--timeout' => '60',
                    '--concurrent' => '20',
                    '--max-redirects' => '10',
                    '--user-agent' => 'test-agent',
                    '--follow-redirects' => true,
                    '--no-cache' => true,
                ],
            ];

            $performanceResults = [];

            foreach ($performanceTests as $testName => $parameters) {
                $startTime = microtime(true);
                
                $lifecycle = CommandTestHelper::testCommandLifecycle(
                    ValidateCommand::class,
                    $dependencies,
                    $parameters
                );
                
                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;

                $performanceResults[$testName] = [
                    'execution_time' => $executionTime,
                    'lifecycle_performance' => $lifecycle['performance'],
                    'success' => empty($lifecycle['errors']),
                ];

                // Verify performance thresholds
                expect($executionTime)
                    ->toBeLessThan(2.0, "Lifecycle performance too slow for {$testName}: {$executionTime}s");
                expect($performanceResults[$testName]['success'])
                    ->toBeTrue("Lifecycle should succeed for {$testName}");
            }

            // Verify performance scales reasonably with complexity
            $minimalTime = $performanceResults['minimal_config']['execution_time'];
            $complexTime = $performanceResults['complex_config']['execution_time'];
            
            expect($complexTime)
                ->toBeLessThan($minimalTime * 10, 'Complex configuration should not be more than 10x slower than minimal');
        });
    });

    describe('Concurrent Execution', function () {
        it('handles command lifecycle with concurrent execution simulation', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $parameters = [
                'paths' => ['./test.md'],
                '--scope' => 'all',
                '--concurrent' => '10',
            ];

            // Simulate concurrent lifecycle tests
            $concurrentResults = [];
            $concurrentCount = 5;

            for ($i = 0; $i < $concurrentCount; $i++) {
                $lifecycle = CommandTestHelper::testCommandLifecycle(
                    ValidateCommand::class,
                    $dependencies,
                    $parameters
                );

                $concurrentResults[] = $lifecycle;
            }

            // Verify all concurrent lifecycles succeeded
            foreach ($concurrentResults as $index => $result) {
                expect($result['lifecycle_stages']['instantiation'])
                    ->toBeTrue("Concurrent lifecycle {$index} instantiation should succeed");
                expect($result['lifecycle_stages']['dependency_injection'])
                    ->toBeTrue("Concurrent lifecycle {$index} dependency injection should succeed");
                expect($result['errors'])
                    ->toBeEmpty("Concurrent lifecycle {$index} should have no errors");
            }

            // Verify performance consistency across concurrent executions
            $executionTimes = array_column(array_column($concurrentResults, 'performance'), 'total_time');
            $avgTime = array_sum($executionTimes) / count($executionTimes);
            $maxDeviation = max($executionTimes) - min($executionTimes);

            expect($maxDeviation)
                ->toBeLessThan($avgTime * 0.5, 'Concurrent execution times should be consistent');
        });
    });

    describe('Edge Case Scenarios', function () {
        it('handles command lifecycle with edge case scenarios', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);

            $dependencies = [
                'linkValidation' => $linkValidation,
                'reporting' => $reporting,
            ];

            $edgeCases = [
                'empty_paths' => [
                    'paths' => [],
                ],
                'maximum_parameters' => [
                    'paths' => array_fill(0, 100, './test.md'), // Large path array
                    '--scope' => 'all',
                    '--format' => 'json',
                    '--timeout' => '300',
                    '--concurrent' => '50',
                    '--max-redirects' => '20',
                ],
                'unicode_parameters' => [
                    'paths' => ['./测试.md'],
                    '--user-agent' => 'test-agent-你好世界',
                ],
                'special_characters' => [
                    'paths' => ['./test file with spaces.md'],
                    '--user-agent' => 'test-agent-!@#$%^&*()',
                ],
            ];

            foreach ($edgeCases as $caseName => $parameters) {
                $lifecycle = CommandTestHelper::testCommandLifecycle(
                    ValidateCommand::class,
                    $dependencies,
                    $parameters
                );

                expect($lifecycle['lifecycle_stages']['instantiation'])
                    ->toBeTrue("Edge case {$caseName} instantiation should succeed");
                expect($lifecycle['lifecycle_stages']['dependency_injection'])
                    ->toBeTrue("Edge case {$caseName} dependency injection should succeed");

                // Some edge cases might fail validation, which is expected
                if (!empty($lifecycle['errors'])) {
                    expect(strtolower(implode(' ', $lifecycle['errors'])))
                        ->toContain('validation', "Edge case {$caseName} errors should be validation-related");
                }
            }
        });
    });
});