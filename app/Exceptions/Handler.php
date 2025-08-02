<?php

// app/Exceptions/Handler.php

declare(strict_types=1);

namespace App\Exceptions;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Response;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ValidateLinksException) {
            return $this->renderValidateLinksException($exception);
        }

        return parent::render($request, $exception);
    }

    private function renderValidateLinksException(ValidateLinksException $exception): Response
    {
        // Try to get console output for CLI context
        try {
            $kernel = app(Kernel::class);
            if (method_exists($kernel, 'getOutput')) {
                $output = $kernel->getOutput();
                if ($output instanceof OutputInterface) {
                    $output->writeln("<error>Error [{$exception->getErrorCode()}]: {$exception->getMessage()}</error>");

                    if ($exception->getContext()) {
                        $output->writeln('<comment>Context:</comment>');
                        foreach ($exception->getContext() as $key => $value) {
                            $valueStr = match (true) {
                                is_string($value) => $value,
                                is_scalar($value) => (string) $value,
                                is_array($value) || is_object($value) => json_encode($value, JSON_THROW_ON_ERROR),
                                default => 'null'
                            };
                            $output->writeln("  <info>{$key}:</info> {$valueStr}");
                        }
                    }

                    if (config('app.debug')) {
                        $output->writeln('<comment>Stack trace:</comment>');
                        $output->writeln($exception->getTraceAsString());
                    }
                }
            }
        } catch (Throwable) {
            // Ignore console output errors in web context
        }

        // Return a proper HTTP response for web contexts
        $message = "Error [{$exception->getErrorCode()}]: {$exception->getMessage()}";

        return new Response($message, 500, ['Content-Type' => 'text/plain']);
    }
}
