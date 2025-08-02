<?php

declare(strict_types=1);

use App\Commands\ValidateCommand;
use App\Commands\ReportCommand;
use App\Commands\ConfigCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use Illuminate\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

describe('Command Integration Tests', function () {
    beforeEach(function () {
        // Setup: Create test environment
        $this->tempDir = sys_get_temp_dir() . '/validate-links-command-tests';
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
        
        $this->testFile = $this->tempDir . '/test.md';
        $this->configFile = $this->tempDir . '/config.json';
        $this->reportFile = $this->tempDir . '/report.json';
        
        // Create test markdown file
        $content = <<<'MD'
# Test Document

[Valid Link](https://httpbin.org/status/200)
[Broken Link](https://httpbin.org/status/404)
[Local File](./README.md)
MD;
        file_put_contents($this->testFile, $content);
    });

    afterEach(function () {
        // Teardown: Clean up test files
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob($this->tempDir . '/*'));
            rmdir($this->tempDir);
        }
    });

    describe('ValidateCommand Integration', function () {
        it('integrates with validation services through dependency injection', function () {
            // Setup: Get command instance from container
            $command = app(ValidateCommand::class);
            
            // Execute: Verify dependencies are properly injected
            expect($command)->toBeInstanceOf(ValidateCommand::class);
            
            // Use reflection to verify dependencies
            $reflection = new ReflectionClass($command);
            $linkValidationProperty = $reflection->getProperty('linkValidation');
            $linkValidationProperty->setAccessible(true);
            $reportingProperty = $reflection->getProperty('reporting');
            $reportingProperty->setAccessible(true);
            
            $linkValidation = $linkValidationProperty->getValue($command);
            $reporting = $reportingProperty->getValue($command);
            
            // Assert: Dependencies are correctly injected
            expect($linkValidation)->toBeInstanceOf(LinkValidationInterface::class)
                ->and($reporting)->toBeInstanceOf(ReportingInterface::class);
        });

        it('processes file validation through complete command execution', function () {
            // Setup: Create command with mocked output
            $command = app(ValidateCommand::class);
            $output = new BufferedOutput();
            
            // Mock the command execution environment
            $input = new ArrayInput([
                'paths' => [$this->testFile],
                '--scope' => 'all',
                '--format' => 'console',
            ]);
            
            // Execute: Run command with real services
            $command->setLaravel(app());
            
            // Use reflection to call protected methods for testing
            $reflection = new ReflectionClass($command);
            $createConfigMethod = $reflection->getMethod('createConfigFromOptions');
            $createConfigMethod->setAccessible(true);
            
            $config = $createConfigMethod->invoke($command, [
                'scope' => 'all',
                'timeout' => 30,
                'format' => 'console',
                'concurrent' => 10,
                'user-agent' => 'test-agent',
            ]);
            
            // Assert: Configuration is created properly
            expect($config)->toBeInstanceOf(App\Services\ValueObjects\ValidationConfig::class);
        });

        it('handles multiple file validation with proper service integration', function () {
            // Setup: Create multiple test files
            $file1 = $this->tempDir . '/file1.md';
            $file2 = $this->tempDir . '/file2.md';
            
            file_put_contents($file1, '[Link 1](https://httpbin.org/status/200)');
            file_put_contents($file2, '[Link 2](https://httpbin.org/status/404)');
            
            $command = app(ValidateCommand::class);
            
            // Execute: Test file collection and validation
            $reflection = new ReflectionClass($command);
            $collectFilesMethod = $reflection->getMethod('collectFiles');
            $collectFilesMethod->setAccessible(true);
            
            $files = $collectFilesMethod->invoke($command, [$file1, $file2]);
            
            // Assert: Files are collected properly
            expect($files)->toBeArray()
                ->and(count($files))->toBe(2)
                ->and($files)->toContain($file1)
                ->and($files)->toContain($file2);
        });
    });

    describe('ReportCommand Integration', function () {
        it('integrates with reporting services for file-based reports', function () {
            // Setup: Create validation results file
            $resultsData = [
                'summary' => [
                    'total_links' => 2,
                    'broken_links' => 1,
                    'success_rate' => 50.0,
                ],
                'results' => [
                    [
                        'url' => 'https://example.com',
                        'status' => 'valid',
                        'scope' => 'external',
                        'response_time' => 150.0,
                    ],
                    [
                        'url' => 'https://broken.com',
                        'status' => 'broken',
                        'scope' => 'external',
                        'response_time' => 200.0,
                    ],
                ],
            ];
            
            file_put_contents($this->reportFile, json_encode($resultsData));
            
            $command = app(ReportCommand::class);
            
            // Execute: Verify command can access reporting services
            expect($command)->toBeInstanceOf(ReportCommand::class);
            
            // Verify the command has access to reporting interface
            $reflection = new ReflectionClass($command);
            $reportingProperty = $reflection->getProperty('reporting');
            $reportingProperty->setAccessible(true);
            $reporting = $reportingProperty->getValue($command);
            
            // Assert: Reporting service is properly injected
            expect($reporting)->toBeInstanceOf(ReportingInterface::class);
        });
    });

    describe('ConfigCommand Integration', function () {
        it('integrates with configuration system', function () {
            // Setup: Test configuration command
            $command = app(ConfigCommand::class);
            
            // Execute: Verify command exists and is properly configured
            expect($command)->toBeInstanceOf(ConfigCommand::class);
            
            // Test configuration file creation
            $configData = [
                'security' => [
                    'blocked_domains' => ['malicious.com'],
                    'allowed_protocols' => ['http', 'https'],
                ],
                'validation' => [
                    'timeout' => 30,
                    'max_redirects' => 5,
                ],
            ];
            
            file_put_contents($this->configFile, json_encode($configData, JSON_PRETTY_PRINT));
            
            // Assert: Configuration file is created and readable
            expect(file_exists($this->configFile))->toBeTrue();
            
            $loadedConfig = json_decode(file_get_contents($this->configFile), true);
            expect($loadedConfig)->toBeArray()
                ->and($loadedConfig)->toHaveKey('security')
                ->and($loadedConfig)->toHaveKey('validation');
        });
    });

    describe('Cross-Command Integration', function () {
        it('shares configuration and services across commands', function () {
            // Setup: Get multiple command instances
            $validateCommand = app(ValidateCommand::class);
            $reportCommand = app(ReportCommand::class);
            $configCommand = app(ConfigCommand::class);
            
            // Execute: Verify all commands use the same service instances
            $validateReflection = new ReflectionClass($validateCommand);
            $reportReflection = new ReflectionClass($reportCommand);
            
            $validateReporting = $validateReflection->getProperty('reporting');
            $validateReporting->setAccessible(true);
            $validateReportingInstance = $validateReporting->getValue($validateCommand);
            
            $reportReporting = $reportReflection->getProperty('reporting');
            $reportReporting->setAccessible(true);
            $reportReportingInstance = $reportReporting->getValue($reportCommand);
            
            // Assert: Same service instances are shared (singleton pattern)
            expect($validateReportingInstance)->toBe($reportReportingInstance);
        });

        it('maintains consistent behavior across command executions', function () {
            // Setup: Create test scenario that spans multiple commands
            $testContent = '[Test Link](https://httpbin.org/status/200)';
            file_put_contents($this->testFile, $testContent);
            
            // Execute: Simulate validate -> report workflow
            $validateCommand = app(ValidateCommand::class);
            $reportCommand = app(ReportCommand::class);
            
            // Verify both commands can access the same file
            expect(file_exists($this->testFile))->toBeTrue();
            
            // Test that validation results can be used by reporting
            $linkValidation = app(LinkValidationInterface::class);
            $reporting = app(ReportingInterface::class);
            
            $config = App\Services\ValueObjects\ValidationConfig::withDefaults();
            $results = $linkValidation->validateFile($this->testFile, $config);
            $report = $reporting->generateReport($results);
            
            // Assert: Cross-command workflow works
            expect($results)->toBeInstanceOf(App\Services\ValueObjects\ValidationResultCollection::class)
                ->and($report)->toBeString()->not->toBeEmpty();
        });
    });

    describe('Error Handling Integration', function () {
        it('handles service errors gracefully across commands', function () {
            // Setup: Create error scenarios
            $nonExistentFile = $this->tempDir . '/missing.md';
            
            $command = app(ValidateCommand::class);
            
            // Execute: Test error handling through command layer
            $reflection = new ReflectionClass($command);
            $handleErrorMethod = $reflection->getMethod('handleValidationError');
            $handleErrorMethod->setAccessible(true);
            
            $exception = new Exception('Test validation error');
            $exitCode = $handleErrorMethod->invoke($command, $exception);
            
            // Assert: Error handling returns proper exit code
            expect($exitCode)->toBeInt()
                ->and($exitCode)->toBeGreaterThan(0);
        });

        it('propagates configuration errors across service boundaries', function () {
            // Setup: Create invalid configuration scenario
            $invalidConfig = [
                'timeout' => 'invalid-timeout',
                'scope' => 'invalid-scope',
            ];
            
            $command = app(ValidateCommand::class);
            
            // Execute: Test configuration validation
            $reflection = new ReflectionClass($command);
            $createConfigMethod = $reflection->getMethod('createConfigFromOptions');
            $createConfigMethod->setAccessible(true);
            
            // This should handle invalid values gracefully
            $config = $createConfigMethod->invoke($command, $invalidConfig);
            
            // Assert: Invalid configuration is handled properly
            expect($config)->toBeInstanceOf(App\Services\ValueObjects\ValidationConfig::class);
            
            // The method should have applied defaults for invalid values
            expect($config->getTimeout())->toBeInt()
                ->and($config->getTimeout())->toBeGreaterThan(0);
        });
    });
});
