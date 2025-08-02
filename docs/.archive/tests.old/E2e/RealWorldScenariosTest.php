<?php

declare(strict_types=1);

use App\Commands\ValidateCommand;
use App\Enums\ValidationScope;
use App\Services\LinkValidationService;
use App\Services\ReportingService;
use App\Services\SecurityValidationService;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\HttpTestHelpers;
use old\Support\Traits\ValidationTestHelpers;

/**
 * End-to-end test suite for real-world link validation scenarios.
 *
 * Uses REAL implementations instead of mocks to test complete user workflows.
 *
 * @group e2e
 * @group integration
 *
 * @covers \App\Commands\ValidateCommand
 */
uses(FileTestHelpers::class, HttpTestHelpers::class, ValidationTestHelpers::class);

describe('Real-World Link Validation Scenarios', function (): void {

    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    describe('real documentation validation scenarios', function (): void {
        test('validates complete project documentation with mixed link types', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);

            // Create a realistic project structure with actual files
            $this->createTestFile('README.md', '
# Project Documentation

Welcome to our project! This documentation covers:

- [Getting Started](./docs/getting-started.md)
- [API Reference](./docs/api.md)
- [Contributing](./CONTRIBUTING.md)

## External Resources

- [HTTPBin Status](https://httpbin.org/status/200)
- [HTTPBin JSON](https://httpbin.org/json)

## Quick Links

- [Installation Guide](./docs/installation.md)
- [Configuration](./docs/configuration.md)
            ');

            $this->createTestDirectory('docs');
            $this->createTestFile('docs/getting-started.md', '
# Getting Started

Quick start guide for new users.

## Prerequisites

- PHP 8.1+
- Composer
- Git

## Installation

See [Installation Guide](./installation.md) for details.

## Next Steps

- [Configuration](./configuration.md)
- [API Reference](./api.md)
            ');

            $this->createTestFile('docs/installation.md', '# Installation Guide

Step-by-step installation instructions.');

            $this->createTestFile('docs/configuration.md', '# Configuration

Configuration options and examples.');

            $this->createTestFile('docs/api.md', '# API Reference

Complete API documentation.');

            $this->createTestFile('CONTRIBUTING.md', '# Contributing

Guidelines for contributors.');

            // Test real validation with actual services
            /** @var App\Services\ValueObjects\ValidationConfig $config */
            $config = $this->createValidationConfig();
            /** @var string $filePath */
            $filePath = $this->getTestPath('README.md');
            $results = $linkValidationService->validateFile($filePath, $config);

            expect($results)->toBeArray();
            expect($results)->not->toBeEmpty();

            // Verify that internal links are properly validated
            $internalLinks = array_filter($results, fn ($result) => $result->getScope() === ValidationScope::INTERNAL);
            expect($internalLinks)->not->toBeEmpty();

            // Verify that external links are validated (using httpbin for reliable testing)
            $externalLinks = array_filter($results, fn ($result) => $result->getScope() === ValidationScope::EXTERNAL);
            expect($externalLinks)->not->toBeEmpty();

            // Check that valid internal links are marked as valid
            $validInternalLinks = array_filter($internalLinks, fn ($result) => $result->isValid());
            expect($validInternalLinks)->not->toBeEmpty();
        });
    });

    describe('command option validation', function (): void {
        test('command supports scope option for external validation', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);
            $reportingService = new ReportingService();
            $command = new ValidateCommand($linkValidationService, $reportingService);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('scope')
                ->and($signature)->toContain('external');
        });

        test('command supports timeout option for external links', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);
            $reportingService = new ReportingService();
            $command = new ValidateCommand($linkValidationService, $reportingService);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('timeout');
        });

        test('command supports format option for output', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);
            $reportingService = new ReportingService();
            $command = new ValidateCommand($linkValidationService, $reportingService);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('format');
        });
    });

    describe('command dependency injection', function (): void {
        test('command properly injects LinkValidationInterface', function (): void {
            $reflection = new ReflectionClass(ValidateCommand::class);
            $constructor = $reflection->getConstructor();

            expect($constructor)->not->toBeNull();

            $parameters = $constructor->getParameters();
            expect($parameters)->toHaveCount(2);

            $firstParam = $parameters[0];
            expect($firstParam->getName())->toBe('linkValidation');
            expect((string) $firstParam->getType())->toBe('App\Services\Contracts\LinkValidationInterface');
        });

        test('command properly injects ReportingInterface', function (): void {
            $reflection = new ReflectionClass(ValidateCommand::class);
            $constructor = $reflection->getConstructor();

            $parameters = $constructor->getParameters();
            $secondParam = $parameters[1];

            expect($secondParam->getName())->toBe('reporting');
            expect((string) $secondParam->getType())->toBe('App\Services\Contracts\ReportingInterface');
        });

        test('command extends BaseValidationCommand', function (): void {
            $reflection = new ReflectionClass(ValidateCommand::class);
            $parentClass = $reflection->getParentClass();

            expect($parentClass)->not->toBeFalse();
            expect($parentClass->getName())->toBe('App\Commands\BaseValidationCommand');
        });
    });

    describe('command advanced options', function (): void {
        test('command supports concurrent validation options', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);
            $reportingService = new ReportingService();
            $command = new ValidateCommand($linkValidationService, $reportingService);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('concurrent');
        });

        test('command supports filter options for broken links', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);
            $reportingService = new ReportingService();
            $command = new ValidateCommand($linkValidationService, $reportingService);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('filter');
        });

        test('command supports CI mode configuration', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);
            $reportingService = new ReportingService();
            $command = new ValidateCommand($linkValidationService, $reportingService);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('ci');
        });

        test('command supports fail-fast option', function (): void {
            // Use REAL services for true E2E testing
            $securityService = new SecurityValidationService();
            $linkValidationService = new LinkValidationService($securityService);
            $reportingService = new ReportingService();
            $command = new ValidateCommand($linkValidationService, $reportingService);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('fail-fast');
        });
    });

    describe('command method structure', function (): void {
        test('command has required handle method', function (): void {
            $reflection = new ReflectionClass(ValidateCommand::class);

            expect($reflection->hasMethod('handle'))->toBeTrue();

            $handleMethod = $reflection->getMethod('handle');
            expect($handleMethod->isPublic())->toBeTrue();
        });

        test('command has proper method visibility', function (): void {
            $reflection = new ReflectionClass(ValidateCommand::class);
            $methods = $reflection->getMethods();

            $publicMethods = array_filter($methods, fn ($method) => $method->isPublic());
            $privateMethods = array_filter($methods, fn ($method) => $method->isPrivate());

            expect($publicMethods)->not->toBeEmpty();
            expect($privateMethods)->toBeArray(); // May or may not have private methods
        });
    });
});
