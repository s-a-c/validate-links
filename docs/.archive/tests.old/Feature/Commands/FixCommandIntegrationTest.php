<?php

declare(strict_types=1);

use App\Commands\FixCommand;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\MockingHelpers;
use old\Support\Traits\ValidationTestHelpers;

/**
 * Feature test suite for FixCommand integration testing.
 *
 * @group feature
 * @group commands
 *
 * @covers \App\Commands\FixCommand
 */
uses(FileTestHelpers::class, MockingHelpers::class, ValidationTestHelpers::class);

describe('FixCommand Integration', function (): void {
    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    describe('command structure validation', function (): void {
        test('command has proper structure for link fixing', function (): void {
            $mockLinkValidation = $this->mockLinkValidation();
            $mockReporting = $this->mockReporting();
            $command = new FixCommand($mockLinkValidation, $mockReporting);

            $reflection = new ReflectionClass(FixCommand::class);

            expect($reflection->hasMethod('handle'))->toBeTrue();

            $parentClass = $reflection->getParentClass();
            expect($parentClass)->not->toBeFalse();
            expect($parentClass->getName())->toBe('App\Commands\BaseValidationCommand');

            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('fix')
                ->and($signature)->toContain('paths');
        });
    });
});
