<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;

/**
 * Trait providing custom assertion methods for validate-links testing.
 *
 * This trait follows the established preference for trait-based composition
 * over base class inheritance, providing reusable assertion utilities for
 * all test classes in the suite.
 */
trait AssertionHelpers
{
    /**
     * Assert that an enum has all expected cases.
     */
    protected function assertEnumHasCases(string $enumClass, array $expectedCases): void
    {
        $actualCases = $enumClass::cases();
        $actualValues = array_column($actualCases, 'value');

        expect($actualCases)->toHaveCount(
            count($expectedCases),
            'Enum should have exactly '.count($expectedCases).' cases'
        );

        foreach ($expectedCases as $expectedValue) {
            expect($actualValues)->toContain(
                $expectedValue,
                "Enum should have case with value: {$expectedValue}"
            );
        }
    }

    /**
     * Assert that an enum case has expected properties.
     */
    protected function assertEnumCaseProperties(object $enumCase, array $expectedProperties): void
    {
        foreach ($expectedProperties as $property => $expectedValue) {
            if (method_exists($enumCase, $property)) {
                $actualValue = $enumCase->$property();
                expect($actualValue)->toBe(
                    $expectedValue,
                    "Enum case {$enumCase->name} property {$property} should be {$expectedValue}"
                );
            } else {
                expect($enumCase)->toHaveProperty($property, $expectedValue);
            }
        }
    }

    /**
     * Assert that a validation result has expected structure.
     */
    protected function assertValidationResultStructure(object $result): void
    {
        expect($result)->toHaveProperty('url');
        expect($result)->toHaveProperty('status');
        expect($result)->toHaveProperty('scope');
        expect($result->getUrl())->toBeString();
        expect($result->getStatus())->toBeInstanceOf(LinkStatus::class);
        expect($result->getScope())->toBeInstanceOf(ValidationScope::class);
    }

    /**
     * Assert that a configuration object has expected structure.
     */
    protected function assertConfigurationStructure(object $config): void
    {
        expect($config)->toHaveProperty('timeout');
        expect($config)->toHaveProperty('concurrent_requests');
        expect($config)->toHaveProperty('scopes');
        expect($config->getTimeout())->toBeInt();
        expect($config->getConcurrentRequests())->toBeInt();
        expect($config->getScopes())->toBeArray();
    }

    /**
     * Assert that an output format is valid.
     */
    protected function assertValidOutputFormat(string $format): void
    {
        $validFormats = array_column(OutputFormat::cases(), 'value');
        expect($validFormats)->toContain(
            $format,
            'Output format should be one of: '.implode(', ', $validFormats)
        );
    }

    /**
     * Assert that a validation scope is valid.
     */
    protected function assertValidValidationScope(string $scope): void
    {
        $validScopes = array_column(ValidationScope::cases(), 'value');
        expect($validScopes)->toContain(
            $scope,
            'Validation scope should be one of: '.implode(', ', $validScopes)
        );
    }

    /**
     * Assert that a link status is valid.
     */
    protected function assertValidLinkStatus(string $status): void
    {
        $validStatuses = array_column(LinkStatus::cases(), 'value');
        expect($validStatuses)->toContain(
            $status,
            'Link status should be one of: '.implode(', ', $validStatuses)
        );
    }

    /**
     * Assert that an array contains expected validation results.
     */
    protected function assertValidationResults(array $results, array $expectedUrls): void
    {
        expect($results)->toBeArray();
        expect($results)->toHaveCount(count($expectedUrls));

        foreach ($expectedUrls as $index => $expectedUrl) {
            expect($results[$index])->toHaveKey('url');
            expect($results[$index]['url'])->toBe($expectedUrl);
            $this->assertValidationResultStructure((object) $results[$index]);
        }
    }

    /**
     * Assert that a command output contains expected patterns.
     */
    protected function assertCommandOutput(string $output, array $expectedPatterns): void
    {
        foreach ($expectedPatterns as $pattern) {
            expect($output)->toContain(
                $pattern,
                "Command output should contain: {$pattern}"
            );
        }
    }

    /**
     * Assert that a file contains expected content.
     */
    protected function assertFileContains(string $filePath, array $expectedContent): void
    {
        expect(file_exists($filePath))->toBeTrue("File should exist: {$filePath}");

        $content = file_get_contents($filePath);
        foreach ($expectedContent as $expected) {
            expect($content)->toContain(
                $expected,
                "File {$filePath} should contain: {$expected}"
            );
        }
    }

