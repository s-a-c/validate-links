<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Services\ValueObjects\ReportSummary;

/**
 * Reporting Custom Matchers for PEST
 * 
 * Provides domain-specific matchers for report validation,
 * format checking, and content verification.
 */

if (!function_exists('toMatchReportFormat')) {
    /**
     * Assert that report matches expected format
     */
    function toMatchReportFormat(OutputFormat $expectedFormat): Closure
    {
        return function () use ($expectedFormat) {
            if (is_string($this->value)) {
                switch ($expectedFormat) {
                    case OutputFormat::JSON:
                        return json_decode($this->value) !== null;
                    case OutputFormat::HTML:
                        return str_contains($this->value, '<html') || str_contains($this->value, '<!DOCTYPE');
                    case OutputFormat::MARKDOWN:
                        return str_contains($this->value, '#') || str_contains($this->value, '**') || str_contains($this->value, '*');
                    case OutputFormat::CONSOLE:
                        return is_string($this->value); // Console format is plain text
                    default:
                        return false;
                }
            }
            
            return false;
        };
    }
}

if (!function_exists('toContainValidationResults')) {
    /**
     * Assert that report contains validation results
     */
    function toContainValidationResults(int $expectedCount = null): Closure
    {
        return function () use ($expectedCount) {
            if (is_string($this->value)) {
                // Try to parse as JSON first
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    $results = $data['results'] ?? $data['validation_results'] ?? [];
                    if ($expectedCount !== null) {
                        return count($results) === $expectedCount;
                    }
                    return !empty($results);
                }
                
                // For other formats, look for common patterns
                $patterns = [
                    'validation',
                    'results',
                    'links',
                    'status',
                    'valid',
                    'broken',
                ];
                
                foreach ($patterns as $pattern) {
                    if (stripos($this->value, $pattern) !== false) {
                        return true;
                    }
                }
                
                return false;
            }
            
            if (is_array($this->value)) {
                $results = $this->value['results'] ?? $this->value['validation_results'] ?? [];
                if ($expectedCount !== null) {
                    return count($results) === $expectedCount;
                }
                return !empty($results);
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveReportSummary')) {
    /**
     * Assert that report has a summary section
     */
    function toHaveReportSummary(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    return isset($data['summary']);
                }
                
                // For other formats, look for summary indicators
                $summaryPatterns = [
                    'summary',
                    'total',
                    'statistics',
                    'overview',
                ];
                
                foreach ($summaryPatterns as $pattern) {
                    if (stripos($this->value, $pattern) !== false) {
                        return true;
                    }
                }
                
                return false;
            }
            
            if (is_array($this->value)) {
                return isset($this->value['summary']);
            }
            
            if ($this->value instanceof ReportSummary) {
                return true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveValidJsonStructure')) {
    /**
     * Assert that JSON report has valid structure
     */
    function toHaveValidJsonStructure(array $requiredKeys = []): Closure
    {
        return function () use ($requiredKeys) {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data === null) {
                    return false;
                }
                
                $defaultKeys = ['results', 'summary', 'metadata'];
                $keys = array_merge($defaultKeys, $requiredKeys);
                
                foreach ($keys as $key) {
                    if (!isset($data[$key])) {
                        return false;
                    }
                }
                
                return true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveValidHtmlStructure')) {
    /**
     * Assert that HTML report has valid structure
     */
    function toHaveValidHtmlStructure(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                // Check for basic HTML structure
                $requiredElements = [
                    '<html',
                    '<head',
                    '<body',
                    '</html>',
                ];
                
                foreach ($requiredElements as $element) {
                    if (stripos($this->value, $element) === false) {
                        return false;
                    }
                }
                
                return true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveValidMarkdownStructure')) {
    /**
     * Assert that Markdown report has valid structure
     */
    function toHaveValidMarkdownStructure(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                // Check for markdown elements
                $markdownPatterns = [
                    '/^#\s+/', // Headers
                    '/\*\*.*\*\*/', // Bold text
                    '/\|.*\|/', // Tables
                    '/^\s*-\s+/', // Lists
                ];
                
                foreach ($markdownPatterns as $pattern) {
                    if (preg_match($pattern, $this->value)) {
                        return true;
                    }
                }
                
                return false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toContainStatistics')) {
    /**
     * Assert that report contains statistics
     */
    function toContainStatistics(array $expectedStats = []): Closure
    {
        return function () use ($expectedStats) {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    $stats = $data['statistics'] ?? $data['summary'] ?? [];
                    
                    foreach ($expectedStats as $stat => $value) {
                        if (!isset($stats[$stat]) || $stats[$stat] !== $value) {
                            return false;
                        }
                    }
                    
                    return !empty($stats);
                }
                
                // For other formats, look for statistical terms
                $statPatterns = [
                    'total',
                    'valid',
                    'broken',
                    'percentage',
                    'count',
                ];
                
                foreach ($statPatterns as $pattern) {
                    if (stripos($this->value, $pattern) !== false) {
                        return true;
                    }
                }
                
                return false;
            }
            
            if (is_array($this->value)) {
                $stats = $this->value['statistics'] ?? $this->value['summary'] ?? [];
                
                foreach ($expectedStats as $stat => $value) {
                    if (!isset($stats[$stat]) || $stats[$stat] !== $value) {
                        return false;
                    }
                }
                
                return !empty($stats);
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveTimestamp')) {
    /**
     * Assert that report has a timestamp
     */
    function toHaveTimestamp(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    return isset($data['timestamp']) || isset($data['generated_at']) || isset($data['created_at']);
                }
                
                // Look for timestamp patterns in text
                $timestampPatterns = [
                    '/\d{4}-\d{2}-\d{2}/',
                    '/\d{2}\/\d{2}\/\d{4}/',
                    '/Generated at:/',
                    '/Created on:/',
                ];
                
                foreach ($timestampPatterns as $pattern) {
                    if (preg_match($pattern, $this->value)) {
                        return true;
                    }
                }
                
                return false;
            }
            
            if (is_array($this->value)) {
                return isset($this->value['timestamp']) || 
                       isset($this->value['generated_at']) || 
                       isset($this->value['created_at']);
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveMetadata')) {
    /**
     * Assert that report has metadata
     */
    function toHaveMetadata(array $expectedMetadata = []): Closure
    {
        return function () use ($expectedMetadata) {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    $metadata = $data['metadata'] ?? [];
                    
                    foreach ($expectedMetadata as $key => $value) {
                        if (!isset($metadata[$key]) || $metadata[$key] !== $value) {
                            return false;
                        }
                    }
                    
                    return !empty($metadata);
                }
                
                return false;
            }
            
            if (is_array($this->value)) {
                $metadata = $this->value['metadata'] ?? [];
                
                foreach ($expectedMetadata as $key => $value) {
                    if (!isset($metadata[$key]) || $metadata[$key] !== $value) {
                        return false;
                    }
                }
                
                return !empty($metadata);
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeValidReport')) {
    /**
     * Assert that value is a valid report with all required sections
     */
    function toBeValidReport(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    $requiredSections = ['results', 'summary', 'metadata'];
                    foreach ($requiredSections as $section) {
                        if (!isset($data[$section])) {
                            return false;
                        }
                    }
                    return true;
                }
                
                // For non-JSON formats, check for basic report structure
                $reportIndicators = [
                    'validation',
                    'results',
                    'summary',
                ];
                
                $foundIndicators = 0;
                foreach ($reportIndicators as $indicator) {
                    if (stripos($this->value, $indicator) !== false) {
                        $foundIndicators++;
                    }
                }
                
                return $foundIndicators >= 2;
            }
            
            if (is_array($this->value)) {
                $requiredSections = ['results', 'summary'];
                foreach ($requiredSections as $section) {
                    if (!isset($this->value[$section])) {
                        return false;
                    }
                }
                return true;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveErrorDetails')) {
    /**
     * Assert that report contains error details for broken links
     */
    function toHaveErrorDetails(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    $results = $data['results'] ?? [];
                    foreach ($results as $result) {
                        if (isset($result['status']) && $result['status'] !== 'valid') {
                            if (isset($result['error']) || isset($result['error_message'])) {
                                return true;
                            }
                        }
                    }
                    return false;
                }
                
                // For other formats, look for error indicators
                $errorPatterns = [
                    'error',
                    'failed',
                    'broken',
                    'timeout',
                    'not found',
                ];
                
                foreach ($errorPatterns as $pattern) {
                    if (stripos($this->value, $pattern) !== false) {
                        return true;
                    }
                }
                
                return false;
            }
            
            if (is_array($this->value)) {
                $results = $this->value['results'] ?? [];
                foreach ($results as $result) {
                    if (isset($result['status']) && $result['status'] !== 'valid') {
                        if (isset($result['error']) || isset($result['error_message'])) {
                            return true;
                        }
                    }
                }
                return false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveSuccessRate')) {
    /**
     * Assert that report shows a specific success rate
     */
    function toHaveSuccessRate(float $expectedRate, float $tolerance = 0.01): Closure
    {
        return function () use ($expectedRate, $tolerance) {
            if (is_string($this->value)) {
                $data = json_decode($this->value, true);
                if ($data !== null) {
                    $summary = $data['summary'] ?? [];
                    $successRate = $summary['success_rate'] ?? null;
                    
                    if ($successRate !== null) {
                        return abs($successRate - $expectedRate) <= $tolerance;
                    }
                }
                
                return false;
            }
            
            if (is_array($this->value)) {
                $summary = $this->value['summary'] ?? [];
                $successRate = $summary['success_rate'] ?? null;
                
                if ($successRate !== null) {
                    return abs($successRate - $expectedRate) <= $tolerance;
                }
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeExportable')) {
    /**
     * Assert that report can be exported to a file
     */
    function toBeExportable(): Closure
    {
        return function () {
            if (is_string($this->value)) {
                // Check if content is not empty and has reasonable length
                return !empty(trim($this->value)) && strlen($this->value) > 10;
            }
            
            if (is_array($this->value)) {
                return !empty($this->value);
            }
            
            return false;
        };
    }
}