<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use InvalidArgumentException;

final readonly class ValidationConfig
{
    /**
     * @param ValidationScope[] $scopes
     * @param array<string, mixed> $additionalOptions
     */
    public function __construct(
        private array $scopes = [ValidationScope::ALL],
        private int $timeout = 30,
        private int $maxRedirects = 5,
        private int $concurrentRequests = 10,
        private bool $cacheEnabled = true,
        private bool $followRedirects = true,
        private string $userAgent = 'validate-links/1.0',
        private ?OutputFormat $outputFormat = null,
        private array $additionalOptions = [],
        private bool $interactive = false,
        private bool $dryRun = false,
    ) {
        $this->validate();
    }

    /**
     * Create configuration from array.
     *
     * @param array<string, mixed> $config
     */
    public static function create(array $config): self
    {
        // Validate and cast scopes
        $scopes = $config['scopes'] ?? [ValidationScope::ALL];
        if (! is_array($scopes)) {
            $scopes = [ValidationScope::ALL];
        }

        // Validate each scope is a ValidationScope enum
        $validatedScopes = [];
        foreach ($scopes as $scope) {
            if ($scope instanceof ValidationScope) {
                $validatedScopes[] = $scope;
            }
        }

        // Always ensure ALL scope is present when no valid scopes
        if ($validatedScopes === []) {
            $validatedScopes = [ValidationScope::ALL];
        }

        // Validate and cast output format
        $outputFormat = $config['output_format'] ?? null;
        if ($outputFormat !== null && ! ($outputFormat instanceof OutputFormat)) {
            $outputFormat = null;
        }

        // Validate and cast additional options
        $additionalOptions = $config['additional_options'] ?? [];
        if (! is_array($additionalOptions)) {
            $additionalOptions = [];
        }
        /** @var array<string, mixed> $additionalOptions */

        // Validate and cast numeric values
        $timeout = $config['timeout'] ?? 30;
        $timeout = is_numeric($timeout) ? (int) $timeout : 30;

        $maxRedirects = $config['max_redirects'] ?? 5;
        $maxRedirects = is_numeric($maxRedirects) ? (int) $maxRedirects : 5;

        $concurrentRequests = $config['concurrent_requests'] ?? 10;
        $concurrentRequests = is_numeric($concurrentRequests) ? (int) $concurrentRequests : 10;

        // Validate and cast string values
        $userAgent = $config['user_agent'] ?? 'validate-links/1.0';
        $userAgent = is_string($userAgent) ? $userAgent : 'validate-links/1.0';

        return new self(
            scopes: $validatedScopes,
            timeout: $timeout,
            maxRedirects: $maxRedirects,
            concurrentRequests: $concurrentRequests,
            cacheEnabled: (bool) ($config['cache_enabled'] ?? true),
            followRedirects: (bool) ($config['follow_redirects'] ?? true),
            userAgent: $userAgent,
            outputFormat: $outputFormat,
            additionalOptions: $additionalOptions,
            interactive: (bool) ($config['interactive'] ?? false),
            dryRun: (bool) ($config['dry_run'] ?? false),
        );
    }

    /**
     * Create configuration with defaults.
     *
     * @param array<string, mixed> $overrides
     */
    public static function withDefaults(array $overrides = []): self
    {
        return self::create($overrides);
    }

    /**
     * Get validation scopes.
     *
     * @return ValidationScope[]
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * Check if specific scope is enabled.
     */
    public function hasScope(ValidationScope $validationScope): bool
    {
        return in_array($validationScope, $this->scopes, true) ||
               in_array(ValidationScope::ALL, $this->scopes, true);
    }

    /**
     * Get effective scopes (expand ALL to individual scopes).
     *
     * @return ValidationScope[]
     */
    public function getEffectiveScopes(): array
    {
        if (in_array(ValidationScope::ALL, $this->scopes, true)) {
            return ValidationScope::ALL->getIncludedScopes();
        }

        return $this->scopes;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function getMaxRedirects(): int
    {
        return $this->maxRedirects;
    }

    public function getConcurrentRequests(): int
    {
        return $this->concurrentRequests;
    }

    public function isCacheEnabled(): bool
    {
        return $this->cacheEnabled;
    }

    public function shouldFollowRedirects(): bool
    {
        return $this->followRedirects;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getOutputFormat(): ?OutputFormat
    {
        return $this->outputFormat;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdditionalOptions(): array
    {
        return $this->additionalOptions;
    }

    public function shouldValidateInternal(): bool
    {
        return $this->hasScope(ValidationScope::INTERNAL);
    }

    public function shouldValidateExternal(): bool
    {
        return $this->hasScope(ValidationScope::EXTERNAL);
    }

    public function shouldValidateAnchors(): bool
    {
        return $this->hasScope(ValidationScope::ANCHOR);
    }

    public function shouldValidateCrossReferences(): bool
    {
        return $this->hasScope(ValidationScope::ANCHOR);
    }

    /**
     * Create new instance with modified scopes.
     *
     * @param ValidationScope[] $scopes
     */
    public function withScopes(array $scopes): self
    {
        return new self(
            scopes: $scopes,
            timeout: $this->timeout,
            maxRedirects: $this->maxRedirects,
            concurrentRequests: $this->concurrentRequests,
            cacheEnabled: $this->cacheEnabled,
            followRedirects: $this->followRedirects,
            userAgent: $this->userAgent,
            outputFormat: $this->outputFormat,
            additionalOptions: $this->additionalOptions,
        );
    }

    /**
     * Create new instance with modified output format.
     */
    public function withOutputFormat(OutputFormat $outputFormat): self
    {
        return new self(
            scopes: $this->scopes,
            timeout: $this->timeout,
            maxRedirects: $this->maxRedirects,
            concurrentRequests: $this->concurrentRequests,
            cacheEnabled: $this->cacheEnabled,
            followRedirects: $this->followRedirects,
            userAgent: $this->userAgent,
            outputFormat: $outputFormat,
            additionalOptions: $this->additionalOptions,
        );
    }

    public function isInteractive(): bool
    {
        return $this->interactive;
    }

    public function isDryRun(): bool
    {
        return $this->dryRun;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'scopes' => array_map(fn ($scope) => $scope->value, $this->scopes),
            'timeout' => $this->timeout,
            'max_redirects' => $this->maxRedirects,
            'concurrent_requests' => $this->concurrentRequests,
            'cache_enabled' => $this->cacheEnabled,
            'follow_redirects' => $this->followRedirects,
            'user_agent' => $this->userAgent,
            'output_format' => $this->outputFormat?->value,
            'additional_options' => $this->additionalOptions,
            'interactive' => $this->interactive,
            'dry_run' => $this->dryRun,
        ];
    }

    private function validate(): void
    {
        if ($this->scopes === []) {
            throw new InvalidArgumentException('At least one validation scope must be specified');
        }

        // Type safety is guaranteed by the constructor parameter type ValidationScope[]

        if ($this->concurrentRequests < 1 || $this->concurrentRequests > 50) {
            throw new InvalidArgumentException('Concurrent requests must be between 1 and 50');
        }

        if ($this->timeout < 1 || $this->timeout > 300) {
            throw new InvalidArgumentException('Timeout must be between 1 and 300 seconds');
        }

        if ($this->maxRedirects < 0 || $this->maxRedirects > 10) {
            throw new InvalidArgumentException('Max redirects must be between 0 and 10');
        }
    }
}
