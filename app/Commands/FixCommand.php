<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\Contracts\LinkValidationInterface;
use App\Services\ValueObjects\ValidationConfig;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;

class FixCommand extends BaseValidationCommand
{
    protected $signature = 'fix
        {paths* : Paths to fix}
        {--backup : Create backup files before fixing}
        {--interactive : Interactive mode for fixes}
        {--dry-run : Preview fixes without applying them}';

    protected $description = 'Fix broken links in documentation files';

    public function handle(LinkValidationInterface $validator): int
    {
        $paths = $this->argument('paths');
        if (empty($paths)) {
            $this->error('No paths provided');

            return self::FAILURE;
        }

        $config = ValidationConfig::withDefaults([
            'paths' => $paths,
            'fix' => true,
            'dryRun' => $this->option('dry-run'),
            'interactive' => $this->option('interactive'),
        ]);

        if ($config->isInteractive()) {
            return $this->handleInteractiveFix();
        }

        return $this->handleAutomaticFix($config);
    }

    private function handleInteractiveFix(): int
    {
        info('🔧 Interactive Link Fixing Mode');
        if (! confirm('This will attempt to fix broken links. Continue?', true)) {
            $this->info('Fix cancelled');

            return self::SUCCESS;
        }
        // Implementation for interactive fixing
        $this->warn('Interactive fixing not yet implemented');

        return self::SUCCESS;
    }

    private function handleAutomaticFix(
        ValidationConfig $config
    ): int {
        if ($config->isDryRun()) {
            $this->info('🔍 DRY RUN: Preview of fixes');
        } else {
            $this->info('🔧 Automatically fixing broken links');
        }

        // Implementation for automatic fixing
        $this->warn('Automatic fixing not yet implemented');

        return self::SUCCESS;
    }
}
