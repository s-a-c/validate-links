<?php

declare(strict_types=1);

use App\Commands\ValidateCommand;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\MockingHelpers;
use old\Support\Traits\ValidationTestHelpers;

/**
 * Feature test suite for file system integration testing.
 *
 * @group feature
 * @group filesystem
 *
 * @covers \App\Commands\ValidateCommand
 */
uses(FileTestHelpers::class, MockingHelpers::class, ValidationTestHelpers::class);

describe('File System Integration', function (): void {
    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    describe('command file handling capabilities', function (): void {
        test('command supports file type processing options', function (): void {
            $mockLinkValidation = $this->mockLinkValidation();
            $mockReporting = $this->mockReporting();
            $command = new ValidateCommand($mockLinkValidation, $mockReporting);

            $reflection = new ReflectionClass(ValidateCommand::class);
            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('path')
                ->and($signature)->toBeString();
        });

        test('command has proper structure for file system integration', function (): void {
            $reflection = new ReflectionClass(ValidateCommand::class);

            expect($reflection->hasMethod('handle'))->toBeTrue();

            $parentClass = $reflection->getParentClass();
            expect($parentClass)->not->toBeFalse();
            expect($parentClass->getName())->toBe('App\Commands\BaseValidationCommand');
        });
    });
});
