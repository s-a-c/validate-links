<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Commands\BaseValidationCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResultCollection;
use Throwable;

/**
 * Concrete test implementation of the abstract BaseValidationCommand for exception testing.
 */
class TestValidationCommandForExceptions extends BaseValidationCommand
{
    protected $signature = 'test:validate {paths*} {--scope=all} {--format=console} {--output=}';
    protected $description = 'Test validation command';

    public function handle(): int
    {
        try {
            $this->validateCommandOptions();
            $config = $this->createConfigFromOptions();
            $paths = $this->argument('paths') ?? ['./test'];
            $files = $this->collectFiles($paths, $config);
            $results = $this->executeValidation($files, $config);
            return $this->displayResults($results);
        } catch (Throwable $e) {
            return $this->handleValidationError($e);
        }
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

    public function testValidateCommandOptions(): void
    {
        $this->validateCommandOptions();
    }

    public function testCollectFiles(array $paths, ValidationConfig $config): array
    {
        return $this->collectFiles($paths, $config);
    }

    public function testExecuteValidation(array $files, ValidationConfig $config): ValidationResultCollection
    {
        return $this->executeValidation($files, $config);
    }

    public function testDisplayResults(ValidationResultCollection $results): int
    {
        return $this->displayResults($results);
    }

    public function testHandleValidationError(Throwable $e): int
    {
        return $this->handleValidationError($e);
    }
}