<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Commands\BaseValidationCommand;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResultCollection;
use Throwable;

/**
 * Concrete test implementation of the abstract BaseValidationCommand.
 */
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

    /**
     * @return array<ValidationScope>
     */
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

    /**
     * @param  array<string>  $paths
     * @return array<string>
     */
    public function testCollectFiles(array $paths, ValidationConfig $config): array
    {
        return $this->collectFiles($paths, $config);
    }

    /**
     * @param  array<string>  $files
     */
    public function testExecuteValidation(array $files, ValidationConfig $config): ValidationResultCollection
    {
        return $this->executeValidation($files, $config);
    }

    public function testHandleValidationError(Throwable $e): int
    {
        return $this->handleValidationError($e);
    }

    /**
     * @return array<string, array<App\Services\ValueObjects\ValidationResult>>
     */
    public function testGroupLinksByStatus(ValidationResultCollection $results): array
    {
        return $this->groupLinksByStatus($results);
    }

    /**
     * @param  array<string, array<App\Services\ValueObjects\ValidationResult>>  $statusGroups
     */
    public function testDisplayStatusGroups(array $statusGroups): void
    {
        $this->displayStatusGroups($statusGroups);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function testDisplaySummary(array $summary): void
    {
        $this->displaySummary($summary);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function testDisplayValidationSummary(array $summary): void
    {
        $this->displayValidationSummary($summary);
    }
}