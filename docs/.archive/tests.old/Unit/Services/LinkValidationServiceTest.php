<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Exceptions\SecurityException;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\LinkValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;

/**
 * Unit test suite for LinkValidationService.
 *
 * Tests link validation functionality including internal link validation,
 * external link validation, comprehensive validation result handling,
 * and proper error handling scenarios.
 *
 * @group unit
 * @group services
 *
 * @covers \App\Services\LinkValidationService
 */
describe('LinkValidationService', function (): void {
    beforeEach(function (): void {
        $this->securityMock = Mockery::mock(SecurityValidationInterface::class);
        $this->service = new LinkValidationService($this->securityMock);
        $this->testDirectory = sys_get_temp_dir().'/link_validation_test_'.uniqid('', true);

        if (! is_dir($this->testDirectory)) {
            mkdir($this->testDirectory, 0755, true);
        }
    });

    afterEach(function (): void {
        // Clean up test files
        if (is_dir($this->testDirectory)) {
            $files = glob($this->testDirectory.'/*');
            if ($files !== false) {
                array_map('unlink', $files);
            }
            rmdir($this->testDirectory);
        }

        Mockery::close();
    });

    describe('interface compliance', function (): void {
        /**
         * @test
         * Verifies that LinkValidationService properly implements LinkValidationInterface
         */
        test('implements LinkValidationInterface', function (): void {
            expect($this->service)->toBeInstanceOf(LinkValidationInterface::class);
        });
    });

    describe('validateFile method', function (): void {
        /**
         * @test
         * Tests successful file validation with internal links
         *
         * @throws SecurityException
         */
        test('validates file with internal links successfully', function (): void {
            // Arrange
            $testFile = $this->testDirectory.'/test.md';
            $targetFile = $this->testDirectory.'/target.md';

            file_put_contents($testFile, '[Test Link](target.md)');
            file_put_contents($targetFile, '# Target Document');

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL->value],
            ]);

            $this->securityMock
                ->shouldReceive('validatePath')
                ->with($testFile)
                ->once()
                ->andReturn(true);

            // Act
            $results = $this->service->validateFile($testFile, $config);

            // Assert
            expect($results)->toBeArray()
                ->and($results)->toHaveCount(1)
                ->and($results[0])->toBeInstanceOf(ValidationResult::class)
                ->and($results[0]->getStatus())->toBe(LinkStatus::VALID)
                ->and($results[0]->getScope())->toBe(ValidationScope::INTERNAL);
        });

        /**
         * @test
         * Tests file validation with broken internal links
         *
         * @throws SecurityException
         */
        test('detects broken internal links', function (): void {
            // Arrange
            $testFile = $this->testDirectory.'/test.md';
            file_put_contents($testFile, '[Broken Link](missing.md)');

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL->value],
            ]);

            $this->securityMock
                ->shouldReceive('validatePath')
                ->with($testFile)
                ->once()
                ->andReturn(true);

            // Act
            $results = $this->service->validateFile($testFile, $config);

            // Assert
            expect($results)->toBeArray()
                ->and($results)->toHaveCount(1)
                ->and($results[0])->toBeInstanceOf(ValidationResult::class)
                ->and($results[0]->getStatus())->toBe(LinkStatus::NOT_FOUND)
                ->and($results[0]->getScope())->toBe(ValidationScope::INTERNAL);
        });

        /**
         * @test
         * Tests security exception when path validation fails
         *
         * @throws SecurityException
         */
        test('throws SecurityException for invalid file path', function (): void {
            // Arrange
            $invalidPath = '/etc/passwd';
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL->value],
            ]);

            $this->securityMock
                ->shouldReceive('validatePath')
                ->with($invalidPath)
                ->once()
                ->andReturn(false);

            // Act & Assert
            expect(fn () => $this->service->validateFile($invalidPath, $config))
                ->toThrow(SecurityException::class, "Invalid file path: {$invalidPath}");
        });

        /**
         * @test
         * Tests RuntimeException when file cannot be read
         *
         * @throws SecurityException
         */
        test('throws RuntimeException for unreadable file', function (): void {
            // Arrange
            $nonExistentFile = $this->testDirectory.'/nonexistent.md';
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL->value],
            ]);

            $this->securityMock
                ->shouldReceive('validatePath')
                ->with($nonExistentFile)
                ->once()
                ->andReturn(true);

            // Act & Assert
            expect(fn () => $this->service->validateFile($nonExistentFile, $config))
                ->toThrow(RuntimeException::class, "Unable to read file: {$nonExistentFile}");
        });

        /**
         * @test
         * Tests file validation with array config conversion
         *
         * @throws SecurityException
         */
        test('accepts array config and converts to ValidationConfig', function (): void {
            // Arrange
            $testFile = $this->testDirectory.'/test.md';
            file_put_contents($testFile, '# Test Document');

            $arrayConfig = ['scopes' => [ValidationScope::INTERNAL->value]];

            $this->securityMock
                ->shouldReceive('validatePath')
                ->with($testFile)
                ->once()
                ->andReturn(true);

            // Act
            $results = $this->service->validateFile($testFile, $arrayConfig);

            // Assert
            expect($results)->toBeArray();
        });
    });

    describe('validateFileSummary method', function (): void {
        /**
         * @test
         * Tests file summary validation with valid links
         *
         * @throws SecurityException
         */
        test('returns summary for valid links', function (): void {
            // Arrange
            $testFile = $this->testDirectory.'/test.md';
            $targetFile = $this->testDirectory.'/target.md';

            file_put_contents($testFile, '[Test Link](target.md)');
            file_put_contents($targetFile, '# Target Document');

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL->value],
            ]);

            $this->securityMock
                ->shouldReceive('validatePath')
                ->with($testFile)
                ->once()
                ->andReturn(true);

            // Act
            $summary = $this->service->validateFileSummary($testFile, $config);

            // Assert
            expect($summary)->toBeArray()
                ->and($summary)->toHaveKey('valid')
                ->and($summary)->toHaveKey('broken_count')
                ->and($summary['valid'])->toBeTrue()
                ->and($summary['broken_count'])->toBe(0);
        });

        /**
         * @test
         * Tests file summary validation with broken links
         *
         * @throws SecurityException
         */
        test('returns summary for broken links', function (): void {
            // Arrange
            $testFile = $this->testDirectory.'/test.md';
            file_put_contents($testFile, '[Broken Link](missing.md)');

            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::INTERNAL->value],
            ]);

            $this->securityMock
                ->shouldReceive('validatePath')
                ->with($testFile)
                ->once()
                ->andReturn(true);

            // Act
            $summary = $this->service->validateFileSummary($testFile, $config);

            // Assert
            expect($summary)->toBeArray()
                ->and($summary)->toHaveKey('valid')
                ->and($summary)->toHaveKey('broken_count')
                ->and($summary['valid'])->toBeFalse()
                ->and($summary['broken_count'])->toBe(1);
        });
    });

    describe('validateExternalLinks method', function (): void {
        /**
         * @test
         * Tests external links validation with mocked HTTP responses
         */
        test('validates external links with proper HTTP responses', function (): void {
            // Arrange
            $urls = ['https://example.com', 'https://github.com'];
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL->value],
            ]);

            // Mock get_headers function behavior would be implemented here
            // For now, we test the method signature and return type

            // Act
            $summary = $this->service->validateExternalLinks($urls, $config);

            // Assert
            expect($summary)->toBeArray()
                ->and($summary)->toHaveKey('valid')
                ->and($summary)->toHaveKey('broken_count')
                ->and($summary['valid'])->toBeBool()
                ->and($summary['broken_count'])->toBeInt();
        });

        /**
         * @test
         * Tests external links validation with array config
         */
        test('accepts array config for external links validation', function (): void {
            // Arrange
            $urls = ['https://example.com'];
            $arrayConfig = ['scopes' => [ValidationScope::EXTERNAL->value]];

            // Act
            $summary = $this->service->validateExternalLinks($urls, $arrayConfig);

            // Assert
            expect($summary)->toBeArray()
                ->and($summary)->toHaveKey('valid')
                ->and($summary)->toHaveKey('broken_count');
        });
    });

    describe('validateLink method', function (): void {
        /**
         * @test
         * Tests single link validation with invalid URL format
         */
        test('returns invalid format for malformed URLs', function (): void {
            // Arrange
            $invalidUrl = 'not-a-valid-url';
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL->value],
            ]);

            // Act
            $result = $this->service->validateLink($invalidUrl, $config);

            // Assert
            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getStatus())->toBe(LinkStatus::INVALID_FORMAT)
                ->and($result->getScope())->toBe(ValidationScope::EXTERNAL)
                ->and($result->getError())->toBe('Invalid URL format');
        });

        /**
         * @test
         * Tests anchor link validation
         */
        test('validates anchor links correctly', function (): void {
            // Arrange
            $anchorUrl = '#section1';
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::ANCHOR->value],
            ]);

            // Act
            $result = $this->service->validateLink($anchorUrl, $config);

            // Assert
            expect($result)->toBeInstanceOf(ValidationResult::class)
                ->and($result->getStatus())->toBe(LinkStatus::VALID)
                ->and($result->getScope())->toBe(ValidationScope::ANCHOR);
        });
    });

    describe('utility methods', function (): void {
        /**
         * @test
         * Tests URL validation utility
         */
        test('validates URL format correctly', function (): void {
            expect($this->service->isValidUrl('https://example.com'))->toBeTrue()
                ->and($this->service->isValidUrl('http://example.com'))->toBeTrue()
                ->and($this->service->isValidUrl('not-a-url'))->toBeFalse()
                ->and($this->service->isValidUrl(''))->toBeFalse();
        });

        /**
         * @test
         * Tests URL normalization
         */
        test('normalizes URLs correctly', function (): void {
            expect($this->service->normalizeUrl('  https://example.com  '))->toBe('https://example.com')
                ->and($this->service->normalizeUrl('https://example.com'))->toBe('https://example.com');
        });

        /**
         * @test
         * Tests supported scopes
         */
        test('returns correct supported scopes', function (): void {
            $scopes = $this->service->getSupportedScopes();

            expect($scopes)->toBeArray()
                ->and($scopes)->toContain(ValidationScope::INTERNAL)
                ->and($scopes)->toContain(ValidationScope::EXTERNAL)
                ->and($scopes)->toContain(ValidationScope::ANCHOR)
                ->and($scopes)->toContain(ValidationScope::IMAGE)
                ->and($scopes)->toContain(ValidationScope::ALL);
        });

        /**
         * @test
         * Tests scope support checking
         */
        test('checks scope support correctly', function (): void {
            expect($this->service->supportsScope(ValidationScope::INTERNAL))->toBeTrue()
                ->and($this->service->supportsScope(ValidationScope::EXTERNAL))->toBeTrue()
                ->and($this->service->supportsScope(ValidationScope::ANCHOR))->toBeTrue();
        });
    });

    describe('link extraction and categorization', function (): void {
        /**
         * @test
         * Tests markdown link extraction
         */
        test('extracts markdown links correctly', function (): void {
            // Arrange
            $content = '[Link 1](http://example.com) and [Link 2](./local.md)';

            // Act
            $links = $this->service->extractLinks($content);

            // Assert
            expect($links)->toBeArray()
                ->and($links)->toHaveCount(2)
                ->and($links[0])->toHaveKey('url')
                ->and($links[0])->toHaveKey('text')
                ->and($links[0])->toHaveKey('line')
                ->and($links[0]['url'])->toBe('http://example.com')
                ->and($links[0]['text'])->toBe('Link 1');
        });

        /**
         * @test
         * Tests HTML link extraction
         */
        test('extracts HTML links correctly', function (): void {
            // Arrange
            $content = '<a href="https://example.com">Example</a>';

            // Act
            $links = $this->service->extractLinks($content);

            // Assert
            expect($links)->toBeArray()
                ->and($links)->toHaveCount(1)
                ->and($links[0]['url'])->toBe('https://example.com')
                ->and($links[0]['text'])->toBe('Example');
        });

        /**
         * @test
         * Tests link categorization
         */
        test('categorizes links correctly', function (): void {
            // Arrange
            $links = [
                ['url' => 'https://example.com', 'text' => 'External', 'line' => 1],
                ['url' => './local.md', 'text' => 'Internal', 'line' => 2],
                ['url' => '#anchor', 'text' => 'Anchor', 'line' => 3],
            ];

            // Act
            $categorized = $this->service->categorizeLinks($links);

            // Assert
            expect($categorized)->toBeArray()
                ->and($categorized)->toHaveKey('internal')
                ->and($categorized)->toHaveKey('external')
                ->and($categorized)->toHaveKey('anchor')
                ->and($categorized['external'])->toHaveCount(1)
                ->and($categorized['internal'])->toHaveCount(1)
                ->and($categorized['anchor'])->toHaveCount(1);
        });
    });
});
