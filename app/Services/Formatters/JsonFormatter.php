<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;
use Carbon\Carbon;

/**
 * JSON formatter for validation results with structured data output.
 *
 * This formatter provides machine-readable JSON output suitable for
 * API responses, CI/CD integration, and programmatic processing.
 */
final class JsonFormatter
{
    /**
     * Format validation result as structured JSON.
     *
     * @param ValidationResult $validationResult The validation result to format
     * @param array<string, mixed> $statistics Additional statistics data
     *
     * @return string Pretty-printed JSON string with validation data
     */
    public function format(ValidationResult $validationResult, array $statistics): string
    {
        $data = [
            'validation' => [
                'status' => $validationResult->isSuccessful() ? 'passed' : 'failed',
                'timestamp' => Carbon::now()->format('c'),
                'summary' => $validationResult->getSummary(),
            ],
            'files' => $validationResult->files,
            'links' => [
                'total' => $validationResult->getTotalLinks(),
                'broken' => $validationResult->getBrokenCount(),
                'success_rate' => $validationResult->getSuccessRate(),
                'details' => $validationResult->links,
            ],
            'broken_links' => $validationResult->broken,
            'statistics' => $statistics,
            'performance' => [
                'duration' => $validationResult->duration,
                'files_per_second' => count($validationResult->files) / max($validationResult->duration, 0.001),
                'links_per_second' => $validationResult->getTotalLinks() / max($validationResult->duration, 0.001),
            ],
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $json !== false ? $json : '{}';
    }
}
