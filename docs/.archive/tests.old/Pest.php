<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\ValueObjects\ValidationResult;
use old\TestCase;

/**
 * Pest PHP configuration for validate-links test suite.
 *
 * This configuration file sets up:
 * - Pure Pest syntax compliance
 * - Trait-based test composition
 * - Custom expectations with complete type safety
 * - PHP 8+ attributes for test metadata
 * - Comprehensive type annotations
 *
 * @phpstan-type UrlPattern string
 * @phpstan-type MarkdownContent string
 * @phpstan-type BrokenLinksCollection array<int, array{url: string, status: LinkStatus}>
 */

// Base test functionality for all test directories
uses(TestCase::class)->in('Feature');
uses(TestCase::class)->in('Unit');
uses(TestCase::class)->in('Integration');
uses(TestCase::class)->in('E2e');

// Set up test environment hooks
beforeEach(function (): void {
    // Initialize the application using TestCase trait
    if (method_exists($this, 'setUpTestCase')) {
        $this->setUpTestCase();
    }
});

afterEach(function (): void {
    // Clean up the application using TestCase trait
    if (method_exists($this, 'tearDownTestCase')) {
        $this->tearDownTestCase();
    }
});

/**
 * Custom expectation: Validate URL format.
 *
 * @return Pest\Expectation<string>
 */
expect()->extend('toBeValidUrl', function (): Pest\Expectation {
    /** @var string $url */
    $url = $this->value;

    return $this->toMatch('/^https?:\/\/.+/')
        ->and($url)->not->toBeEmpty()
        ->and($url)->toBeString();
});

/**
 * Custom expectation: Validate Markdown content.
 *
 * @return Pest\Expectation<string>
 */
expect()->extend('toBeValidMarkdown', function (): Pest\Expectation {
    /** @var string $content */
    $content = $this->value;

    return $this->toContain('# ')
        ->and($content)->toBeString()
        ->and($content)->not->toBeEmpty();
});

/**
 * Custom expectation: Validate ValidationResult instance.
 *
 * @return Pest\Expectation<ValidationResult>
 */
expect()->extend('toBeValidationResult', function () {
    return $this->toBeInstanceOf(ValidationResult::class)
        ->and($this->value)->toHaveMethod('isValid')
        ->and($this->value)->toHaveMethod('getUrl');
});

/**
 * Custom expectation: Validate broken links count.
 *
 * @param  int|null  $count  Expected count of broken links, null to check for any
 * @return Pest\Expectation<ValidationResult>
 */
expect()->extend('toHaveBrokenLinks', function (?int $count = null) {
    /** @var ValidationResult $result */
    $result = $this->value;
    $brokenLinks = $result->getBrokenCount();

    if ($count === null) {
        return expect($brokenLinks)->toBeGreaterThan(0);
    }

    return expect($brokenLinks)->toBe($count);
});

/**
 * Custom expectation: Validate link status.
 *
 * @param  LinkStatus  $expectedStatus  Expected link status
 * @return Pest\Expectation<LinkStatus>
 */
expect()->extend('toHaveLinkStatus', function (LinkStatus $expectedStatus) {
    /** @var LinkStatus $status */
    $status = $this->value;

    return $this->toBe($expectedStatus)
        ->and($status)->toBeInstanceOf(LinkStatus::class);
});

/**
 * Custom expectation: Validate file path exists.
 *
 * @return Pest\Expectation<string>
 */
expect()->extend('toBeValidFilePath', function () {
    /** @var string $path */
    $path = $this->value;

    return $this->toBeString()
        ->and($path)->not->toBeEmpty()
        ->and(file_exists($path))->toBeTrue("File should exist: {$path}");
});

/**
 * Custom expectation: Validate array has specific structure.
 *
 * @param  array<string, string>  $expectedKeys  Expected keys with their types
 * @return Pest\Expectation<array<string, mixed>>
 */
expect()->extend('toHaveStructure', function (array $expectedKeys) {
    /** @var array<string, mixed> $array */
    $array = $this->value;

    $expectation = $this->toBeArray();

    foreach ($expectedKeys as $key => $type) {
        $expectation = $expectation->and($array)->toHaveKey($key);

        $expectation = match ($type) {
            'string' => $expectation->and($array[$key])->toBeString(),
            'int' => $expectation->and($array[$key])->toBeInt(),
            'bool' => $expectation->and($array[$key])->toBeBool(),
            'array' => $expectation->and($array[$key])->toBeArray(),
            default => $expectation,
        };
    }

    return $expectation;
});
