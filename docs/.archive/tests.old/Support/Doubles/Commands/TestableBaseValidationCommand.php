<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Commands;

use App\Commands\BaseValidationCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use Throwable;

/**
 * Testable concrete implementation of BaseValidationCommand for testing.
 *
 * This class exposes protected methods and properties for comprehensive testing
 * while maintaining the abstract nature of the base class.
 */
final class TestableBaseValidationCommand extends BaseValidationCommand
{
    protected $signature = 'test:validation';

    protected $description = 'Test validation command';

    /** @var array<string, mixed> */
    private array $options = [];

    public function handle(): int
    {
        return 0;
    }

    // Expose protected methods for testing
    public function collectFiles(array $paths, ValidationConfig $config): array
    {
        return parent::collectFiles($paths, $config);
    }

    public function validateCommandOptions(): void
    {
        parent::validateCommandOptions();
    }

    public function processValidationResults(array $results): int
    {
        return parent::processValidationResults($results);
    }

    /**
     * @param  array<string>  $files
     * @return array<mixed>
     */
    public function executeValidation(array $files, ValidationConfig $config): array
    {
        return parent::executeValidation($files, $config);
    }

    public function handleValidationError(Throwable $e): int
    {
        return parent::handleValidationError($e);
    }

    /**
     * @param  array<mixed>  $brokenLinks
     * @return array<string, array<mixed>>
     */
    public function groupLinksByStatus(array $brokenLinks): array
    {
        return parent::groupLinksByStatus($brokenLinks);
    }

    /**
     * @param  array<string, array<mixed>>  $statusGroups
     */
    public function displayStatusGroups(array $statusGroups): void
    {
        parent::displayStatusGroups($statusGroups);
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    public function displayValidationSummary(array $summary): void
    {
        parent::displayValidationSummary($summary);
    }

    // Expose protected properties for testing
    public function getLinkValidation(): LinkValidationInterface
    {
        return $this->linkValidation;
    }

    public function getReporting(): ReportingInterface
    {
        return $this->reporting;
    }

    // Mock option handling for testing
    public function setOption(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    /**
     * @return array<string, mixed>|mixed|null
     */
    public function option($key = null)
    {
        if ($key === null) {
            return $this->options;
        }

        return $this->options[$key] ?? null;
    }
}
