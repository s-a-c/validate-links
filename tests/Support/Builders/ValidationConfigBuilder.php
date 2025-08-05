<?php

declare(strict_types=1);

namespace Tests\Support\Builders;

use App\Services\ValueObjects\ValidationConfig;
use App\Enums\ValidationScope;

/**
 * Validation Config Builder for PEST Tests
 * 
 * Implements the Builder pattern for creating complex ValidationConfig objects
 * with fluent interface and lazy evaluation.
 */
final class ValidationConfigBuilder
{
    private array $scopes = [ValidationScope::ALL];
    private int $timeout = 30;
    private int $maxRedirects = 5;
    private int $concurrentRequests = 10;
    private bool $cacheEnabled = true;
    private bool $followRedirects = true;
    private string $userAgent = 'validate-links/1.0';
    private array $headers = [];
    private array $excludePatterns = [];
    private array $includePatterns = [];
    
    /**
     * Create a new builder instance
     */
    public static function create(): self
    {
        return new self();
    }
    
    /**
     * Set validation scopes
     */
    public function withScopes(array $scopes): self
    {
        $this->scopes = $scopes;
        return $this;
    }
    
    /**
     * Add a single scope
     */
    public function addScope(ValidationScope $scope): self
    {
        if (!in_array($scope, $this->scopes, true)) {
            $this->scopes[] = $scope;
        }
        return $this;
    }
    
    /**
     * Set timeout
     */
    public function withTimeout(int $timeout): self
    {
        $this->timeout = $timeout;
        return $this;
    }
    
    /**
     * Set max redirects
     */
    public function withMaxRedirects(int $maxRedirects): self
    {
        $this->maxRedirects = $maxRedirects;
        return $this;
    }
    
    /**
     * Set concurrent requests
     */
    public function withConcurrentRequests(int $concurrentRequests): self
    {
        $this->concurrentRequests = $concurrentRequests;
        return $this;
    }
    
    /**
     * Enable/disable cache
     */
    public function withCache(bool $enabled = true): self
    {
        $this->cacheEnabled = $enabled;
        return $this;
    }
    
    /**
     * Enable/disable following redirects
     */
    public function withRedirects(bool $follow = true): self
    {
        $this->followRedirects = $follow;
        return $this;
    }
    
    /**
     * Set user agent
     */
    public function withUserAgent(string $userAgent): self
    {
        $this->userAgent = $userAgent;
        return $this;
    }
    
    /**
     * Add custom headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }
    
    /**
     * Add a single header
     */
    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }
    
    /**
     * Add exclude patterns
     */
    public function withExcludePatterns(array $patterns): self
    {
        $this->excludePatterns = array_merge($this->excludePatterns, $patterns);
        return $this;
    }
    
    /**
     * Add include patterns
     */
    public function withIncludePatterns(array $patterns): self
    {
        $this->includePatterns = array_merge($this->includePatterns, $patterns);
        return $this;
    }
    
    /**
     * Configure for fast testing
     */
    public function forFastTesting(): self
    {
        return $this
            ->withTimeout(5)
            ->withMaxRedirects(2)
            ->withConcurrentRequests(20)
            ->withCache(false);
    }
    
    /**
     * Configure for slow/thorough testing
     */
    public function forThoroughTesting(): self
    {
        return $this
            ->withTimeout(60)
            ->withMaxRedirects(10)
            ->withConcurrentRequests(5)
            ->withCache(true);
    }
    
    /**
     * Configure for external links only
     */
    public function forExternalLinks(): self
    {
        return $this->withScopes([ValidationScope::EXTERNAL]);
    }
    
    /**
     * Configure for internal links only
     */
    public function forInternalLinks(): self
    {
        return $this->withScopes([ValidationScope::INTERNAL]);
    }
    
    /**
     * Configure for anchor links only
     */
    public function forAnchorLinks(): self
    {
        return $this->withScopes([ValidationScope::ANCHOR]);
    }
    
    /**
     * Configure for CI environment
     */
    public function forCI(): self
    {
        return $this
            ->withTimeout(30)
            ->withMaxRedirects(3)
            ->withConcurrentRequests(10)
            ->withCache(false)
            ->addHeader('User-Agent', 'validate-links-ci/1.0');
    }
    
    /**
     * Build the ValidationConfig object
     */
    public function build(): ValidationConfig
    {
        return new ValidationConfig(
            scopes: $this->scopes,
            timeout: $this->timeout,
            maxRedirects: $this->maxRedirects,
            concurrentRequests: $this->concurrentRequests,
            cacheEnabled: $this->cacheEnabled,
            followRedirects: $this->followRedirects,
            userAgent: $this->userAgent
        );
    }
    
    /**
     * Build with custom overrides
     */
    public function buildWith(array $overrides): ValidationConfig
    {
        $config = clone $this;
        
        foreach ($overrides as $property => $value) {
            if (property_exists($config, $property)) {
                $config->$property = $value;
            }
        }
        
        return $config->build();
    }
    
    /**
     * Create multiple configs with variations
     */
    public function buildMany(int $count, callable $modifier = null): array
    {
        $configs = [];
        
        for ($i = 0; $i < $count; $i++) {
            $builder = clone $this;
            
            if ($modifier) {
                $builder = $modifier($builder, $i);
            }
            
            $configs[] = $builder->build();
        }
        
        return $configs;
    }
}