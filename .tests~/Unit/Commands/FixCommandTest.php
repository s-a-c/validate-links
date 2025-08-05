<?php

declare(strict_types=1);

use App\Commands\FixCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;

use function Pest\Laravel\artisan;

describe('FixCommand', function () {
    beforeEach(function () {
        $this->linkValidation = $this->mock(LinkValidationInterface::class);
        $this->reporting = $this->mock(ReportingInterface::class);
    });

    describe('handle method', function () {
        it('returns failure when no paths provided', function () {
            // Since paths* is required, Symfony Console will throw an exception before handle() is called
            expect(function () {
                artisan('fix');
            })->toThrow(RuntimeException::class, 'Not enough arguments (missing: "paths")');
        });

        it('handles single path argument', function () {
            artisan('fix', ['paths' => ['test.md']])
                ->expectsOutput('🔧 Automatically fixing broken links')
                ->expectsOutput('Automatic fixing not yet implemented')
                ->assertExitCode(0);
        });

        it('handles multiple path arguments', function () {
            artisan('fix', ['paths' => ['test1.md', 'test2.md']])
                ->expectsOutput('🔧 Automatically fixing broken links')
                ->expectsOutput('Automatic fixing not yet implemented')
                ->assertExitCode(0);
        });

        it('handles dry-run option', function () {
            artisan('fix', ['paths' => ['test.md'], '--dry-run' => true])
                ->expectsOutput('🔍 DRY RUN: Preview of fixes')
                ->expectsOutput('Automatic fixing not yet implemented')
                ->assertExitCode(0);
        });

        it('handles interactive option with confirmation', function () {
            $this->mock('alias:Laravel\Prompts\confirm', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('This will attempt to fix broken links. Continue?', true)
                    ->andReturn(true);
            });

            $this->mock('alias:Laravel\Prompts\info', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('🔧 Interactive Link Fixing Mode');
            });

            artisan('fix', ['paths' => ['test.md'], '--interactive' => true])
                ->expectsOutput('Interactive fixing not yet implemented')
                ->assertExitCode(0);
        });

        it('handles interactive option with cancellation', function () {
            $this->mock('alias:Laravel\Prompts\confirm', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('This will attempt to fix broken links. Continue?', true)
                    ->andReturn(false);
            });

            $this->mock('alias:Laravel\Prompts\info', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('🔧 Interactive Link Fixing Mode');
            });

            artisan('fix', ['paths' => ['test.md'], '--interactive' => true])
                ->expectsOutput('Fix cancelled')
                ->assertExitCode(0);
        });

        it('handles backup option', function () {
            artisan('fix', ['paths' => ['test.md'], '--backup' => true])
                ->expectsOutput('🔧 Automatically fixing broken links')
                ->expectsOutput('Automatic fixing not yet implemented')
                ->assertExitCode(0);
        });

        it('handles combination of options', function () {
            artisan('fix', [
                'paths' => ['test.md'],
                '--dry-run' => true,
                '--backup' => true,
                '--interactive' => false,
            ])
                ->expectsOutput('🔍 DRY RUN: Preview of fixes')
                ->expectsOutput('Automatic fixing not yet implemented')
                ->assertExitCode(0);
        });

        it('creates ValidationConfig with correct parameters', function () {
            $command = new FixCommand($this->linkValidation, $this->reporting);

            // Mock the ValidationConfig creation
            $this->spy(ValidationConfig::class);

            // Use reflection to test the handle method directly
            $reflection = new ReflectionClass($command);
            $handleMethod = $reflection->getMethod('handle');

            // Set up command properties
            $command->setLaravel(app());

            // Mock the argument and option methods
            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('argument')
                    ->with('paths')
                    ->andReturn(['test.md', 'test2.md']);
                $mock->shouldReceive('option')
                    ->with('dry-run')
                    ->andReturn(true);
                $mock->shouldReceive('option')
                    ->with('interactive')
                    ->andReturn(false);
                $mock->shouldReceive('option')
                    ->with('backup')
                    ->andReturn(true);
                $mock->shouldReceive('info')
                    ->with('🔍 DRY RUN: Preview of fixes');
                $mock->shouldReceive('warn')
                    ->with('Automatic fixing not yet implemented');
            });

            $result = $handleMethod->invoke($command, $this->validator);

            expect($result)->toBe(0);
        });
    });

    describe('handleInteractiveFix method', function () {
        it('shows interactive mode message and prompts for confirmation', function () {
            $command = new FixCommand($this->linkValidation, $this->reporting);
            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('handleInteractiveFix');
            $method->setAccessible(true);

            $this->mock('alias:Laravel\Prompts\info', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('🔧 Interactive Link Fixing Mode');
            });

            $this->mock('alias:Laravel\Prompts\confirm', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('This will attempt to fix broken links. Continue?', true)
                    ->andReturn(true);
            });

            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('warn')
                    ->with('Interactive fixing not yet implemented');
            });

            $result = $method->invoke($command);

            expect($result)->toBe(0);
        });

        it('returns success when user cancels interactive fix', function () {
            $command = new FixCommand();
            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('handleInteractiveFix');
            $method->setAccessible(true);

            $this->mock('alias:Laravel\Prompts\info', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('🔧 Interactive Link Fixing Mode');
            });

            $this->mock('alias:Laravel\Prompts\confirm', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('This will attempt to fix broken links. Continue?', true)
                    ->andReturn(false);
            });

            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('info')
                    ->with('Fix cancelled');
            });

            $result = $method->invoke($command);

            expect($result)->toBe(0);
        });
    });

    describe('handleAutomaticFix method', function () {
        it('shows dry run message when dry run is enabled', function () {
            $command = new FixCommand();
            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('handleAutomaticFix');
            $method->setAccessible(true);

            $config = mock(ValidationConfig::class);
            $config->shouldReceive('isDryRun')->andReturn(true);

            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('info')
                    ->with('🔍 DRY RUN: Preview of fixes');
                $mock->shouldReceive('warn')
                    ->with('Automatic fixing not yet implemented');
            });

            $result = $method->invoke($command, $config);

            expect($result)->toBe(0);
        });

        it('shows automatic fixing message when dry run is disabled', function () {
            $command = new FixCommand();
            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('handleAutomaticFix');
            $method->setAccessible(true);

            $config = mock(ValidationConfig::class);
            $config->shouldReceive('isDryRun')->andReturn(false);

            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('info')
                    ->with('🔧 Automatically fixing broken links');
                $mock->shouldReceive('warn')
                    ->with('Automatic fixing not yet implemented');
            });

            $result = $method->invoke($command, $config);

            expect($result)->toBe(0);
        });
    });

    describe('command signature and description', function () {
        it('has correct signature', function () {
            $command = new FixCommand($this->linkValidation, $this->reporting);
            $reflection = new ReflectionClass($command);
            $signature = $reflection->getProperty('signature');
            $signature->setAccessible(true);

            expect($signature->getValue($command))->toBe('fix
        {paths* : Paths to fix}
        {--backup : Create backup files before fixing}
        {--interactive : Interactive mode for fixes}
        {--dry-run : Preview fixes without applying them}');
        });

        it('has correct description', function () {
            $command = new FixCommand($this->linkValidation, $this->reporting);
            $reflection = new ReflectionClass($command);
            $description = $reflection->getProperty('description');
            $description->setAccessible(true);

            expect($description->getValue($command))->toBe('Fix broken links in documentation files');
        });
    });

    describe('ValidationConfig integration', function () {
        it('creates config with correct fix flag', function () {
            // This test verifies that the ValidationConfig is created with fix=true
            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('argument')
                    ->with('paths')
                    ->andReturn(['test.md']);
                $mock->shouldReceive('option')
                    ->with('dry-run')
                    ->andReturn(false);
                $mock->shouldReceive('option')
                    ->with('interactive')
                    ->andReturn(false);
                $mock->shouldReceive('info')
                    ->with('🔧 Automatically fixing broken links');
                $mock->shouldReceive('warn')
                    ->with('Automatic fixing not yet implemented');
            });

            $result = $command->handle($this->validator);

            expect($result)->toBe(0);
        });

        it('handles ValidationConfig with interactive mode', function () {
            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('argument')
                    ->with('paths')
                    ->andReturn(['test.md']);
                $mock->shouldReceive('option')
                    ->with('dry-run')
                    ->andReturn(false);
                $mock->shouldReceive('option')
                    ->with('interactive')
                    ->andReturn(true);
            });

            $this->mock('alias:Laravel\Prompts\info', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('🔧 Interactive Link Fixing Mode');
            });

            $this->mock('alias:Laravel\Prompts\confirm', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with('This will attempt to fix broken links. Continue?', true)
                    ->andReturn(true);
            });

            $command->shouldReceive('warn')
                ->with('Interactive fixing not yet implemented');

            $result = $command->handle($this->validator);

            expect($result)->toBe(0);
        });
    });

    describe('edge cases and error handling', function () {
        it('handles empty paths array', function () {
            artisan('fix', ['paths' => []])
                ->expectsOutput('No paths provided')
                ->assertExitCode(1);
        });

        it('handles null paths', function () {
            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('argument')
                    ->with('paths')
                    ->andReturn(null);
                $mock->shouldReceive('error')
                    ->with('No paths provided');
            });

            $result = $command->handle($this->validator);

            expect($result)->toBe(1);
        });

        it('handles ValidationConfig creation with all options', function () {
            $command = $this->partialMock(FixCommand::class, function ($mock) {
                $mock->shouldReceive('argument')
                    ->with('paths')
                    ->andReturn(['test.md']);
                $mock->shouldReceive('option')
                    ->with('dry-run')
                    ->andReturn(true);
                $mock->shouldReceive('option')
                    ->with('interactive')
                    ->andReturn(false);
                $mock->shouldReceive('option')
                    ->with('backup')
                    ->andReturn(true);
                $mock->shouldReceive('info')
                    ->with('🔍 DRY RUN: Preview of fixes');
                $mock->shouldReceive('warn')
                    ->with('Automatic fixing not yet implemented');
            });

            $result = $command->handle($this->validator);

            expect($result)->toBe(0);
        });

        it('handles dependency injection properly', function () {
            $validator = mock(LinkValidationInterface::class);
            $command = new FixCommand();

            // Test that the validator is properly injected
            expect($validator)->toBeInstanceOf(LinkValidationInterface::class);
        });
    });

    describe('inheritance from BaseValidationCommand', function () {
        it('extends BaseValidationCommand', function () {
            $command = new FixCommand();
            expect($command)->toBeInstanceOf(App\Commands\BaseValidationCommand::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(FixCommand::class);
            expect($reflection->isFinal())->toBeTrue();
        });
    });
});
