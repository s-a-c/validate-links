<?php

declare(strict_types=1);

use App\Commands\ConfigCommand;
use old\Support\Traits\FileTestHelpers;
use old\Support\Traits\ValidationTestHelpers;

/**
 * Feature test suite for ConfigCommand integration testing.
 *
 * @group feature
 * @group commands
 *
 * @covers \App\Commands\ConfigCommand
 */
uses(FileTestHelpers::class, ValidationTestHelpers::class);

describe('ConfigCommand Integration', function (): void {
    afterEach(function (): void {
        $this->cleanupTestFiles();
    });

    describe('command structure validation', function (): void {
        test('command has proper structure for configuration management', function (): void {
            $command = new ConfigCommand();

            $reflection = new ReflectionClass(ConfigCommand::class);

            expect($reflection->hasMethod('handle'))->toBeTrue();

            $signatureProperty = $reflection->getProperty('signature');
            $signatureProperty->setAccessible(true);

            /** @var string $signature */
            $signature = $signatureProperty->getValue($command);

            expect($signature)->toContain('config')
                ->and($signature)->toContain('init');
        });
    });
});
