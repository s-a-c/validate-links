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
 * Unit test suite for OutputFormat enum.
 *
 * Tests all enum cases, values, and formatter integration methods to ensure
 * proper functionality of output format selection and formatter instantiation.
 *
 * @group unit
 * @group enums
 *
 * @covers \App\Enums\OutputFormat
 */
describe('OutputFormat Enum', function (): void {

    test('has all expected cases with correct values', function (): void {
        $expectedCases = [
            'CONSOLE' => 'console',
            'JSON' => 'json',
            'HTML' => 'html',
            'MARKDOWN' => 'markdown',
            'XML' => 'xml',
            'CSV' => 'csv',
        ];

        $actualCases = OutputFormat::cases();
        expect($actualCases)->toHaveCount(count($expectedCases));

        foreach ($expectedCases as $name => $value) {
            $case = OutputFormat::tryFrom($value);
            expect($case)->not->toBeNull("Case {$name} with value '{$value}' should exist");
            expect($case->name)->toBe($name);
            expect($case->value)->toBe($value);
        }
    });

    test('values() returns correct array', function (): void {
        $expectedValues = ['console', 'json', 'html', 'markdown', 'xml', 'csv'];

        $values = OutputFormat::values();
        expect($values)->toBeArray();
        expect($values)->toHaveCount(count($expectedValues));
        expect($values)->toEqual($expectedValues);
    });

    test('cases() returns all enum cases', function (): void {
        $cases = OutputFormat::cases();

        expect($cases)->toHaveCount(6);
        expect($cases)->each->toBeInstanceOf(OutputFormat::class);
    });

    test('tryFrom() works correctly', function (): void {
        $validValues = ['console', 'json', 'html', 'markdown', 'xml', 'csv'];
        $invalidValues = ['invalid', 'unknown', '', 'JSON', 'Html', 'txt', 'pdf'];

        foreach ($validValues as $value) {
            $case = OutputFormat::tryFrom($value);
            expect($case)->not->toBeNull("tryFrom('{$value}') should return a case");
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            $case = OutputFormat::tryFrom($value);
            expect($case)->toBeNull("tryFrom('{$value}') should return null");
        }
    });

    test('from() works correctly and throws for invalid values', function (): void {
        $validValues = ['console', 'json', 'html', 'markdown', 'xml', 'csv'];
        $invalidValues = ['invalid', 'unknown', '', 'JSON', 'Html', 'txt', 'pdf'];

        foreach ($validValues as $value) {
            $case = OutputFormat::from($value);
            expect($case)->toBeInstanceOf(OutputFormat::class);
            expect($case->value)->toBe($value);
        }

        foreach ($invalidValues as $value) {
            expect(fn () => OutputFormat::from($value))
                ->toThrow(ValueError::class);
        }
    });

    test('isValid() static method works correctly', function (): void {
        $validValues = ['console', 'json', 'html', 'markdown', 'xml', 'csv'];
        $invalidValues = ['invalid', 'unknown', '', 'JSON', 'Html', 'txt', 'pdf'];

        foreach ($validValues as $value) {
            expect(OutputFormat::isValid($value))->toBeTrue("'{$value}' should be valid");
        }

        foreach ($invalidValues as $value) {
            expect(OutputFormat::isValid($value))->toBeFalse("'{$value}' should be invalid");
        }
    });

    test('getSelectOptions() returns properly formatted array', function (): void {
        $options = OutputFormat::getSelectOptions();

        expect($options)->toBeArray();
        expect($options)->not->toBeEmpty();
        expect($options)->toHaveCount(6);

        // Verify the new format: array<string, string> where key is value and value is label
        foreach ($options as $value => $label) {
            expect($value)->toBeString();
            expect($label)->toBeString();
            expect(OutputFormat::isValid($value))->toBeTrue();
            expect($label)->not->toBeEmpty();
        }

        // Verify all expected format values are present
        $expectedValues = ['console', 'json', 'html', 'markdown', 'xml', 'csv'];
        foreach ($expectedValues as $expectedValue) {
            expect($options)->toHaveKey($expectedValue);
        }
    });

    test('getExtension() returns correct file extensions', function (): void {
        $expectedExtensions = [
            'console' => 'txt',
            'json' => 'json',
            'html' => 'html',
            'markdown' => 'md',
            'xml' => 'xml',
            'csv' => 'csv',
        ];

        foreach ($expectedExtensions as $caseValue => $expectedReturn) {
            $case = OutputFormat::from($caseValue);
            $actualReturn = $case->getExtension();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getExtension() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getMimeType() returns correct MIME types', function (): void {
        $expectedMimeTypes = [
            'console' => 'text/plain',
            'json' => 'application/json',
            'html' => 'text/html',
            'markdown' => 'text/markdown',
            'xml' => 'application/xml',
            'csv' => 'text/csv',
        ];

        foreach ($expectedMimeTypes as $caseValue => $expectedReturn) {
            $case = OutputFormat::from($caseValue);
            $actualReturn = $case->getMimeType();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getMimeType() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('isStructured() returns correct values', function (): void {
        $trueCases = ['json', 'xml', 'csv'];
        $falseCases = ['console', 'html', 'markdown'];

        foreach ($trueCases as $caseValue) {
            $case = OutputFormat::from($caseValue);
            expect($case->isStructured())->toBeTrue("Method isStructured() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = OutputFormat::from($caseValue);
            expect($case->isStructured())->toBeFalse("Method isStructured() should return false for case {$caseValue}");
        }
    });

    test('supportsFormatting() returns correct values', function (): void {
        $trueCases = ['console', 'html', 'markdown'];
        $falseCases = ['json', 'xml', 'csv'];

        foreach ($trueCases as $caseValue) {
            $case = OutputFormat::from($caseValue);
            expect($case->supportsFormatting())->toBeTrue("Method supportsFormatting() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = OutputFormat::from($caseValue);
            expect($case->supportsFormatting())->toBeFalse("Method supportsFormatting() should return false for case {$caseValue}");
        }
    });

    test('isCiCdFriendly() returns correct values', function (): void {
        $trueCases = ['json', 'xml', 'csv'];
        $falseCases = ['console', 'html', 'markdown'];

        foreach ($trueCases as $caseValue) {
            $case = OutputFormat::from($caseValue);
            expect($case->isCiCdFriendly())->toBeTrue("Method isCiCdFriendly() should return true for case {$caseValue}");
        }

        foreach ($falseCases as $caseValue) {
            $case = OutputFormat::from($caseValue);
            expect($case->isCiCdFriendly())->toBeFalse("Method isCiCdFriendly() should return false for case {$caseValue}");
        }
    });

    test('getDefaultFilename() returns appropriate filenames', function (): void {
        $expectedFilenames = [
            'console' => 'validation-output.txt',
            'json' => 'validation-report.json',
            'html' => 'validation-report.html',
            'markdown' => 'VALIDATION_REPORT.md',
            'xml' => 'validation-report.xml',
            'csv' => 'validation-data.csv',
        ];

        foreach ($expectedFilenames as $caseValue => $expectedReturn) {
            $case = OutputFormat::from($caseValue);
            $actualReturn = $case->getDefaultFilename();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getDefaultFilename() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getFormatterClass() returns correct class names', function (): void {
        $expectedClasses = [
            'console' => ConsoleFormatter::class,
            'json' => JsonFormatter::class,
            'html' => HtmlFormatter::class,
            'markdown' => MarkdownFormatter::class,
            'xml' => 'App\\Services\\Formatters\\XmlFormatter',
            'csv' => 'App\\Services\\Formatters\\CsvFormatter',
        ];

        foreach ($expectedClasses as $caseValue => $expectedReturn) {
            $case = OutputFormat::from($caseValue);
            $actualReturn = $case->getFormatterClass();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getFormatterClass() for case {$caseValue} should return '{$expectedReturn}', got '{$actualReturn}'");
        }
    });

    test('getDescription() returns non-empty strings', function (): void {
        foreach (OutputFormat::cases() as $case) {
            $description = $case->getDescription();
            expect($description)->toBeString();
            expect($description)->not->toBeEmpty("Description for case {$case->name} should not be empty");
        }
    });

    test('getHelpText() returns non-empty strings', function (): void {
        foreach (OutputFormat::cases() as $case) {
            $helpText = $case->getHelpText();
            expect($helpText)->toBeString();
            expect($helpText)->not->toBeEmpty("Help text for case {$case->name} should not be empty");
        }
    });

    test('getUseCases() returns arrays of use cases', function (): void {
        $expectedUseCases = [
            'console' => ['Interactive development', 'Quick validation', 'CI/CD logs'],
            'json' => ['API integration', 'Automated processing', 'Data analysis'],
            'html' => ['Team reports', 'Documentation sites', 'Executive summaries'],
            'markdown' => ['Project documentation', 'README files', 'Wiki pages'],
            'xml' => ['Enterprise integration', 'Data exchange', 'Legacy systems'],
            'csv' => ['Data analysis', 'Spreadsheet import', 'Statistical processing'],
        ];

        foreach ($expectedUseCases as $caseValue => $expectedReturn) {
            $case = OutputFormat::from($caseValue);
            $actualReturn = $case->getUseCases();
            expect($actualReturn)->toBe($expectedReturn,
                "Method getUseCases() for case {$caseValue} should return expected array");
        }
    });

    test('serialization works correctly', function (): void {
        foreach (OutputFormat::cases() as $case) {
            // Test JSON serialization
            $json = json_encode($case);
            expect($json)->toBeString();
            expect($json)->toContain($case->value);

            // Test that we can recreate from value
            $recreated = OutputFormat::from($case->value);
            expect($recreated)->toBe($case);
        }
    });

    test('comparison operations work correctly', function (): void {
        $cases = OutputFormat::cases();

        foreach ($cases as $case1) {
            foreach ($cases as $case2) {
                if ($case1 === $case2) {
                    expect($case1)->toBe($case2);
                    expect($case1->value)->toBe($case2->value);
                } else {
                    expect($case1)->not->toBe($case2);
                }
            }
        }
    });

    test('all methods return consistent types', function (): void {
        // Test string methods
        $stringMethods = ['getExtension', 'getMimeType', 'getDefaultFilename', 'getFormatterClass'];
        foreach ($stringMethods as $method) {
            foreach (OutputFormat::cases() as $case) {
                $value = $case->$method();
                expect($value)->toBeString("Method {$method}() for case {$case->name} should return a string");
            }
        }

        // Test optional string methods
        $optionalStringMethods = ['getDescription', 'getHelpText'];
        foreach ($optionalStringMethods as $method) {
            if (method_exists(OutputFormat::class, $method)) {
                foreach (OutputFormat::cases() as $case) {
                    $value = $case->$method();
                    expect($value)->toBeString("Method {$method}() for case {$case->name} should return a string");
                }
            }
        }

        // Test array methods
        foreach (OutputFormat::cases() as $case) {
            $value = $case->getUseCases();
            expect($value)->toBeArray("Method getUseCases() for case {$case->name} should return an array");
        }

        // Test boolean methods
        $boolMethods = ['isStructured', 'supportsFormatting', 'isCiCdFriendly'];
        foreach ($boolMethods as $method) {
            foreach (OutputFormat::cases() as $case) {
                $value = $case->$method();
                expect($value)->toBeBool("Method {$method}() for case {$case->name} should return a boolean");
            }
        }
    });

    // Business logic and edge case tests
    test('structured formats are CI/CD friendly', function (): void {
        foreach (OutputFormat::cases() as $format) {
            if ($format->isStructured()) {
                expect($format->isCiCdFriendly())->toBeTrue(
                    "Structured format {$format->name} should be CI/CD friendly"
                );
            }
        }
    });

    test('formatting and structured are mutually exclusive for most formats', function (): void {
        foreach (OutputFormat::cases() as $format) {
            // HTML and Markdown are exceptions - they support formatting but aren't structured
            if ($format === OutputFormat::HTML || $format === OutputFormat::MARKDOWN) {
                expect($format->supportsFormatting())->toBeTrue();
                expect($format->isStructured())->toBeFalse();
            } elseif ($format === OutputFormat::CONSOLE) {
                // Console supports formatting but isn't structured
                expect($format->supportsFormatting())->toBeTrue();
                expect($format->isStructured())->toBeFalse();
            } else {
                // JSON, XML, CSV are structured but don't support formatting
                expect($format->isStructured())->toBeTrue();
                expect($format->supportsFormatting())->toBeFalse();
            }
        }
    });

    test('MIME types are valid', function (): void {
        $validMimePatterns = [
            'text/plain', 'text/html', 'text/markdown', 'text/csv',
            'application/json', 'application/xml',
        ];

        foreach (OutputFormat::cases() as $format) {
            $mimeType = $format->getMimeType();
            expect($mimeType)->toBeIn($validMimePatterns,
                "MIME type {$mimeType} for {$format->name} should be valid"
            );
        }
    });

    test('file extensions match format names where appropriate', function (): void {
        // Most formats should have extensions that match their names
        $directMatches = [
            OutputFormat::JSON, OutputFormat::HTML,
            OutputFormat::XML, OutputFormat::CSV,
        ];

        foreach ($directMatches as $format) {
            expect($format->getExtension())->toBe($format->value);
        }

        // Special cases
        expect(OutputFormat::MARKDOWN->getExtension())->toBe('md');
        expect(OutputFormat::CONSOLE->getExtension())->toBe('txt');
    });

    test('default filenames include appropriate extensions', function (): void {
        foreach (OutputFormat::cases() as $format) {
            $filename = $format->getDefaultFilename();
            $extension = $format->getExtension();

            expect($filename)->toEndWith(".{$extension}",
                "Default filename {$filename} should end with .{$extension}"
            );
        }
    });

    test('formatter class names follow naming convention', function (): void {
        foreach (OutputFormat::cases() as $format) {
            $className = $format->getFormatterClass();

            expect($className)->toStartWith('App\\Services\\Formatters\\');
            expect($className)->toEndWith('Formatter');
            expect($className)->toContain(ucfirst($format->value));
        }
    });

    test('use cases are relevant to format capabilities', function (): void {
        // CI/CD friendly formats should have automation-related use cases
        $ciCdFormats = array_filter(OutputFormat::cases(), fn ($f): bool => $f->isCiCdFriendly());
        foreach ($ciCdFormats as $format) {
            $useCases = $format->getUseCases();
            $useCasesText = implode(' ', $useCases);

            expect($useCasesText)->toMatch('/(API|integration|processing|analysis|automated)/i',
                "CI/CD format {$format->name} should have automation-related use cases"
            );
        }

        // Formatting formats should have presentation-related use cases
        $formattingFormats = array_filter(OutputFormat::cases(), fn ($f): bool => $f->supportsFormatting());
        foreach ($formattingFormats as $format) {
            $useCases = $format->getUseCases();
            $useCasesText = implode(' ', $useCases);

            expect($useCasesText)->toMatch('/(report|documentation|display|interactive|summary)/i',
                "Formatting format {$format->name} should have presentation-related use cases"
            );
        }
    });
});
