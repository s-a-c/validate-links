<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Services\LinkValidationService;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationSummary;

describe('LinkValidationService Integration', function () {
    it('validates file and returns collection', function () {
        $service = app(LinkValidationService::class);
        $config = ValidationConfig::withDefaults();

        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '[Valid Link](https://example.com)');

        $results = $service->validateFile($tempFile, $config);

        expect($results)->toBeInstanceOf(ValidationResultCollection::class)
            ->and($results->count())->toBeGreaterThan(0);

        unlink($tempFile);
    });

    it('handles non-existent files gracefully', function () {
        $service = app(LinkValidationService::class);
        $config = ValidationConfig::withDefaults();

        $results = $service->validateFile('/non/existent/file.md', $config);

        expect($results)->toBeInstanceOf(ValidationResultCollection::class)
            ->and($results->count())->toBe(1)
            ->and($results->getFailed()->count())->toBe(1);
    });

    it('validates external links', function () {
        $service = app(LinkValidationService::class);
        $config = ValidationConfig::withDefaults();
        $urls = ['https://httpbin.org/status/200', 'https://httpbin.org/status/404'];

        $results = $service->validateExternalLinks($urls, $config);

        expect($results)->toBeInstanceOf(ValidationResultCollection::class)
            ->and($results->count())->toBe(2);
    });

    it('validates file summary and returns ValidationSummary', function () {
        $service = app(LinkValidationService::class);
        $config = ValidationConfig::withDefaults();

        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '[Test Link](https://example.com)');

        $summary = $service->validateFileSummary($tempFile, $config);

        expect($summary)->toBeInstanceOf(ValidationSummary::class)
            ->and($summary->getFilePath())->toBe($tempFile)
            ->and($summary->getResults())->toBeInstanceOf(ValidationResultCollection::class)
            ->and($summary->getStatistics()->getTotal())->toBeGreaterThan(0);

        unlink($tempFile);
    });

    it('validates multiple files and returns collection', function () {
        $service = app(LinkValidationService::class);
        $config = ValidationConfig::withDefaults();

        $tempFile1 = tempnam(sys_get_temp_dir(), 'test1_');
        $tempFile2 = tempnam(sys_get_temp_dir(), 'test2_');

        file_put_contents($tempFile1, '[Link 1](https://example.com)');
        file_put_contents($tempFile2, '[Link 2](https://google.com)');

        $results = $service->validateFiles([$tempFile1, $tempFile2], $config);

        expect($results)->toBeInstanceOf(ValidationResultCollection::class)
            ->and($results->count())->toBeGreaterThan(0);

        unlink($tempFile1);
        unlink($tempFile2);
    });

    it('extracts links from markdown content', function () {
        $service = app(LinkValidationService::class);

        $content = '
# Test Document

[External Link](https://example.com)
[Internal Link](./internal.md)
[Anchor Link](#section)
        ';

        $links = $service->extractLinks($content);

        expect($links)->toBeArray()
            ->and($links)->toHaveCount(3);
    });

    it('validates single link with proper typing', function () {
        $service = app(LinkValidationService::class);
        $config = ValidationConfig::withDefaults();

        $result = $service->validateLink('https://example.com', $config);

        expect($result)->toBeInstanceOf(App\Services\ValueObjects\ValidationResult::class)
            ->and($result->getUrl())->toBe('https://example.com')
            ->and($result->getStatus())->toBeInstanceOf(LinkStatus::class);
    });

    it('handles anchor links correctly', function () {
        $service = app(LinkValidationService::class);
        $config = ValidationConfig::withDefaults();

        $result = $service->validateLink('#test-anchor', $config);

        expect($result)->toBeInstanceOf(App\Services\ValueObjects\ValidationResult::class)
            ->and($result->getUrl())->toBe('#test-anchor')
            ->and($result->getStatus())->toBe(LinkStatus::VALID);
    });

    it('validates URL format correctly', function () {
        $service = app(LinkValidationService::class);

        expect($service->isValidUrl('https://example.com'))->toBeTrue()
            ->and($service->isValidUrl('http://example.com'))->toBeTrue()
            ->and($service->isValidUrl('invalid-url'))->toBeFalse()
            ->and($service->isValidUrl(''))->toBeFalse();
    });

    it('normalizes URLs correctly', function () {
        $service = app(LinkValidationService::class);

        $normalized = $service->normalizeUrl('  https://example.com  ');

        expect($normalized)->toBe('https://example.com');
    });

    it('provides validation statistics', function () {
        $service = app(LinkValidationService::class);

        $stats = $service->getStatusStatistics();

        expect($stats)->toBeInstanceOf(App\Services\ValueObjects\ValidationStatistics::class)
            ->and($stats->getTotal())->toBeInt();
    });

    it('supports validation scopes', function () {
        $service = app(LinkValidationService::class);

        $scopes = $service->getSupportedScopes();

        expect($scopes)->toBeArray()
            ->and($scopes)->not->toBeEmpty();
    });

    it('handles timeout configuration', function () {
        $service = app(LinkValidationService::class);

        expect(fn () => $service->setTimeout(30))->not->toThrow(Exception::class);
    });
});
