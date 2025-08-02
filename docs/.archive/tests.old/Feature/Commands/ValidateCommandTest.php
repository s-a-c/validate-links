<?php

declare(strict_types=1);

use App\Commands\ValidateCommand;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\HttpTestHelpers;
use old\Support\Traits\MockingHelpers;

/**
 * Feature test suite for ValidateCommand integration testing.
 *
 * @group feature
 * @group commands
 *
 * @covers \App\Commands\ValidateCommand
 */
uses(FileTestHelpers::class, HttpTestHelpers::class, MockingHelpers::class);

describe('ValidateCommand Feature Tests', function (): void {
    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    test('command has proper structure for documentation validation', function (): void {
        $mockLinkValidation = $this->mockLinkValidation();
        $mockReporting = $this->mockReporting();
        $command = new ValidateCommand($mockLinkValidation, $mockReporting);

        $reflection = new ReflectionClass(ValidateCommand::class);

        expect($reflection->hasMethod('handle'))->toBeTrue();

        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue($command);

        expect($signature)->toContain('validate')
            ->and($signature)->toContain('paths');
    });

    test('command supports external link validation options', function (): void {
        $mockLinkValidation = $this->mockLinkValidation();
        $mockReporting = $this->mockReporting();
        $command = new ValidateCommand($mockLinkValidation, $mockReporting);

        $reflection = new ReflectionClass(ValidateCommand::class);
        $signatureProperty = $reflection->getProperty('signature');
        $signatureProperty->setAccessible(true);

        /** @var string $signature */
        $signature = $signatureProperty->getValue($command);

        expect($signature)->toContain('check-external');
    });

    test('command extends proper base class for validation features', function (): void {
        $reflection = new ReflectionClass(ValidateCommand::class);
        $parentClass = $reflection->getParentClass();

        expect($parentClass)->not->toBeFalse();
        expect($parentClass->getName())->toBe('App\Commands\BaseValidationCommand');
    });
});
