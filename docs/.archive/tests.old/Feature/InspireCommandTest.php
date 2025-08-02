<?php

declare(strict_types=1);

use App\Commands\InspireCommand;

/**
 * Feature test suite for InspireCommand.
 *
 * @group feature
 * @group commands
 *
 * @covers \App\Commands\InspireCommand
 */
test('inspire command has proper structure', function (): void {
    $reflection = new ReflectionClass(InspireCommand::class);

    expect($reflection->hasMethod('handle'))->toBeTrue();

    $signatureProperty = $reflection->getProperty('signature');
    $signatureProperty->setAccessible(true);

    /** @var string $signature */
    $signature = $signatureProperty->getValue(new InspireCommand());

    expect($signature)->toContain('inspire');
});
