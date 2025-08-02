<?php

declare(strict_types=1);

namespace Tests\Unit\Contracts;

use App\Enums\LinkStatus;
use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationResult;
use old\Support\Doubles\Contracts\TestReportingImplementation;
use old\Support\Traits\AssertionHelpers;
use old\Support\Traits\MockingHelpers;
use ReflectionClass;

uses(AssertionHelpers::class, MockingHelpers::class);

describe('ReportingInterface', function (): void {
    test('interface exists and is interface', function (): void {
        expect(interface_exists(ReportingInterface::class))->toBeTrue();

        $reflection = new ReflectionClass(ReportingInterface::class);
        expect($reflection->isInterface())->toBeTrue();
    });

    test('interface has required methods', function (): void {
        $reflection = new ReflectionClass(ReportingInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn ($method): string => $method->getName(), $methods);

        $requiredMethods = [
            'generateReport',
            'generateSummary',
            'exportReport',
        ];

        foreach ($requiredMethods as $method) {
            expect($methodNames)->toContain($method, "Interface should have method: {$method}");
        }
    });

    test('generate report method has correct signature', function (): void {
        $reflection = new ReflectionClass(ReportingInterface::class);
        $method = $reflection->getMethod('generateReport');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(3);

        $parameters = $method->getParameters();
        expect($parameters[0]->getName())->toBe('results');
        expect($parameters[1]->getName())->toBe('config');
        expect($parameters[2]->getName())->toBe('command');
    });

    test('generate summary method has correct signature', function (): void {
        $reflection = new ReflectionClass(ReportingInterface::class);
        $method = $reflection->getMethod('generateSummary');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(1);

        $parameters = $method->getParameters();
        expect($parameters[0]->getName())->toBe('results');
    });

    test('export report method has correct signature', function (): void {
        $reflection = new ReflectionClass(ReportingInterface::class);
        $method = $reflection->getMethod('exportReport');

        expect($method->isPublic())->toBeTrue();
        expect($method->getParameters())->toHaveCount(3);

        $parameters = $method->getParameters();
        expect($parameters[0]->getName())->toBe('results');
        expect($parameters[1]->getName())->toBe('filePath');
        expect($parameters[2]->getName())->toBe('format');
    });

    test('interface can be implemented', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();

        expect($implementation)->toBeInstanceOf(ReportingInterface::class);
    });

    test('implementation returns expected types', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();
        $results = [
            ValidationResult::success('https://example1.com', ValidationScope::EXTERNAL, 200, 0.5, []),
            ValidationResult::success('https://example2.com', ValidationScope::EXTERNAL, 200, 0.3, []),
            ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'Not found', 404, null, 1.0, []),
        ];

        $reportResult = $implementation->generateReport($results, OutputFormat::CONSOLE);
        $summaryResult = $implementation->generateSummary($results);
        $exportResult = $implementation->exportReport($results, '/tmp/report.json', OutputFormat::JSON);

        expect($reportResult)->toBeString();
        expect($summaryResult)->toBeArray();
        expect($exportResult)->toBeBool();
    });

    test('implementation generates report', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();
        $results = [
            ValidationResult::success('https://example1.com', ValidationScope::EXTERNAL, 200, 0.5, []),
            ValidationResult::success('https://example2.com', ValidationScope::EXTERNAL, 200, 0.3, []),
            ValidationResult::success('https://example3.com', ValidationScope::EXTERNAL, 200, 0.4, []),
            ValidationResult::success('https://example4.com', ValidationScope::EXTERNAL, 200, 0.6, []),
            ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'Not found', 404, null, 1.0, []),
        ];

        $report = $implementation->generateReport($results, OutputFormat::CONSOLE);

        expect($report)->toBeString();
        expect($report)->toContain('1 broken links');
    });

    test('implementation generates summary', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();
        $results = [
            ValidationResult::success('https://example1.com', ValidationScope::EXTERNAL, 200, 0.5, []),
            ValidationResult::success('https://example2.com', ValidationScope::EXTERNAL, 200, 0.3, []),
            ValidationResult::success('https://example3.com', ValidationScope::EXTERNAL, 200, 0.4, []),
            ValidationResult::success('https://example4.com', ValidationScope::EXTERNAL, 200, 0.6, []),
            ValidationResult::success('https://example5.com', ValidationScope::EXTERNAL, 200, 0.2, []),
            ValidationResult::success('https://example6.com', ValidationScope::EXTERNAL, 200, 0.7, []),
            ValidationResult::success('https://example7.com', ValidationScope::EXTERNAL, 200, 0.3, []),
            ValidationResult::success('https://example8.com', ValidationScope::EXTERNAL, 200, 0.5, []),
            ValidationResult::failure('https://broken1.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'Not found', 404, null, 1.0, []),
            ValidationResult::failure('https://broken2.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'Server error', 500, null, 1.5, []),
        ];

        $summary = $implementation->generateSummary($results);

        expect($summary)->toBeArray();
        expect($summary)->toHaveKey('total_links');
        expect($summary)->toHaveKey('broken_links');
        expect($summary)->toHaveKey('success_rate');
        expect($summary)->toHaveKey('status_breakdown');
    });

    test('implementation exports report', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();
        $results = [
            ValidationResult::success('https://example1.com', ValidationScope::EXTERNAL, 200, 0.5, []),
            ValidationResult::success('https://example2.com', ValidationScope::EXTERNAL, 200, 0.3, []),
            ValidationResult::success('https://example3.com', ValidationScope::EXTERNAL, 200, 0.4, []),
            ValidationResult::success('https://example4.com', ValidationScope::EXTERNAL, 200, 0.6, []),
            ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'Not found', 404, null, 1.0, []),
        ];

        $success = $implementation->exportReport($results, '/tmp/test-report.json', OutputFormat::JSON);

        expect($success)->toBeBool();
    });

    test('interface methods are callable', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();

        // Test that methods exist and can be called (first-class callables are always callable)
        expect($implementation->generateReport(...))->toBeCallable();
        expect($implementation->generateSummary(...))->toBeCallable();
        expect($implementation->exportReport(...))->toBeCallable();
    });

    test('interface has proper namespace', function (): void {
        $reflection = new ReflectionClass(ReportingInterface::class);

        expect($reflection->getNamespaceName())->toBe('App\Services\Contracts');
    });

    test('interface can be used for type hinting', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();

        expect($implementation)->toBeInstanceOf(ReportingInterface::class);

        // Test that all interface methods are implemented
        $reflection = new ReflectionClass(ReportingInterface::class);
        $interfaceMethods = $reflection->getMethods();

        foreach ($interfaceMethods as $method) {
            expect(method_exists($implementation, $method->getName()))->toBeTrue(
                "Implementation should have method: {$method->getName()}"
            );
        }
    });

    test('interface supports different formats', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();
        $results = [
            ValidationResult::success('https://example1.com', ValidationScope::EXTERNAL, 200, 0.5, []),
            ValidationResult::success('https://example2.com', ValidationScope::EXTERNAL, 200, 0.3, []),
            ValidationResult::success('https://example3.com', ValidationScope::EXTERNAL, 200, 0.4, []),
            ValidationResult::success('https://example4.com', ValidationScope::EXTERNAL, 200, 0.6, []),
            ValidationResult::failure('https://broken.com', ValidationScope::EXTERNAL, LinkStatus::BROKEN, 'Not found', 404, null, 1.0, []),
        ];

        $formats = ['json', 'html', 'markdown', 'console'];

        foreach ($formats as $format) {
            $success = $implementation->exportReport($results, "/tmp/report.{$format}", OutputFormat::from($format));
            expect($success)->toBeBool();
        }
    });

    test('interface handles empty results', function (): void {
        $implementation = new old\Support\Doubles\Contracts\TestReportingImplementation();
        $emptyResults = []; // Empty array of ValidationResult objects

        $summary = $implementation->generateSummary($emptyResults);
        $export = $implementation->exportReport($emptyResults, '/tmp/empty-report.json', OutputFormat::JSON);

        expect($summary)->toBeArray();
        expect($export)->toBeBool();
    });

});
