<?php

declare(strict_types=1);

use App\Enums\ValidationScope;
use App\Services\ValueObjects\LinkExtractionResult;

describe('LinkExtractionResult', function () {
    it('creates valid link extraction result with all parameters', function () {
        $result = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Example Link',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL,
            anchor: 'section1',
            filePath: '/path/to/file.md'
        );

        expect($result->url)->toBe('https://example.com');
        expect($result->text)->toBe('Example Link');
        expect($result->lineNumber)->toBe(42);
        expect($result->scope)->toBe(ValidationScope::EXTERNAL);
        expect($result->anchor)->toBe('section1');
        expect($result->filePath)->toBe('/path/to/file.md');
    });

    it('creates link extraction result with minimal parameters', function () {
        $result = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Example Link',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL
        );

        expect($result->url)->toBe('https://example.com');
        expect($result->text)->toBe('Example Link');
        expect($result->lineNumber)->toBe(42);
        expect($result->scope)->toBe(ValidationScope::EXTERNAL);
        expect($result->anchor)->toBeNull();
        expect($result->filePath)->toBeNull();
    });

    it('creates result from array data with all fields', function () {
        $data = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
            'line' => 42,
            'scope' => 'external',
            'anchor' => 'section1',
            'file_path' => '/path/to/file.md',
        ];

        $result = LinkExtractionResult::create($data);

        expect($result->url)->toBe('https://example.com');
        expect($result->text)->toBe('Example Link');
        expect($result->lineNumber)->toBe(42);
        expect($result->scope)->toBe(ValidationScope::EXTERNAL);
        expect($result->anchor)->toBe('section1');
        expect($result->filePath)->toBe('/path/to/file.md');
    });

    it('creates result from array data with minimal fields', function () {
        $data = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
        ];

        $result = LinkExtractionResult::create($data);

        expect($result->url)->toBe('https://example.com');
        expect($result->text)->toBe('Example Link');
        expect($result->lineNumber)->toBe(0);
        expect($result->scope)->toBe(ValidationScope::ALL);
        expect($result->anchor)->toBeNull();
        expect($result->filePath)->toBeNull();
    });

    it('handles ValidationScope enum in create method', function () {
        $data = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
            'scope' => ValidationScope::INTERNAL,
        ];

        $result = LinkExtractionResult::create($data);

        expect($result->scope)->toBe(ValidationScope::INTERNAL);
    });

    it('defaults to ALL scope for invalid scope string', function () {
        $data = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
            'scope' => 'invalid_scope',
        ];

        $result = LinkExtractionResult::create($data);

        expect($result->scope)->toBe(ValidationScope::ALL);
    });

    it('throws exception for empty URL in create method', function () {
        $data = [
            'url' => '',
            'text' => 'Example Link',
        ];

        expect(fn () => LinkExtractionResult::create($data))
            ->toThrow(InvalidArgumentException::class, 'URL must be a non-empty string');
    });

    it('throws exception for non-string URL in create method', function () {
        $data = [
            'url' => 123,
            'text' => 'Example Link',
        ];

        expect(fn () => LinkExtractionResult::create($data))
            ->toThrow(InvalidArgumentException::class, 'URL must be a non-empty string');
    });

    it('throws exception for non-string text in create method', function () {
        $data = [
            'url' => 'https://example.com',
            'text' => 123,
        ];

        expect(fn () => LinkExtractionResult::create($data))
            ->toThrow(InvalidArgumentException::class, 'Text must be a string');
    });

    it('validates empty URL in constructor', function () {
        expect(fn () => new LinkExtractionResult(
            url: '',
            text: 'Example Link',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL
        ))->toThrow(InvalidArgumentException::class, 'URL cannot be empty');
    });

    it('validates negative line number', function () {
        expect(fn () => new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Example Link',
            lineNumber: -1,
            scope: ValidationScope::EXTERNAL
        ))->toThrow(InvalidArgumentException::class, 'Line number must be non-negative');
    });

    it('correctly identifies internal links', function () {
        $internalResult = new LinkExtractionResult(
            url: '/internal/path',
            text: 'Internal Link',
            lineNumber: 42,
            scope: ValidationScope::INTERNAL
        );

        $externalResult = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'External Link',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL
        );

        expect($internalResult->isInternal())->toBeTrue();
        expect($externalResult->isInternal())->toBeFalse();
    });

    it('correctly identifies external links', function () {
        $externalResult = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'External Link',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL
        );

        $internalResult = new LinkExtractionResult(
            url: '/internal/path',
            text: 'Internal Link',
            lineNumber: 42,
            scope: ValidationScope::INTERNAL
        );

        expect($externalResult->isExternal())->toBeTrue();
        expect($internalResult->isExternal())->toBeFalse();
    });

    it('correctly detects presence of anchor', function () {
        $withAnchor = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Link with anchor',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL,
            anchor: 'section1'
        );

        $withoutAnchor = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Link without anchor',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL
        );

        expect($withAnchor->hasAnchor())->toBeTrue();
        expect($withoutAnchor->hasAnchor())->toBeFalse();
    });

    it('converts to array correctly with all fields', function () {
        $result = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Example Link',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL,
            anchor: 'section1',
            filePath: '/path/to/file.md'
        );

        $expected = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
            'line' => 42,
            'scope' => 'external',
            'anchor' => 'section1',
            'file_path' => '/path/to/file.md',
        ];

        expect($result->toArray())->toBe($expected);
    });

    it('converts to array correctly with null fields', function () {
        $result = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Example Link',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL
        );

        $expected = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
            'line' => 42,
            'scope' => 'external',
            'anchor' => null,
            'file_path' => null,
        ];

        expect($result->toArray())->toBe($expected);
    });

    it('handles zero line number correctly', function () {
        $result = new LinkExtractionResult(
            url: 'https://example.com',
            text: 'Example Link',
            lineNumber: 0,
            scope: ValidationScope::EXTERNAL
        );

        expect($result->lineNumber)->toBe(0);
        expect($result->toArray()['line'])->toBe(0);
    });

    it('handles different validation scopes correctly', function () {
        $scopes = [
            ValidationScope::INTERNAL,
            ValidationScope::EXTERNAL,
            ValidationScope::ANCHOR,
            ValidationScope::IMAGE,
            ValidationScope::ALL,
        ];

        foreach ($scopes as $scope) {
            $result = new LinkExtractionResult(
                url: 'https://example.com',
                text: 'Example Link',
                lineNumber: 42,
                scope: $scope
            );

            expect($result->scope)->toBe($scope);
            expect($result->toArray()['scope'])->toBe($scope->value);
        }
    });

    it('handles empty text correctly', function () {
        $result = new LinkExtractionResult(
            url: 'https://example.com',
            text: '',
            lineNumber: 42,
            scope: ValidationScope::EXTERNAL
        );

        expect($result->text)->toBe('');
        expect($result->toArray()['text'])->toBe('');
    });

    it('converts anchor and file_path to string in create method', function () {
        $data = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
            'anchor' => 123,
            'file_path' => 456,
        ];

        $result = LinkExtractionResult::create($data);

        expect($result->anchor)->toBe('123');
        expect($result->filePath)->toBe('456');
    });
});
