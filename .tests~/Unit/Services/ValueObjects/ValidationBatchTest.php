<?php

declare(strict_types=1);

use App\Enums\ValidationScope;
use App\Services\ValueObjects\LinkExtractionResult;
use App\Services\ValueObjects\ValidationBatch;
use App\Services\ValueObjects\ValidationConfig;

describe('ValidationBatch', function () {
    /** @var ValidationConfig|null */
    $mockConfig = null;
    /** @var array<LinkExtractionResult>|null */
    $mockLinks = null;

    beforeEach(function () use (&$mockConfig, &$mockLinks) {
        $mockConfig = new ValidationConfig(
            scopes: [ValidationScope::EXTERNAL],
            timeout: 30,
            maxRedirects: 5,
            concurrentRequests: 10,
            cacheEnabled: true,
            followRedirects: true,
            userAgent: 'Test Agent',
            outputFormat: null,
            additionalOptions: [],
            interactive: false,
            dryRun: false
        );

        $mockLinks = [
            new LinkExtractionResult(
                url: 'https://example.com',
                text: 'Example Link',
                lineNumber: 1,
                scope: ValidationScope::EXTERNAL
            ),
            new LinkExtractionResult(
                url: 'https://test.com',
                text: 'Test Link',
                lineNumber: 2,
                scope: ValidationScope::EXTERNAL
            ),
        ];
    });

    it('creates valid validation batch with all parameters', function () use (&$mockLinks, &$mockConfig) {
        if ($mockLinks === null || $mockConfig === null) {
            throw new RuntimeException('Mock data not initialized');
        }
        
        $batch = new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: 123,
            priority: 5
        );

        expect($batch->links)->toBe($mockLinks);
        expect($batch->config)->toBe($mockConfig);
        expect($batch->batchId)->toBe(123);
        expect($batch->priority)->toBe(5);
    });

    it('creates validation batch with default priority', function () use (&$mockLinks, &$mockConfig) {
        if ($mockLinks === null || $mockConfig === null) {
            throw new RuntimeException('Mock data not initialized');
        }
        
        $batch = new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: 123
        );

        expect($batch->priority)->toBe(0);
    });

    it('creates batch from array data with LinkExtractionResult instances', function () use (&$mockLinks, &$mockConfig) {
        if ($mockLinks === null || $mockConfig === null) {
            throw new RuntimeException('Mock data not initialized');
        }
        
        $data = [
            'links' => $mockLinks,
            'config' => $mockConfig,
            'batch_id' => 123,
            'priority' => 5,
        ];

        $batch = ValidationBatch::create($data);

        expect($batch->links)->toBe($mockLinks);
        expect($batch->config)->toBe($mockConfig);
        expect($batch->batchId)->toBe(123);
        expect($batch->priority)->toBe(5);
    });

    it('creates batch from array data with array link data', function () use (&$mockConfig) {
        if ($mockConfig === null) {
            throw new RuntimeException('Mock config not initialized');
        }
        $linkData = [
            [
                'url' => 'https://example.com',
                'text' => 'Example Link',
                'line' => 1,
                'scope' => 'external',
            ],
            [
                'url' => 'https://test.com',
                'text' => 'Test Link',
                'line' => 2,
                'scope' => 'external',
            ],
        ];

        $data = [
            'links' => $linkData,
            'config' => $mockConfig,
            'batch_id' => 123,
            'priority' => 5,
        ];

        $batch = ValidationBatch::create($data);

        expect($batch->links)->toHaveCount(2);
        expect($batch->links[0])->toBeInstanceOf(LinkExtractionResult::class);
        expect($batch->links[0]->url)->toBe('https://example.com');
        expect($batch->links[1]->url)->toBe('https://test.com');
    });

    it('creates batch with default values when data is missing', function () use (&$mockLinks, &$mockConfig) {
        $data = [
            'links' => $mockLinks,
            'config' => $mockConfig,
        ];

        $batch = ValidationBatch::create($data);

        expect($batch->batchId)->toBe(0);
        expect($batch->priority)->toBe(0);
    });

    it('handles mixed link data types in create method', function () use (&$mockLinks, &$mockConfig) {
        $mixedLinks = [
            $mockLinks[0], // LinkExtractionResult instance
            [
                'url' => 'https://mixed.com',
                'text' => 'Mixed Link',
                'line' => 3,
                'scope' => 'external',
            ], // Array data
        ];

        $data = [
            'links' => $mixedLinks,
            'config' => $mockConfig,
            'batch_id' => 123,
        ];

        $batch = ValidationBatch::create($data);

        expect($batch->links)->toHaveCount(2);
        expect($batch->links[0])->toBe($mockLinks[0]);
        expect($batch->links[1])->toBeInstanceOf(LinkExtractionResult::class);
        expect($batch->links[1]->url)->toBe('https://mixed.com');
    });

    it('throws exception when config is not ValidationConfig instance', function () use (&$mockLinks) {
        $data = [
            'links' => $mockLinks,
            'config' => 'invalid_config',
            'batch_id' => 123,
        ];

        expect(fn () => ValidationBatch::create($data))
            ->toThrow(InvalidArgumentException::class, 'Config must be a ValidationConfig instance');
    });

    it('throws exception when config is missing', function () use (&$mockLinks) {
        $data = [
            'links' => $mockLinks,
            'batch_id' => 123,
        ];

        expect(fn () => ValidationBatch::create($data))
            ->toThrow(InvalidArgumentException::class, 'Config must be a ValidationConfig instance');
    });

    it('validates empty links array', function () use (&$mockConfig) {
        expect(fn () => new ValidationBatch(
            links: [],
            config: $mockConfig,
            batchId: 123
        ))->toThrow(InvalidArgumentException::class, 'Batch must contain at least one link');
    });

    it('validates all links are LinkExtractionResult instances', function () use (&$mockLinks, &$mockConfig) {
        $invalidLinks = [
            $mockLinks[0],
            'invalid_link', // Not a LinkExtractionResult
        ];

        expect(fn () => new ValidationBatch(
            links: $invalidLinks,
            config: $mockConfig,
            batchId: 123
        ))->toThrow(InvalidArgumentException::class, 'All links must be LinkExtractionResult instances');
    });

    it('validates negative batch ID', function () use (&$mockLinks, &$mockConfig) {
        expect(fn () => new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: -1
        ))->toThrow(InvalidArgumentException::class, 'Batch ID must be non-negative');
    });

    it('returns correct link count', function () use (&$mockLinks, &$mockConfig) {
        $batch = new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: 123
        );

        expect($batch->getLinkCount())->toBe(2);
    });

    it('returns correct URLs array', function () use (&$mockLinks, &$mockConfig) {
        $batch = new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: 123
        );

        $expectedUrls = ['https://example.com', 'https://test.com'];

        expect($batch->getUrls())->toBe($expectedUrls);
    });

    it('converts to array correctly', function () use (&$mockLinks, &$mockConfig) {
        $batch = new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: 123,
            priority: 5
        );

        $result = $batch->toArray();

        expect($result['batch_id'])->toBe(123);
        expect($result['priority'])->toBe(5);
        expect($result['link_count'])->toBe(2);
        expect($result['links'])->toHaveCount(2);
        expect($result['links'][0])->toBe($mockLinks[0]->toArray());
        expect($result['links'][1])->toBe($mockLinks[1]->toArray());
        expect($result['config'])->toBe($mockConfig->toArray());
    });

    it('handles single link correctly', function () use (&$mockLinks, &$mockConfig) {
        $singleLink = [$mockLinks[0]];

        $batch = new ValidationBatch(
            links: $singleLink,
            config: $mockConfig,
            batchId: 123
        );

        expect($batch->getLinkCount())->toBe(1);
        expect($batch->getUrls())->toBe(['https://example.com']);
    });

    it('handles zero batch ID correctly', function () use (&$mockLinks, &$mockConfig) {
        $batch = new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: 0
        );

        expect($batch->batchId)->toBe(0);
        expect($batch->toArray()['batch_id'])->toBe(0);
    });

    it('handles negative priority correctly', function () use (&$mockLinks, &$mockConfig) {
        $batch = new ValidationBatch(
            links: $mockLinks,
            config: $mockConfig,
            batchId: 123,
            priority: -5
        );

        expect($batch->priority)->toBe(-5);
        expect($batch->toArray()['priority'])->toBe(-5);
    });

    it('handles empty links array in create method', function () use (&$mockConfig) {
        $data = [
            'links' => [],
            'config' => $mockConfig,
            'batch_id' => 123,
        ];

        expect(fn () => ValidationBatch::create($data))
            ->toThrow(InvalidArgumentException::class, 'Batch must contain at least one link');
    });

    it('handles non-array links in create method', function () use (&$mockConfig) {
        $data = [
            'links' => 'not_an_array',
            'config' => $mockConfig,
            'batch_id' => 123,
        ];

        expect(fn () => ValidationBatch::create($data))
            ->toThrow(InvalidArgumentException::class, 'Batch must contain at least one link');
    });

    it('handles missing links in create method', function () use (&$mockConfig) {
        $data = [
            'config' => $mockConfig,
            'batch_id' => 123,
        ];

        expect(fn () => ValidationBatch::create($data))
            ->toThrow(InvalidArgumentException::class, 'Batch must contain at least one link');
    });

    it('converts string values to integers in create method', function () use (&$mockLinks, &$mockConfig) {
        $data = [
            'links' => $mockLinks,
            'config' => $mockConfig,
            'batch_id' => '123',
            'priority' => '5',
        ];

        $batch = ValidationBatch::create($data);

        expect($batch->batchId)->toBe(123);
        expect($batch->priority)->toBe(5);
    });
});