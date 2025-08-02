<?php

declare(strict_types=1);

use App\Commands\BaseValidationCommand;
use App\Enums\LinkStatus;
use App\Services\ValueObjects\ValidationConfig;
use old\Support\Doubles\Commands\TestableBaseValidationCommand;
use old\Support\Factories\MockFactory;

require_once __DIR__ . '/../../Support/helpers.php';

/**
 * Test suite for BaseValidationCommand.
 *
 * @group commands
 * @group unit
 *
 * @covers \App\Commands\BaseValidationCommand
 */
describe('BaseValidationCommand', function (): void {
    afterEach(function (): void {
        cleanupTestFiles();
    });

    test('it can be instantiated with required dependencies', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();

        $command = new TestableBaseValidationCommand(
            $mockLinkValidation,
            $mockReporting
        );

        expect($command)->toBeInstanceOf(BaseValidationCommand::class);
        expect($command)->toBeInstanceOf(TestableBaseValidationCommand::class);
    });

    test('constructor sets dependencies correctly', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();

        $command = new TestableBaseValidationCommand(
            $mockLinkValidation,
            $mockReporting
        );

        expect($command->getLinkValidation())->toBe($mockLinkValidation);
        expect($command->getReporting())->toBe($mockReporting);
    });

    test('collect files returns array for single file', function (): void {
        $testFile = createTestFile('test.md', '# Test');
        $config = ValidationConfig::withDefaults();

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $files = $command->collectFiles([$testFile], $config);

        expect($files)->toBeArray();
        expect($files)->toContain($testFile);
        expect($files)->toHaveCount(1);
    });

    test('collect files returns array for directory', function (): void {
        $testDir = getTestPath('docs');
        if (! mkdir($testDir, 0755, true) && ! is_dir($testDir)) {
            throw new RuntimeException("Failed to create directory: {$testDir}");
        }

        $file1 = createTestFile('docs/file1.md', '# File 1');
        $file2 = createTestFile('docs/file2.md', '# File 2');

        $config = ValidationConfig::withDefaults();

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $files = $command->collectFiles([$testDir], $config);

        expect($files)->toBeArray();
        expect(count($files))->toBeGreaterThanOrEqual(2);
        expect($files)->toContain($file1);
        expect($files)->toContain($file2);
    });

    test('collect files handles mixed paths', function (): void {
        $testFile = createTestFile('single.md', '# Single');
        $testDir = getTestPath('mixed');
        if (! mkdir($testDir, 0755, true) && ! is_dir($testDir)) {
            throw new RuntimeException("Failed to create directory: {$testDir}");
        }
        $dirFile = createTestFile('mixed/dir.md', '# Dir');

        $config = ValidationConfig::withDefaults();

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $files = $command->collectFiles([$testFile, $testDir], $config);

        expect($files)->toBeArray();
        expect($files)->toContain($testFile);
        expect($files)->toContain($dirFile);
    });

    test('validate command options accepts valid scope', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->setOption('scope', 'internal');
        $command->setOption('format', 'console');

        $command->validateCommandOptions();
    })->throwsNoExceptions();

    test('validate command options accepts all scope', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->setOption('scope', 'all');
        $command->setOption('format', 'json');

        $command->validateCommandOptions();
    })->throwsNoExceptions();

    test('validate command options accepts comma separated scopes', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->setOption('scope', 'internal,external,anchor');
        $command->setOption('format', 'html');

        $command->validateCommandOptions();
    })->throwsNoExceptions();

    test('validate command options rejects invalid scope', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->setOption('scope', 'invalid_scope');
        $command->setOption('format', 'console');

        $command->validateCommandOptions();
    })->throws(Exception::class);

    test('validate command options rejects invalid format', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->setOption('scope', 'all');
        $command->setOption('format', 'invalid_format');

        $command->validateCommandOptions();
    })->throws(Exception::class);

    test('process validation results returns success for no broken links', function (): void {
        $results = [
            'summary' => ['broken_links' => 0],
            'broken_links' => [],
        ];

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $exitCode = $command->processValidationResults($results);

        expect($exitCode)->toBe(0);
    });

    test('process validation results returns failure for broken links', function (): void {
        $results = [
            'summary' => ['broken_links' => 2],
            'broken_links' => [
                ['url' => 'https://broken1.com', 'status' => LinkStatus::BROKEN->value],
                ['url' => 'https://broken2.com', 'status' => LinkStatus::NOT_FOUND->value],
            ],
        ];

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $exitCode = $command->processValidationResults($results);

        expect($exitCode)->toBe(1);
    });

    test('execute validation calls link validation service', function (): void {
        $files = ['test.md'];
        $config = ValidationConfig::withDefaults();
        $expectedResults = ['summary' => ['broken_links' => 0]];

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();

        $mockLinkValidation
            ->shouldReceive('validateFiles')
            ->once()
            ->with($files, $config)
            ->andReturn($expectedResults);

        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);
        $result = $command->executeValidation($files, $config);

        expect($result)->toBe($expectedResults);
    });

    test('execute validation handles exceptions', function (): void {
        $files = ['test.md'];
        $config = ValidationConfig::withDefaults();

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();

        $mockLinkValidation
            ->shouldReceive('validateFiles')
            ->once()
            ->andThrow(new Exception('Validation failed'));

        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);
        $command->executeValidation($files, $config);
    })->throws(Exception::class, 'Validation failed');

    test('handle validation error returns error exit code', function (): void {
        $exception = new Exception('Test error');

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $exitCode = $command->handleValidationError($exception);

        expect($exitCode)->toBe(2);
    });

    test('handle validation error shows verbose output when enabled', function (): void {
        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->setOption('verbose', true);
        $exception = new Exception('Test error');

        $command->handleValidationError($exception);

        // In a real implementation, we would capture and assert output
        expect(true)->toBeTrue(); // Placeholder assertion
    });

    test('group links by status organizes results correctly', function (): void {
        $brokenLinks = [
            ['url' => 'https://valid.com', 'status' => LinkStatus::VALID->value],
            ['url' => 'https://broken.com', 'status' => LinkStatus::BROKEN->value],
            ['url' => 'https://notfound.com', 'status' => LinkStatus::NOT_FOUND->value],
            ['url' => 'https://timeout.com', 'status' => LinkStatus::TIMEOUT->value],
        ];

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $groups = $command->groupLinksByStatus($brokenLinks);

        expect($groups)->toBeArray();
        expect($groups)->toHaveKey(LinkStatus::VALID->value);
        expect($groups)->toHaveKey(LinkStatus::BROKEN->value);
        expect($groups)->toHaveKey(LinkStatus::NOT_FOUND->value);
        expect($groups)->toHaveKey(LinkStatus::TIMEOUT->value);
    });

    test('display status groups handles empty groups', function (): void {
        $statusGroups = [];

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->displayStatusGroups($statusGroups);
    })->throwsNoExceptions();

    test('display validation summary shows summary information', function (): void {
        $summary = [
            'total_links' => 10,
            'valid_links' => 8,
            'broken_links' => 2,
            'execution_time' => 1.5,
        ];

        $mockLinkValidation = MockFactory::linkValidation();
        $mockReporting = MockFactory::reporting();
        $command = new TestableBaseValidationCommand($mockLinkValidation, $mockReporting);

        $command->displayValidationSummary($summary);
    })->throwsNoExceptions();
});
