<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Services\ValueObjects\FormatterOptions;

describe('FormatterOptions', function () {
    it('creates valid formatter options with all parameters', function () {
        $customOptions = ['theme' => 'dark', 'verbose' => true];

        $options = new FormatterOptions(
            format: OutputFormat::JSON,
            includeTimestamps: true,
            colorOutput: false,
            maxLineLength: 80,
            showProgress: false,
            customOptions: $customOptions
        );

        expect($options->format)->toBe(OutputFormat::JSON);
        expect($options->includeTimestamps)->toBeTrue();
        expect($options->colorOutput)->toBeFalse();
        expect($options->maxLineLength)->toBe(80);
        expect($options->showProgress)->toBeFalse();
        expect($options->customOptions)->toBe($customOptions);
    });

    it('creates formatter options with default values', function () {
        $options = new FormatterOptions(
            format: OutputFormat::CONSOLE
        );

        expect($options->format)->toBe(OutputFormat::CONSOLE);
        expect($options->includeTimestamps)->toBeFalse();
        expect($options->colorOutput)->toBeTrue();
        expect($options->maxLineLength)->toBe(120);
        expect($options->showProgress)->toBeTrue();
        expect($options->customOptions)->toBe([]);
    });

    it('creates options from array data with all fields', function () {
        $data = [
            'format' => 'json',
            'include_timestamps' => true,
            'color_output' => false,
            'max_line_length' => 80,
            'show_progress' => false,
            'custom_options' => ['theme' => 'dark'],
        ];

        $options = FormatterOptions::create($data);

        expect($options->format)->toBe(OutputFormat::JSON);
        expect($options->includeTimestamps)->toBeTrue();
        expect($options->colorOutput)->toBeFalse();
        expect($options->maxLineLength)->toBe(80);
        expect($options->showProgress)->toBeFalse();
        expect($options->customOptions)->toBe(['theme' => 'dark']);
    });

    it('creates options from array data with minimal fields', function () {
        $data = [
            'format' => 'html',
        ];

        $options = FormatterOptions::create($data);

        expect($options->format)->toBe(OutputFormat::HTML);
        expect($options->includeTimestamps)->toBeFalse();
        expect($options->colorOutput)->toBeTrue();
        expect($options->maxLineLength)->toBe(120);
        expect($options->showProgress)->toBeTrue();
        expect($options->customOptions)->toBe([]);
    });

    it('creates options with default values when data is empty', function () {
        $options = FormatterOptions::create([]);

        expect($options->format)->toBe(OutputFormat::CONSOLE);
        expect($options->includeTimestamps)->toBeFalse();
        expect($options->colorOutput)->toBeTrue();
        expect($options->maxLineLength)->toBe(120);
        expect($options->showProgress)->toBeTrue();
        expect($options->customOptions)->toBe([]);
    });

    it('handles OutputFormat enum in create method', function () {
        $data = [
            'format' => OutputFormat::MARKDOWN,
        ];

        $options = FormatterOptions::create($data);

        expect($options->format)->toBe(OutputFormat::MARKDOWN);
    });

    it('defaults to CONSOLE format for invalid format string', function () {
        $data = [
            'format' => 'invalid_format',
        ];

        $options = FormatterOptions::create($data);

        expect($options->format)->toBe(OutputFormat::CONSOLE);
    });

    it('validates minimum line length', function () {
        expect(fn () => new FormatterOptions(
            format: OutputFormat::CONSOLE,
            maxLineLength: 39
        ))->toThrow(InvalidArgumentException::class, 'Max line length must be at least 40 characters');
    });

    it('validates custom options is array', function () {
        expect(fn () => new FormatterOptions(
            format: OutputFormat::CONSOLE,
            customOptions: 'not_an_array'
        ))->toThrow(TypeError::class);
    });

    it('allows minimum valid line length', function () {
        $options = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            maxLineLength: 40
        );

        expect($options->maxLineLength)->toBe(40);
    });

    it('creates new instance with different format', function () {
        $original = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            includeTimestamps: true,
            colorOutput: false,
            maxLineLength: 80,
            showProgress: false,
            customOptions: ['theme' => 'dark']
        );

        $modified = $original->withFormat(OutputFormat::JSON);

        expect($modified->format)->toBe(OutputFormat::JSON);
        expect($modified->includeTimestamps)->toBe($original->includeTimestamps);
        expect($modified->colorOutput)->toBe($original->colorOutput);
        expect($modified->maxLineLength)->toBe($original->maxLineLength);
        expect($modified->showProgress)->toBe($original->showProgress);
        expect($modified->customOptions)->toBe($original->customOptions);
        expect($modified)->not->toBe($original); // Ensure immutability
    });

    it('creates new instance with different color output', function () {
        $original = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            colorOutput: true
        );

        $modified = $original->withColorOutput(false);

        expect($modified->colorOutput)->toBeFalse();
        expect($modified->format)->toBe($original->format);
        expect($modified)->not->toBe($original); // Ensure immutability
    });

    it('creates new instance with custom option', function () {
        $original = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            customOptions: ['existing' => 'value']
        );

        $modified = $original->withCustomOption('new_option', 'new_value');

        expect($modified->customOptions)->toBe([
            'existing' => 'value',
            'new_option' => 'new_value',
        ]);
        expect($modified)->not->toBe($original); // Ensure immutability
    });

    it('overwrites existing custom option', function () {
        $original = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            customOptions: ['theme' => 'light']
        );

        $modified = $original->withCustomOption('theme', 'dark');

        expect($modified->customOptions)->toBe(['theme' => 'dark']);
    });

    it('gets custom option with default value', function () {
        $options = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            customOptions: ['theme' => 'dark']
        );

        expect($options->getCustomOption('theme'))->toBe('dark');
        expect($options->getCustomOption('missing'))->toBeNull();
        expect($options->getCustomOption('missing', 'default'))->toBe('default');
    });

    it('checks if custom option exists', function () {
        $options = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            customOptions: ['theme' => 'dark', 'verbose' => null]
        );

        expect($options->hasCustomOption('theme'))->toBeTrue();
        expect($options->hasCustomOption('verbose'))->toBeTrue(); // null value still exists
        expect($options->hasCustomOption('missing'))->toBeFalse();
    });

    it('correctly determines color support', function () {
        $consoleWithColor = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            colorOutput: true
        );

        $consoleWithoutColor = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            colorOutput: false
        );

        $jsonWithColor = new FormatterOptions(
            format: OutputFormat::JSON,
            colorOutput: true
        );

        expect($consoleWithColor->supportsColor())->toBeTrue();
        expect($consoleWithoutColor->supportsColor())->toBeFalse();
        expect($jsonWithColor->supportsColor())->toBeFalse(); // JSON doesn't support formatting
    });

    it('correctly identifies structured formats', function () {
        $structuredFormats = [
            OutputFormat::JSON,
            OutputFormat::XML,
            OutputFormat::CSV,
        ];

        $nonStructuredFormats = [
            OutputFormat::CONSOLE,
            OutputFormat::HTML,
            OutputFormat::MARKDOWN,
        ];

        foreach ($structuredFormats as $format) {
            $options = new FormatterOptions(format: $format);
            expect($options->isStructuredFormat())->toBeTrue();
        }

        foreach ($nonStructuredFormats as $format) {
            $options = new FormatterOptions(format: $format);
            expect($options->isStructuredFormat())->toBeFalse();
        }
    });

    it('converts to array correctly', function () {
        $customOptions = ['theme' => 'dark', 'verbose' => true];

        $options = new FormatterOptions(
            format: OutputFormat::JSON,
            includeTimestamps: true,
            colorOutput: false,
            maxLineLength: 80,
            showProgress: false,
            customOptions: $customOptions
        );

        $expected = [
            'format' => 'json',
            'include_timestamps' => true,
            'color_output' => false,
            'max_line_length' => 80,
            'show_progress' => false,
            'custom_options' => $customOptions,
        ];

        expect($options->toArray())->toBe($expected);
    });

    it('converts to array with default values', function () {
        $options = new FormatterOptions(
            format: OutputFormat::CONSOLE
        );

        $expected = [
            'format' => 'console',
            'include_timestamps' => false,
            'color_output' => true,
            'max_line_length' => 120,
            'show_progress' => true,
            'custom_options' => [],
        ];

        expect($options->toArray())->toBe($expected);
    });

    it('handles all output formats correctly', function () {
        $formats = [
            OutputFormat::CONSOLE,
            OutputFormat::JSON,
            OutputFormat::HTML,
            OutputFormat::MARKDOWN,
            OutputFormat::XML,
            OutputFormat::CSV,
        ];

        foreach ($formats as $format) {
            $options = new FormatterOptions(format: $format);

            expect($options->format)->toBe($format);
            expect($options->toArray()['format'])->toBe($format->value);
        }
    });

    it('handles boolean type conversion in create method', function () {
        $data = [
            'format' => 'console',
            'include_timestamps' => 1,
            'color_output' => 0,
            'show_progress' => 'true',
        ];

        $options = FormatterOptions::create($data);

        expect($options->includeTimestamps)->toBeTrue();
        expect($options->colorOutput)->toBeFalse();
        expect($options->showProgress)->toBeTrue();
    });

    it('handles integer type conversion in create method', function () {
        $data = [
            'format' => 'console',
            'max_line_length' => '100',
        ];

        $options = FormatterOptions::create($data);

        expect($options->maxLineLength)->toBe(100);
    });

    it('handles empty custom options correctly', function () {
        $options = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            customOptions: []
        );

        expect($options->customOptions)->toBe([]);
        expect($options->hasCustomOption('anything'))->toBeFalse();
        expect($options->getCustomOption('anything'))->toBeNull();
        expect($options->toArray()['custom_options'])->toBe([]);
    });

    it('preserves custom option types', function () {
        $customOptions = [
            'string' => 'value',
            'integer' => 42,
            'boolean' => true,
            'array' => ['nested', 'values'],
            'null' => null,
        ];

        $options = new FormatterOptions(
            format: OutputFormat::CONSOLE,
            customOptions: $customOptions
        );

        expect($options->getCustomOption('string'))->toBe('value');
        expect($options->getCustomOption('integer'))->toBe(42);
        expect($options->getCustomOption('boolean'))->toBeTrue();
        expect($options->getCustomOption('array'))->toBe(['nested', 'values']);
        expect($options->getCustomOption('null'))->toBeNull();
    });
});
