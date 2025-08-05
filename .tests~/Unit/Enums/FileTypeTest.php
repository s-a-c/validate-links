<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\FileType;

/**
 * Comprehensive unit tests for FileType enum.
 *
 * Tests all enum cases, methods, and edge cases with 100% coverage.
 * Validates file type detection, supported extensions, and descriptions.
 */
describe('FileType Enum', function () {

    describe('Enum Cases', function () {
        it('has all expected enum cases', function () {
            $expectedCases = [
                'MARKDOWN',
                'MARKDOWN_FULL',
                'HTML',
                'HTML_SHORT',
            ];

            $actualCases = array_map(fn ($case) => $case->name, FileType::cases());

            expect($actualCases)->toBe($expectedCases);
        });

        it('has correct string values for all cases', function () {
            expect(FileType::MARKDOWN->value)->toBe('md');
            expect(FileType::MARKDOWN_FULL->value)->toBe('markdown');
            expect(FileType::HTML->value)->toBe('html');
            expect(FileType::HTML_SHORT->value)->toBe('htm');
        });
    });

    describe('isSupported() method', function () {
        it('returns true for supported extensions', function () {
            expect(FileType::isSupported('md'))->toBeTrue();
            expect(FileType::isSupported('markdown'))->toBeTrue();
            expect(FileType::isSupported('html'))->toBeTrue();
            expect(FileType::isSupported('htm'))->toBeTrue();
        });

        it('returns false for unsupported extensions', function () {
            expect(FileType::isSupported('txt'))->toBeFalse();
            expect(FileType::isSupported('php'))->toBeFalse();
            expect(FileType::isSupported('js'))->toBeFalse();
            expect(FileType::isSupported('css'))->toBeFalse();
            expect(FileType::isSupported('json'))->toBeFalse();
            expect(FileType::isSupported(''))->toBeFalse();
        });

        it('is case sensitive', function () {
            expect(FileType::isSupported('MD'))->toBeFalse();
            expect(FileType::isSupported('HTML'))->toBeFalse();
            expect(FileType::isSupported('Markdown'))->toBeFalse();
        });

        it('handles edge cases', function () {
            expect(FileType::isSupported('md '))->toBeFalse(); // with space
            expect(FileType::isSupported(' md'))->toBeFalse(); // with leading space
            expect(FileType::isSupported('.md'))->toBeFalse(); // with dot
        });
    });

    describe('getSupportedExtensions() method', function () {
        it('returns all supported extensions as array', function () {
            $extensions = FileType::getSupportedExtensions();

            expect($extensions)->toBeArray()
                ->and($extensions)->toHaveCount(4)
                ->and($extensions)->toBe(['md', 'markdown', 'html', 'htm']);
        });

        it('returns extensions in enum definition order', function () {
            $expectedOrder = ['md', 'markdown', 'html', 'htm'];

            expect(FileType::getSupportedExtensions())->toBe($expectedOrder);
        });

        it('returns only string values', function () {
            $extensions = FileType::getSupportedExtensions();

            foreach ($extensions as $extension) {
                expect($extension)->toBeString();
            }
        });
    });

    describe('getDescription() method', function () {
        it('returns correct descriptions for all file types', function () {
            expect(FileType::MARKDOWN->getDescription())->toBe('Markdown files (.md)');
            expect(FileType::MARKDOWN_FULL->getDescription())->toBe('Markdown files (.markdown)');
            expect(FileType::HTML->getDescription())->toBe('HTML files (.html)');
            expect(FileType::HTML_SHORT->getDescription())->toBe('HTML files (.htm)');
        });

        it('returns non-empty descriptions for all cases', function () {
            foreach (FileType::cases() as $fileType) {
                expect($fileType->getDescription())->toBeString()
                    ->and($fileType->getDescription())->not->toBeEmpty();
            }
        });

        it('includes file extension in description', function () {
            foreach (FileType::cases() as $fileType) {
                $description = $fileType->getDescription();
                $extension = $fileType->value;

                expect($description)->toContain(".{$extension}");
            }
        });
    });

    describe('Integration Tests', function () {
        it('all enum values are supported by isSupported method', function () {
            foreach (FileType::cases() as $fileType) {
                expect(FileType::isSupported($fileType->value))->toBeTrue();
            }
        });

        it('getSupportedExtensions includes all enum values', function () {
            $supportedExtensions = FileType::getSupportedExtensions();

            foreach (FileType::cases() as $fileType) {
                expect($supportedExtensions)->toContain($fileType->value);
            }
        });

        it('enum count matches supported extensions count', function () {
            expect(count(FileType::cases()))->toBe(count(FileType::getSupportedExtensions()));
        });
    });

    describe('Type Safety', function () {
        it('enum cases are of correct type', function () {
            foreach (FileType::cases() as $fileType) {
                expect($fileType)->toBeInstanceOf(FileType::class);
                expect($fileType->value)->toBeString();
            }
        });

        it('static methods return correct types', function () {
            expect(FileType::isSupported('md'))->toBeBool();
            expect(FileType::getSupportedExtensions())->toBeArray();
        });

        it('instance methods return correct types', function () {
            $fileType = FileType::MARKDOWN;

            expect($fileType->getDescription())->toBeString();
        });
    });

    describe('Edge Cases and Boundary Conditions', function () {
        it('handles empty string input', function () {
            expect(FileType::isSupported(''))->toBeFalse();
        });

        it('handles null-like inputs gracefully', function () {
            expect(FileType::isSupported('0'))->toBeFalse();
            expect(FileType::isSupported('false'))->toBeFalse();
            expect(FileType::isSupported('null'))->toBeFalse();
        });

        it('handles special characters', function () {
            expect(FileType::isSupported('md!'))->toBeFalse();
            expect(FileType::isSupported('html@'))->toBeFalse();
            expect(FileType::isSupported('md#'))->toBeFalse();
        });

        it('handles numeric strings', function () {
            expect(FileType::isSupported('123'))->toBeFalse();
            expect(FileType::isSupported('0'))->toBeFalse();
        });
    });
});
