<?php

declare(strict_types=1);

use App\Enums\ValidationScope;
use App\Services\LinkValidationService;
use App\Services\SecurityValidationService;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\HttpTestHelpers;
use old\Support\Traits\ValidationTestHelpers;

/**
 * End-to-end test suite for performance and scale testing.
 *
 * Uses REAL implementations to test actual performance scenarios.
 *
 * @group e2e
 * @group performance
 *
 * @covers \App\Commands\ValidateCommand
 */
uses(FileTestHelpers::class, HttpTestHelpers::class, ValidationTestHelpers::class);

describe('Performance and Scale Testing', function (): void {

    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    describe('real performance scenarios', function (): void {
        test('handles multiple files efficiently', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create multiple test files with various link types
            $files = [];
            for ($i = 1; $i <= 5; $i++) {
                $fileName = "test-file-{$i}.md";
                $this->createTestFile($fileName, "
# Test File {$i}

This file contains multiple links:

- [HTTPBin Status](https://httpbin.org/status/200)
- [Internal Link](./test-file-".(($i % 5) + 1).'.md)
- [Another Internal](./shared-file.md)

## More Content

Additional content with more links:
- [HTTPBin JSON](https://httpbin.org/json)
                ');
                $files[] = $this->getTestPath($fileName);
            }

            // Create shared file that all files reference
            $this->createTestFile('shared-file.md', '# Shared File

This is a shared file referenced by multiple documents.');

            /** @var App\Services\ValueObjects\ValidationConfig $config */
            $config = $this->createValidationConfig();

            // Measure performance by validating all files
            $startTime = microtime(true);
            $allResults = [];

            foreach ($files as $file) {
                /** @var string $filePath */
                $filePath = $file;
                $results = $linkValidationService->validateFile($filePath, $config);
                $allResults = array_merge($allResults, $results);
            }

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            expect($allResults)->toBeArray();
            expect($allResults)->not->toBeEmpty();
            expect($executionTime)->toBeLessThan(30.0); // Should complete within 30 seconds

            // Verify that results contain both internal and external links
            $internalLinks = array_filter($allResults, fn ($result) => $result->getScope() === ValidationScope::INTERNAL);
            $externalLinks = array_filter($allResults, fn ($result) => $result->getScope() === ValidationScope::EXTERNAL);

            expect($internalLinks)->not->toBeEmpty();
            expect($externalLinks)->not->toBeEmpty();
        });

        test('handles large file with many links', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create a large file with many links
            $content = "# Large Test File\n\nThis file contains many links:\n\n";

            // Add 50 links to test performance
            for ($i = 1; $i <= 50; $i++) {
                $content .= "- [Link {$i}](https://httpbin.org/status/200)\n";
                if ($i % 10 === 0) {
                    $content .= "- [Internal Link {$i}](./test-file-{$i}.md)\n";
                }
            }

            $this->createTestFile('large-file.md', $content);

            /** @var App\Services\ValueObjects\ValidationConfig $config */
            $config = $this->createValidationConfig();

            $startTime = microtime(true);
            /** @var string $filePath */
            $filePath = $this->getTestPath('large-file.md');
            $results = $linkValidationService->validateFile($filePath, $config);
            $endTime = microtime(true);

            $executionTime = $endTime - $startTime;

            expect($results)->toBeArray();
            expect($results)->not->toBeEmpty();
            expect(count($results))->toBeGreaterThan(50); // Should find all the links
            expect($executionTime)->toBeLessThan(60.0); // Should complete within 60 seconds
        });
    });
});
