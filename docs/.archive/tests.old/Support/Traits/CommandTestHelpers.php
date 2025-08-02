<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use Exception;
use Mockery;
use Mockery\MockInterface;

/**
 * Test implementation extracted from tests.old/Traits/CommandTestHelpers.php
 */
trait CommandTestHelpers
{
    /**
     * Create a mock LinkValidationInterface with common expectations.
     */
    protected function createMockLinkValidation(): MockInterface
    {
        return Mockery::mock(LinkValidationInterface::class);
    }

    /**
     * Create a mock ReportingInterface with common expectations.
     */
    protected function createMockReporting(): MockInterface
    {
        return Mockery::mock(ReportingInterface::class);
    }

    /**
     * Create a sample ValidationConfig for testing.
     */
    protected function createSampleValidationConfig(array $overrides = []): ValidationConfig
    {
        return ValidationConfig::create(array_merge([
            'timeout' => 30,
            'concurrent_requests' => 5,
            'follow_redirects' => true,
            'cache_enabled' => false,
        ], $overrides));
    }

    /**
     * Create a sample ValidationResult for testing.
     */
    protected function createSampleValidationResult(array $overrides = []): ValidationResult
    {
        $defaults = [
            'url' => 'https://example.com',
            'scope' => ValidationScope::EXTERNAL,
            'httpStatusCode' => 200,
            'responseTime' => 150.0,
            'metadata' => [],
        ];

        $merged = array_merge($defaults, $overrides);

        return ValidationResult::success(
            $merged['url'],
            $merged['scope'],
            $merged['httpStatusCode'],
            $merged['responseTime'],
            $merged['metadata']
        );
    }

    /**
     * Set up mock expectations for successful validation.
     */
    protected function expectSuccessfulValidation(
        MockInterface $mockValidation,
        MockInterface $mockReporting,
        array $expectedResults = []
    ): void {
        $results = $expectedResults ?: [$this->createSampleValidationResult()->toArray()];

        $mockValidation->shouldReceive('validateFiles')
            ->once()
            ->andReturn($results);

        $mockReporting->shouldReceive('generateReport')
            ->once()
            ->andReturn(0);
    }

    /**
     * Set up mock expectations for validation with broken links.
     */
    protected function expectValidationWithBrokenLinks(
        MockInterface $mockValidation,
        MockInterface $mockReporting,
        int $brokenLinkCount = 1
    ): void {
        $brokenResult = ValidationResult::failure(
            'https://broken.example.com',
            ValidationScope::EXTERNAL,
            LinkStatus::BROKEN,
            'Not Found',
            404,
            null,
            1000.0
        );

        $results = [$brokenResult->toArray()];

        $mockValidation->shouldReceive('validateFiles')
            ->once()
            ->andReturn($results);

        $mockReporting->shouldReceive('generateReport')
            ->once()
            ->andReturn(1);
    }

    /**
     * Set up mock expectations for validation failure.
     */
    protected function expectValidationFailure(
        MockInterface $mockValidation,
        string $exceptionMessage = 'Validation failed'
    ): void {
        $mockValidation->shouldReceive('validateFiles')
            ->once()
            ->andThrow(new Exception($exceptionMessage));
    }

    /**
     * Assert that command output contains expected success messages.
     */
    protected function assertSuccessOutput(): void
    {
        $this->assertStringContainsString('✅', $this->getOutput());
    }

    /**
     * Assert that command output contains expected error messages.
     */
    protected function assertErrorOutput(): void
    {
        $this->assertStringContainsString('❌', $this->getOutput());
    }

    /**
     * Assert that command output contains expected warning messages.
     */
    protected function assertWarningOutput(): void
    {
        $this->assertStringContainsString('⚠️', $this->getOutput());
    }

    /**
     * Create test files for command testing.
     */
    protected function createTestFiles(array $files): array
    {
        $createdFiles = [];

        foreach ($files as $path => $content) {
            $fullPath = $this->createTestFile($path, $content);
            $createdFiles[] = $fullPath;
        }

        return $createdFiles;
    }

    /**
     * Clean up command test files after command testing.
     */
    protected function cleanupCommandTestFiles(array $files): void
    {
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Get command output for assertions.
     */
    protected function getOutput(): string
    {
        return $this->artisan->output();
    }

    /**
     * Assert command exit code.
     */
    protected function assertExitCode(int $expectedCode): void
    {
        $this->assertEquals($expectedCode, $this->artisan->getExitCode());
    }

    /**
     * Create command arguments array.
     */
    protected function createCommandArguments(array $arguments = [], array $options = []): array
    {
        return array_merge($arguments, $options);
    }

    /**
     * Set up common command test environment.
     */
    protected function setUpCommandTest(): void
    {
        // Disable output buffering for command tests
        config(['app.debug' => false]);

        // Set up test-specific configurations
        config(['validate-links.cache.enabled' => false]);
        config(['validate-links.logging.enabled' => false]);
    }

    /**
     * Tear down command test environment.
     */
    protected function tearDownCommandTest(): void
    {
        // Clean up any global state
        Mockery::close();
    }
}
