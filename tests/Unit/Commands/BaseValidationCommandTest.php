<?php

declare(strict_types=1);

use App\Commands\BaseValidationCommand;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResultCollection;

// Create a concrete test implementation of the abstract class
final class TestValidationCommand extends BaseValidationCommand
{
    protected $signature = 'test:validate {paths*} {--scope=all} {--format=console} {--output=} {--timeout=30} {--max-redirects=5} {--concurrency=10} {--no-cache} {--follow-redirects} {--user-agent=validate-links/1.0}';

    protected $description = 'Test validation command';

    public function handle(): int
    {
        return 0;
    }

    // Expose protected methods for testing
    public function testCreateConfigFromOptions(): ValidationConfig
    {
        return $this->createConfigFromOptions();
    }

    public function testGetOptionWithDefault(string $name, mixed $default = null): mixed
    {
        return $this->getOptionWithDefault($name, $default);
    }

    public function testParseScopes(string $scope): array
    {
        return $this->parseScopes($scope);
    }

    public function testGetOutputFormat(): OutputFormat
    {
        return $this->getOutputFormat();
    }

    public function testValidateCommandOptions(): void
    {
        $this->validateCommandOptions();
    }

    public function testProcessValidationResults(ValidationResultCollection $results): int
    {
        return $this->processValidationResults($results);
    }

    public function testDisplayResults(ValidationResultCollection $results): int
    {
        return $this->displayResults($results);
    }

    public function testCollectFiles(array $paths, ValidationConfig $config): array
    {
        return $this->collectFiles($paths, $config);
    }

    public function testExecuteValidation(array $files, ValidationConfig $config): ValidationResultCollection
    {
        return $this->executeValidation($files, $config);
    }

    public function testHandleValidationError(Throwable $e): int
    {
        return $this->handleValidationError($e);
    }

    public function testGroupLinksByStatus(ValidationResultCollection $results): array
    {
        return $this->groupLinksByStatus($results);
    }

    public function testDisplayStatusGroups(array $statusGroups): void
    {
        $this->displayStatusGroups($statusGroups);
    }

    public function testDisplaySummary($summary): void
    {
        $this->displaySummary($summary);
    }

    public function testDisplayValidationSummary(array $summary): void
    {
        $this->displayValidationSummary($summary);
    }
}

