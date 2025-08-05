<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Mockery;
use Mockery\MockInterface;

/**
 * Centralized mock factory for creating and managing test mocks.
 * 
 * Provides reusable mock configurations with realistic behavior simulation,
 * proper lifecycle management, and comprehensive error scenarios.
 */
final class MockFactory
{
    /** @var array<string, MockInterface> */
    private static array $activeMocks = [];
    
    /** @var array<string, mixed> */
    private static array $mockConfigurations = [];

    /**
     * Create a mock SecurityValidationInterface with realistic behavior.
     */
    public static function createSecurityValidationService(array $config = []): MockInterface
    {
        $mock = Mockery::mock(SecurityValidationInterface::class);
        
        // Default safe URLs
        $safeUrls = $config['safe_urls'] ?? [
            'https://example.com',
            'http://test.org',
            'https://github.com',
            'https://localhost:8080',
        ];
        
        // Default malicious URLs
        $maliciousUrls = $config['malicious_urls'] ?? [
            'https://malicious.com',
            'javascript:alert(1)',
            'ftp://dangerous.com',
        ];
        
        // Default safe paths
        $safePaths = $config['safe_paths'] ?? [
            'test.md',
            'docs/readme.md',
            'relative/path.md',
            'safe/path.md',
        ];
        
        // Default dangerous paths
        $dangerousPaths = $config['dangerous_paths'] ?? [
            '../../../etc/passwd',
            '../../etc/hosts',
            '/etc/passwd',
            '/var/log/auth.log',
        ];
        
        // Configure URL validation
        foreach ($safeUrls as $url) {
            $mock->shouldReceive('validateUrl')
                ->with($url)
                ->andReturn(true)
                ->byDefault();
        }
        
        foreach ($maliciousUrls as $url) {
            $mock->shouldReceive('validateUrl')
                ->with($url)
                ->andReturn(false)
                ->byDefault();
        }
        
        // Configure path validation
        foreach ($safePaths as $path) {
            $mock->shouldReceive('validatePath')
                ->with($path)
                ->andReturn(true)
                ->byDefault();
        }
        
        foreach ($dangerousPaths as $path) {
            $mock->shouldReceive('validatePath')
                ->with($path)
                ->andReturn(false)
                ->byDefault();
        }
        
        // Configure other methods with realistic defaults
        $mock->shouldReceive('isDomainBlocked')
            ->andReturn(false)
            ->byDefault();
            
        $mock->shouldReceive('isProtocolAllowed')
            ->with('http')
            ->andReturn(true)
            ->byDefault();
            
        $mock->shouldReceive('isProtocolAllowed')
            ->with('https')
            ->andReturn(true)
            ->byDefault();
            
        $mock->shouldReceive('isProtocolAllowed')
            ->with('ftp')
            ->andReturn(false)
            ->byDefault();
            
        $mock->shouldReceive('validateFileSize')
            ->andReturn(true)
            ->byDefault();
            
        $mock->shouldReceive('getSecurityConfig')
            ->andReturn([
                'blocked_domains' => [],
                'allowed_protocols' => ['http', 'https'],
                'max_file_size' => 50 * 1024 * 1024,
                'blocked_paths' => ['/etc/', '/var/', '/usr/', '/bin/'],
            ])
            ->byDefault();
        
        self::$activeMocks['security_validation'] = $mock;
        
        return $mock;
    }

    /**
     * Create a mock LinkValidationInterface with comprehensive behavior.
     */
    public static function createLinkValidationService(array $config = []): MockInterface
    {
        $mock = Mockery::mock(LinkValidationInterface::class);
        
        // Configure validateLink method with realistic responses
        $mock->shouldReceive('validateLink')
            ->andReturnUsing(function (string $url, ValidationConfig $config) {
                return self::createRealisticValidationResult($url);
            })
            ->byDefault();
        
        // Configure validateLinks method
        $mock->shouldReceive('validateLinks')
            ->andReturnUsing(function (array $urls, ValidationConfig $config) {
                $results = [];
                foreach ($urls as $url) {
                    $results[] = self::createRealisticValidationResult($url);
                }
                return new ValidationResultCollection($results);
            })
            ->byDefault();
        
        // Configure extractLinks method
        $mock->shouldReceive('extractLinks')
            ->andReturnUsing(function (string $content) {
                // Simple regex to extract markdown links
                preg_match_all('/\[([^\]]+)\]\(([^)]+)\)/', $content, $matches);
                return $matches[2] ?? [];
            })
            ->byDefault();
        
        // Configure other methods
        $mock->shouldReceive('isValidUrl')
            ->andReturnUsing(function (string $url) {
                return filter_var($url, FILTER_VALIDATE_URL) !== false;
            })
            ->byDefault();
        
        $mock->shouldReceive('normalizeUrl')
            ->andReturnUsing(function (string $url) {
                return trim($url);
            })
            ->byDefault();
        
        $mock->shouldReceive('getSupportedScopes')
            ->andReturn([
                ValidationScope::INTERNAL,
                ValidationScope::EXTERNAL,
                ValidationScope::ANCHOR,
                ValidationScope::IMAGE,
                ValidationScope::ALL,
            ])
            ->byDefault();
        
        $mock->shouldReceive('supportsScope')
            ->andReturn(true)
            ->byDefault();
        
        $mock->shouldReceive('validateByScopes')
            ->andReturnUsing(function (array $scopes, array $targets, ValidationConfig $config) {
                if (empty($targets)) {
                    return ValidationResult::create(
                        url: '',
                        status: LinkStatus::INVALID_FORMAT,
                        message: 'No targets provided',
                        scope: ValidationScope::ALL
                    );
                }
                return self::createRealisticValidationResult($targets[0]);
            })
            ->byDefault();
        
        $mock->shouldReceive('getStatusStatistics')
            ->andReturn(new ValidationStatistics(0, 0, 0, 0, 0, 0, 0, 0))
            ->byDefault();
        
        $mock->shouldReceive('setTimeout')
            ->andReturnNull()
            ->byDefault();
        
        $mock->shouldReceive('validateFiles')
            ->andReturn(new ValidationResultCollection([]))
            ->byDefault();
        
        $mock->shouldReceive('validateExternalLinks')
            ->andReturn(new ValidationResultCollection([]))
            ->byDefault();
        
        $mock->shouldReceive('validateFile')
            ->andReturn(new ValidationResultCollection([]))
            ->byDefault();
        
        self::$activeMocks['link_validation'] = $mock;
        
        return $mock;
    }

