<?php

declare(strict_types=1);

namespace Tests\E2e;

use App\Enums\ValidationScope;
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use App\Services\ValueObjects\ValidationConfig;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\HttpTestHelpers;
use old\Support\Traits\ValidationTestHelpers;

/**
 * End-to-end test suite for error handling and edge cases.
 *
 * Uses REAL implementations to test actual error scenarios.
 *
 * @group e2e
 * @group error-handling
 *
 * @covers \App\Commands\ValidateCommand
 */
uses(FileTestHelpers::class, HttpTestHelpers::class, ValidationTestHelpers::class);

describe('Error Handling and Edge Cases', function (): void {

    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    describe('real error handling scenarios', function (): void {
        test('handles broken external links gracefully', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create a file with broken external links
            $this->createTestFile('broken-links.md', '
# Test Document with Broken Links

This document contains various broken links:

- [Non-existent domain](https://this-domain-does-not-exist-12345.com)
- [Invalid URL format](not-a-valid-url)

## Valid Links for Comparison

- [Working HTTPBin](https://httpbin.org/status/200)
            ');

            /** @var ValidationConfig $config */
            $config = $this->createValidationConfig();
            /** @var string $filePath */
            $filePath = $this->getTestPath('broken-links.md');
            $results = $linkValidationService->validateFile($filePath, $config);

            expect($results)->toBeArray();
            expect($results)->not->toBeEmpty();

            // Check that broken links are properly identified
            $brokenLinks = array_filter($results, fn ($result) => ! $result->isValid());
            expect($brokenLinks)->not->toBeEmpty();

            // Check that valid links are still marked as valid
            $validLinks = array_filter($results, fn ($result) => $result->isValid());
            expect($validLinks)->not->toBeEmpty();
        });

        test('handles missing internal files correctly', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create a file with missing internal links
            $this->createTestFile('missing-internal.md', '
# Test Document with Missing Internal Links

This document references files that do not exist:

- [Missing file](./does-not-exist.md)
- [Missing directory](./missing-dir/file.md)
- [Valid file](./existing-file.md)
            ');

            // Create only one of the referenced files
            $this->createTestFile('existing-file.md', '# Existing File

This file exists.');

            /** @var ValidationConfig $config */
            $config = $this->createValidationConfig();
            /** @var string $filePath */
            $filePath = $this->getTestPath('missing-internal.md');
            $results = $linkValidationService->validateFile($filePath, $config);

            expect($results)->toBeArray();
            expect($results)->not->toBeEmpty();

            // Check that missing internal files are identified
            $internalLinks = array_filter($results, fn ($result) => $result->getScope() === ValidationScope::INTERNAL);
            expect($internalLinks)->not->toBeEmpty();

            // Should have both valid and invalid internal links
            $validInternal = array_filter($internalLinks, fn ($result) => $result->isValid());
            $invalidInternal = array_filter($internalLinks, fn ($result) => ! $result->isValid());

            expect($validInternal)->not->toBeEmpty();
            expect($invalidInternal)->not->toBeEmpty();
        });
    });

    describe('edge case validation scenarios', function (): void {
        test('handles empty files gracefully', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create an empty file
            $this->createTestFile('empty.md', '');

            /** @var ValidationConfig $config */
            $config = $this->createValidationConfig();
            /** @var string $filePath */
            $filePath = $this->getTestPath('empty.md');
            $results = $linkValidationService->validateFile($filePath, $config);

            expect($results)->toBeArray();
            // Empty file should return empty results array
            expect($results)->toBeEmpty();
        });

        test('handles files with only whitespace', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create a file with only whitespace
            $this->createTestFile('whitespace.md', '


            ');

            /** @var ValidationConfig $config */
            $config = $this->createValidationConfig();
            /** @var string $filePath */
            $filePath = $this->getTestPath('whitespace.md');
            $results = $linkValidationService->validateFile($filePath, $config);

            expect($results)->toBeArray();
            // Whitespace-only file should return empty results array
            expect($results)->toBeEmpty();
        });

        test('handles malformed markdown gracefully', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create a file with malformed markdown
            $this->createTestFile('malformed.md', '
# Malformed Markdown

This has [incomplete link syntax](
And [another broken one
And a [valid link](https://httpbin.org/status/200)

[Missing closing bracket](https://example.com
            ');

            /** @var ValidationConfig $config */
            $config = $this->createValidationConfig();
            /** @var string $filePath */
            $filePath = $this->getTestPath('malformed.md');
            $results = $linkValidationService->validateFile($filePath, $config);

            expect($results)->toBeArray();
            // Should still extract valid links despite malformed syntax
            $validLinks = array_filter($results, fn ($result) => $result->isValid());
            expect($validLinks)->not->toBeEmpty();
        });
    });

});
