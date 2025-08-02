<?php

declare(strict_types=1);

namespace App\Commands;

use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\text;

final class ConfigCommand extends Command
{
    protected $signature = 'config
        {--init : Initialize configuration file}
        {--show : Show current configuration}';

    protected $description = 'Manage validate-links configuration';

    public function handle(): int
    {
        if ($this->option('init')) {
            return $this->initializeConfig();
        }

        if ($this->option('show')) {
            return $this->showConfig();
        }

        $this->info('Use --init to create a configuration file or --show to display current settings');

        return self::SUCCESS;
    }

    private function initializeConfig(): int
    {
        $this->info('🔧 Initializing validate-links configuration');

        $configPath = getcwd().'/validate-links.json';

        if (file_exists($configPath) && ! confirm("Configuration file already exists at {$configPath}. Overwrite?", false)) {
            $this->info('Configuration initialization cancelled');

            return self::SUCCESS;
        }

        $config = $this->gatherConfigurationSettings();

        if (file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT))) {
            $this->info("✅ Configuration saved to {$configPath}");

            return self::SUCCESS;
        }

        $this->error("❌ Failed to save configuration to {$configPath}");

        return self::FAILURE;
    }

    private function showConfig(): int
    {
        $this->info('📋 Current Configuration:');
        $this->newLine();

        $config = config('validate-links');
        $configJson = json_encode($config, JSON_PRETTY_PRINT);
        $this->line($configJson !== false ? $configJson : 'Error encoding configuration');

        return self::SUCCESS;
    }

    /**
     * @return array<string, mixed>
     */
    private function gatherConfigurationSettings(): array
    {
        $scope = multiselect(
            'Default validation scope',
            ['internal', 'anchor', 'cross-reference', 'external'],
            ['internal', 'anchor']
        );

        $timeout = (int) text('External link timeout (seconds)', '30');
        $maxBroken = (int) text('Maximum broken links before stopping', '50');
        $checkExternal = in_array('external', $scope);

        return [
            'scope' => $scope,
            'timeout' => $timeout,
            'max_broken' => $maxBroken,
            'check_external' => $checkExternal,
            'format' => 'console',
            'include_hidden' => false,
            'case_sensitive' => false,
        ];
    }
}
