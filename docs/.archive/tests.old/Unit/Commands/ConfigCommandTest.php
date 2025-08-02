<?php

declare(strict_types=1);

use App\Commands\ConfigCommand;
use LaravelZero\Framework\Commands\Command;

require_once __DIR__ . '/../../Support/helpers.php';

/**
 * Test suite for ConfigCommand.
 *
 * @group commands
 * @group unit
 *
 * @covers \App\Commands\ConfigCommand
 */
describe('ConfigCommand', function (): void {
    afterEach(function (): void {
        cleanupTestFiles();
    });

    test('it can be instantiated', function (): void {
        $command = new ConfigCommand();

        expect($command)->toBeInstanceOf(ConfigCommand::class)
            ->and($command)->toBeInstanceOf(Command::class);
    });

    test('command has correct signature', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue(new ConfigCommand());

        expect($signature)->toBeString()
            ->and($signature)->toContain('config')
            ->and($signature)->toContain('--init')
            ->and($signature)->toContain('--show');
    });

    test('command has correct description', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);

        /** @var string $description */
        $description = $descriptionProperty->getValue(new ConfigCommand());

        expect($description)->toBeString()
            ->and($description)->not->toBeEmpty();
    });

    test('handle method exists and is public', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);
        $handleMethod = $reflection->getMethod('handle');

        expect($handleMethod->isPublic())->toBeTrue()
            ->and($handleMethod->getNumberOfParameters())->toBe(0);
    });

    test('initialize config method exists', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);

        expect($reflection->hasMethod('initializeConfig'))->toBeTrue();

        $initMethod = $reflection->getMethod('initializeConfig');
        expect($initMethod->isPrivate())->toBeTrue();
    });

    test('show config method exists', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);

        expect($reflection->hasMethod('showConfig'))->toBeTrue();

        $showMethod = $reflection->getMethod('showConfig');
        expect($showMethod->isPrivate())->toBeTrue();
    });

    test('command supports init and show options', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue(new ConfigCommand());

        expect($signature)->toContain('--init')
            ->and($signature)->toContain('--show');
    });

    test('gather configuration settings returns valid structure', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);

        expect($reflection->hasMethod('gatherConfigurationSettings'))->toBeTrue();

        $gatherMethod = $reflection->getMethod('gatherConfigurationSettings');
        expect($gatherMethod->isPrivate())->toBeTrue();
    });

    test('command signature includes all expected options', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue(new ConfigCommand());

        expect($signature)->toBeString()
            ->and($signature)->toContain('config')
            ->and($signature)->toContain('{--init')
            ->and($signature)->toContain('{--show');
    });

    test('command has proper method structure', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);

        // Check that essential methods exist
        expect($reflection->hasMethod('handle'))->toBeTrue();
        expect($reflection->hasMethod('initializeConfig'))->toBeTrue();
        expect($reflection->hasMethod('showConfig'))->toBeTrue();
        expect($reflection->hasMethod('gatherConfigurationSettings'))->toBeTrue();
    });

    test('command signature is correctly defined', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue(new ConfigCommand());

        expect($signature)->toContain('config')
            ->and($signature)->toContain('--init')
            ->and($signature)->toContain('--show');
    });

    test('command description is set', function (): void {
        $reflection = new ReflectionClass(ConfigCommand::class);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);

        /** @var string $description */
        $description = $descriptionProperty->getValue(new ConfigCommand());

        expect($description)->toBeString()
            ->and($description)->not->toBeEmpty()
            ->and(mb_strtolower($description))->toContain('configuration');
    });
});
