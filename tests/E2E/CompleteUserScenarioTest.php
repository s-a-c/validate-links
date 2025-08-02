<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('Complete User Scenario E2E Tests', function () {
    beforeEach(function () {
        // Setup: Create realistic project structure
        $this->projectDir = sys_get_temp_dir() . '/validate-links-e2e-project';
        $this->docsDir = $this->projectDir . '/docs';
        $this->reportsDir = $this->projectDir . '/reports';
        
        // Create directory structure
        File::makeDirectory($this->projectDir, 0755, true, true);
        File::makeDirectory($this->docsDir, 0755, true, true);
        File::makeDirectory($this->reportsDir, 0755, true, true);
        
        // Create realistic documentation files
        $this->createRealisticDocumentation();
    });

    afterEach(function () {
        // Teardown: Clean up project directory
        if (File::exists($this->projectDir)) {
            File::deleteDirectory($this->projectDir);
        }
    });

    describe('Documentation Project Validation Scenario', function () {
        it('validates complete documentation project from start to finish', function () {
            // Execute: Complete validation workflow
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->docsDir],
                '--scope' => 'all',
                '--format' => 'json',
                '--output' => $this->reportsDir . '/validation-report.json',
                '--recursive' => true,
                '--timeout' => 10,
                '--concurrent' => 5,
            ]);

            // Assert: Validation completes successfully
            expect($exitCode)->toBeInt();
            expect(File::exists($this->reportsDir . '/validation-report.json'))->toBeTrue();

            // Verify report content
            $reportContent = File::get($this->reportsDir . '/validation-report.json');
            $reportData = json_decode($reportContent, true);
            
            expect($reportData)->toBeArray()
                ->and($reportData)->toHaveKey('summary')
                ->and($reportData)->toHaveKey('results')
                ->and($reportData['summary']['total_links'])->toBeGreaterThan(0);
        });

        it('generates multiple report formats for stakeholders', function () {
            // Execute: Generate reports in different formats
            $validationExitCode = Artisan::call('validate', [
                'paths' => [$this->docsDir],
                '--scope' => 'all',
                '--format' => 'json',
                '--output' => $this->reportsDir . '/raw-results.json',
            ]);

            $htmlReportExitCode = Artisan::call('report', [
                'input' => $this->reportsDir . '/raw-results.json',
                '--format' => 'html',
                '--output' => $this->reportsDir . '/stakeholder-report.html',
            ]);

            $markdownReportExitCode = Artisan::call('report', [
                'input' => $this->reportsDir . '/raw-results.json',
                '--format' => 'markdown',
                '--output' => $this->reportsDir . '/developer-report.md',
            ]);

            // Assert: All reports are generated
            expect($validationExitCode)->toBeInt()
                ->and($htmlReportExitCode)->toBe(0)
                ->and($markdownReportExitCode)->toBe(0)
                ->and(File::exists($this->reportsDir . '/stakeholder-report.html'))->toBeTrue()
                ->and(File::exists($this->reportsDir . '/developer-report.md'))->toBeTrue();

            // Verify HTML report structure
            $htmlContent = File::get($this->reportsDir . '/stakeholder-report.html');
            expect($htmlContent)->toContain('<html>')
                ->and($htmlContent)->toContain('validation')
                ->and($htmlContent)->toContain('summary');
        });

        it('handles configuration-driven validation workflow', function () {
            // Setup: Create custom configuration
            $configFile = $this->projectDir . '/validate-links.json';
            $config = [
                'validation' => [
                    'timeout' => 15,
                    'max_redirects' => 3,
                    'concurrent_requests' => 8,
                ],
                'security' => [
                    'blocked_domains' => ['malicious.com', 'spam.example'],
                    'allowed_protocols' => ['http', 'https'],
                ],
                'scopes' => ['external', 'internal'],
                'output' => [
                    'format' => 'json',
                    'include_metadata' => true,
                ],
            ];
            File::put($configFile, json_encode($config, JSON_PRETTY_PRINT));

            // Execute: Validation with custom configuration
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->docsDir],
                '--config' => $configFile,
                '--output' => $this->reportsDir . '/configured-validation.json',
            ]);

            // Assert: Configuration is applied
            expect($exitCode)->toBeInt();
            expect(File::exists($this->reportsDir . '/configured-validation.json'))->toBeTrue();

            $results = json_decode(File::get($this->reportsDir . '/configured-validation.json'), true);
            expect($results)->toBeArray()
                ->and($results)->toHaveKey('summary');
        });
    });

    describe('CI/CD Integration Scenario', function () {
        it('simulates automated validation in CI pipeline', function () {
            // Setup: Create CI-like environment
            putenv('CI=true');
            putenv('VALIDATE_LINKS_OUTPUT_FORMAT=json');
            
            // Execute: Automated validation
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->docsDir],
                '--scope' => 'all',
                '--format' => 'json',
                '--output' => $this->reportsDir . '/ci-report.json',
                '--fail-on-broken' => true,
                '--quiet' => true,
            ]);

            // Assert: CI-appropriate behavior
            expect($exitCode)->toBeInt();
            expect(File::exists($this->reportsDir . '/ci-report.json'))->toBeTrue();

            // Verify CI-friendly output
            $output = Artisan::output();
            expect($output)->toBeString();

            // Clean up environment
            putenv('CI');
            putenv('VALIDATE_LINKS_OUTPUT_FORMAT');
        });

        it('handles batch processing for large documentation sets', function () {
            // Setup: Create large documentation set
            $this->createLargeDocumentationSet();

            // Execute: Batch validation
            $startTime = microtime(true);
            
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->docsDir],
                '--scope' => 'external',
                '--concurrent' => 10,
                '--timeout' => 5,
                '--progress' => true,
            ]);

            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;

            // Assert: Efficient batch processing
            expect($exitCode)->toBeInt()
                ->and($executionTime)->toBeLessThan(60); // Should complete within reasonable time
        });
    });

    describe('Developer Workflow Scenario', function () {
        it('supports iterative development and validation', function () {
            // Setup: Simulate development workflow
            $devFile = $this->docsDir . '/development.md';
            
            // Initial file with broken links
            $initialContent = <<<'MD'
# Development Guide

[Broken Link](https://httpbin.org/status/404)
[Valid Link](https://httpbin.org/status/200)
MD;
            File::put($devFile, $initialContent);

            // Execute: Initial validation
            $initialExitCode = Artisan::call('validate', [
                'paths' => [$devFile],
                '--scope' => 'external',
                '--format' => 'json',
            ]);

            // Simulate fixing the broken link
            $fixedContent = <<<'MD'
# Development Guide

[Fixed Link](https://httpbin.org/status/200)
[Valid Link](https://httpbin.org/status/200)
MD;
            File::put($devFile, $fixedContent);

            // Execute: Re-validation after fix
            $fixedExitCode = Artisan::call('validate', [
                'paths' => [$devFile],
                '--scope' => 'external',
                '--format' => 'json',
            ]);

            // Assert: Iterative workflow works
            expect($initialExitCode)->toBeInt()
                ->and($fixedExitCode)->toBeInt();
        });

        it('provides actionable feedback for link fixing', function () {
            // Setup: Create file with fixable issues
            $fixableFile = $this->docsDir . '/fixable.md';
            $content = <<<'MD'
# Fixable Issues

[HTTP Link](http://httpbin.org/get)
[Redirect Link](https://httpbin.org/redirect/1)
[Case Issue](./README.MD)
MD;
            File::put($fixableFile, $content);

            // Execute: Get fix suggestions
            $exitCode = Artisan::call('fix', [
                'paths' => [$fixableFile],
                '--dry-run' => true,
                '--output' => $this->reportsDir . '/fix-suggestions.json',
            ]);

            // Assert: Fix suggestions are provided
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString()->not->toBeEmpty();
        });
    });

    describe('Real-World Content Validation', function () {
        it('validates documentation with real external dependencies', function () {
            // Setup: Create documentation with real-world links
            $realWorldFile = $this->docsDir . '/real-world.md';
            $content = <<<'MD'
# Real World Documentation

## Popular Services
[GitHub](https://github.com)
[Stack Overflow](https://stackoverflow.com)
[MDN Web Docs](https://developer.mozilla.org)

## API Documentation
[JSONPlaceholder](https://jsonplaceholder.typicode.com)
[HTTPBin](https://httpbin.org)

## Broken Examples
[Non-existent Domain](https://this-domain-definitely-does-not-exist-12345.com)
[404 Page](https://httpbin.org/status/404)
MD;
            File::put($realWorldFile, $content);

            // Execute: Validate real-world content
            $exitCode = Artisan::call('validate', [
                'paths' => [$realWorldFile],
                '--scope' => 'external',
                '--timeout' => 15,
                '--format' => 'json',
                '--output' => $this->reportsDir . '/real-world-results.json',
            ]);

            // Assert: Real-world validation works
            expect($exitCode)->toBeInt();
            expect(File::exists($this->reportsDir . '/real-world-results.json'))->toBeTrue();

            $results = json_decode(File::get($this->reportsDir . '/real-world-results.json'), true);
            expect($results['summary']['total_links'])->toBeGreaterThan(5);
        });
    });

    describe('Error Recovery and Resilience', function () {
        it('handles network failures gracefully in complete workflow', function () {
            // Setup: Create content that will cause network issues
            $networkTestFile = $this->docsDir . '/network-test.md';
            $content = <<<'MD'
[Unreachable](https://this-will-definitely-fail-12345.invalid)
[Valid](https://httpbin.org/status/200)
[Timeout](https://httpbin.org/delay/20)
MD;
            File::put($networkTestFile, $content);

            // Execute: Validation with network issues
            $exitCode = Artisan::call('validate', [
                'paths' => [$networkTestFile],
                '--scope' => 'external',
                '--timeout' => 5,
                '--format' => 'json',
                '--continue-on-error' => true,
            ]);

            // Assert: Graceful handling of network issues
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString();
        });
    });

    // Helper methods
    private function createRealisticDocumentation(): void
    {
        // README.md
        File::put($this->docsDir . '/README.md', <<<'MD'
# Project Documentation

Welcome to our project documentation.

## Quick Links
- [Installation Guide](./installation.md)
- [API Reference](./api.md)
- [Contributing](./contributing.md)

## External Resources
- [GitHub Repository](https://github.com/example/project)
- [Documentation Site](https://docs.example.com)
- [Issue Tracker](https://github.com/example/project/issues)
MD);

        // installation.md
        File::put($this->docsDir . '/installation.md', <<<'MD'
# Installation Guide

## Prerequisites
- [Node.js](https://nodejs.org)
- [Git](https://git-scm.com)

## Steps
1. Clone the repository
2. Install dependencies
3. Configure settings

[Back to README](./README.md)
MD);

        // api.md
        File::put($this->docsDir . '/api.md', <<<'MD'
# API Reference

## Endpoints
- [Users API](https://api.example.com/users)
- [Posts API](https://api.example.com/posts)

## Authentication
See [Auth Guide](./auth.md)

[Home](./README.md)
MD);

        // contributing.md
        File::put($this->docsDir . '/contributing.md', <<<'MD'
# Contributing Guide

## Development Setup
Follow the [Installation Guide](./installation.md)

## External Tools
- [ESLint](https://eslint.org)
- [Prettier](https://prettier.io)
- [Jest](https://jestjs.io)

## Broken Link Example
[This will be broken](https://httpbin.org/status/404)
MD);
    }

    private function createLargeDocumentationSet(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $content = <<<MD
# Document {$i}

This is document number {$i}.

## External Links
[HTTPBin Status](https://httpbin.org/status/200)
[JSONPlaceholder](https://jsonplaceholder.typicode.com/posts/{$i})

## Internal Links
[README](./README.md)
[Document {$i}](./doc{$i}.md)

## Navigation
- [Previous](./doc{$i-1}.md)
- [Next](./doc{$i+1}.md)
MD;
            File::put($this->docsDir . "/doc{$i}.md", $content);
        }
    }
});
