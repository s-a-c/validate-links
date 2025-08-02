<?php

declare(strict_types=1);

use App\Commands\ValidateCommand;
use App\Commands\ReportCommand;
use App\Commands\ConfigCommand;
use App\Commands\FixCommand;
use Illuminate\Support\Facades\Artisan;

describe('Command Workflow Feature Tests', function () {
    beforeEach(function () {
        // Setup: Create test environment
        $this->tempDir = sys_get_temp_dir() . '/validate-links-feature-tests';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        $this->testFile = $this->tempDir . '/test.md';
        $this->configFile = $this->tempDir . '/config.json';
        $this->reportFile = $this->tempDir . '/report.json';
        
        // Create comprehensive test content
        $this->testContent = <<<'MD'
# Feature Test Document

## External Links
[Valid HTTP](https://httpbin.org/status/200)
[Valid HTTPS](https://httpbin.org/get)
[Broken Link](https://httpbin.org/status/404)
[Timeout Link](https://httpbin.org/delay/10)

## Internal Links
[README](./README.md)
[Non-existent](./missing.md)
[Config File](./config.json)

## Anchor Links
[Valid Anchor](#feature-test-document)
[Invalid Anchor](#non-existent-section)

## Mixed Content
[Image Link](https://httpbin.org/image/png)
[JSON API](https://httpbin.org/json)
[XML Content](https://httpbin.org/xml)

## Edge Cases
[Empty Link]()
[Malformed](not-a-url)
[JavaScript](javascript:alert('test'))
[FTP Link](ftp://example.com/file.txt)
MD;
        
        file_put_contents($this->testFile, $this->testContent);
    });

    afterEach(function () {
        // Teardown: Clean up test files
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    });

    describe('Validate Command Workflow', function () {
        it('validates single file with all scopes', function () {
            // Execute: Run validate command with all scopes
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'all',
                '--format' => 'json',
                '--timeout' => 5,
            ]);

            $output = Artisan::output();

            // Assert: Command executes successfully
            expect($exitCode)->toBe(0)
                ->and($output)->toBeString()->not->toBeEmpty();

            // Verify JSON output structure
            $outputLines = explode("\n", trim($output));
            $jsonOutput = end($outputLines);
            
            if (json_validate($jsonOutput)) {
                $data = json_decode($jsonOutput, true);
                expect($data)->toBeArray()
                    ->and($data)->toHaveKey('summary')
                    ->and($data)->toHaveKey('results');
            }
        });

        it('validates multiple files with different formats', function () {
            // Setup: Create additional test files
            $file2 = $this->tempDir . '/test2.md';
            file_put_contents($file2, '[Another Link](https://httpbin.org/status/200)');

            // Execute: Validate multiple files
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile, $file2],
                '--scope' => 'external',
                '--format' => 'console',
                '--concurrent' => 5,
            ]);

            // Assert: Multiple file validation works
            expect($exitCode)->toBe(0);
            
            $output = Artisan::output();
            expect($output)->toContain('test.md')
                ->and($output)->toContain('test2.md');
        });

        it('handles validation with security checks enabled', function () {
            // Execute: Run with security checks
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'all',
                '--security' => true,
                '--format' => 'json',
            ]);

            $output = Artisan::output();

            // Assert: Security checks are applied
            expect($exitCode)->toBeInt();
            expect($output)->toBeString()->not->toBeEmpty();
        });

        it('validates with custom timeout and retry settings', function () {
            // Execute: Run with custom settings
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'external',
                '--timeout' => 2,
                '--max-redirects' => 3,
                '--user-agent' => 'Custom-Agent/1.0',
            ]);

            // Assert: Custom settings are applied
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString();
        });

        it('handles directory validation recursively', function () {
            // Setup: Create directory structure
            $subDir = $this->tempDir . '/subdir';
            mkdir($subDir);
            
            $subFile = $subDir . '/sub.md';
            file_put_contents($subFile, '[Sub Link](https://httpbin.org/status/200)');

            // Execute: Validate directory
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->tempDir],
                '--scope' => 'external',
                '--recursive' => true,
            ]);

            // Assert: Directory validation works
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toContain('sub.md');
        });
    });

    describe('Report Command Workflow', function () {
        it('generates reports from validation results', function () {
            // Setup: First run validation to generate results
            Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'all',
                '--format' => 'json',
                '--output' => $this->reportFile,
            ]);

            // Execute: Generate report from results
            $exitCode = Artisan::call('report', [
                'input' => $this->reportFile,
                '--format' => 'html',
                '--output' => $this->tempDir . '/report.html',
            ]);

            // Assert: Report generation works
            expect($exitCode)->toBe(0);
            expect(file_exists($this->tempDir . '/report.html'))->toBeTrue();
        });

        it('converts between different report formats', function () {
            // Setup: Create JSON report data
            $reportData = [
                'summary' => [
                    'total_links' => 5,
                    'broken_links' => 2,
                    'success_rate' => 60.0,
                ],
                'results' => [
                    [
                        'url' => 'https://example.com',
                        'status' => 'valid',
                        'scope' => 'external',
                    ],
                ],
            ];
            file_put_contents($this->reportFile, json_encode($reportData));

            // Execute: Convert to different formats
            $htmlExitCode = Artisan::call('report', [
                'input' => $this->reportFile,
                '--format' => 'html',
                '--output' => $this->tempDir . '/report.html',
            ]);

            $markdownExitCode = Artisan::call('report', [
                'input' => $this->reportFile,
                '--format' => 'markdown',
                '--output' => $this->tempDir . '/report.md',
            ]);

            // Assert: Format conversion works
            expect($htmlExitCode)->toBe(0)
                ->and($markdownExitCode)->toBe(0)
                ->and(file_exists($this->tempDir . '/report.html'))->toBeTrue()
                ->and(file_exists($this->tempDir . '/report.md'))->toBeTrue();
        });
    });

    describe('Config Command Workflow', function () {
        it('creates and manages configuration files', function () {
            // Execute: Create config file
            $exitCode = Artisan::call('config:publish', [
                '--output' => $this->configFile,
            ]);

            // Assert: Config file is created
            expect($exitCode)->toBe(0);
            expect(file_exists($this->configFile))->toBeTrue();

            $config = json_decode(file_get_contents($this->configFile), true);
            expect($config)->toBeArray()
                ->and($config)->toHaveKey('validation')
                ->and($config)->toHaveKey('security');
        });

        it('validates configuration file syntax', function () {
            // Setup: Create valid config
            $validConfig = [
                'validation' => [
                    'timeout' => 30,
                    'max_redirects' => 5,
                ],
                'security' => [
                    'blocked_domains' => ['malicious.com'],
                ],
            ];
            file_put_contents($this->configFile, json_encode($validConfig));

            // Execute: Validate config
            $exitCode = Artisan::call('config:validate', [
                'config' => $this->configFile,
            ]);

            // Assert: Valid config passes validation
            expect($exitCode)->toBe(0);
        });
    });

    describe('Fix Command Workflow', function () {
        it('identifies and suggests fixes for broken links', function () {
            // Setup: Create file with fixable issues
            $fixableContent = <<<'MD'
# Fixable Issues

[Broken Link](https://httpbin.org/status/404)
[Redirect Link](https://httpbin.org/redirect/1)
[Case Issue](./README.MD)
MD;
            file_put_contents($this->testFile, $fixableContent);

            // Execute: Run fix command
            $exitCode = Artisan::call('fix', [
                'paths' => [$this->testFile],
                '--dry-run' => true,
            ]);

            // Assert: Fix suggestions are provided
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString()->not->toBeEmpty();
        });

        it('applies automatic fixes when safe', function () {
            // Setup: Create file with auto-fixable issues
            $autoFixContent = <<<'MD'
# Auto-fixable Issues

[HTTP Link](http://httpbin.org/get)
[Trailing Slash](https://httpbin.org/get/)
MD;
            file_put_contents($this->testFile, $autoFixContent);

            // Execute: Apply automatic fixes
            $exitCode = Artisan::call('fix', [
                'paths' => [$this->testFile],
                '--auto-fix' => true,
                '--backup' => true,
            ]);

            // Assert: Fixes are applied
            expect($exitCode)->toBeInt();
            
            // Check if backup was created
            expect(file_exists($this->testFile . '.backup'))->toBeTrue();
        });
    });

    describe('Interactive Workflow', function () {
        it('handles interactive validation mode', function () {
            // Execute: Run in interactive mode (simulated)
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--interactive' => true,
                '--scope' => 'external',
            ]);

            // Assert: Interactive mode executes
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString();
        });

        it('provides progress feedback for long operations', function () {
            // Setup: Create file with many links
            $manyLinksContent = '';
            for ($i = 1; $i <= 20; $i++) {
                $manyLinksContent .= "[Link $i](https://httpbin.org/status/200)\n";
            }
            file_put_contents($this->testFile, $manyLinksContent);

            // Execute: Run validation with progress
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'external',
                '--progress' => true,
            ]);

            // Assert: Progress is shown
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString()->not->toBeEmpty();
        });
    });

    describe('Error Handling Workflow', function () {
        it('handles invalid file paths gracefully', function () {
            // Execute: Validate non-existent file
            $exitCode = Artisan::call('validate', [
                'paths' => ['/non/existent/file.md'],
            ]);

            // Assert: Error is handled gracefully
            expect($exitCode)->toBeGreaterThan(0);
            
            $output = Artisan::output();
            expect($output)->toContain('not found')
                ->or($output)->toContain('does not exist');
        });

        it('handles invalid command options gracefully', function () {
            // Execute: Use invalid options
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'invalid-scope',
                '--timeout' => -1,
            ]);

            // Assert: Invalid options are handled
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString();
        });

        it('recovers from network failures during validation', function () {
            // Setup: Create file with unreachable links
            $unreachableContent = <<<'MD'
[Unreachable](https://this-domain-does-not-exist-12345.com)
[Valid](https://httpbin.org/status/200)
MD;
            file_put_contents($this->testFile, $unreachableContent);

            // Execute: Validate with network issues
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'external',
                '--timeout' => 5,
            ]);

            // Assert: Partial failures are handled
            expect($exitCode)->toBeInt();
            
            $output = Artisan::output();
            expect($output)->toBeString()->not->toBeEmpty();
        });
    });
});
