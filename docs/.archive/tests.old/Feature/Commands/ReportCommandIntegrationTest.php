<?php

declare(strict_types=1);

use App\Commands\ReportCommand;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\MockingHelpers;
use old\Support\Traits\ValidationTestHelpers;

/**
 * Feature test suite for ReportCommand integration testing.
 *
 * @group feature
 * @group commands
 *
 * @covers \App\Commands\ReportCommand
 */
uses(FileTestHelpers::class, MockingHelpers::class, ValidationTestHelpers::class);

describe('ReportCommand Integration', function (): void {
    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    describe('command structure validation', function (): void {
        test('command has proper structure for report generation', function (): void {
            $mockLinkValidation = $this->mockLinkValidation();
            $mockReporting = $this->mockReporting();
            $command = new ReportCommand($mockLinkValidation, $mockReporting);

            $reflection = new ReflectionClass(ReportCommand::class);

            expect($reflection->hasMethod('handle'))->toBeTrue();

            $parentClass = $reflection->getParentClass();
            expect($parentClass)->not->toBeFalse();
            expect($parentClass->getName())->toBe('App\Commands\BaseValidationCommand');

            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('report')
                ->and($signature)->toContain('paths');
        });
    });
});
