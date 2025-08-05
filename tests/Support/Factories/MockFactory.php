<?php

declare(strict_types=1);

namespace Tests\Support\Factories;

use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use Mockery;

/**
 * Streamlined Mock Factory
 * 
 * Replaces the complex 390-line MockFactory with efficient,
 * lazy-loaded mock creation focused on performance.
 */
final class MockFactory
{
    /**
     * Create LinkValidation mock with minimal configuration
     */
    public static function linkValidation(array $config = []): object
    {
        $mock = Mockery::mock(LinkValidationInterface::class);
        
        // Default behaviors - only what's commonly needed
        $mock->shouldReceive('validateLink')
            ->andReturnUsing(fn($url) => self::createValidationResult($url))
            ->byDefault();
            
        $mock->shouldReceive('validateLinks')
            ->andReturn(new ValidationResultCollection([]))
            ->byDefault();
            
        $mock->shouldReceive('extractLinks')
            ->andReturn([])
            ->byDefault();
            
        $mock->shouldReceive('isValidUrl')
            ->andReturn(true)
            ->byDefault();
            
        $mock->shouldReceive('getSupportedScopes')
            ->andReturn([ValidationScope::ALL])
            ->byDefault();
            
        $mock->shouldReceive('getStatusStatistics')
            ->andReturn(new ValidationStatistics(0, 0, 0, 0, 0, 0, 0, 0))
            ->byDefault();
        
        return $mock;
    }
    
    /**
     * Create SecurityValidation mock with minimal configuration
     */
    public static function securityValidation(array $config = []): object
    {
        $mock = Mockery::mock(SecurityValidationInterface::class);
        
        // Safe defaults
        $mock->shouldReceive('validateUrl')->andReturn(true)->byDefault();
        $mock->shouldReceive('validatePath')->andReturn(true)->byDefault();
        $mock->shouldReceive('isDomainBlocked')->andReturn(false)->byDefault();
        $mock->shouldReceive('isProtocolAllowed')->andReturn(true)->byDefault();
        
        return $mock;
    }
    
    /**
     * Create Reporting mock with minimal configuration
     */
    public static function reporting(array $config = []): object
    {
        $mock = Mockery::mock(ReportingInterface::class);
        
        $mock->shouldReceive('generateReport')->andReturn('Mock Report')->byDefault();
        $mock->shouldReceive('generateSummary')->andReturn(DataFactory::reportSummary())->byDefault();
        $mock->shouldReceive('exportReport')->andReturn(true)->byDefault();
        
        return $mock;
    }
    
    /**
     * Create realistic validation result based on URL
     */
    private static function createValidationResult(string $url): ValidationResult
    {
        $status = str_contains($url, 'broken') ? LinkStatus::BROKEN : LinkStatus::VALID;
        $scope = str_starts_with($url, '#') ? ValidationScope::ANCHOR : ValidationScope::EXTERNAL;
        
        return ValidationResult::create(
            url: $url,
            status: $status,
            scope: $scope,
            responseTime: 0.1
        );
    }
}