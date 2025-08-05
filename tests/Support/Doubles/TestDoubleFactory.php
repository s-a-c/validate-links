<?php

declare(strict_types=1);

namespace Tests\Support\Doubles;

use Mockery;
use Mockery\MockInterface;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\StatisticsInterface;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;

/**
 * Test Double Factory for PEST Tests
 * 
 * Advanced factory for creating test doubles with realistic behaviors,
 * state management, and complex interaction patterns.
 */
final class TestDoubleFactory
{
    /**
     * Create a realistic LinkValidation mock with state
     */
    public static function linkValidationWithState(array $config = []): MockInterface
    {
        $mock = Mockery::mock(LinkValidationInterface::class);
        $state = new LinkValidationState($config);
        
        $mock->shouldReceive('validateLink')
            ->andReturnUsing(function ($url) use ($state) {
                return $state->validateLink($url);
            });
            
        $mock->shouldReceive('validateLinks')
            ->andReturnUsing(function ($urls) use ($state) {
                return $state->validateLinks($urls);
            });
            
        $mock->shouldReceive('extractLinks')
            ->andReturnUsing(function ($content) use ($state) {
                return $state->extractLinks($content);
            });
            
        $mock->shouldReceive('isValidUrl')
            ->andReturnUsing(function ($url) use ($state) {
                return $state->isValidUrl($url);
            });
            
        $mock->shouldReceive('getSupportedScopes')
            ->andReturn($state->getSupportedScopes());
            
        $mock->shouldReceive('getStatusStatistics')
            ->andReturnUsing(function () use ($state) {
                return $state->getStatistics();
            });
        
        return $mock;
    }
    
    /**
     * Create a security validation mock with configurable rules
     */
    public static function securityValidationWithRules(array $rules = []): MockInterface
    {
        $mock = Mockery::mock(SecurityValidationInterface::class);
        $validator = new SecurityValidator($rules);
        
        $mock->shouldReceive('validateUrl')
            ->andReturnUsing(function ($url) use ($validator) {
                return $validator->validateUrl($url);
            });
            
        $mock->shouldReceive('validatePath')
            ->andReturnUsing(function ($path) use ($validator) {
                return $validator->validatePath($path);
            });
            
        $mock->shouldReceive('isDomainBlocked')
            ->andReturnUsing(function ($domain) use ($validator) {
                return $validator->isDomainBlocked($domain);
            });
            
        $mock->shouldReceive('isProtocolAllowed')
            ->andReturnUsing(function ($protocol) use ($validator) {
                return $validator->isProtocolAllowed($protocol);
            });
        
        return $mock;
    }
    
    /**
     * Create a reporting mock with output tracking
     */
    public static function reportingWithTracking(): MockInterface
    {
        $mock = Mockery::mock(ReportingInterface::class);
        $tracker = new ReportingTracker();
        
        $mock->shouldReceive('generateReport')
            ->andReturnUsing(function ($results, $format = 'text') use ($tracker) {
                return $tracker->generateReport($results, $format);
            });
            
        $mock->shouldReceive('generateSummary')
            ->andReturnUsing(function ($results) use ($tracker) {
                return $tracker->generateSummary($results);
            });
            
        $mock->shouldReceive('exportReport')
            ->andReturnUsing(function ($report, $path) use ($tracker) {
                return $tracker->exportReport($report, $path);
            });
        
        // Add method to access tracker for assertions
        $mock->shouldReceive('getTracker')
            ->andReturn($tracker);
        
        return $mock;
    }
    
    /**
     * Create a failing service mock for error testing
     */
    public static function failingService(string $interface, array $failures = []): MockInterface
    {
        $mock = Mockery::mock($interface);
        
        foreach ($failures as $method => $exception) {
            $mock->shouldReceive($method)
                ->andThrow($exception);
        }
        
        return $mock;
    }
    
    /**
     * Create a slow service mock for performance testing
     */
    public static function slowService(string $interface, array $delays = []): MockInterface
    {
        $mock = Mockery::mock($interface);
        
        foreach ($delays as $method => $delay) {
            $mock->shouldReceive($method)
                ->andReturnUsing(function (...$args) use ($delay) {
                    usleep($delay * 1000); // Convert ms to microseconds
                    return null; // or appropriate return value
                });
        }
        
        return $mock;
    }
    
    /**
     * Create a spy that records all interactions
     */
    public static function recordingSpy(string $interface): MockInterface
    {
        $spy = Mockery::spy($interface);
        $recorder = new InteractionRecorder();
        
        // Wrap all method calls to record interactions
        $spy->shouldReceive(Mockery::any())
            ->andReturnUsing(function ($method, $args) use ($recorder) {
                $recorder->record($method, $args);
                return null; // or appropriate return value
            });
        
        // Add method to access recorder
        $spy->shouldReceive('getRecorder')
            ->andReturn($recorder);
        
        return $spy;
    }
    
