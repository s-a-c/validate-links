<?php

declare(strict_types=1);

namespace App\Commands;

use App\Enums\OutputFormat;
use App\Services\ValueObjects\ValidationConfig;

final class ReportCommand extends BaseValidationCommand
{
    protected $signature = 'report
        {paths* : Paths to analyze}
        {--format=html : Report format (html, markdown, json)}
        {--output= : Output file path}
        {--detailed : Include detailed link analysis}';

    protected $description = 'Generate detailed link validation reports';

    public function handle(): int
    {
        $paths = $this->argument('paths');
        if (empty($paths)) {
            $this->error('No paths provided');

            return self::FAILURE;
        }

        $config = ValidationConfig::withDefaults([
            'paths' => $paths,
            'format' => $this->option('format'),
            'output' => $this->option('output'),
        ]);

        $this->info('📊 Generating link validation report');

        $files = $this->collectFiles($paths, $config);
        $result = $this->linkValidation->validateFiles($files, $config);

        $format = OutputFormat::from($this->option('format') ?? 'html');
        $report = $this->reporting->generateReport($result, $format);

        if ($this->option('output')) {
            $outputPath = $this->option('output');
            $saveResult = $this->reporting->saveReport($report, $outputPath);

            if ($saveResult) {
                $this->info("Report saved to: {$outputPath}");
            } else {
                $this->error("Failed to save report to: {$outputPath}");

                return self::FAILURE;
            }
        } else {
            $this->line($report);
        }

        return self::SUCCESS;
    }
}
