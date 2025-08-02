<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Builders;

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;

/**
 * Builder for creating ValidationConfig instances with fluent configuration.
 *
 * Provides a fluent interface for building ValidationConfig objects with
 * step-by-step customization for different testing scenarios.
 */
final class ConfigurationBuilder
{
    /** @var array<string, mixed> */
    private array $config = [];

    /**
     * Create a new ConfigurationBuilder instance.
     */
    public static function new(): self
    {
        return new self();
    }

    /**
     * Set the request timeout.
     */
    public function timeout(int $seconds): self
    {
        $this->config['timeout'] = $seconds;

        return $this;
    }

    /**
     * Set the number of concurrent requests.
     */
    public function concurrency(int $concurrent): self
    {
        $this->config['concurrent_requests'] = $concurrent;

        return $this;
    }

    /**
     * Enable or disable following redirects.
     */
    public function followRedirects(bool $follow = true): self
    {
        $this->config['follow_redirects'] = $follow;

        return $this;
    }

    /**
     * Enable or disable external link checking.
     */
    public function checkExternal(bool $check = true): self
    {
        $this->config['check_external'] = $check;

        return $this;
    }

    /**
     * Set the output format.
     */
    public function format(OutputFormat $format): self
    {
        $this->config['output_format'] = $format;

        return $this;
    }

    /**
     * Set the validation scope.
     */
    public function scope(ValidationScope $scope): self
    {
        $this->config['scope'] = $scope;

        return $this;
    }

    /**
     * Set the maximum file size.
     */
    public function maxFileSize(int $bytes): self
    {
        $this->config['max_file_size'] = $bytes;

        return $this;
    }

    /**
     * Set the user agent string.
     */
    public function userAgent(string $userAgent): self
    {
        $this->config['user_agent'] = $userAgent;

        return $this;
    }

    /**
     * Set the number of retry attempts.
     */
    public function retryAttempts(int $attempts): self
    {
        $this->config['retry_attempts'] = $attempts;

        return $this;
    }

    /**
     * Set the retry delay in milliseconds.
     */
    public function retryDelay(int $milliseconds): self
    {
        $this->config['retry_delay'] = $milliseconds;

        return $this;
    }

    /**
     * Configure for fast validation (minimal timeouts, high concurrency).
     */
    public function fast(): self
    {
        return $this
            ->timeout(5)
            ->concurrency(10)
            ->retryAttempts(1)
            ->retryDelay(100);
    }

    /**
     * Configure for thorough validation (longer timeouts, more retries).
     */
    public function thorough(): self
    {
        return $this
            ->timeout(60)
            ->concurrency(2)
            ->retryAttempts(5)
            ->retryDelay(2000);
    }

    /**
     * Configure for internal links only.
     */
    public function internalOnly(): self
    {
        return $this
            ->checkExternal(false)
            ->scope(ValidationScope::INTERNAL);
    }

    /**
     * Configure for external links only.
     */
    public function externalOnly(): self
    {
        return $this
            ->checkExternal(true)
            ->scope(ValidationScope::EXTERNAL);
    }

    /**
     * Configure for JSON output.
     */
    public function json(): self
    {
        return $this->format(OutputFormat::JSON);
    }

    /**
     * Configure for table output.
     */
    public function table(): self
    {
        return $this->format(OutputFormat::CONSOLE);
    }

    /**
     * Configure for markdown output.
     */
    public function markdown(): self
    {
        return $this->format(OutputFormat::MARKDOWN);
    }

    /**
     * Configure for testing error scenarios (very short timeouts).
     */
    public function forErrors(): self
    {
        return $this
            ->timeout(1)
            ->retryAttempts(0)
            ->followRedirects(false);
    }

    /**
     * Configure for large file processing.
     */
    public function largeFiles(): self
    {
        return $this
            ->maxFileSize(10 * 1024 * 1024) // 10MB
            ->timeout(120)
            ->concurrency(1);
    }

    /**
     * Configure with custom user agent.
     */
    public function withUserAgent(string $agent): self
    {
        return $this->userAgent($agent);
    }

    /**
     * Set a custom configuration value.
     */
    public function set(string $key, mixed $value): self
    {
        $this->config[$key] = $value;

        return $this;
    }

    /**
     * Merge additional configuration.
     *
     * @param  array<string, mixed>  $config
     */
    public function merge(array $config): self
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    /**
     * Build the ValidationConfig instance.
     */
    public function build(): ValidationConfig
    {
        // Set defaults for any missing values
        $defaults = [
            'timeout' => 30,
            'concurrent_requests' => 5,
            'follow_redirects' => true,
            'check_external' => true,
            'output_format' => OutputFormat::JSON,
            'scope' => ValidationScope::ALL,
            'max_file_size' => 1024 * 1024,
            'user_agent' => 'ValidateLinks/1.0 (Test)',
            'retry_attempts' => 3,
            'retry_delay' => 1000,
        ];

        $finalConfig = array_merge($defaults, $this->config);

        return ValidationConfig::create($finalConfig);
    }

    /**
     * Build with specific overrides.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function buildWith(array $overrides): ValidationConfig
    {
        return $this->merge($overrides)->build();
    }
}