    /**
     * Create a partial mock with real implementation
     */
    public static function partialWithReal(string $class, array $mockMethods = []): MockInterface
    {
        $mock = Mockery::mock($class)->makePartial();
        
        foreach ($mockMethods as $method => $return) {
            $mock->shouldReceive($method)->andReturn($return);
        }
        
        return $mock;
    }
}

/**
 * Link Validation State Manager
 */
class LinkValidationState
{
    private array $validUrls = [];
    private array $brokenUrls = [];
    private array $statistics = [];
    
    public function __construct(array $config = [])
    {
        $this->validUrls = $config['valid_urls'] ?? ['https://example.com'];
        $this->brokenUrls = $config['broken_urls'] ?? ['https://broken.example.com'];
    }
    
    public function validateLink(string $url): ValidationResult
    {
        $status = in_array($url, $this->validUrls) ? LinkStatus::VALID : LinkStatus::BROKEN;
        $scope = $this->determineScope($url);
        
        $this->updateStatistics($status);
        
        return ValidationResult::create(
            url: $url,
            status: $status,
            scope: $scope,
            responseTime: rand(50, 500) / 1000 // Random response time
        );
    }
    
    public function validateLinks(array $urls): ValidationResultCollection
    {
        $results = array_map([$this, 'validateLink'], $urls);
        return new ValidationResultCollection($results);
    }
    
    public function extractLinks(string $content): array
    {
        // Simple regex to extract URLs from content
        preg_match_all('/https?:\/\/[^\s<>"]+/', $content, $matches);
        return $matches[0] ?? [];
    }
    
    public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    public function getSupportedScopes(): array
    {
        return [ValidationScope::ALL, ValidationScope::EXTERNAL, ValidationScope::INTERNAL];
    }
    
    public function getStatistics(): array
    {
        return $this->statistics;
    }
    
    private function determineScope(string $url): ValidationScope
    {
        if (str_starts_with($url, '#')) {
            return ValidationScope::ANCHOR;
        }
        
        if (str_starts_with($url, 'http')) {
            return ValidationScope::EXTERNAL;
        }
        
        return ValidationScope::INTERNAL;
    }
    
    private function updateStatistics(LinkStatus $status): void
    {
        $key = $status->value;
        $this->statistics[$key] = ($this->statistics[$key] ?? 0) + 1;
    }
}

/**
 * Security Validator
 */
class SecurityValidator
{
    private array $blockedDomains = [];
    private array $allowedProtocols = ['http', 'https'];
    private array $blockedPaths = [];
    
    public function __construct(array $rules = [])
    {
        $this->blockedDomains = $rules['blocked_domains'] ?? ['malicious.com'];
        $this->allowedProtocols = $rules['allowed_protocols'] ?? ['http', 'https'];
        $this->blockedPaths = $rules['blocked_paths'] ?? ['/admin', '/private'];
    }
    
    public function validateUrl(string $url): bool
    {
        $parsed = parse_url($url);
        
        if (!$this->isProtocolAllowed($parsed['scheme'] ?? '')) {
            return false;
        }
        
        if ($this->isDomainBlocked($parsed['host'] ?? '')) {
            return false;
        }
        
        if (!$this->validatePath($parsed['path'] ?? '')) {
            return false;
        }
        
        return true;
    }
    
    public function validatePath(string $path): bool
    {
        foreach ($this->blockedPaths as $blockedPath) {
            if (str_starts_with($path, $blockedPath)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function isDomainBlocked(string $domain): bool
    {
        return in_array($domain, $this->blockedDomains);
    }
    
    public function isProtocolAllowed(string $protocol): bool
    {
        return in_array($protocol, $this->allowedProtocols);
    }
}

/**
 * Reporting Tracker
 */
class ReportingTracker
{
    private array $reports = [];
    private array $summaries = [];
    private array $exports = [];
    
    public function generateReport($results, string $format): string
    {
        $report = "Mock Report in {$format} format";
        $this->reports[] = ['results' => $results, 'format' => $format, 'report' => $report];
        
        return $report;
    }
    
    public function generateSummary($results): array
    {
        $summary = ['total' => count($results), 'format' => 'summary'];
        $this->summaries[] = $summary;
        
        return $summary;
    }
    
    public function exportReport(string $report, string $path): bool
    {
        $this->exports[] = ['report' => $report, 'path' => $path];
        return true;
    }
    
    public function getReports(): array
    {
        return $this->reports;
    }
    
    public function getSummaries(): array
    {
        return $this->summaries;
    }
    
    public function getExports(): array
    {
        return $this->exports;
    }
}

/**
 * Interaction Recorder
 */
class InteractionRecorder
{
    private array $interactions = [];
    
    public function record(string $method, array $args): void
    {
        $this->interactions[] = [
            'method' => $method,
            'args' => $args,
            'timestamp' => microtime(true),
        ];
    }
    
    public function getInteractions(): array
    {
        return $this->interactions;
    }
    
    public function getInteractionCount(): int
    {
        return count($this->interactions);
    }
    
    public function getMethodCalls(string $method): array
    {
        return array_filter($this->interactions, fn($i) => $i['method'] === $method);
    }
}