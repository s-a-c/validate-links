<?php

declare(strict_types=1);

namespace Tests\Support\Factories;

use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ReportSummary;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Enums\OutputFormat;

/**
 * Efficient Test Data Factory
 * 
 * Streamlined data creation that replaces complex mock data builders
 * with simple, performant factory methods.
 */
final class DataFactory
{
    /**
     * Create validation configuration
     */
    public static function validationConfig(array $overrides = []): ValidationConfig
    {
        $defaults = [
            'scopes' => [ValidationScope::ALL],
            'timeout' => 30,
            'maxRedirects' => 5,
            'concurrentRequests' => 10,
            'cacheEnabled' => true,
            'followRedirects' => true,
            'userAgent' => 'validate-links/1.0',
        ];
        
        $config = array_merge($defaults, $overrides);
        
        return new ValidationConfig(
            scopes: $config['scopes'],
            timeout: $config['timeout'],
            maxRedirects: $config['maxRedirects'],
            concurrentRequests: $config['concurrentRequests'],
            cacheEnabled: $config['cacheEnabled'],
            followRedirects: $config['followRedirects'],
            userAgent: $config['userAgent']
        );
    }
    
    /**
     * Create validation result
     */
    public static function validationResult(array $overrides = []): ValidationResult
    {
        $defaults = [
            'url' => 'https://example.com',
            'status' => LinkStatus::VALID,
            'scope' => ValidationScope::EXTERNAL,
            'httpStatusCode' => 200,
            'responseTime' => 0.15,
            'error' => '',
        ];
        
        $data = array_merge($defaults, $overrides);
        
        return ValidationResult::create(
            url: $data['url'],
            status: $data['status'],
            scope: $data['scope'],
            httpStatusCode: $data['httpStatusCode'],
            responseTime: $data['responseTime'],
            message: $data['error']
        );
    }
    
    /**
     * Create validation result collection
     */
    public static function validationCollection(array $overrides = []): ValidationResultCollection
    {
        $count = $overrides['count'] ?? 3;
        $results = [];
        
        for ($i = 0; $i < $count; $i++) {
            $results[] = self::validationResult([
                'url' => "https://example{$i}.com",
                'status' => $i === 0 ? LinkStatus::VALID : LinkStatus::BROKEN,
            ]);
        }
        
        return new ValidationResultCollection($results);
    }
    
    /**
     * Create test URLs
     */
    public static function testUrls(array $overrides = []): array
    {
        $type = $overrides['type'] ?? 'mixed';
        
        return match ($type) {
            'valid' => [
                'https://example.com',
                'http://test.org',
                './valid-file.md',
                '#valid-anchor',
            ],
            'invalid' => [
                'https://broken.example.com',
                'invalid-url',
                './missing-file.md',
                '#missing-anchor',
            ],
            'mixed' => [
                'https://example.com',
                'https://broken.example.com',
                './valid-file.md',
                './missing-file.md',
                '#valid-anchor',
                '#missing-anchor',
            ],
            default => ['https://example.com'],
        };
    }
    
    /**
     * Create report summary
     */
    public static function reportSummary(array $overrides = []): ReportSummary
    {
        $defaults = [
            'totalLinks' => 10,
            'brokenLinks' => 2,
            'validLinks' => 8,
            'timeoutLinks' => 0,
            'redirectLinks' => 0,
            'securityViolations' => 0,
            'executionTime' => 1.5,
            'successRate' => 80.0,
        ];
        
        $data = array_merge($defaults, $overrides);
        
        return new ReportSummary(
            totalLinks: $data['totalLinks'],
            brokenLinks: $data['brokenLinks'],
            validLinks: $data['validLinks'],
            timeoutLinks: $data['timeoutLinks'],
            redirectLinks: $data['redirectLinks'],
            securityViolations: $data['securityViolations'],
            executionTime: $data['executionTime'],
            successRate: $data['successRate']
        );
    }
}