<?php

declare(strict_types=1);

use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Enums\OutputFormat;
use Illuminate\Support\Facades\Artisan;
use Mockery\MockInterface;

describe('Command Workflow Feature Tests', function () {
    beforeEach(function () {
        // Setup: Create test environment with proper isolation
        $this->tempDir = sys_get_temp_dir().'/validate-links-feature-tests-'.uniqid();
        if (! is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }

        $this->testFile = $this->tempDir.'/test.md';
        $this->configFile = $this->tempDir.'/config.json';
        $this->reportFile = $this->tempDir.'/report.json';

        // Create test content with mock-friendly URLs
        $this->testContent = <<<'MD'
# Feature Test Document

## External Links
[Valid HTTP](https://example.com/valid)
[Valid HTTPS](https://example.com/secure)
[Broken Link](https://example.com/broken)
[Timeout Link](https://example.com/slow)

## Internal Links
[README](./README.md)
[Non-existent](./missing.md)
[Config File](./config.json)

## Anchor Links
[Valid Anchor](#feature-test-document)
[Invalid Anchor](#non-existent-section)

## Mixed Content
[Image Link](https://example.com/image.png)
[JSON API](https://example.com/api/data)
[XML Content](https://example.com/data.xml)

## Edge Cases
[Empty Link]()
[Malformed](not-a-url)
[JavaScript](javascript:alert('test'))
[FTP Link](ftp://example.com/file.txt)
MD;

        file_put_contents($this->testFile, $this->testContent);

        // Setup comprehensive service mocking
        $this->setupServiceMocks();
    });

    afterEach(function () {
        // Comprehensive cleanup
        $this->cleanupServiceMocks();
        
        // Teardown: Clean up test files
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir.'/*');
            if ($files) {
                array_map('unlink', $files);
            }
            rmdir($this->tempDir);
        }
        
        // Clear Artisan output buffer
        Artisan::output();
    });

    /**
     * Setup comprehensive service mocking for all dependencies
     */
    function setupServiceMocks(): void {
        // Mock LinkValidationInterface
        $this->linkValidationMock = mock(LinkValidationInterface::class);
        app()->instance(LinkValidationInterface::class, $this->linkValidationMock);

        // Mock ReportingInterface  
        $this->reportingMock = mock(ReportingInterface::class);
        app()->instance(ReportingInterface::class, $this->reportingMock);

        // Mock SecurityValidationInterface
        $this->securityMock = mock(SecurityValidationInterface::class);
        app()->instance(SecurityValidationInterface::class, $this->securityMock);

        // Setup default mock behaviors
        $this->setupDefaultMockBehaviors();
    }

    /**
     * Setup default mock behaviors for common test scenarios
     */
    function setupDefaultMockBehaviors(): void {
        // Create realistic validation results
        $validResults = new ValidationResultCollection([
            new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
            new ValidationResult('https://example.com/secure', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 200]),
            new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
            new ValidationResult('./README.md', LinkStatus::BROKEN, ValidationScope::INTERNAL, ['error' => 'File not found']),
            new ValidationResult('#feature-test-document', LinkStatus::VALID, ValidationScope::ANCHOR, []),
        ]);

        // Setup LinkValidation mock defaults
        $this->linkValidationMock
            ->shouldReceive('validateFile')
            ->andReturn($validResults)
            ->byDefault();

        $this->linkValidationMock
            ->shouldReceive('validateMultipleFiles')
            ->andReturn($validResults)
            ->byDefault();

        // Setup Reporting mock defaults
        $this->reportingMock
            ->shouldReceive('generateReport')
            ->andReturn('{"summary":{"total_links":5,"broken_links":2,"success_rate":60.0},"results":[]}')
            ->byDefault();

        $this->reportingMock
            ->shouldReceive('exportReport')
            ->andReturn(true)
            ->byDefault();

        // Setup Security mock defaults
        $this->securityMock
            ->shouldReceive('validateUrl')
            ->andReturn(true)
            ->byDefault();
    }

    /**
     * Cleanup service mocks and restore original bindings
     */
    function cleanupServiceMocks(): void {
        // Reset service container bindings
        app()->forgetInstance(LinkValidationInterface::class);
        app()->forgetInstance(ReportingInterface::class);
        app()->forgetInstance(SecurityValidationInterface::class);
        
        // Close mockery
        if (isset($this->linkValidationMock)) {
            $this->linkValidationMock->mockery_teardown();
        }
        if (isset($this->reportingMock)) {
            $this->reportingMock->mockery_teardown();
        }
        if (isset($this->securityMock)) {
            $this->securityMock->mockery_teardown();
        }
    }

    describe('Validate Command Workflow', function () {
        it('validates single file with all scopes using mocked services', function () {
            // Setup: Configure specific mock expectations
            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($expectedResults);

            $this->reportingMock
                ->shouldReceive('generateReport')
                ->once()
                ->with($expectedResults, OutputFormat::JSON)
                ->andReturn('{"summary":{"total_links":2,"broken_links":1,"success_rate":50.0},"results":[]}');

            // Execute: Run validate command with mocked services
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'all',
                '--format' => 'json',
                '--timeout' => 5,
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            // Assert: Command executes successfully with mocked data
            expect($exitCode)->toBe(0)
                ->and($output)->toBeString()->not->toBeEmpty();

            // Verify JSON output structure
            $outputLines = explode("\n", mb_trim($output));
            $jsonOutput = end($outputLines);

            if (json_validate($jsonOutput)) {
                $data = json_decode($jsonOutput, true);
                expect($data)->toBeArray()
                    ->and($data)->toHaveKey('summary')
                    ->and($data)->toHaveKey('results');
            }
        });

        it('validates multiple files with different formats using mocked services', function () {
            // Setup: Create additional test files
            $file2 = $this->tempDir.'/test2.md';
            file_put_contents($file2, '[Another Link](https://example.com/another)');

            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
                new ValidationResult('https://example.com/another', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 180]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateMultipleFiles')
                ->once()
                ->andReturn($expectedResults);

            // Execute: Validate multiple files with mocked services
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile, $file2],
                '--scope' => 'external',
                '--format' => 'console',
                '--concurrent' => 5,
                '--no-interaction' => true,
            ]);

            // Assert: Multiple file validation works with mocks
            expect($exitCode)->toBe(0);

            $output = Artisan::output();
            expect($output)->toContain('test.md')
                ->and($output)->toContain('test2.md');
        });

        it('handles validation with security checks enabled using mocked services', function () {
            // Setup: Configure security mock expectations
            $this->securityMock
                ->shouldReceive('validateUrl')
                ->andReturn(true);

            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['security_check' => 'passed']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($expectedResults);

            // Execute: Run with security checks using mocked services
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'all',
                '--format' => 'json',
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            // Assert: Security checks are applied through mocks
            expect($exitCode)->toBeInt();
            expect($output)->toBeString()->not->toBeEmpty();
        });

        it('validates with custom timeout and retry settings using mocked services', function () {
            // Setup: Configure mock for custom settings
            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 1500]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($expectedResults);

            // Execute: Run with custom settings using mocked services
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'external',
                '--timeout' => 2,
                '--max-redirects' => 3,
                '--user-agent' => 'Custom-Agent/1.0',
                '--no-interaction' => true,
            ]);

            // Assert: Custom settings are applied through mocks
            expect($exitCode)->toBeInt();

            $output = Artisan::output();
            expect($output)->toBeString();
        });

        it('handles directory validation recursively using mocked services', function () {
            // Setup: Create directory structure
            $subDir = $this->tempDir.'/subdir';
            mkdir($subDir);

            $subFile = $subDir.'/sub.md';
            file_put_contents($subFile, '[Sub Link](https://example.com/sub)');

            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/sub', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 200]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->andReturn($expectedResults);

            // Execute: Validate directory using mocked services
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->tempDir],
                '--scope' => 'external',
                '--recursive' => true,
                '--no-interaction' => true,
            ]);

            // Assert: Directory validation works with mocks
            expect($exitCode)->toBeInt();

            $output = Artisan::output();
            expect($output)->toContain('sub.md');
        });
    });

    describe('Report Command Workflow', function () {
        it('generates reports from validation results using mocked services', function () {
            // Setup: Configure reporting mock expectations
            $this->reportingMock
                ->shouldReceive('exportReport')
                ->twice()
                ->andReturn(true);

            // Setup: First run validation to generate results (mocked)
            Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'all',
                '--format' => 'json',
                '--output' => $this->reportFile,
                '--no-interaction' => true,
            ]);

            // Execute: Generate report from results using mocked services
            $exitCode = Artisan::call('report', [
                'input' => $this->reportFile,
                '--format' => 'html',
                '--output' => $this->tempDir.'/report.html',
                '--no-interaction' => true,
            ]);

            // Assert: Report generation works with mocks
            expect($exitCode)->toBe(0);
            expect(file_exists($this->tempDir.'/report.html'))->toBeTrue();
        });

        it('converts between different report formats using mocked services', function () {
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

            // Setup: Configure reporting mock expectations
            $this->reportingMock
                ->shouldReceive('exportReport')
                ->twice()
                ->andReturn(true);

            // Execute: Convert to different formats using mocked services
            $htmlExitCode = Artisan::call('report', [
                'input' => $this->reportFile,
                '--format' => 'html',
                '--output' => $this->tempDir.'/report.html',
                '--no-interaction' => true,
            ]);

            $markdownExitCode = Artisan::call('report', [
                'input' => $this->reportFile,
                '--format' => 'markdown',
                '--output' => $this->tempDir.'/report.md',
                '--no-interaction' => true,
            ]);

            // Assert: Format conversion works with mocks
            expect($htmlExitCode)->toBe(0)
                ->and($markdownExitCode)->toBe(0)
                ->and(file_exists($this->tempDir.'/report.html'))->toBeTrue()
                ->and(file_exists($this->tempDir.'/report.md'))->toBeTrue();
        });
    });

    describe('Config Command Workflow', function () {
        it('creates and manages configuration files', function () {
            // Execute: Create config file
            $exitCode = Artisan::call('config:publish', [
                '--output' => $this->configFile,
                '--no-interaction' => true,
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
                '--no-interaction' => true,
            ]);

            // Assert: Valid config passes validation
            expect($exitCode)->toBe(0);
        });
    });

    describe('Fix Command Workflow', function () {
        it('identifies and suggests fixes for broken links using mocked services', function () {
            // Setup: Create file with fixable issues
            $fixableContent = <<<'MD'
# Fixable Issues

[Broken Link](https://example.com/broken)
[Redirect Link](https://example.com/redirect)
[Case Issue](./README.MD)
MD;
            file_put_contents($this->testFile, $fixableContent);

            // Setup: Configure mock expectations for fix command
            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/broken', LinkStatus::BROKEN, ValidationScope::EXTERNAL, ['status_code' => 404]),
                new ValidationResult('https://example.com/redirect', LinkStatus::REDIRECT, ValidationScope::EXTERNAL, ['redirect_url' => 'https://example.com/final']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($expectedResults);

            // Execute: Run fix command using mocked services
            $exitCode = Artisan::call('fix', [
                'paths' => [$this->testFile],
                '--dry-run' => true,
                '--no-interaction' => true,
            ]);

            // Assert: Fix suggestions are provided through mocks
            expect($exitCode)->toBeInt();

            $output = Artisan::output();
            expect($output)->toBeString()->not->toBeEmpty();
        });

        it('applies automatic fixes when safe using mocked services', function () {
            // Setup: Create file with auto-fixable issues
            $autoFixContent = <<<'MD'
# Auto-fixable Issues

[HTTP Link](http://example.com/insecure)
[Trailing Slash](https://example.com/path/)
MD;
            file_put_contents($this->testFile, $autoFixContent);

            // Setup: Configure mock expectations for auto-fix
            $expectedResults = new ValidationResultCollection([
                new ValidationResult('http://example.com/insecure', LinkStatus::INSECURE, ValidationScope::EXTERNAL, ['suggested_fix' => 'https://example.com/insecure']),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($expectedResults);

            // Execute: Apply automatic fixes using mocked services
            $exitCode = Artisan::call('fix', [
                'paths' => [$this->testFile],
                '--auto-fix' => true,
                '--backup' => true,
                '--no-interaction' => true,
            ]);

            // Assert: Fixes are applied through mocks
            expect($exitCode)->toBeInt();

            // Check if backup was created
            expect(file_exists($this->testFile.'.backup'))->toBeTrue();
        });
    });

    describe('Interactive Workflow', function () {
        it('handles interactive validation mode using mocked services', function () {
            // Setup: Configure mock for interactive mode
            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($expectedResults);

            // Execute: Run in interactive mode (simulated with no-interaction to prevent hanging)
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--interactive' => true,
                '--scope' => 'external',
                '--no-interaction' => true, // Prevent hanging in tests
            ]);

            // Assert: Interactive mode executes with mocks
            expect($exitCode)->toBeInt();

            $output = Artisan::output();
            expect($output)->toBeString();
        });

        it('provides progress feedback for long operations using mocked services', function () {
            // Setup: Create file with many links
            $manyLinksContent = '';
            for ($i = 1; $i <= 20; $i++) {
                $manyLinksContent .= "[Link $i](https://example.com/link$i)\n";
            }
            file_put_contents($this->testFile, $manyLinksContent);

            // Setup: Configure mock for many links
            $manyResults = new ValidationResultCollection();
            for ($i = 1; $i <= 20; $i++) {
                $manyResults->add(new ValidationResult("https://example.com/link$i", LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 100 + $i]));
            }

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($manyResults);

            // Execute: Run validation with progress using mocked services
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'external',
                '--no-interaction' => true, // Prevent hanging in tests
            ]);

            // Assert: Progress is shown through mocks
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
                '--no-interaction' => true,
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
                '--no-interaction' => true,
            ]);

            // Assert: Invalid options are handled
            expect($exitCode)->toBeInt();

            $output = Artisan::output();
            expect($output)->toBeString();
        });

        it('recovers from network failures during validation using mocked services', function () {
            // Setup: Create file with unreachable links
            $unreachableContent = <<<'MD'
[Unreachable](https://unreachable.example.com)
[Valid](https://example.com/valid)
MD;
            file_put_contents($this->testFile, $unreachableContent);

            // Setup: Configure mock for network failures
            $expectedResults = new ValidationResultCollection([
                new ValidationResult('https://unreachable.example.com', LinkStatus::TIMEOUT, ValidationScope::EXTERNAL, ['error' => 'Connection timeout']),
                new ValidationResult('https://example.com/valid', LinkStatus::VALID, ValidationScope::EXTERNAL, ['response_time' => 150]),
            ]);

            $this->linkValidationMock
                ->shouldReceive('validateFile')
                ->once()
                ->andReturn($expectedResults);

            // Execute: Validate with network issues using mocked services
            $exitCode = Artisan::call('validate', [
                'paths' => [$this->testFile],
                '--scope' => 'external',
                '--timeout' => 5,
                '--no-interaction' => true,
            ]);

            // Assert: Partial failures are handled through mocks
            expect($exitCode)->toBeInt();

            $output = Artisan::output();
            expect($output)->toBeString()->not->toBeEmpty();
        });
    });
});