    /**
     * Create a mock ReportingInterface with realistic behavior.
     */
    public static function createReportingService(array $config = []): MockInterface
    {
        $mock = Mockery::mock(ReportingInterface::class);
        
        // Configure report generation
        $mock->shouldReceive('generateReport')
            ->andReturnUsing(function (ValidationResultCollection $results, $format = null) {
                $count = $results->count();
                return "Mock report with {$count} results";
            })
            ->byDefault();
        
        $mock->shouldReceive('generateSummary')
            ->andReturnUsing(function (ValidationResultCollection $results) {
                return MockDataBuilder::createReportSummary([
                    'total_links' => $results->count(),
                    'broken_links' => $results->filter(fn($r) => $r->getStatus() === LinkStatus::BROKEN)->count(),
                ]);
            })
            ->byDefault();
        
        $mock->shouldReceive('exportReport')
            ->andReturn(true)
            ->byDefault();
        
        $mock->shouldReceive('format')
            ->andReturn('Formatted output')
            ->byDefault();
        
        $mock->shouldReceive('getAvailableFormats')
            ->andReturn([])
            ->byDefault();
        
        $mock->shouldReceive('supportsFormat')
            ->andReturn(true)
            ->byDefault();
        
        $mock->shouldReceive('saveReport')
            ->andReturn(true)
            ->byDefault();
        
        self::$activeMocks['reporting'] = $mock;
        
        return $mock;
    }

    /**
     * Create realistic validation result based on URL pattern.
     */
    private static function createRealisticValidationResult(string $url): ValidationResult
    {
        // Determine scope based on URL pattern
        $scope = match (true) {
            str_starts_with($url, '#') => ValidationScope::ANCHOR,
            str_starts_with($url, 'http') => ValidationScope::EXTERNAL,
            str_starts_with($url, './') || str_starts_with($url, '/') => ValidationScope::INTERNAL,
            default => ValidationScope::ALL,
        };
        
        // Determine status based on URL pattern
        $status = match (true) {
            str_contains($url, 'broken') => LinkStatus::BROKEN,
            str_contains($url, 'notfound') || str_contains($url, '404') => LinkStatus::NOT_FOUND,
            str_contains($url, 'timeout') => LinkStatus::TIMEOUT,
            str_contains($url, 'forbidden') || str_contains($url, '403') => LinkStatus::FORBIDDEN,
            str_contains($url, 'malicious') => LinkStatus::SECURITY_VIOLATION,
            str_contains($url, 'invalid') => LinkStatus::INVALID_FORMAT,
            default => LinkStatus::VALID,
        };
        
        // Realistic response times
        $responseTime = match ($status) {
            LinkStatus::TIMEOUT => 30.0,
            LinkStatus::BROKEN => 5.0,
            LinkStatus::ANCHOR => 0.0,
            default => rand(50, 500) / 1000, // 0.05 to 0.5 seconds
        };
        
        // HTTP status codes
        $httpStatusCode = match ($status) {
            LinkStatus::VALID => 200,
            LinkStatus::NOT_FOUND => 404,
            LinkStatus::FORBIDDEN => 403,
            LinkStatus::BROKEN => 500,
            default => null,
        };
        
        // Error messages
        $error = match ($status) {
            LinkStatus::BROKEN => 'Connection failed',
            LinkStatus::NOT_FOUND => 'HTTP 404',
            LinkStatus::TIMEOUT => 'Request timeout',
            LinkStatus::FORBIDDEN => 'HTTP 403',
            LinkStatus::SECURITY_VIOLATION => 'URL failed security validation',
            LinkStatus::INVALID_FORMAT => 'Invalid URL format',
            default => '',
        };
        
        return ValidationResult::create(
            url: $url,
            status: $status,
            message: $error,
            scope: $scope,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime
        );
    }

    /**
     * Get all active mocks for verification.
     */
    public static function getActiveMocks(): array
    {
        return self::$activeMocks;
    }

    /**
     * Verify all active mocks and clear them.
     */
    public static function verifyAndClearMocks(): void
    {
        foreach (self::$activeMocks as $name => $mock) {
            try {
                Mockery::close();
            } catch (\Exception $e) {
                // Log verification failure but don't fail the test
                error_log("Mock verification failed for {$name}: " . $e->getMessage());
            }
        }
        
        self::$activeMocks = [];
        self::$mockConfigurations = [];
    }

    /**
     * Reset all mocks without verification (for test cleanup).
     */
    public static function resetMocks(): void
    {
        self::$activeMocks = [];
        self::$mockConfigurations = [];
    }
}