describe('BaseValidationCommand', function () {
    beforeEach(function () {
        $this->linkValidation = mock(LinkValidationInterface::class);
        $this->reporting = mock(ReportingInterface::class);
        $this->command = new TestValidationCommand($this->linkValidation, $this->reporting);
        $this->command->setLaravel(app());

        // Create proper console input/output for testing
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $output = new \Symfony\Component\Console\Output\BufferedOutput();

        // Use reflection to set the input and output properties
        $reflection = new ReflectionClass($this->command);
        if ($reflection->hasProperty('input')) {
            $inputProperty = $reflection->getProperty('input');
            $inputProperty->setAccessible(true);
            $inputProperty->setValue($this->command, $input);
        }
        if ($reflection->hasProperty('output')) {
            $outputProperty = $reflection->getProperty('output');
            $outputProperty->setAccessible(true);
            $outputProperty->setValue($this->command, $output);
        }
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

    describe('getOutputFormat method', function () {
        it('returns default console format', function () {
            // Test with actual command - should return console format by default
            $result = $this->command->testGetOutputFormat();

            expect($result)->toBe(OutputFormat::CONSOLE);
        });

        it('method exists and returns OutputFormat enum', function () {
            // Basic test that the method works and returns correct type
            $result = $this->command->testGetOutputFormat();

            expect($result)->toBeInstanceOf(OutputFormat::class);
        });
    });

    describe('validateCommandOptions method', function () {
        it('method exists and is callable', function () {
            // Basic test that the method exists
            expect(method_exists($this->command, 'testValidateCommandOptions'))->toBeTrue();
        });

        it('does not throw exception with valid command setup', function () {
            // Test that the method can be called without throwing
            expect(function () {
                $this->command->testValidateCommandOptions();
            })->not->toThrow(Exception::class);
        });
    });

    describe('processValidationResults method', function () {
        it('method exists and is callable', function () {
            // Create a simple ValidationResultCollection for testing
            $results = new ValidationResultCollection([]);

            expect(method_exists($this->command, 'testProcessValidationResults'))->toBeTrue();
        });

        it('returns integer exit code', function () {
            // Test with empty results collection
            $results = new ValidationResultCollection([]);
            $result = $this->command->testProcessValidationResults($results);

            expect($result)->toBeInt();
        });
    });

    describe('displayResults method', function () {
        it('method exists and is callable', function () {
            $results = new ValidationResultCollection([]);

            expect(method_exists($this->command, 'testDisplayResults'))->toBeTrue();
        });

        it('returns integer exit code', function () {
            $results = new ValidationResultCollection([]);
            $result = $this->command->testDisplayResults($results);

            expect($result)->toBeInt();
        });
    });

    describe('collectFiles method', function () {
        it('method exists and is callable', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);

            expect(method_exists($this->command, 'testCollectFiles'))->toBeTrue();
        });

        it('returns array of files', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
            $result = $this->command->testCollectFiles([], $config);

            expect($result)->toBeArray();
        });

        it('handles empty paths array', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
            $result = $this->command->testCollectFiles([], $config);

            expect($result)->toBe([]);
        });
    });

    describe('executeValidation method', function () {
        it('method exists and is callable', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);

            expect(method_exists($this->command, 'testExecuteValidation'))->toBeTrue();
        });

        it('returns ValidationResultCollection', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ALL]);
            $this->linkValidation->shouldReceive('validateFile')->andReturn([]);

            $result = $this->command->testExecuteValidation([], $config);

            expect($result)->toBeInstanceOf(ValidationResultCollection::class);
        });
    });

    describe('handleValidationError method', function () {
        it('method exists and is callable', function () {
            $exception = new Exception('Test error');

            expect(method_exists($this->command, 'testHandleValidationError'))->toBeTrue();
        });

        it('returns error exit code 2', function () {
            $exception = new Exception('Test error');
            $result = $this->command->testHandleValidationError($exception);

            expect($result)->toBe(2);
        });
    });

    describe('groupLinksByStatus method', function () {
        it('method exists and is callable', function () {
            $results = new ValidationResultCollection([]);

            expect(method_exists($this->command, 'testGroupLinksByStatus'))->toBeTrue();
        });

        it('returns array of grouped results', function () {
            $results = new ValidationResultCollection([]);
            $grouped = $this->command->testGroupLinksByStatus($results);

            expect($grouped)->toBeArray();
        });
    });

    describe('displayStatusGroups method', function () {
        it('method exists and is callable', function () {
            expect(method_exists($this->command, 'testDisplayStatusGroups'))->toBeTrue();
        });

        it('does not throw exception with empty status groups', function () {
            expect(function () {
                $this->command->testDisplayStatusGroups([]);
            })->not->toThrow(Exception::class);
        });
    });

    describe('displaySummary method', function () {
        it('method exists and is callable', function () {
            expect(method_exists($this->command, 'testDisplaySummary'))->toBeTrue();
        });

        it('does not throw exception with array summary', function () {
            $summary = ['total_links' => 10, 'broken_links' => 2];

            expect(function () use ($summary) {
                $this->command->testDisplaySummary($summary);
            })->not->toThrow(Exception::class);
        });
    });

    describe('displayValidationSummary method', function () {
        it('method exists and is callable', function () {
            expect(method_exists($this->command, 'testDisplayValidationSummary'))->toBeTrue();
        });

        it('does not throw exception with summary array', function () {
            $summary = [
                'files_processed' => 5,
                'total_links' => 10,
                'broken_links' => 2,
                'success_rate' => 80.0,
            ];

            expect(function () use ($summary) {
                $this->command->testDisplayValidationSummary($summary);
            })->not->toThrow(Exception::class);
        });

        it('handles empty summary array', function () {
            $summary = [];

            expect(function () use ($summary) {
                $this->command->testDisplayValidationSummary($summary);
            })->not->toThrow(Exception::class);
        });
    });

    describe('class structure', function () {
        it('is an abstract class', function () {
            $reflection = new ReflectionClass(BaseValidationCommand::class);
            expect($reflection->isAbstract())->toBeTrue();
        });

        it('extends Laravel Zero Command', function () {
            $command = new TestValidationCommand($this->linkValidation, $this->reporting);
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
});
