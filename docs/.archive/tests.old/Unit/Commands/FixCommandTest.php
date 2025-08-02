<?php

declare(strict_types=1);

use App\Commands\BaseValidationCommand;
use App\Commands\FixCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use old\Support\Factories\MockFactory;

require_once __DIR__ . '/../../Support/helpers.php';

/**
 * Test suite for FixCommand.
 *
 * @group commands
 * @group unit
 *
 * @covers \App\Commands\FixCommand
 */
describe('FixCommand', function (): void {
    afterEach(function (): void {
        cleanupTestFiles();
    });

    test('it can be instantiated with required dependencies', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new FixCommand($mockLinkValidation, $mockReporting);

        expect($command)->toBeInstanceOf(FixCommand::class)
            ->and($command)->toBeInstanceOf(BaseValidationCommand::class);
    });

    test('command has correct signature', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new FixCommand($mockLinkValidation, $mockReporting);

        $reflection = new ReflectionClass(FixCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue($command);

        expect($signature)->toBeString()
            ->and($signature)->toContain('fix')
            ->and($signature)->toContain('paths*')
            ->and($signature)->toContain('--backup')
            ->and($signature)->toContain('--interactive')
            ->and($signature)->toContain('--dry-run');
    });

    test('command has correct description', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new FixCommand($mockLinkValidation, $mockReporting);

        /** @var string $description */
        $description = $descriptionProperty->getValue($command);

        expect($description)->toBeString()
            ->and($description)->toContain('Fix broken links');
    });

    test('command has handle method', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);

        expect($reflection->hasMethod('handle'))->toBeTrue();

        $handleMethod = $reflection->getMethod('handle');
        expect($handleMethod->isPublic())->toBeTrue();
    });

    test('command has private handleInteractiveFix method', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);

        expect($reflection->hasMethod('handleInteractiveFix'))->toBeTrue();

        $method = $reflection->getMethod('handleInteractiveFix');
        expect($method->isPrivate())->toBeTrue();
    });

    test('command has private handleAutomaticFix method', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);

        expect($reflection->hasMethod('handleAutomaticFix'))->toBeTrue();

        $method = $reflection->getMethod('handleAutomaticFix');
        expect($method->isPrivate())->toBeTrue();
    });

    test('handle method has correct parameter types', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $handleMethod = $reflection->getMethod('handle');
        $parameters = $handleMethod->getParameters();

        expect($parameters)->toHaveCount(1);
        expect($parameters[0]->getName())->toBe('validator');

        $paramType = $parameters[0]->getType();
        expect($paramType)->not->toBeNull();
        expect((string) $paramType)->toBe(LinkValidationInterface::class);
    });

    test('handle method returns integer', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $handleMethod = $reflection->getMethod('handle');
        $returnType = $handleMethod->getReturnType();

        expect($returnType)->not->toBeNull();
        expect((string) $returnType)->toBe('int');
    });

    test('handleInteractiveFix method has correct return type', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $method = $reflection->getMethod('handleInteractiveFix');
        $returnType = $method->getReturnType();

        expect($returnType)->not->toBeNull();
        expect((string) $returnType)->toBe('int');
    });

    test('handleAutomaticFix method has correct parameter types', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $method = $reflection->getMethod('handleAutomaticFix');
        $parameters = $method->getParameters();

        expect($parameters)->toHaveCount(1);
        expect($parameters[0]->getName())->toBe('config');

        $paramType = $parameters[0]->getType();
        expect($paramType)->not->toBeNull();
        expect((string) $paramType)->toBe(ValidationConfig::class);
    });

    test('handleAutomaticFix method has correct return type', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $method = $reflection->getMethod('handleAutomaticFix');
        $returnType = $method->getReturnType();

        expect($returnType)->not->toBeNull();
        expect((string) $returnType)->toBe('int');
    });

    test('command extends BaseValidationCommand', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $parentClass = $reflection->getParentClass();

        expect($parentClass)->not->toBeFalse();
        expect($parentClass->getName())->toBe(BaseValidationCommand::class);
    });

    test('command is final class', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    test('command signature contains required arguments and options', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new FixCommand($mockLinkValidation, $mockReporting);

        /** @var string $signature */
        $signature = $signatureProperty->getValue($command);

        expect($signature)->toContain('fix');
        expect($signature)->toContain('paths*');
        expect($signature)->toContain('--backup');
        expect($signature)->toContain('--interactive');
        expect($signature)->toContain('--dry-run');
    });

    test('command description is set correctly', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new FixCommand($mockLinkValidation, $mockReporting);

        /** @var string $description */
        $description = $descriptionProperty->getValue($command);

        expect($description)->toBeString()
            ->and($description)->not->toBeEmpty()
            ->and(mb_strtolower((string) $description))->toContain('fix')
            ->and(mb_strtolower((string) $description))->toContain('broken links');
    });

    test('command has all required constants', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);

        // Check if command has access to parent constants
        expect($reflection->hasConstant('SUCCESS'))->toBeTrue();
        expect($reflection->hasConstant('FAILURE'))->toBeTrue();
    });

    test('command constructor calls parent constructor', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);
        $constructor = $reflection->getConstructor();

        expect($constructor)->not->toBeNull();
        expect($constructor?->isPublic())->toBeTrue();

        $parameters = $constructor?->getParameters() ?? [];
        expect($parameters)->toHaveCount(2);
    });

    test('command has proper namespace', function (): void {
        $reflection = new ReflectionClass(FixCommand::class);

        expect($reflection->getNamespaceName())->toBe('App\\Commands');
    });
});
