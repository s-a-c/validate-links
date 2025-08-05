<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Commands\BaseValidationCommand;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;
use App\Services\ValueObjects\ValidationConfig;

/**
 * Testable implementation of BaseValidationCommand for testing purposes.
 */
class TestableBaseValidationCommand extends BaseValidationCommand
{
    public function __construct(
        LinkValidationInterface $linkValidation,
        ReportingInterface $reporting
    ) {
        parent::__construct($linkValidation, $reporting);
    }
    
    protected function configure(): void
    {
        $this->setName('test:validate')
             ->setDescription('Test validation command')
             ->addArgument('path', \Symfony\Component\Console\Input\InputArgument::REQUIRED, 'Path to validate')
             ->addOption('timeout', 't', \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Timeout in seconds', 30)
             ->addOption('concurrent', 'c', \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Concurrent requests', 10)
             ->addOption('max-redirects', 'r', \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Max redirects', 5)
             ->addOption('format', 'f', \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'Output format', 'console')
             ->addOption('dry-run', null, \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Dry run mode')
             ->addOption('interactive', 'i', \Symfony\Component\Console\Input\InputOption::VALUE_NONE, 'Interactive mode');
    }
    
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output): int
    {
        try {
            $path = $input->getArgument('path');
            $timeout = (int) $input->getOption('timeout');
            $concurrent = (int) $input->getOption('concurrent');
            $maxRedirects = (int) $input->getOption('max-redirects');
            $format = $input->getOption('format');
            
            // Validate inputs
            if (empty($path)) {
                $output->writeln('<error>Path cannot be empty</error>');
                return 1;
            }
            
            if ($timeout < 1 || $timeout > 300) {
                $output->writeln('<error>Timeout must be between 1 and 300 seconds</error>');
                return 1;
            }
            
            if ($concurrent < 1 || $concurrent > 50) {
                $output->writeln('<error>Concurrent requests must be between 1 and 50</error>');
                return 1;
            }
            
            if ($maxRedirects < 0 || $maxRedirects > 10) {
                $output->writeln('<error>Max redirects must be between 0 and 10</error>');
                return 1;
            }
            
            // Create configuration
            $config = ValidationConfig::create([
                'timeout' => $timeout,
                'concurrent_requests' => $concurrent,
                'max_redirects' => $maxRedirects,
                'scopes' => [ValidationScope::ALL],
            ]);
            
            // Simulate validation
            $output->writeln("<info>Validating: {$path}</info>");
            $output->writeln("<info>Configuration: timeout={$timeout}, concurrent={$concurrent}, format={$format}</info>");
            
            // Return success
            return 0;
            
        } catch (\Throwable $e) {
            $output->writeln("<error>Command execution failed: {$e->getMessage()}</error>");
            return 1;
        }
    }
}