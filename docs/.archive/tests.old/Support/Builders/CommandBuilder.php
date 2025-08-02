<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Builders;

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use old\Support\Doubles\Commands\TestableBaseValidationCommand;
use old\Support\Factories\ValidationConfigFactory;

/**
 * Builder for creating command instances with fluent configuration.
 *
 * Provides a fluent interface for building command objects with
 * customizable dependencies and configuration for testing.
 */
final class CommandBuilder
{
    private ?LinkValidationInterface $linkValidation = null;

    private ?ReportingInterface $reporting = null;

    private ?ValidationConfig $config = null;

    /** @var array<string, mixed> */
    private array $options = [];

    /** @var array<string, mixed> */
    private array $arguments = [];

    /**
     * Create a new CommandBuilder instance.
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Set the link validation service.
     */
    public function withLinkValidation(LinkValidationInterface $linkValidation): self
    {
        $this->linkValidation = $linkValidation;

        return $this;
    }

    /**
     * Set the reporting service.
     */
    public function withReporting(ReportingInterface $reporting): self
    {
        $this->reporting = $reporting;

        return $this;
    }

    /**
     * Set the validation configuration.
     */
    public function withConfig(ValidationConfig $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Set a command option.
     */
    public function withOption(string $key, mixed $value): self
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * Set multiple command options.
     *
     * @param  array<string, mixed>  $options
     */
    public function withOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    /**
     * Set a command argument.
     */
    public function withArgument(string $key, mixed $value): self
    {
        $this->arguments[$key] = $value;

        return $this;
    }

    /**
     * Set multiple command arguments.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function withArguments(array $arguments): self
    {
        $this->arguments = array_merge($this->arguments, $arguments);

        return $this;
    }

    /**
     * Set timeout option.
     */
    public function withTimeout(int $timeout): self
    {
        return $this->withOption('timeout', $timeout);
    }

    /**
     * Set output format option.
     */
    public function withFormat(OutputFormat $format): self
    {
        return $this->withOption('format', $format->value);
    }

    /**
     * Set validation scope option.
     */
    public function withScope(ValidationScope $scope): self
    {
        return $this->withOption('scope', $scope->value);
    }

    /**
     * Set concurrency option.
     */
    public function withConcurrency(int $concurrent): self
    {
        return $this->withOption('concurrent', $concurrent);
    }

    /**
     * Enable verbose output.
     */
    public function verbose(): self
    {
        return $this->withOption('verbose', true);
    }

    /**
     * Enable quiet mode.
     */
    public function quiet(): self
    {
        return $this->withOption('quiet', true);
    }

    /**
     * Enable dry run mode.
     */
    public function dryRun(): self
    {
        return $this->withOption('dry-run', true);
    }

    /**
     * Enable backup creation.
     */
    public function withBackup(): self
    {
        return $this->withOption('backup', true);
    }

    /**
     * Set paths to validate.
     *
     * @param  string[]  $paths
     */
    public function withPaths(array $paths): self
    {
        return $this->withArgument('paths', $paths);
    }

    /**
     * Set single path to validate.
     */
    public function withPath(string $path): self
    {
        return $this->withPaths([$path]);
    }

    /**
     * Configure for fast testing.
     */
    public function fast(): self
    {
        return $this
            ->withTimeout(5)
            ->withConcurrency(10)
            ->withOption('retry-attempts', 1);
    }

    /**
     * Configure for thorough testing.
     */
    public function thorough(): self
    {
        return $this
            ->withTimeout(60)
            ->withConcurrency(2)
            ->withOption('retry-attempts', 5);
    }

    /**
     * Configure for internal links only.
     */
    public function internalOnly(): self
    {
        return $this
            ->withScope(ValidationScope::INTERNAL)
            ->withOption('check-external', false);
    }

    /**
     * Configure for external links only.
     */
    public function externalOnly(): self
    {
        return $this
            ->withScope(ValidationScope::EXTERNAL)
            ->withOption('check-external', true);
    }

    /**
     * Build the command instance.
     */
    public function build(): TestableBaseValidationCommand
    {
        // Create default dependencies if not provided
        if (! $this->linkValidation instanceof LinkValidationInterface) {
            $this->linkValidation = Mockery::mock(LinkValidationInterface::class);
        }

        if (! $this->reporting instanceof ReportingInterface) {
            $this->reporting = Mockery::mock(ReportingInterface::class);
        }

        // Create the command
        $command = new TestableBaseValidationCommand(
            $this->linkValidation,
            $this->reporting
        );

        // Set options and arguments
        foreach ($this->options as $key => $value) {
            $command->setOption($key, $value);
        }

        foreach ($this->arguments as $key => $value) {
            $command->setOption($key, $value);
        }

        return $command;
    }

    /**
     * Build and return the command with default configuration.
     */
    public function buildDefault(): TestableBaseValidationCommand
    {
        if (! $this->config instanceof ValidationConfig) {
            $this->config = ValidationConfigFactory::create();
        }

        return $this->build();
    }
}
