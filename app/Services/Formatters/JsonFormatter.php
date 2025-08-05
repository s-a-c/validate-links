<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;

/**
 * JSON formatter for validation results with structured data output.
 *
 * This formatter provides machine-readable JSON output suitable for
 * API responses, CI/CD integration, and programmatic processing.
 */
class JsonFormatter
{
    /**
     * Format validation result as structured JSON.
     *
     * @param ValidationResult $result The validation result to format
     * @param array<string, mixed> $statistics Additional statistics data
     * @return string Pretty-printed JSON string with validation data
     */
    public function format(ValidationResult $result, array $statistics): string
    {
        $data = [
            'validation' => [
                'status' => $result->isSuccessful() ? 'passed' : 'failed',
                'timestamp' => date('c'),
                'summary' => $result->getSummary(),
            ],
            'files' => $result->files,
            'links' => [
                'total' => $result->getTotalLinks(),
                'broken' => $result->getBrokenCount(),
                'success_rate' => $result->getSuccessRate(),
                'details' => $result->links,
            ],
            'broken_links' => $result->broken,
            'statistics' => $statistics,
            'performance' => [
                'duration' => $result->duration,
                'files_per_second' => count($result->files) / max($result->duration, 0.001),
                'links_per_second' => $result->getTotalLinks() / max($result->duration, 0.001),
            ],
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return $json !== false ? $json : '{}';
    }
}
