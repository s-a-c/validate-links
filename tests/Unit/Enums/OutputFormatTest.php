<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OutputFormat;
use App\Services\Formatters\ConsoleFormatter;
use App\Services\Formatters\HtmlFormatter;
use App\Services\Formatters\JsonFormatter;
use App\Services\Formatters\MarkdownFormatter;
use ValueError;

/**
 * Comprehensive unit tests for OutputFormat enum.
 *
 * Tests all enum cases, methods, and edge cases with 100% coverage.
 * Validates output format behavior, file extensions, MIME types, and formatter classes.
 */
describe('OutputFormat Enum', function () {

    describe('Enum Cases', function () {
        it('has all expected enum cases', function () {
            $expectedCases = [
                'CONSOLE',
                'JSON',
                'HTML',
                'MARKDOWN',
                'XML',
                'CSV',
            ];

            $actualCases = array_map(fn ($case) => $case->name, OutputFormat::cases());

            expect($actualCases)->toBe($expectedCases);
        });

        it('has correct string values for all cases', function () {
            expect(OutputFormat::CONSOLE->value)->toBe('console');
            expect(OutputFormat::JSON->value)->toBe('json');
            expect(OutputFormat::HTML->value)->toBe('html');
            expect(OutputFormat::MARKDOWN->value)->toBe('markdown');
            expect(OutputFormat::XML->value)->toBe('xml');
            expect(OutputFormat::CSV->value)->toBe('csv');
        });
    });

    describe('values() method', function () {
        it('returns all enum values as array', function () {
            $values = OutputFormat::values();

            expect($values)->toBeArray()
                ->and($values)->toHaveCount(6)
                ->and($values)->toBe(['console', 'json', 'html', 'markdown', 'xml', 'csv']);
        });

        it('returns values in correct order', function () {
            $expectedValues = ['console', 'json', 'html', 'markdown', 'xml', 'csv'];

            expect(OutputFormat::values())->toBe($expectedValues);
        });
    });

    describe('getSelectOptions() method', function () {
        it('returns associative array with value => description mapping', function () {
            $options = OutputFormat::getSelectOptions();

            expect($options)->toBeArray()
                ->and($options)->toHaveCount(6);

            foreach (OutputFormat::cases() as $format) {
                expect($options)->toHaveKey($format->value)
                    ->and($options[$format->value])->toBe($format->getDescription());
            }
        });

        it('includes all formats in select options', function () {
            $options = OutputFormat::getSelectOptions();
            $expectedKeys = OutputFormat::values();

            expect(array_keys($options))->toBe($expectedKeys);
        });
    });

    describe('isValid() method', function () {
        it('returns true for valid format values', function () {
            foreach (OutputFormat::values() as $value) {
                expect(OutputFormat::isValid($value))->toBeTrue();
            }
        });

        it('returns false for invalid format values', function () {
            expect(OutputFormat::isValid('invalid'))->toBeFalse();
            expect(OutputFormat::isValid('pdf'))->toBeFalse();
            expect(OutputFormat::isValid(''))->toBeFalse();
            expect(OutputFormat::isValid('JSON'))->toBeFalse(); // case sensitive
        });
    });

    describe('getExtension() method', function () {
        it('returns correct file extensions for all formats', function () {
            expect(OutputFormat::CONSOLE->getExtension())->toBe('txt');
            expect(OutputFormat::JSON->getExtension())->toBe('json');
            expect(OutputFormat::HTML->getExtension())->toBe('html');
            expect(OutputFormat::MARKDOWN->getExtension())->toBe('md');
            expect(OutputFormat::XML->getExtension())->toBe('xml');
            expect(OutputFormat::CSV->getExtension())->toBe('csv');
        });

        it('returns non-empty extensions for all formats', function () {
            foreach (OutputFormat::cases() as $format) {
                expect($format->getExtension())->toBeString()
                    ->and($format->getExtension())->not->toBeEmpty();
            }
        });
    });

    describe('getMimeType() method', function () {
        it('returns correct MIME types for all formats', function () {
            expect(OutputFormat::CONSOLE->getMimeType())->toBe('text/plain');
            expect(OutputFormat::JSON->getMimeType())->toBe('application/json');
            expect(OutputFormat::HTML->getMimeType())->toBe('text/html');
            expect(OutputFormat::MARKDOWN->getMimeType())->toBe('text/markdown');
            expect(OutputFormat::XML->getMimeType())->toBe('application/xml');
            expect(OutputFormat::CSV->getMimeType())->toBe('text/csv');
        });

        it('returns valid MIME type format for all formats', function () {
            foreach (OutputFormat::cases() as $format) {
                $mimeType = $format->getMimeType();

                expect($mimeType)->toBeString()
                    ->and($mimeType)->toMatch('/^[a-z]+\/[a-z-]+$/');
            }
        });
    });

    describe('isStructured() method', function () {
        it('returns true for structured data formats', function () {
            expect(OutputFormat::JSON->isStructured())->toBeTrue();
            expect(OutputFormat::XML->isStructured())->toBeTrue();
            expect(OutputFormat::CSV->isStructured())->toBeTrue();
        });

        it('returns false for presentation formats', function () {
            expect(OutputFormat::CONSOLE->isStructured())->toBeFalse();
            expect(OutputFormat::HTML->isStructured())->toBeFalse();
            expect(OutputFormat::MARKDOWN->isStructured())->toBeFalse();
        });
    });

    describe('supportsFormatting() method', function () {
        it('returns true for formats that support styling', function () {
            expect(OutputFormat::CONSOLE->supportsFormatting())->toBeTrue();
            expect(OutputFormat::HTML->supportsFormatting())->toBeTrue();
            expect(OutputFormat::MARKDOWN->supportsFormatting())->toBeTrue();
        });

        it('returns false for plain data formats', function () {
            expect(OutputFormat::JSON->supportsFormatting())->toBeFalse();
            expect(OutputFormat::XML->supportsFormatting())->toBeFalse();
            expect(OutputFormat::CSV->supportsFormatting())->toBeFalse();
        });
    });

    describe('getFormatterClass() method', function () {
        it('returns correct formatter classes for implemented formats', function () {
            expect(OutputFormat::CONSOLE->getFormatterClass())->toBe(ConsoleFormatter::class);
            expect(OutputFormat::JSON->getFormatterClass())->toBe(JsonFormatter::class);
            expect(OutputFormat::HTML->getFormatterClass())->toBe(HtmlFormatter::class);
            expect(OutputFormat::MARKDOWN->getFormatterClass())->toBe(MarkdownFormatter::class);
        });

        it('returns string class names for unimplemented formats', function () {
            expect(OutputFormat::XML->getFormatterClass())->toBe('App\\Services\\Formatters\\XmlFormatter');
            expect(OutputFormat::CSV->getFormatterClass())->toBe('App\\Services\\Formatters\\CsvFormatter');
        });

        it('returns valid class name format for all formats', function () {
            foreach (OutputFormat::cases() as $format) {
                $className = $format->getFormatterClass();

                expect($className)->toBeString()
                    ->and($className)->toMatch('/^[A-Za-z\\\\]+Formatter$/');
            }
        });
    });

    describe('getDefaultFilename() method', function () {
        it('returns appropriate default filenames for all formats', function () {
            expect(OutputFormat::CONSOLE->getDefaultFilename())->toBe('validation-output.txt');
            expect(OutputFormat::JSON->getDefaultFilename())->toBe('validation-report.json');
            expect(OutputFormat::HTML->getDefaultFilename())->toBe('validation-report.html');
            expect(OutputFormat::MARKDOWN->getDefaultFilename())->toBe('VALIDATION_REPORT.md');
            expect(OutputFormat::XML->getDefaultFilename())->toBe('validation-report.xml');
            expect(OutputFormat::CSV->getDefaultFilename())->toBe('validation-data.csv');
        });

        it('includes correct extension in default filename', function () {
            foreach (OutputFormat::cases() as $format) {
                $filename = $format->getDefaultFilename();
                $extension = $format->getExtension();

                expect($filename)->toEndWith(".{$extension}");
            }
        });
    });

    describe('isCiCdFriendly() method', function () {
        it('returns true for machine-readable formats', function () {
            expect(OutputFormat::JSON->isCiCdFriendly())->toBeTrue();
            expect(OutputFormat::XML->isCiCdFriendly())->toBeTrue();
            expect(OutputFormat::CSV->isCiCdFriendly())->toBeTrue();
        });

        it('returns false for human-readable formats', function () {
            expect(OutputFormat::CONSOLE->isCiCdFriendly())->toBeFalse();
            expect(OutputFormat::HTML->isCiCdFriendly())->toBeFalse();
            expect(OutputFormat::MARKDOWN->isCiCdFriendly())->toBeFalse();
        });

        it('aligns with structured data classification', function () {
            foreach (OutputFormat::cases() as $format) {
                // CI/CD friendly formats should be structured
                if ($format->isCiCdFriendly()) {
                    expect($format->isStructured())->toBeTrue();
                }
            }
        });
    });

    describe('getDescription() method', function () {
        it('returns meaningful descriptions for all formats', function () {
            foreach (OutputFormat::cases() as $format) {
                $description = $format->getDescription();

                expect($description)->toBeString()
                    ->and($description)->not->toBeEmpty()
                    ->and(mb_strlen($description))->toBeGreaterThan(10);
            }
        });

        it('includes format name in description', function () {
            expect(OutputFormat::JSON->getDescription())->toContain('JSON');
            expect(OutputFormat::HTML->getDescription())->toContain('HTML');
            expect(OutputFormat::MARKDOWN->getDescription())->toContain('Markdown');
            expect(OutputFormat::XML->getDescription())->toContain('XML');
            expect(OutputFormat::CSV->getDescription())->toContain('CSV');
        });
    });

    describe('getHelpText() method', function () {
        it('returns detailed help text for all formats', function () {
            foreach (OutputFormat::cases() as $format) {
                $helpText = $format->getHelpText();

                expect($helpText)->toBeString()
                    ->and($helpText)->not->toBeEmpty()
                    ->and(mb_strlen($helpText))->toBeGreaterThan(20);
            }
        });
    });

    describe('getUseCases() method', function () {
        it('returns array of use cases for all formats', function () {
            foreach (OutputFormat::cases() as $format) {
                $useCases = $format->getUseCases();

                expect($useCases)->toBeArray()
                    ->and($useCases)->not->toBeEmpty();

                foreach ($useCases as $useCase) {
                    expect($useCase)->toBeString()
                        ->and($useCase)->not->toBeEmpty();
                }
            }
        });

        it('provides multiple use cases for each format', function () {
            foreach (OutputFormat::cases() as $format) {
                expect($format->getUseCases())->toHaveCount(3);
            }
        });
    });

    describe('Type Safety and Edge Cases', function () {
        it('enum cases are of correct type', function () {
            foreach (OutputFormat::cases() as $format) {
                expect($format)->toBeInstanceOf(OutputFormat::class);
                expect($format->value)->toBeString();
            }
        });

        it('handles enum creation from values', function () {
            foreach (OutputFormat::values() as $value) {
                expect(OutputFormat::from($value))->toBeInstanceOf(OutputFormat::class);
                expect(OutputFormat::tryFrom($value))->toBeInstanceOf(OutputFormat::class);
            }
        });

        it('throws exception for invalid values with from()', function () {
            expect(fn () => OutputFormat::from('invalid'))->toThrow(ValueError::class);
        });

        it('returns null for invalid values with tryFrom()', function () {
            expect(OutputFormat::tryFrom('invalid'))->toBeNull();
        });
    });
});
