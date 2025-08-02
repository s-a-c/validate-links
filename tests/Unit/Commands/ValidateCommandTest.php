<?php

declare(strict_types=1);

use App\Commands\ValidateCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;

describe('ValidateCommand', function () {
    beforeEach(function () {
        $this->linkValidation = mock(LinkValidationInterface::class);
        $this->reporting = mock(ReportingInterface::class);
    });

    describe('class structure', function () {
        it('extends BaseValidationCommand', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);
            expect($command)->toBeInstanceOf(App\Commands\BaseValidationCommand::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(ValidateCommand::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has correct signature', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);
            $reflection = new ReflectionClass($command);
            $signature = $reflection->getProperty('signature');
            $signature->setAccessible(true);

            expect($signature->getValue($command))->toContain('validate')
                ->and($signature->getValue($command))->toContain('{paths* : Paths to validate (files or directories)}')
                ->and($signature->getValue($command))->toContain('--interactive');
        });

        it('has correct description', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);
            $reflection = new ReflectionClass($command);
            $description = $reflection->getProperty('description');
            $description->setAccessible(true);

            expect($description->getValue($command))->toBe('Validate links in documentation files with comprehensive scope and format options');
        });
    });

    describe('constructor', function () {
        it('accepts LinkValidationInterface and ReportingInterface dependencies', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);

            expect($command)->toBeInstanceOf(ValidateCommand::class);
        });

        it('calls parent constructor', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);

            expect($command)->toBeInstanceOf(LaravelZero\Framework\Commands\Command::class);
        });
    });

    describe('methods', function () {
        it('has handle method', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);

            expect(method_exists($command, 'handle'))->toBeTrue();
        });

        it('has displaySummary method', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);

            expect(method_exists($command, 'displaySummary'))->toBeTrue();
        });

        it('displaySummary method is protected', function () {
            $reflection = new ReflectionClass(ValidateCommand::class);
            $method = $reflection->getMethod('displaySummary');

            expect($method->isProtected())->toBeTrue();
        });
    });

    describe('command options', function () {
        it('supports all required options', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);
            $reflection = new ReflectionClass($command);
            $signature = $reflection->getProperty('signature');
            $signature->setAccessible(true);
            $signatureValue = $signature->getValue($command);

            expect($signatureValue)->toContain('--scope=all')
                ->and($signatureValue)->toContain('--format=console')
                ->and($signatureValue)->toContain('--output=')
                ->and($signatureValue)->toContain('--interactive')
                ->and($signatureValue)->toContain('--concurrent=10')
                ->and($signatureValue)->toContain('--timeout=30')
                ->and($signatureValue)->toContain('--max-redirects=5')
                ->and($signatureValue)->toContain('--no-cache')
                ->and($signatureValue)->toContain('--follow-redirects')
                ->and($signatureValue)->toContain('--user-agent=validate-links/1.0')
                ->and($signatureValue)->toContain('--check-external')
                ->and($signatureValue)->toContain('--filter=')
                ->and($signatureValue)->toContain('--ci')
                ->and($signatureValue)->toContain('--fail-fast');
        });
    });

    describe('inheritance from BaseValidationCommand', function () {
        it('inherits validation methods', function () {
            $command = new ValidateCommand($this->linkValidation, $this->reporting);

            expect(method_exists($command, 'createConfigFromOptions'))->toBeTrue()
                ->and(method_exists($command, 'validateCommandOptions'))->toBeTrue()
                ->and(method_exists($command, 'handleValidationError'))->toBeTrue();
        });
    });

    describe('namespace and strict types', function () {
        it('has proper namespace', function () {
            $reflection = new ReflectionClass(ValidateCommand::class);
            expect($reflection->getNamespaceName())->toBe('App\Commands');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Commands/ValidateCommand.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });
});
