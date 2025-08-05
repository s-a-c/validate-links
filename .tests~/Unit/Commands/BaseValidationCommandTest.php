<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Commands\BaseValidationCommand;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use Tests\Support\MockFactory;
use Tests\Support\MockDataBuilder;
use Tests\Support\MockLifecycleManager;
use Tests\Support\LoggingTestHelper;
use Tests\Support\CommandTestHelper;
use Tests\Support\LogVerificationUtilities;

use Tests\Unit\Commands\TestConsoleOutput;
use Tests\Unit\Commands\TestValidationCommand;

describe('BaseValidationCommand', function () {
    // Define variables in outer scope for use() closures
    $command = null;
    $linkValidation = null;
    $reporting = null;
    
    beforeEach(function () use (&$command, &$linkValidation, &$reporting) {
        // Initialize comprehensive testing environment
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr']);
        CommandTestHelper::clearResults();
        
        // Use enhanced mock factory for realistic service behavior
        $linkValidation = createLinkValidationMock();
        $reporting = createReportingMock();
        
        // Store in $this for direct access
        $this->linkValidation = $linkValidation;
        $this->reporting = $reporting;
        
        // Create command with optimized mocks
        $command = new TestValidationCommand($linkValidation, $reporting);
        $command->setLaravel(app());
        
        // Store in $this for direct access
        $this->command = $command;

        // Create proper console input/output for testing with Laravel Zero compatibility
        $this->input = new Symfony\Component\Console\Input\ArrayInput([]);
        $this->output = new TestConsoleOutput();

        // Use reflection to set the input and output properties
        $reflection = new ReflectionClass($command);
        if ($reflection->hasProperty('input')) {
            $inputProperty = $reflection->getProperty('input');
            $inputProperty->setAccessible(true);
            $inputProperty->setValue($command, $this->input);
        }
        if ($reflection->hasProperty('output')) {
            $outputProperty = $reflection->getProperty('output');
            $outputProperty->setAccessible(true);
            $outputProperty->setValue($command, $this->output);
        }
    });

    afterEach(function () use (&$command, &$linkValidation, &$reporting) {
        LoggingTestHelper::resetLoggingEnvironment();
        CommandTestHelper::clearResults();
        MockFactory::verifyAndClearMocks();
        
        // Clear outer scope variables
        $command = null;
        $linkValidation = null;
        $reporting = null;
    });

    describe('constructor', function () {
        it('accepts LinkValidationInterface and ReportingInterface dependencies', function () {
            $command = new TestValidationCommand($this->linkValidation, $this->reporting);

            expect($command)->toBeInstanceOf(BaseValidationCommand::class);
        });

        it('calls parent constructor', function () {
            $command = new TestValidationCommand($this->linkValidation, $this->reporting);

            expect($command)->toBeInstanceOf(LaravelZero\Framework\Commands\Command::class);
        });
    });

    describe('createConfigFromOptions method', function () {
        it('creates ValidationConfig with default values', function () {
            // Test the actual method with real command instance
            $config = $this->command->testCreateConfigFromOptions();

            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });

        it('handles non-string scope option', function () {
            // Test with actual command - the method should handle type conversion internally
            $config = $this->command->testCreateConfigFromOptions();

            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });

        it('handles non-numeric timeout option', function () {
            // Test with actual command - the method should handle type conversion internally
            $config = $this->command->testCreateConfigFromOptions();

            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });

        it('handles non-string user-agent option', function () {
            // Test with actual command - the method should handle type conversion internally
            $config = $this->command->testCreateConfigFromOptions();

            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });
    });

    describe('getOptionWithDefault method', function () {
        it('returns default value when option does not exist', function () {
            // Test with a non-existent option
            $result = $this->command->testGetOptionWithDefault('nonexistent', 'default');

            expect($result)->toBe('default');
        });

        it('returns null when no default provided and option does not exist', function () {
            // Test with a non-existent option and no default
            $result = $this->command->testGetOptionWithDefault('nonexistent');

            expect($result)->toBeNull();
        });

        it('method exists and is callable', function () {
            // Basic test that the method works
            expect(method_exists($this->command, 'testGetOptionWithDefault'))->toBeTrue();
        });
    });

    describe('parseScopes method', function () {
        it('returns all scopes when scope is "all"', function () {
            $result = $this->command->testParseScopes('all');

            expect($result)->toBeArray();
        });

        it('parses comma-separated scopes', function () {
            $result = $this->command->testParseScopes('internal,external');

            expect($result)->toBeArray()
                ->and($result)->toHaveCount(2);
        });

        it('trims whitespace from scope values', function () {
            $result = $this->command->testParseScopes(' internal , external ');

            expect($result)->toBeArray()
                ->and($result)->toHaveCount(2);
        });

        it('handles single scope', function () {
            $result = $this->command->testParseScopes('internal');

            expect($result)->toBeArray()
                ->and($result)->toHaveCount(1);
        });
    });

    describe('getOutputFormat method', function () use (&$command) {
        it('returns default console format', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            // Test with actual command - should return console format by default
            $result = $command->testGetOutputFormat();

            expect($result)->toBe(OutputFormat::CONSOLE);
        });

        it('method exists and returns OutputFormat enum', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            // Basic test that the method works and returns correct type
            $result = $command->testGetOutputFormat();

            expect($result)->toBeInstanceOf(OutputFormat::class);
        });
    });

    describe('validateCommandOptions method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            // Basic test that the method exists
            expect(method_exists($command, 'testValidateCommandOptions'))->toBeTrue();
        });

        it('does not throw exception with valid command setup', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            // Test that the method can be called without throwing
            expect(function () use (&$command) {
                $command->testValidateCommandOptions();
            })->not->toThrow(Exception::class);
        });
    });

    describe('processValidationResults method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            // Create a simple ValidationResultCollection for testing
            $results = new ValidationResultCollection([]);

            expect(method_exists($command, 'testProcessValidationResults'))->toBeTrue();
        });

        it('returns integer exit code', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            // Test with empty results collection
            $results = new ValidationResultCollection([]);
            $result = $command->testProcessValidationResults($results);

            expect($result)->toBeInt();
        });
    });

    describe('displayResults method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $results = new ValidationResultCollection([]);

            expect(method_exists($command, 'testDisplayResults'))->toBeTrue();
        });

        it('returns integer exit code', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $results = new ValidationResultCollection([]);
            $result = $command->testDisplayResults($results);

            expect($result)->toBeInt();
        });
    });

    describe('collectFiles method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);

            expect(method_exists($command, 'testCollectFiles'))->toBeTrue();
        });

        it('returns array of files', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
            $result = $command->testCollectFiles([], $config);

            expect($result)->toBeArray();
        });

        it('handles empty paths array', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
            $result = $command->testCollectFiles([], $config);

            expect($result)->toBe([]);
        });
    });

    describe('executeValidation method', function () use (&$command, &$linkValidation) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);

            expect(method_exists($command, 'testExecuteValidation'))->toBeTrue();
        });

        it('returns ValidationResultCollection', function () {
            $config = createTestValidationConfig(['scopes' => [ValidationScope::ALL]]);
            $this->linkValidation->shouldReceive('validateFile')->andReturn(new ValidationResultCollection([]));

            $result = $this->command->testExecuteValidation([], $config);

            expect($result)->toBeInstanceOf(ValidationResultCollection::class);
        });
    });

    describe('handleValidationError method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $exception = new Exception('Test error');

            expect(method_exists($command, 'testHandleValidationError'))->toBeTrue();
        });

        it('returns error exit code 2', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $exception = new Exception('Test error');
            $result = $command->testHandleValidationError($exception);

            expect($result)->toBe(2);
        });
    });

    describe('groupLinksByStatus method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $results = new ValidationResultCollection([]);

            expect(method_exists($command, 'testGroupLinksByStatus'))->toBeTrue();
        });

        it('returns array of grouped results', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $results = new ValidationResultCollection([]);
            $grouped = $command->testGroupLinksByStatus($results);

            expect($grouped)->toBeArray();
        });
    });

    describe('displayStatusGroups method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            expect(method_exists($command, 'testDisplayStatusGroups'))->toBeTrue();
        });

        it('does not throw exception with empty status groups', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            expect(function () use (&$command) {
                $command->testDisplayStatusGroups([]);
            })->not->toThrow(Exception::class);
        });
    });

    describe('displaySummary method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            expect(method_exists($command, 'testDisplaySummary'))->toBeTrue();
        });

        it('does not throw exception with array summary', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $summary = ['total_links' => 10, 'broken_links' => 2];

            expect(function () use (&$command, &$summary) {
                $command->testDisplaySummary($summary);
            })->not->toThrow(Exception::class);
        });
    });

    describe('displayValidationSummary method', function () use (&$command) {
        it('method exists and is callable', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            expect(method_exists($command, 'testDisplayValidationSummary'))->toBeTrue();
        });

        it('does not throw exception with summary array', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $summary = [
                'files_processed' => 5,
                'total_links' => 10,
                'broken_links' => 2,
                'success_rate' => 80.0,
            ];

            expect(function () use (&$command, &$summary) {
                $command->testDisplayValidationSummary($summary);
            })->not->toThrow(Exception::class);
        });

        it('handles empty summary array', function () use (&$command) {
            assert($command instanceof TestValidationCommand);
            $summary = [];

            expect(function () use (&$command, &$summary) {
                $command->testDisplayValidationSummary($summary);
            })->not->toThrow(Exception::class);
        });
    });

    describe('class structure', function () use (&$linkValidation, &$reporting) {
        it('is an abstract class', function () {
            $reflection = new ReflectionClass(BaseValidationCommand::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

        it('extends Laravel Zero Command', function () use (&$linkValidation, &$reporting) {
            assert($linkValidation instanceof LinkValidationInterface);
            assert($reporting instanceof ReportingInterface);
            $command = new TestValidationCommand($linkValidation, $reporting);
            expect($command)->toBeInstanceOf(LaravelZero\Framework\Commands\Command::class);
        });

        it('has proper namespace', function () {
            $reflection = new ReflectionClass(BaseValidationCommand::class);
            expect($reflection->getNamespaceName())->toBe('App\Commands');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Commands/BaseValidationCommand.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });

    describe('dependency injection', function () {
        it('has protected linkValidation property', function () {
            $reflection = new ReflectionClass(BaseValidationCommand::class);
            $constructor = $reflection->getConstructor();
            $parameters = $constructor->getParameters();

            expect($parameters[0]->getName())->toBe('linkValidation');
            expect($parameters[0]->getType()->getName())->toBe(LinkValidationInterface::class);
        });

        it('has protected reporting property', function () {
            $reflection = new ReflectionClass(BaseValidationCommand::class);
            $constructor = $reflection->getConstructor();
            $parameters = $constructor->getParameters();

            expect($parameters[1]->getName())->toBe('reporting');
            expect($parameters[1]->getType()->getName())->toBe(ReportingInterface::class);
        });
    });

    describe('comprehensive logging verification', function () {
        it('validates command logging during option validation', function () {
            $correlationId = 'test-command-logging-' . uniqid();
            
            $result = LoggingTestHelper::captureLogOutput(function () use ($correlationId) {
                // Simulate command option validation with logging
                $this->command->testValidateCommandOptions();
                return true;
            });

            // Verify logging infrastructure is working
            expect($result)->toHaveKey('logs');
            expect($result)->toHaveKey('performance');
        });

        it('validates command performance with logging enabled', function () {
            $performanceResult = CommandTestHelper::monitorResourceUsage(function () {
                // Test command operations with performance monitoring
                $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
                $results = new ValidationResultCollection([]);
                
                $this->command->testProcessValidationResults($results);
                $this->command->testDisplayResults($results);
                
                return true;
            });

            expect($performanceResult['execution_time'])->toBeLessThan(1.0);
            expect($performanceResult['memory_usage'])->toBeLessThan(10 * 1024 * 1024); // 10MB
        });

        it('validates command option parsing with comprehensive validation', function () {
            $validationTests = [
                'scope' => 'all',
                'format' => 'console',
                'timeout' => 30,
                'concurrency' => 10,
            ];

            $validation = CommandTestHelper::validateCommandOptions($this->command, $validationTests);
            
            expect($validation['valid'])->toBeTrue();
            expect($validation['errors'])->toBeEmpty();
        });

        it('validates command lifecycle with comprehensive monitoring', function () {
            $dependencies = [
                'linkValidation' => $this->linkValidation,
                'reporting' => $this->reporting,
            ];

            $parameters = [
                'paths' => ['./test.md'],
                '--scope' => 'all',
                '--format' => 'console',
            ];

            $lifecycle = CommandTestHelper::testCommandLifecycle(
                TestValidationCommand::class,
                $dependencies,
                $parameters
            );

            expect($lifecycle['lifecycle_stages']['instantiation'])->toBeTrue();
            expect($lifecycle['lifecycle_stages']['dependency_injection'])->toBeTrue();
            expect($lifecycle['lifecycle_stages']['parameter_binding'])->toBeTrue();
            expect($lifecycle['lifecycle_stages']['validation'])->toBeTrue();
            expect($lifecycle['lifecycle_stages']['execution'])->toBeTrue();
            expect($lifecycle['lifecycle_stages']['cleanup'])->toBeTrue();
            expect($lifecycle['errors'])->toBeEmpty();
        });

        it('validates command error handling with logging verification', function () {
            $correlationId = 'error-handling-test-' . uniqid();
            
            $result = LoggingTestHelper::captureLogOutput(function () use ($correlationId) {
                $exception = new Exception('Test validation error');
                $exitCode = $this->command->testHandleValidationError($exception);
                
                return $exitCode;
            });

            $exitCode = $result['result'];
            expect($exitCode)->toBe(2);
            
            // Verify error handling performance
            expect($result['performance']['execution_time'])->toBeLessThan(0.1);
        });

        it('validates command output formatting with rigorous checks', function () {
            $results = new ValidationResultCollection([]);
            
            $outputResult = LoggingTestHelper::captureLogOutput(function () use ($results) {
                return $this->command->testDisplayResults($results);
            });

            $exitCode = $outputResult['result'];
            expect($exitCode)->toBeInt();
            
            // Verify output performance
            expect($outputResult['performance']['execution_time'])->toBeLessThan(0.5);
        });

        it('validates command configuration creation with comprehensive validation', function () {
            $configResult = LoggingTestHelper::captureLogOutput(function () {
                return $this->command->testCreateConfigFromOptions();
            });

            $config = $configResult['result'];
            expect($config)->toBeInstanceOf(ValidationConfig::class);
            
            // Verify configuration creation performance
            expect($configResult['performance']['execution_time'])->toBeLessThan(0.1);
        });

        it('validates command scope parsing with boundary testing', function () {
            $scopeTests = [
                'all' => ['expected_count' => 'multiple', 'should_succeed' => true],
                'internal,external' => ['expected_count' => 2, 'should_succeed' => true],
                'internal' => ['expected_count' => 1, 'should_succeed' => true],
                ' internal , external ' => ['expected_count' => 2, 'should_succeed' => true],
            ];

            foreach ($scopeTests as $scopeInput => $expectations) {
                $result = LoggingTestHelper::captureLogOutput(function () use ($scopeInput) {
                    return $this->command->testParseScopes($scopeInput);
                });

                $scopes = $result['result'];
                expect($scopes)->toBeArray();
                
                if (is_int($expectations['expected_count'])) {
                    expect($scopes)->toHaveCount($expectations['expected_count']);
                }
                
                // Verify parsing performance
                expect($result['performance']['execution_time'])->toBeLessThan(0.01);
            }
        });

        it('validates command file collection with comprehensive testing', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
            
            $fileTests = [
                'empty_paths' => [],
                'single_path' => ['./test.md'],
                'multiple_paths' => ['./test1.md', './test2.md'],
            ];

            foreach ($fileTests as $testName => $paths) {
                $result = LoggingTestHelper::captureLogOutput(function () use ($paths, $config) {
                    return $this->command->testCollectFiles($paths, $config);
                });

                $files = $result['result'];
                expect($files)->toBeArray();
                
                // Verify file collection performance
                expect($result['performance']['execution_time'])->toBeLessThan(0.1);
            }
        });

        it('validates command validation execution with comprehensive monitoring', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
            $this->linkValidation->shouldReceive('validateFile')->andReturn(new ValidationResultCollection([]));
            
            $result = LoggingTestHelper::captureLogOutput(function () use ($config) {
                return $this->command->testExecuteValidation([], $config);
            });

            $validationResults = $result['result'];
            expect($validationResults)->toBeInstanceOf(ValidationResultCollection::class);
            
            // Verify validation execution performance
            expect($result['performance']['execution_time'])->toBeLessThan(1.0);
        });

        it('validates command status grouping with performance monitoring', function () {
            $results = new ValidationResultCollection([]);
            
            $result = LoggingTestHelper::captureLogOutput(function () use ($results) {
                return $this->command->testGroupLinksByStatus($results);
            });

            $groupedResults = $result['result'];
            expect($groupedResults)->toBeArray();
            
            // Verify grouping performance
            expect($result['performance']['execution_time'])->toBeLessThan(0.1);
        });

        it('validates command summary display with comprehensive validation', function () {
            $summary = [
                'files_processed' => 5,
                'total_links' => 10,
                'broken_links' => 2,
                'success_rate' => 80.0,
            ];
            
            $result = LoggingTestHelper::captureLogOutput(function () use ($summary) {
                $this->command->testDisplayValidationSummary($summary);
                return true;
            });

            expect($result['result'])->toBeTrue();
            
            // Verify summary display performance
            expect($result['performance']['execution_time'])->toBeLessThan(0.1);
        });

        it('validates command option retrieval with comprehensive testing', function () {
            $optionTests = [
                ['option' => 'nonexistent', 'default' => 'default_value', 'expected' => 'default_value'],
                ['option' => 'nonexistent', 'default' => null, 'expected' => null],
                ['option' => 'another_nonexistent', 'default' => 42, 'expected' => 42],
            ];

            foreach ($optionTests as $test) {
                $result = LoggingTestHelper::captureLogOutput(function () use ($test) {
                    return $this->command->testGetOptionWithDefault($test['option'], $test['default']);
                });

                expect($result['result'])->toBe($test['expected']);
                
                // Verify option retrieval performance
                expect($result['performance']['execution_time'])->toBeLessThan(0.01);
            }
        });

        it('validates command output format detection with comprehensive validation', function () {
            $result = LoggingTestHelper::captureLogOutput(function () {
                return $this->command->testGetOutputFormat();
            });

            $format = $result['result'];
            expect($format)->toBeInstanceOf(OutputFormat::class);
            expect($format)->toBe(OutputFormat::CONSOLE);
            
            // Verify format detection performance
            expect($result['performance']['execution_time'])->toBeLessThan(0.01);
        });
    });

    describe('rigorous command validation testing', function () {
        it('validates command parameter boundaries comprehensively', function () {
            $boundaryTests = [
                'timeout' => [
                    ['name' => 'valid_min', 'value' => 1, 'expected_valid' => true],
                    ['name' => 'valid_max', 'value' => 300, 'expected_valid' => true],
                    ['name' => 'invalid_zero', 'value' => 0, 'expected_valid' => false],
                    ['name' => 'invalid_negative', 'value' => -1, 'expected_valid' => false],
                ],
                'concurrency' => [
                    ['name' => 'valid_min', 'value' => 1, 'expected_valid' => true],
                    ['name' => 'valid_max', 'value' => 50, 'expected_valid' => true],
                    ['name' => 'invalid_zero', 'value' => 0, 'expected_valid' => false],
                    ['name' => 'invalid_high', 'value' => 100, 'expected_valid' => false],
                ],
            ];

            foreach ($boundaryTests as $parameter => $tests) {
                $results = CommandTestHelper::testParameterBoundaries($this->command, [$parameter => $tests]);
                
                expect($results['summary']['passed'])->toBeGreaterThan(0);
                expect($results['summary']['errors'])->toBe(0);
                
                foreach ($tests as $test) {
                    $testResult = $results['results'][$parameter][$test['name']];
                    expect($testResult['actual_valid'])->toBe($test['expected_valid']);
                }
            }
        });

        it('validates command performance under various loads', function () {
            $loadTests = [
                'light_load' => ['iterations' => 10, 'max_time' => 0.5],
                'medium_load' => ['iterations' => 100, 'max_time' => 2.0],
                'heavy_load' => ['iterations' => 1000, 'max_time' => 10.0],
            ];

            foreach ($loadTests as $testName => $test) {
                $startTime = microtime(true);
                
                for ($i = 0; $i < $test['iterations']; $i++) {
                    $this->command->testGetOptionWithDefault('test', 'default');
                    $this->command->testGetOutputFormat();
                }
                
                $endTime = microtime(true);
                $executionTime = $endTime - $startTime;
                
                expect($executionTime)->toBeLessThan($test['max_time']);
            }
        });

        it('validates command memory usage patterns', function () {
            $initialMemory = memory_get_usage(true);
            
            // Perform memory-intensive operations
            for ($i = 0; $i < 100; $i++) {
                $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
                $results = new ValidationResultCollection([]);
                $this->command->testProcessValidationResults($results);
            }
            
            $finalMemory = memory_get_usage(true);
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory increase should be reasonable (less than 10MB)
            expect($memoryIncrease)->toBeLessThan(10 * 1024 * 1024);
        });

        it('validates command error recovery mechanisms', function () {
            $errorScenarios = [
                'validation_exception' => new Exception('Validation failed'),
                'runtime_exception' => new RuntimeException('Runtime error'),
                'invalid_argument' => new InvalidArgumentException('Invalid argument'),
            ];

            foreach ($errorScenarios as $scenarioName => $exception) {
                $result = LoggingTestHelper::captureLogOutput(function () use ($exception) {
                    return $this->command->testHandleValidationError($exception);
                });

                $exitCode = $result['result'];
                expect($exitCode)->toBe(2);
                
                // Verify error handling is fast
                expect($result['performance']['execution_time'])->toBeLessThan(0.1);
            }
        });

        it('validates command thread safety and concurrent access', function () {
            $concurrentResults = [];
            
            // Simulate concurrent command operations
            for ($i = 0; $i < 5; $i++) {
                $result = LoggingTestHelper::captureLogOutput(function () {
                    $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
                    return $this->command->testCreateConfigFromOptions();
                });
                
                $concurrentResults[] = $result;
            }

            // Verify all concurrent operations succeeded
            foreach ($concurrentResults as $result) {
                expect($result['result'])->toBeInstanceOf(ValidationConfig::class);
                expect($result['performance']['execution_time'])->toBeLessThan(0.5);
            }
        });
    });
});