    /**
     * Assert that a JSON string has expected structure.
     */
    protected function assertJsonStructure(string $json, array $expectedKeys): void
    {
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray('JSON should be valid and decode to array');

        foreach ($expectedKeys as $key) {
            expect($decoded)->toHaveKey(
                $key,
                "JSON should have key: {$key}"
            );
        }
    }

    /**
     * Assert that an exception has expected properties.
     */
    protected function assertExceptionProperties(object $exception, array $expectedProperties): void
    {
        foreach ($expectedProperties as $property => $expectedValue) {
            $getter = 'get'.ucfirst($property);
            if (method_exists($exception, $getter)) {
                $actualValue = $exception->$getter();
                expect($actualValue)->toBe(
                    $expectedValue,
                    "Exception {$property} should be {$expectedValue}"
                );
            }
        }
    }

    /**
     * Assert that a URL is valid format.
     */
    protected function assertValidUrl(string $url): void
    {
        expect(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse(
            "URL should be valid format: {$url}"
        );
    }

    /**
     * Assert that a path exists and is readable.
     */
    protected function assertPathReadable(string $path): void
    {
        expect(file_exists($path))->toBeTrue("Path should exist: {$path}");
        expect(is_readable($path))->toBeTrue("Path should be readable: {$path}");
    }

    /**
     * Assert that a directory contains expected files.
     */
    protected function assertDirectoryContains(string $directory, array $expectedFiles): void
    {
        expect(is_dir($directory))->toBeTrue("Directory should exist: {$directory}");

        $actualFiles = scandir($directory);
        foreach ($expectedFiles as $expectedFile) {
            expect($actualFiles)->toContain(
                $expectedFile,
                "Directory {$directory} should contain file: {$expectedFile}"
            );
        }
    }

    /**
     * Assert that a string matches expected pattern.
     */
    protected function assertStringPattern(string $string, string $pattern, string $description = ''): void
    {
        expect(preg_match($pattern, $string))->toBe(
            1,
            $description ?: "String should match pattern {$pattern}: {$string}"
        );
    }

    /**
     * Assert that an array has expected structure.
     */
    protected function assertArrayStructure(array $array, array $expectedKeys): void
    {
        foreach ($expectedKeys as $key) {
            expect($array)->toHaveKey(
                $key,
                "Array should have key: {$key}"
            );
        }
    }

    /**
     * Assert that a value is within expected range.
     */
    protected function assertValueInRange($value, $min, $max, string $description = ''): void
    {
        expect($value)->toBeGreaterThanOrEqual(
            $min,
            $description ?: "Value should be >= {$min}"
        );
        expect($value)->toBeLessThanOrEqual(
            $max,
            $description ?: "Value should be <= {$max}"
        );
    }

    /**
     * Assert that a collection contains items matching criteria.
     */
    protected function assertCollectionContains(array $collection, callable $criteria, string $description = ''): void
    {
        $found = array_any($collection, fn ($item) => $criteria($item));
        expect($found)->toBeTrue(
            $description ?: 'Collection should contain item matching criteria'
        );
    }

    /**
     * Assert that a timestamp is recent (within last N seconds).
     */
    protected function assertRecentTimestamp($timestamp, int $maxAgeSeconds = 60): void
    {
        $now = time();
        $age = $now - $timestamp;

        expect($age)->toBeLessThanOrEqual(
            $maxAgeSeconds,
            "Timestamp should be recent (within {$maxAgeSeconds} seconds)"
        );
    }

    /**
     * Assert that a response time is reasonable.
     */
    protected function assertReasonableResponseTime(float $responseTime, float $maxSeconds = 5.0): void
    {
        expect($responseTime)->toBeGreaterThan(0, 'Response time should be positive');
        expect($responseTime)->toBeLessThanOrEqual(
            $maxSeconds,
            "Response time should be reasonable (< {$maxSeconds}s)"
        );
    }

    /**
     * Assert that a memory usage is reasonable.
     */
    protected function assertReasonableMemoryUsage(int $memoryBytes, int $maxMB = 100): void
    {
        $maxBytes = $maxMB * 1024 * 1024;
        expect($memoryBytes)->toBeLessThanOrEqual(
            $maxBytes,
            "Memory usage should be reasonable (< {$maxMB}MB)"
        );
    }

    /**
     * Assert that a process completed successfully.
     */
    protected function assertProcessSuccess(int $exitCode, string $output = '', string $error = ''): void
    {
        // Suppress unused parameter warning for optional output parameter
        unset($output);

        expect($exitCode)->toBe(0, 'Process should exit successfully');

        if ($error !== '' && $error !== '0') {
            expect($error)->toBeEmpty("Process should not have errors: {$error}");
        }
    }
}
