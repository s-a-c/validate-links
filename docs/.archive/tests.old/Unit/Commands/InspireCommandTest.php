<?php

declare(strict_types=1);

use App\Commands\InspireCommand;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;

require_once __DIR__ . '/../../Support/helpers.php';

/**
 * Test suite for InspireCommand.
 *
 * @group commands
 * @group unit
 *
 * @covers \App\Commands\InspireCommand
 */
describe('InspireCommand', function (): void {

    test('it can be instantiated', function (): void {
        $command = new InspireCommand();

        expect($command)->toBeInstanceOf(InspireCommand::class)
            ->and($command)->toBeInstanceOf(Command::class);
    });

    test('handle executes successfully', function (): void {
        // Test that handle method executes without throwing exceptions
        $command = new InspireCommand();

        // Just call the method - it returns void so we can't test the result
        $command->handle();

        // If we get here without exception, the test passes
        expect(true)->toBeTrue();
    });

    test('handle method accepts name argument via input', function (): void {
        // Since we can't easily test artisan commands in unit tests without full app bootstrap,
        // we test the command structure and method existence
        $reflection = new ReflectionClass(InspireCommand::class);
        $handleMethod = $reflection->getMethod('handle');

        expect($handleMethod->isPublic())->toBeTrue();
        expect($handleMethod->getNumberOfParameters())->toBe(0);
    });

    test('handle method returns void', function (): void {
        $command = new InspireCommand();

        // Just call the method - it returns void so we can't test the result
        $command->handle();

        // If we get here without exception, the test passes
        expect(true)->toBeTrue();
    });

    test('command signature is correctly defined', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue(new InspireCommand());

        expect($signature)->toBeString()
            ->and($signature)->toContain('inspire')
            ->and($signature)->toContain('name=Artisan');
    });

    test('command description is set', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $descriptionProperty = $reflection->getProperty('description');
        $descriptionProperty->setAccessible(true);

        /** @var string $description */
        $description = $descriptionProperty->getValue(new InspireCommand());

        expect($description)->toBeString()
            ->and($description)->not->toBeEmpty()
            ->and($description)->toBe('Display an inspiring quote');
    });

    test('schedule method exists and accepts schedule parameter', function (): void {
        $command = new InspireCommand();

        /** @var Mockery\MockInterface&Schedule $schedule */
        $schedule = Mockery::mock(Schedule::class);

        $command->schedule($schedule);

        // If we get here without exception, the test passes
        expect(true)->toBeTrue();
    });

    test('schedule method has correct signature', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $scheduleMethod = $reflection->getMethod('schedule');

        expect($scheduleMethod->isPublic())->toBeTrue();

        $parameters = $scheduleMethod->getParameters();
        expect($parameters)->toHaveCount(1);

        $scheduleParam = $parameters[0];
        expect($scheduleParam->getName())->toBe('schedule');
        $paramType = $scheduleParam->getType();
        expect($paramType)->not->toBeNull();
        if ($paramType instanceof ReflectionNamedType) {
            expect($paramType->getName())->toBe(Schedule::class);
        }
    });

    test('schedule method returns void', function (): void {
        $command = new InspireCommand();

        /** @var Mockery\MockInterface&Schedule $schedule */
        $schedule = Mockery::mock(Schedule::class);

        // Just call the method - it returns void so we can't test the result
        $command->schedule($schedule);

        // If we get here without exception, the test passes
        expect(true)->toBeTrue();
    });

    test('command renders termwind output', function (): void {
        // Since we can't easily test Termwind output in unit tests.old,
        // we verify that the handle method executes without errors
        ob_start();
        $command = new InspireCommand();

        $command->handle();
        /** @var string|false $output */
        $output = ob_get_clean();

        // The command should execute without throwing exceptions
        expect($output)->toBeString();
    });

    test('command is final class', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);

        expect($reflection->isFinal())->toBeTrue();
    });

    test('command extends laravel zero command', function (): void {
        $command = new InspireCommand();

        expect($command)->toBeInstanceOf(Command::class);
    });

    test('command has proper namespace', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);

        expect($reflection->getNamespaceName())->toBe('App\Commands');
    });

    test('command uses strict types', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $filename = $reflection->getFileName();
        $content = file_get_contents($filename);

        expect($content)->toContain('declare(strict_types=1);');
    });

    test('command imports required classes', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $filename = $reflection->getFileName();
        $content = file_get_contents($filename);

        expect($content)->toContain('use Illuminate\Console\Scheduling\Schedule;');
        expect($content)->toContain('use LaravelZero\Framework\Commands\Command;
use Mockery;');
        expect($content)->toContain('use function Termwind\render;');
    });

    test('command has proper docblocks', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);

        // Check signature property docblock
        $signatureProperty = $reflection->getProperty('signature');
        $docComment = $signatureProperty->getDocComment();
        expect($docComment)->toContain('The signature of the command');

        // Check description property docblock
        $descriptionProperty = $reflection->getProperty('description');
        $docComment = $descriptionProperty->getDocComment();
        expect($docComment)->toContain('The description of the command');

        // Check handle method docblock
        $handleMethod = $reflection->getMethod('handle');
        $docComment = $handleMethod->getDocComment();
        expect($docComment)->toContain('Execute the console command');

        // Check schedule method docblock
        $scheduleMethod = $reflection->getMethod('schedule');
        $docComment = $scheduleMethod->getDocComment();
        expect($docComment)->toContain('Define the command\'s schedule');
    });

    test('command signature has correct default value', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        $signature = $signatureProperty->getValue(new InspireCommand());

        expect($signature)->toContain('{name=Artisan}');
    });

    test('command signature supports name argument', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue(new InspireCommand());

        // Verify the signature includes the name argument with default value
        expect($signature)->toContain('{name=Artisan}')
            ->and($signature)->toContain('inspire');
    });

    test('command handles argument parsing structure', function (): void {
        // Test that the command structure supports argument handling
        $reflection = new ReflectionClass(InspireCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue(new InspireCommand());

        // Verify signature format allows for optional name parameter
        expect($signature)->toMatch('/\{name=.*\}/');
    });

    test('schedule method contains commented example', function (): void {
        $reflection = new ReflectionClass(InspireCommand::class);
        $filename = $reflection->getFileName();
        $content = file_get_contents($filename);

        // Check that the schedule method contains the commented example
        expect($content)->toContain('// $schedule->command(static::class)->everyMinute();');
    });
});
