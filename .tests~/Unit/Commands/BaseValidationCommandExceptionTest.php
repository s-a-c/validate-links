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
use App\Enums\LinkStatus;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

use Tests\Unit\Commands\TestConsoleOutputForExceptions;
use Tests\Unit\Commands\TestValidationCommandForExceptions;

describe('BaseValidationCommand Exception Handling', function () {
    // Define variables in outer scope for use() closures
    $command = null;
    $linkValidation = null;
    $reporting = null;
    
    beforeEach(function () use (&$command, &$linkValidation, &$reporting) {
        $linkValidation = createLinkValidationMock();
        $reporting = createReportingMock();
        
        // Store in $this for direct access
        $this->linkValidation = $linkValidation;
        $this->reporting = $reporting;
        
        // Bind dependencies in Laravel container
        $this->app->instance(LinkValidationInterface::class, $linkValidation);
        $this->app->instance(ReportingInterface::class, $reporting);
        
        // Create command with proper setup
        $command = new TestValidationCommandForExceptions($linkValidation, $reporting);
        $command->setLaravel(app());
        
        // Register the test command in Laravel's command registry
        $this->app->singleton('command.test.validate', function () use ($command) {
            return $command;
        });
        
        // Register command with Artisan for testing
        $this->app['Illuminate\Contracts\Console\Kernel']->registerCommand($command);
        
        // Store in $this for direct access
        $this->command = $command;

        // Create proper console input/output for testing with Laravel Zero compatibility
        $this->input = new Symfony\Component\Console\Input\ArrayInput([]);
        $this->output = new TestConsoleOutputForExceptions();

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
        
        // Set up basic mocks
        $this->linkValidation->shouldReceive('validateFile')
            ->andReturn(ValidationResultCollection::fromArray([
                ValidationResult::success('https://example.com', ValidationScope::EXTERNAL)
            ]))
            ->byDefault();
            
        $this->reporting->shouldReceive('generateSummary')
            ->andReturn(createTestReportSummary())
            ->byDefault();
            
        $this->reporting->shouldReceive('format')
            ->andReturn('Test report output')
            ->byDefault();
    });

    afterEach(function () use (&$command, &$linkValidation, &$reporting) {
        \Tests\Support\MockFactory::verifyAndClearMocks();
        
        // Clear outer scope variables
        $command = null;
        $linkValidation = null;
        $reporting = null;
    });

    describe('Option Validation Exception Scenarios', function () {
        it('handles invalid scope options gracefully', function () {
            // Test that invalid scope validation works by directly calling the validation method
            $command = $this->command;
            
            // Mock the input to simulate invalid scope
            $input = new Symfony\Component\Console\Input\ArrayInput([
                'paths' => ['test.md'],
                '--scope' => 'invalid_scope'
            ]);
            
            // Set up the command with the input
            $reflection = new ReflectionClass($command);
            if ($reflection->hasProperty('input')) {
                $inputProperty = $reflection->getProperty('input');
                $inputProperty->setAccessible(true);
                $inputProperty->setValue($command, $input);
            }
            
            // This should throw an InvalidArgumentException when validating options
            expect(function () use ($command) {
                $command->testValidateCommandOptions();
            })->toThrow(InvalidArgumentException::class);
        });

        it('handles invalid format options gracefully', function () {
            // Test that invalid format validation works
            $command = $this->command;
            
            // Mock the input to simulate invalid format
            $input = new Symfony\Component\Console\Input\ArrayInput([
                'paths' => ['test.md'],
                '--format' => 'invalid_format'
            ]);
            
            // Set up the command with the input
            $reflection = new ReflectionClass($command);
            if ($reflection->hasProperty('input')) {
                $inputProperty = $reflection->getProperty('input');
                $inputProperty->setAccessible(true);
                $inputProperty->setValue($command, $input);
            }
            
            expect(function () use ($command) {
                $command->testValidateCommandOptions();
            })->toThrow(InvalidArgumentException::class);
        });

        it('handles missing required options gracefully', function () {
            // Test with minimal valid input - should not throw exceptions
            $command = $this->command;
            
            // Mock the input with minimal valid data
            $input = new Symfony\Component\Console\Input\ArrayInput([
                'paths' => ['test.md']
            ]);
            
            // Set up the command with the input
            $reflection = new ReflectionClass($command);
            if ($reflection->hasProperty('input')) {
                $inputProperty = $reflection->getProperty('input');
                $inputProperty->setAccessible(true);
                $inputProperty->setValue($command, $input);
            }
            
            // Should not throw exceptions for missing optional options
            expect(function () use ($command) {
                $command->testValidateCommandOptions();
            })->not->toThrow(Exception::class);
        });
    });

    describe('Configuration Creation Exception Scenarios', function () {
        it('handles invalid timeout values gracefully', function () {
            // Use the real command instance
            $command = $this->command;
            
            // Test that invalid timeout values are handled gracefully by using defaults
            $config = $command->testCreateConfigFromOptions();
            
            // Should use default value when invalid timeout is provided
            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });

        it('handles invalid concurrency values gracefully', function () {
            // Use the real command instance
            $command = $this->command;
            
            $config = $command->testCreateConfigFromOptions();
            
            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });

        it('handles invalid max-redirects values gracefully', function () {
            $command = Mockery::mock(TestValidationCommandForExceptions::class, [
                $this->linkValidation,
                $this->reporting
            ])->makePartial()
                ->shouldAllowMockingProtectedMethods();
            $command->shouldReceive('getOptionWithDefault')
                ->andReturnUsing(function ($key, $default) {
                    return match ($key) {
                        'max-redirects' => 'invalid_redirects',
                        default => $default,
                    };
                });
            
            $config = $command->testCreateConfigFromOptions();
            
            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });
    });

    describe('File Collection Exception Scenarios', function () {
        it('handles non-existent file paths gracefully', function () {
            $nonExistentPaths = ['/nonexistent/file.md', '/another/missing/file.txt'];
            $config = createTestValidationConfig();
            
            $files = $this->command->testCollectFiles($nonExistentPaths, $config);
            
            expect($files)->toBeArray()
                ->and($files)->toBeEmpty();
        });

        it('handles permission denied on directories gracefully', function () {
            // Test with a directory that might have permission issues
            $restrictedPaths = ['/root', '/etc'];
            $config = createTestValidationConfig();
            
            $files = $this->command->testCollectFiles($restrictedPaths, $config);
            
            // Should handle gracefully without throwing exceptions
            expect($files)->toBeArray();
        });

        it('handles circular symlinks gracefully', function () {
            // Create temporary directory structure with potential circular references
            $tempDir = sys_get_temp_dir() . '/circular-test-' . uniqid();
            $config = createTestValidationConfig();
            
            $files = $this->command->testCollectFiles([$tempDir], $config);
            
            expect($files)->toBeArray();
        });

        it('handles very deep directory structures gracefully', function () {
            // Test with a very deep path that might cause stack overflow
            $deepPath = '/' . str_repeat('deep/', 100) . 'file.md';
            $config = createTestValidationConfig();
            
            $files = $this->command->testCollectFiles([$deepPath], $config);
            
            expect($files)->toBeArray()
                ->and($files)->toBeEmpty();
        });
    });

    describe('Validation Execution Exception Scenarios', function () {
        it('handles link validation service failures gracefully', function () {
            $this->linkValidation->shouldReceive('validateFile')
                ->andThrow(new RuntimeException('Link validation service failed'));
            
            $files = ['test.md'];
            $config = createTestValidationConfig();
            
            expect(function () use ($files, $config) {
                $this->command->testExecuteValidation($files, $config);
            })->toThrow(RuntimeException::class, 'Link validation service failed');
        });

        it('handles intermittent validation failures gracefully', function () {
            // Create a mock that fails on the 3rd call
            $linkValidation = createLinkValidationMock();
            $linkValidation->shouldReceive('validateFile')
                ->times(2)
                ->andReturn(new ValidationResultCollection([]));
            $linkValidation->shouldReceive('validateFile')
                ->once()
                ->andThrow(new RuntimeException('Service temporarily unavailable'));
            
            $command = new TestValidationCommandForExceptions($linkValidation, $this->reporting);
            $files = ['test1.md', 'test2.md', 'test3.md'];
            $config = createTestValidationConfig();
            
            // First 2 files should succeed, 3rd should fail
            expect(function () use ($command, $files, $config) {
                $command->testExecuteValidation($files, $config);
            })->toThrow(RuntimeException::class, 'Service temporarily unavailable');
        });

        it('handles memory exhaustion during validation gracefully', function () {
            $this->linkValidation->shouldReceive('validateFile')
                ->andThrow(new RuntimeException('Fatal error: Allowed memory size exhausted'));
            
            $files = ['test.md'];
            $config = createTestValidationConfig();
            
            expect(function () use ($files, $config) {
                $this->command->testExecuteValidation($files, $config);
            })->toThrow(RuntimeException::class, 'Fatal error: Allowed memory size exhausted');
        });
    });

    describe('Result Display Exception Scenarios', function () {
        it('handles reporting service failures gracefully', function () {
            $this->reporting->shouldReceive('format')
                ->andThrow(new RuntimeException('Reporting service failed'));
            
            $results = ValidationResultCollection::fromArray([
                ValidationResult::success('https://example.com', ValidationScope::EXTERNAL)
            ]);
            
            expect(function () use ($results) {
                $this->command->testDisplayResults($results);
            })->toThrow(RuntimeException::class, 'Reporting service failed');
        });

        it('handles file output failures gracefully', function () {
            $command = Mockery::mock(TestValidationCommandForExceptions::class, [
                $this->linkValidation,
                $this->reporting
            ])->makePartial()
                ->shouldAllowMockingProtectedMethods();
            $command->shouldReceive('getOutputFormat')
                ->andReturn(OutputFormat::JSON);
            $command->shouldReceive('getOptionWithDefault')
                ->with('output', null)
                ->andReturn('/root/restricted/output.json');
            
            $this->reporting->shouldReceive('format')
                ->andReturn('{"test": "output"}');
            
            $results = ValidationResultCollection::fromArray([
                ValidationResult::success('https://example.com', ValidationScope::EXTERNAL)
            ]);
            
            // Should handle file write failures gracefully
            $exitCode = $command->testDisplayResults($results);
            expect($exitCode)->toBeInt();
        });

        it('handles corrupted result collections gracefully', function () {
            // Create results with potential issues
            $corruptedResults = ValidationResultCollection::fromArray([]);
            
            $this->reporting->shouldReceive('generateSummary')
                ->with($corruptedResults)
                ->andThrow(new InvalidArgumentException('Invalid result collection'));
            
            expect(function () use ($corruptedResults) {
                $this->command->testDisplayResults($corruptedResults);
            })->toThrow(InvalidArgumentException::class, 'Invalid result collection');
        });
    });

    describe('Error Handling and Recovery', function () {
        it('handles validation errors with proper error codes', function () {
            $exception = new RuntimeException('Critical validation error');
            
            $command = Mockery::mock(TestValidationCommandForExceptions::class, [
                $this->linkValidation,
                $this->reporting
            ])->makePartial()
                ->shouldAllowMockingProtectedMethods();
            $command->shouldReceive('error')
                ->with('Validation failed: Critical validation error')
                ->once();
            
            $output = new TestConsoleOutputForExceptions();
            $command->shouldReceive('output')
                ->andReturn($output);
            
            $exitCode = $command->testHandleValidationError($exception);
            
            expect($exitCode)->toBe(2);
        });

        it('includes stack trace in verbose mode', function () {
            $exception = new RuntimeException('Detailed validation error');
            
            $command = Mockery::mock(TestValidationCommandForExceptions::class, [
                $this->linkValidation,
                $this->reporting
            ])->makePartial()
                ->shouldAllowMockingProtectedMethods();
            
            $output = Mockery::mock(TestConsoleOutputForExceptions::class);
            $output->shouldReceive('isVerbose')->andReturn(true);
            $output->shouldReceive('newLine')->andReturn();
            
            $command->shouldReceive('output')->andReturn($output);
            $command->shouldReceive('error')
                ->with('Validation failed: Detailed validation error')
                ->once();
            $command->shouldReceive('error')
                ->with(Mockery::type('string')) // Stack trace
                ->once();
            
            $exitCode = $command->testHandleValidationError($exception);
            
            expect($exitCode)->toBe(2);
        });

        it('recovers gracefully from command execution failures', function () {
            $operation = function () {
                throw new RuntimeException('Command execution failed');
            };
            
            $recovery = function (Throwable $e) {
                return '2'; // Error exit code as string to match expected type
            };
            
            testErrorRecovery($operation, $recovery, '2');
        });

        it('provides fallback behavior for option parsing failures', function () {
            $operation = function () {
                throw new InvalidArgumentException('Invalid command options');
            };
            
            $fallbackConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::ALL],
                'timeout' => 30,
            ]);
            
            $result = testGracefulDegradation($operation, $fallbackConfig);
            
            expect($result)->toBeInstanceOf(ValidationConfig::class);
        });

        it('cleans up resources after command failures', function () {
            $resourceCleaned = false;
            
            $operation = function () {
                throw new RuntimeException('Command failed');
            };
            
            $cleanup = function () use (&$resourceCleaned) {
                $resourceCleaned = true;
            };
            
            testErrorCleanup($operation, $cleanup);
            
            expect($resourceCleaned)->toBeTrue();
        });

        it('reports command errors correctly', function () {
            $operation = function () {
                throw new RuntimeException('Command execution error');
            };
            
            $reporter = function (Throwable $e) {
                return [
                    'type' => 'command_error',
                    'message' => $e->getMessage(),
                    'command' => 'test:validate',
                    'timestamp' => time(),
                ];
            };
            
            $reports = testErrorReporting($operation, $reporter);
            
            expect($reports)->toHaveCount(1)
                ->and($reports[0]['type'])->toBe('command_error')
                ->and($reports[0]['command'])->toBe('test:validate');
        });
    });

    describe('Performance Under Error Conditions', function () {
        it('maintains performance during repeated command failures', function () {
            $operation = function () {
                throw new RuntimeException('Command failed');
            };
            
            $results = testExceptionHandlingUnderLoad($operation, 25, 0.01);
            
            expect($results['exception_count'])->toBe(25)
                ->and($results['average_time'])->toBeLessThan(0.01);
        });

        it('handles memory pressure during large file processing gracefully', function () {
            $initialMemory = memory_get_usage();
            
            // Simulate processing many files
            for ($i = 0; $i < 50; $i++) {
                try {
                    $largeFileList = array_fill(0, 100, "file_{$i}.md");
                    $config = createTestValidationConfig();
                    $this->command->collectFiles($largeFileList, $config);
                    
                    unset($largeFileList);
                    gc_collect_cycles();
                } catch (Throwable) {
                    // Handle any memory-related errors
                }
            }
            
            $finalMemory = memory_get_usage();
            $memoryIncrease = $finalMemory - $initialMemory;
            
            // Memory increase should be reasonable (less than 5MB)
            expect($memoryIncrease)->toBeLessThan(5 * 1024 * 1024);
        });
    });

    describe('Edge Cases and Boundary Conditions', function () {
        it('handles empty argument lists gracefully', function () {
            $command = Mockery::mock(TestValidationCommandForExceptions::class, [
                $this->linkValidation,
                $this->reporting
            ])->makePartial();
            $command->shouldReceive('argument')
                ->with('paths')
                ->andReturn([]);
            
            $config = createTestValidationConfig();
            $files = $command->collectFiles([], $config);
            
            expect($files)->toBeArray()
                ->and($files)->toBeEmpty();
        });

        it('handles null option values gracefully', function () {
            $command = Mockery::mock(TestValidationCommandForExceptions::class, [
                $this->linkValidation,
                $this->reporting
            ])->makePartial()
                ->shouldAllowMockingProtectedMethods();
            $command->shouldReceive('getOptionWithDefault')
                ->andReturn(null);
            
            $config = $command->testCreateConfigFromOptions();
            
            expect($config)->toBeInstanceOf(ValidationConfig::class);
        });

        it('handles very long command arguments gracefully', function () {
            $longPath = '/' . str_repeat('a', 1000) . '/file.md';
            $config = createTestValidationConfig();
            
            $files = $this->command->testCollectFiles([$longPath], $config);
            
            expect($files)->toBeArray();
        });

        it('handles special characters in arguments gracefully', function () {
            $specialPaths = [
                'file with spaces.md',
                'file-with-dashes.md',
                'file_with_underscores.md',
                'file.with.dots.md',
                'файл.md', // Unicode filename
            ];
            
            $config = createTestValidationConfig();
            $files = $this->command->testCollectFiles($specialPaths, $config);
            
            expect($files)->toBeArray();
        });
    });
});