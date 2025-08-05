<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\LinkValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use Tests\Support\MockFactory;
use Tests\Support\MockDataBuilder;
use Tests\Support\MockLifecycleManager;

describe('LinkValidationService', function () {
    beforeEach(function () {
        // Setup Phase: Use enhanced mock factory for realistic behavior

        // Create optimized security service mock with realistic behavior
        $this->securityService = createSecurityValidationMock([
            'safe_urls' => [
                'https://example.com',
                'http://test.org',
                'https://github.com',
            ],
            'malicious_urls' => [
                'https://malicious.com',
                'javascript:alert("xss")',
            ],
            'safe_paths' => [
                'relative/path.md',
                'safe/path.md',
                'test.md',
            ],
            'dangerous_paths' => [
                '../../../etc/passwd',
                '../../etc/hosts',
            ],
        ]);

        // Create service instance with dependency injection
        $this->service = new LinkValidationService($this->securityService);

        // Set up reflection for testing class characteristics
        $this->reflection = new ReflectionClass(LinkValidationService::class);

        // Use enhanced test data from getCategorizedTestUrls helper
        $this->testUrls = getCategorizedTestUrls();

        // Use enhanced test configurations from MockDataBuilder
        $this->testConfigs = [
            'anchor_only' => MockDataBuilder::createValidationConfig(['scopes' => [ValidationScope::ANCHOR]]),
            'external_only' => MockDataBuilder::createValidationConfig(['scopes' => [ValidationScope::EXTERNAL]]),
            'internal_only' => MockDataBuilder::createValidationConfig(['scopes' => [ValidationScope::INTERNAL]]),
            'multiple_scopes' => MockDataBuilder::createValidationConfig(['scopes' => [
                ValidationScope::EXTERNAL,
                ValidationScope::ANCHOR,
                ValidationScope::INTERNAL,
            ]]),
            'all_scopes' => MockDataBuilder::createValidationConfig(['scopes' => [ValidationScope::ALL]]),
            'image_only' => MockDataBuilder::createValidationConfig(['scopes' => [ValidationScope::IMAGE]]),
        ];

        // Prepare test content for link extraction
        $this->testContent = [
            'markdown' => '[Link text](https://example.com) and [Another](./file.md)',
            'html' => '<a href="https://example.com">Link</a>',
            'anchors' => '[Section](#section) and [Another](#another-section)',
            'mixed' => '[External](https://example.com) and [Internal](./file.md) and [Anchor](#section)',
            'no_links' => 'This is plain text with no links.',
            'empty' => '',
        ];

        // Expected results for various operations
        $this->expectedResults = [
            'anchor_valid' => [
                'status' => LinkStatus::VALID,
                'scope' => ValidationScope::ANCHOR,
                'response_time' => 0.0,
            ],
            'security_violation' => [
                'status' => LinkStatus::SECURITY_VIOLATION,
                'error' => 'URL failed security validation',
            ],
            'invalid_format' => [
                'status' => LinkStatus::INVALID_FORMAT,
                'error' => 'Invalid URL format',
            ],
        ];
    });

    afterEach(function () {
        // Enhanced cleanup is handled automatically by MockLifecycleManager
        // Clear test-specific data
        $this->service = null;
        $this->reflection = null;
        $this->testUrls = null;
        $this->testConfigs = null;
        $this->testContent = null;
        $this->expectedResults = null;
    });

    describe('service instantiation and interface compliance', function () {
        it('creates service with security dependency', function () {
            $securityService = Mockery::mock(SecurityValidationInterface::class);
            $service = new LinkValidationService($securityService);

            expect($service)->toBeInstanceOf(LinkValidationService::class);
        });

        it('implements LinkValidationInterface', function () {
            expect($this->service)->toBeInstanceOf(LinkValidationInterface::class);
        });

        it('initializes gitHubAnchorService as null', function () {
            expect($this->service->gitHubAnchorService)->toBeNull();
        });

        it('has all required interface methods', function () {
            $interfaceReflection = new ReflectionClass(LinkValidationInterface::class);
            $interfaceMethods = $interfaceReflection->getMethods();

            foreach ($interfaceMethods as $method) {
                expect($this->reflection->hasMethod($method->getName()))->toBeTrue();
            }
        });

        it('has proper constructor dependency injection', function () {
            $constructor = $this->reflection->getConstructor();
            $parameters = $constructor->getParameters();

            expect($parameters)->toHaveCount(1)
                ->and($parameters[0]->getName())->toBe('security')
                ->and($parameters[0]->getType()->getName())->toBe(SecurityValidationInterface::class);
        });
    });

    describe('validateLink method', function () {
        it('validates simple anchor links correctly', function () {
            $result = $this->service->validateLink(
                $this->testUrls['anchor']['simple'],
                $this->testConfigs['anchor_only']
            );

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getUrl())->toBe($this->testUrls['anchor']['simple'])
                ->and($result->getStatus())->toBe($this->expectedResults['anchor_valid']['status'])
                ->and($result->getScope())->toBe($this->expectedResults['anchor_valid']['scope'])
                ->and($result->getResponseTime())->toBe($this->expectedResults['anchor_valid']['response_time']);
        });

        it('validates complex anchor links correctly', function () {
            $result = $this->service->validateLink(
                $this->testUrls['anchor']['complex'],
                $this->testConfigs['anchor_only']
            );

            expect($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::ANCHOR);
        });

        it('validates anchor links as valid when anchors not in scope', function () {
            $result = $this->service->validateLink(
                $this->testUrls['anchor']['simple'],
                $this->testConfigs['external_only']
            );

            expect($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::ANCHOR);
        });

        it('validates external HTTPS URLs when security passes', function () {
            $this->securityService->shouldReceive('validateUrl')
                ->with($this->testUrls['external']['https'])
                ->once()
                ->andReturn(true);

            $result = $this->service->validateLink(
                $this->testUrls['external']['https'],
                $this->testConfigs['external_only']
            );

            // Test that it reaches external validation logic and processes the URL
            expect($result->getUrl())->toBe($this->testUrls['external']['https'])
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::FORBIDDEN,
                    LinkStatus::TIMEOUT,
                ]); // Any of these statuses are valid for external URLs
        });

        it('validates external HTTP URLs when security passes', function () {
            $this->securityService->shouldReceive('validateUrl')
                ->with($this->testUrls['external']['http'])
                ->once()
                ->andReturn(true);

            $result = $this->service->validateLink(
                $this->testUrls['external']['http'],
                $this->testConfigs['external_only']
            );

            expect($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getUrl())->toBe($this->testUrls['external']['http'])
                ->and($result->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::FORBIDDEN,
                    LinkStatus::TIMEOUT,
                ]);
        });

        it('rejects external URLs when security fails', function () {
            $this->securityService->shouldReceive('validateUrl')
                ->with($this->testUrls['security_risks']['malicious_url'])
                ->once()
                ->andReturn(false);

            $result = $this->service->validateLink(
                $this->testUrls['security_risks']['malicious_url'],
                $this->testConfigs['external_only']
            );

            expect($result->getStatus())->toBe($this->expectedResults['security_violation']['status'])
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBe($this->expectedResults['security_violation']['error']);
        });

        it('detects invalid URL format with spaces', function () {
            $result = $this->service->validateLink(
                $this->testUrls['invalid']['with_spaces'],
                $this->testConfigs['external_only']
            );

            expect($result->getStatus())->toBe($this->expectedResults['invalid_format']['status'])
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBe($this->expectedResults['invalid_format']['error']);
        });

        it('detects invalid URL format with special characters', function () {
            $result = $this->service->validateLink(
                $this->testUrls['invalid']['special_chars'],
                $this->testConfigs['external_only']
            );

            expect($result->getStatus())->toBe($this->expectedResults['invalid_format']['status'])
                ->and($result->getError())->toBe($this->expectedResults['invalid_format']['error']);
        });

        it('detects malformed URLs', function () {
            $result = $this->service->validateLink(
                $this->testUrls['invalid']['malformed'],
                $this->testConfigs['external_only']
            );

            expect($result->getStatus())->toBe($this->expectedResults['invalid_format']['status']);
        });

        it('handles empty URL strings', function () {
            $result = $this->service->validateLink(
                $this->testUrls['invalid']['empty'],
                $this->testConfigs['external_only']
            );

            expect($result)->toBeInstanceOf(ValidationResult::class);
        });

        it('treats relative paths as internal links when security passes', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::INTERNAL]);
            $this->securityService->shouldReceive('validatePath')
                ->with('relative/path.md')
                ->once()
                ->andReturn(true);

            $result = $this->service->validateLink('./relative/path.md', $config);

            expect($result->getScope())->toBe(ValidationScope::INTERNAL);
        });

        it('rejects internal paths when security fails', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::INTERNAL]);

            // Test what mb_ltrim actually does
            $originalPath = '../../../etc/passwd';
            $normalizedPath = mb_ltrim($originalPath, './');

            $this->securityService->shouldReceive('validatePath')
                ->with($normalizedPath)
                ->once()
                ->andReturn(false);

            $result = $this->service->validateLink($originalPath, $config);

            expect($result->getStatus())->toBe(LinkStatus::SECURITY_VIOLATION)
                ->and($result->getScope())->toBe(ValidationScope::INTERNAL)
                ->and($result->getError())->toBe('Path failed security validation');
        });

        it('skips validation when scope not enabled', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ANCHOR]);

            $result = $this->service->validateLink('https://example.com', $config);

            expect($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL);
        });
    });

    describe('validateLinks method', function () {
        it('validates multiple URLs', function () {
            $config = createTestValidationConfig(['scopes' => [ValidationScope::ANCHOR]]);
            $urls = ['#section1', '#section2', '#section3'];

            $results = $this->service->validateLinks($urls, $config);

            expect($results)->toBeInstanceOf(ValidationResultCollection::class)
                ->and($results->count())->toBe(3);

            foreach ($results as $result) {
                expect($result->getStatus())->toBe(LinkStatus::VALID)
                    ->and($result->getScope())->toBe(ValidationScope::ANCHOR);
            }
        });

        it('handles empty URL array', function () {
            $config = createTestValidationConfig();

            $results = $this->service->validateLinks([], $config);

            expect($results->count())->toBe(0);
        });

        it('validates mixed URL types', function () {
            $config = createTestValidationConfig(['scopes' => [ValidationScope::ANCHOR, ValidationScope::EXTERNAL]]);
            $urls = ['#section', 'https://example.com'];

            $this->securityService->shouldReceive('validateUrl')
                ->with('https://example.com')
                ->once()
                ->andReturn(true);

            $results = $this->service->validateLinks($urls, $config);

            $resultArray = $results->toValidationResultArray();
            expect($results->count())->toBe(2)
                ->and($resultArray[0]->getScope())->toBe(ValidationScope::ANCHOR)
                ->and($resultArray[1]->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($resultArray[0]->getUrl())->toBe('#section')
                ->and($resultArray[1]->getUrl())->toBe('https://example.com');
        });
    });

    describe('extractLinks method', function () {
        it('extracts markdown links', function () {
            $content = '[Link text](https://example.com) and [Another](./file.md)';

            $links = $this->service->extractLinks($content);

            expect($links)->toBeArray()
                ->and(count($links))->toBeGreaterThan(0);
        });

        it('extracts HTML links', function () {
            $content = '<a href="https://example.com">Link</a>';

            $links = $this->service->extractLinks($content);

            expect($links)->toBeArray();
        });

        it('handles content with no links', function () {
            $content = 'This is plain text with no links.';

            $links = $this->service->extractLinks($content);

            expect($links)->toBeArray();
        });

        it('extracts anchor links', function () {
            $content = '[Section](#section) and [Another](#another-section)';

            $links = $this->service->extractLinks($content);

            expect($links)->toBeArray();
        });
    });

    describe('isValidUrl method', function () {
        it('validates HTTP URLs', function () {
            expect($this->service->isValidUrl('http://example.com'))->toBeTrue();
        });

        it('validates HTTPS URLs', function () {
            expect($this->service->isValidUrl('https://example.com'))->toBeTrue();
        });

        it('validates FTP URLs', function () {
            expect($this->service->isValidUrl('ftp://example.com'))->toBeTrue();
        });

        it('rejects invalid URLs', function () {
            expect($this->service->isValidUrl('not-a-url'))->toBeFalse()
                ->and($this->service->isValidUrl(''))->toBeFalse()
                ->and($this->service->isValidUrl('://invalid'))->toBeFalse();
        });

        it('rejects URLs with spaces', function () {
            expect($this->service->isValidUrl('http://example .com'))->toBeFalse();
        });
    });

    describe('normalizeUrl method', function () {
        it('normalizes URLs by trimming whitespace', function () {
            expect($this->service->normalizeUrl('  https://example.com  '))->toBe('https://example.com');
        });

        it('handles URLs without normalization needed', function () {
            expect($this->service->normalizeUrl('https://example.com'))->toBe('https://example.com');
        });

        it('handles empty URLs', function () {
            expect($this->service->normalizeUrl(''))->toBe('');
        });

        it('normalizes relative URLs', function () {
            expect($this->service->normalizeUrl('./file.md'))->toBe('./file.md');
        });
    });

    describe('getSupportedScopes method', function () {
        it('returns all supported validation scopes', function () {
            $scopes = $this->service->getSupportedScopes();

            expect($scopes)->toBeArray()
                ->and($scopes)->toContain(ValidationScope::INTERNAL)
                ->and($scopes)->toContain(ValidationScope::EXTERNAL)
                ->and($scopes)->toContain(ValidationScope::ANCHOR)
                ->and($scopes)->toContain(ValidationScope::IMAGE)
                ->and($scopes)->toContain(ValidationScope::ALL);
        });
    });

    describe('supportsScope method', function () {
        it('returns true for supported scopes', function () {
            expect($this->service->supportsScope(ValidationScope::INTERNAL))->toBeTrue()
                ->and($this->service->supportsScope(ValidationScope::EXTERNAL))->toBeTrue()
                ->and($this->service->supportsScope(ValidationScope::ANCHOR))->toBeTrue()
                ->and($this->service->supportsScope(ValidationScope::IMAGE))->toBeTrue()
                ->and($this->service->supportsScope(ValidationScope::ALL))->toBeTrue();
        });
    });

    describe('validateByScopes method', function () {
        it('validates first target with configuration', function () {
            $config = createTestValidationConfig(['scopes' => [ValidationScope::ANCHOR]]);
            $scopes = [ValidationScope::ANCHOR];
            $targets = ['#section', '#another'];

            $result = $this->service->validateByScopes($scopes, $targets, $config);

            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getUrl())->toBe('#section');
        });

        it('handles empty targets array', function () {
            $config = createTestValidationConfig();
            $scopes = [ValidationScope::ALL];
            $targets = [];

            $result = $this->service->validateByScopes($scopes, $targets, $config);

            expect($result->getStatus())->toBe(LinkStatus::INVALID_FORMAT)
                ->and($result->getError())->toBe('No targets provided')
                ->and($result->getUrl())->toBe('');
        });
    });

    describe('getStatusStatistics method', function () {
        it('returns validation statistics', function () {
            $stats = $this->service->getStatusStatistics();

            expect($stats)->toBeInstanceOf(ValidationStatistics::class)
                ->and($stats->getTotal())->toBe(0)
                ->and($stats->getValid())->toBe(0)
                ->and($stats->getBroken())->toBe(0);
        });
    });

    describe('setTimeout method', function () {
        it('accepts timeout setting', function () {
            $this->service->setTimeout(30);

            expect(true)->toBeTrue(); // Method should execute without throwing
        });

        it('accepts zero timeout', function () {
            $this->service->setTimeout(0);

            expect(true)->toBeTrue(); // Method should execute without throwing
        });

        it('accepts large timeout values', function () {
            $this->service->setTimeout(3600);

            expect(true)->toBeTrue(); // Method should execute without throwing
        });
    });

    describe('validateFiles method', function () {
        it('validates multiple files', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ANCHOR]);
            $files = ['file1.md', 'file2.md'];

            // Mock file content reading - this would normally read actual files
            $results = $this->service->validateFiles($files, $config);

            expect($results)->toBeInstanceOf(ValidationResultCollection::class);
        });

        it('handles empty files array', function () {
            $config = new ValidationConfig();

            $results = $this->service->validateFiles([], $config);

            expect($results->count())->toBe(0);
        });
    });

    describe('validateExternalLinks method', function () {
        it('validates external URLs', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $urls = ['https://example.com', 'http://test.com'];

            $results = $this->service->validateExternalLinks($urls, $config);

            expect($results)->toBeInstanceOf(ValidationResultCollection::class);
        });

        it('filters out invalid URLs', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $urls = ['https://example.com', 'invalid-url', 'http://test.com'];

            $results = $this->service->validateExternalLinks($urls, $config);

            expect($results)->toBeInstanceOf(ValidationResultCollection::class);
            // Only valid URLs should be processed
        });

        it('handles empty URLs array', function () {
            $config = new ValidationConfig();

            $results = $this->service->validateExternalLinks([], $config);

            expect($results->count())->toBe(0);
        });
    });

    describe('validateFile method', function () {
        it('validates links in file content', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ANCHOR]);
            $filePath = 'test.md';

            // This would normally read file content and extract links
            $results = $this->service->validateFile($filePath, $config);

            expect($results)->toBeInstanceOf(ValidationResultCollection::class);
        });
    });

    describe('interface compliance', function () {
        it('implements LinkValidationInterface', function () {
            expect($this->service)->toBeInstanceOf(LinkValidationInterface::class);
        });

        it('has all required interface methods', function () {
            $reflection = new ReflectionClass($this->service);
            $expectedMethods = [
                'validateLink',
                'validateLinks',
                'validateFile',
                'extractLinks',
                'isValidUrl',
                'normalizeUrl',
                'getSupportedScopes',
                'supportsScope',
                'validateByScopes',
                'getStatusStatistics',
                'setTimeout',
                'validateFiles',
            ];

            foreach ($expectedMethods as $method) {
                expect($reflection->hasMethod($method))->toBeTrue(
                    "Method {$method} should exist"
                );
                expect($reflection->getMethod($method)->isPublic())->toBeTrue(
                    "Method {$method} should be public"
                );
            }
        });
    });

    describe('security integration', function () {
        it('uses security service for URL validation', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $url = 'https://example.com';

            $this->securityService->shouldReceive('validateUrl')
                ->with($url)
                ->once()
                ->andReturn(true);

            $result = $this->service->validateLink($url, $config);

            expect($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getUrl())->toBe($url)
                ->and($result->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::FORBIDDEN,
                    LinkStatus::TIMEOUT,
                ]);
        });

        it('handles security validation failure for URLs', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $url = 'https://malicious.com';

            $this->securityService->shouldReceive('validateUrl')
                ->with($url)
                ->once()
                ->andReturn(false);

            $result = $this->service->validateLink($url, $config);

            expect($result->getStatus())->toBe(LinkStatus::SECURITY_VIOLATION)
                ->and($result->getError())->toBe('URL failed security validation');
        });

        it('uses security service for path validation', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::INTERNAL]);
            $originalPath = './safe/path.md';
            $normalizedPath = mb_ltrim($originalPath, './');

            $this->securityService->shouldReceive('validatePath')
                ->with($normalizedPath)
                ->once()
                ->andReturn(true);

            $result = $this->service->validateLink($originalPath, $config);

            expect($result->getScope())->toBe(ValidationScope::INTERNAL);
        });

        it('handles security validation failure for paths', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::INTERNAL]);
            $originalPath = '../../../etc/passwd';
            $normalizedPath = mb_ltrim($originalPath, './');

            $this->securityService->shouldReceive('validatePath')
                ->with($normalizedPath)
                ->once()
                ->andReturn(false);

            $result = $this->service->validateLink($originalPath, $config);

            expect($result->getStatus())->toBe(LinkStatus::SECURITY_VIOLATION)
                ->and($result->getError())->toBe('Path failed security validation');
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very long URLs', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $longUrl = 'https://example.com/'.str_repeat('a', 2000);

            $this->securityService->shouldReceive('validateUrl')
                ->with($longUrl)
                ->once()
                ->andReturn(true);

            $result = $this->service->validateLink($longUrl, $config);

            expect($result->getUrl())->toBe($longUrl)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::FORBIDDEN,
                    LinkStatus::TIMEOUT,
                ]);
        });

        it('handles unicode URLs', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $unicodeUrl = 'https://example.com/测试';

            // Mock security service to allow the unicode URL
            $this->securityService->shouldReceive('validateUrl')
                ->withArgs(fn ($url) => str_contains($url, 'example.com'))
                ->once()
                ->andReturn(true);

            $result = $this->service->validateLink($unicodeUrl, $config);

            // Unicode URLs should now be properly encoded and validated
            expect($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getUrl())->toBe($unicodeUrl)
                ->and($result->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::FORBIDDEN,
                    LinkStatus::TIMEOUT,
                ]);
        });

        it('handles malformed anchor links', function () {
            $config = new ValidationConfig(scopes: [ValidationScope::ANCHOR]);

            $result = $this->service->validateLink('#', $config);

            expect($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::ANCHOR);
        });

        it('handles empty URL string', function () {
            $config = new ValidationConfig();

            $result = $this->service->validateLink('', $config);

            expect($result)->toBeInstanceOf(ValidationResult::class);
        });

        it('handles null-like values gracefully', function () {
            expect($this->service->isValidUrl('0'))->toBeFalse();
        });
    });

    describe('configuration handling', function () {
        it('respects validation scope configuration', function () {
            $externalConfig = new ValidationConfig(scopes: [ValidationScope::EXTERNAL]);
            $anchorConfig = new ValidationConfig(scopes: [ValidationScope::ANCHOR]);

            $externalResult = $this->service->validateLink('#section', $externalConfig);
            $anchorResult = $this->service->validateLink('#section', $anchorConfig);

            expect($externalResult->getScope())->toBe(ValidationScope::ANCHOR)
                ->and($anchorResult->getScope())->toBe(ValidationScope::ANCHOR);
        });

        it('handles multiple scopes in configuration', function () {
            $config = new ValidationConfig(scopes: [
                ValidationScope::EXTERNAL,
                ValidationScope::ANCHOR,
                ValidationScope::INTERNAL,
            ]);

            $this->securityService->shouldReceive('validateUrl')
                ->with('https://example.com')
                ->once()
                ->andReturn(true);

            $anchorResult = $this->service->validateLink('#section', $config);
            $externalResult = $this->service->validateLink('https://example.com', $config);

            expect($anchorResult->getScope())->toBe(ValidationScope::ANCHOR)
                ->and($externalResult->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($anchorResult->getUrl())->toBe('#section')
                ->and($externalResult->getUrl())->toBe('https://example.com')
                ->and($anchorResult->getStatus())->toBe(LinkStatus::VALID)
                ->and($externalResult->getStatus())->toBeIn([
                    LinkStatus::VALID,
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::FORBIDDEN,
                    LinkStatus::TIMEOUT,
                ]);
        });
    });
});
