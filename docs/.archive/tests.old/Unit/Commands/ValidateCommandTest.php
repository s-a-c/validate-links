<?php

declare(strict_types=1);

use App\Commands\BaseValidationCommand;
use App\Commands\ValidateCommand;
use App\Services\Contracts\LinkValidationInterface;
use old\Support\Factories\MockFactory;

require_once __DIR__ . '/../../Support/helpers.php';

/**
 * Test suite for ValidateCommand.
 *
 * @group commands
 * @group unit
 *
 * @covers \App\Commands\ValidateCommand
 */
describe('ValidateCommand', function (): void {
    afterEach(function (): void {
        cleanupTestFiles();
    });

    test('it can be instantiated with required dependencies', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new ValidateCommand($mockLinkValidation, $mockReporting);

        expect($command)->toBeInstanceOf(ValidateCommand::class)
            ->and($command)->toBeInstanceOf(BaseValidationCommand::class);
    });

    test('command has correct signature', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new ValidateCommand($mockLinkValidation, $mockReporting);

        $reflection = new ReflectionClass(ValidateCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue($command);

        expect($signature)->toBeString()
            ->and($signature)->toContain('validate')
            ->and($signature)->toContain('paths*')
            ->and($signature)->toContain('--scope')
            ->and($signature)->toContain('--format')
            ->and($signature)->toContain('--timeout')
            ->and($signature)->toContain('--max-redirects')
            ->and($signature)->toContain('--concurrency')
            ->and($signature)->toContain('--no-cache')
            ->and($signature)->toContain('--follow-redirects')
            ->and($signature)->toContain('--user-agent');
    });

    test('command has correct description', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new ValidateCommand($mockLinkValidation, $mockReporting);

        $reflection = new ReflectionClass(ValidateCommand::class);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);

        /** @var string $description */
        $description = $descriptionProperty->getValue($command);

        expect($description)->toBeString()
            ->and($description)->toContain('Validate links');
    });

    test('command has handle method', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);

        expect($reflection->hasMethod('handle'))->toBeTrue();

        $handleMethod = $reflection->getMethod('handle');
        expect($handleMethod->isPublic())->toBeTrue();
    });

    test('handle method has correct parameter types', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);
        $handleMethod = $reflection->getMethod('handle');
        $parameters = $handleMethod->getParameters();

        expect($parameters)->toHaveCount(1);
        expect($parameters[0]->getName())->toBe('validator');

        $paramType = $parameters[0]->getType();
        expect($paramType)->not->toBeNull();
        expect((string) $paramType)->toBe(LinkValidationInterface::class);
    });

    test('handle method returns integer', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);
        $handleMethod = $reflection->getMethod('handle');
        $returnType = $handleMethod->getReturnType();

        expect($returnType)->not->toBeNull();
        expect((string) $returnType)->toBe('int');
    });

    test('command extends BaseValidationCommand', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);
        $parentClass = $reflection->getParentClass();

        expect($parentClass)->not->toBeFalse();
        expect($parentClass->getName())->toBe(BaseValidationCommand::class);
    });

    test('command is final class', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    test('command has proper namespace', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);

        expect($reflection->getNamespaceName())->toBe('App\\Commands');
    });

    test('command constructor calls parent constructor', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        expect($constructor?->isPublic())->toBeTrue();

        $parameters = $constructor?->getParameters() ?? [];
        expect($parameters)->toHaveCount(2);
    });

    test('command has all required constants', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);

        // Check if command has access to parent constants
        expect($reflection->hasConstant('SUCCESS'))->toBeTrue();
        expect($reflection->hasConstant('FAILURE'))->toBeTrue();
    });

});
