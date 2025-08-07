<?php

declare(strict_types=1);

namespace App\Services\Formatters;

use App\Services\ValueObjects\ValidationResult;
use Carbon\Carbon;

/**
 * HTML formatter for validation results with styled web output.
 *
 * This formatter generates complete HTML documents with embedded CSS
 * for web-based viewing and sharing of validation reports.
 */
final class HtmlFormatter
{
    /**
     * Format validation result as a complete HTML document.
     *
     * @param ValidationResult $validationResult The validation result to format
     *
     * @return string Complete HTML document with embedded styles
     */
    public function format(ValidationResult $validationResult): string
    {
        $html = $this->getHtmlTemplate();
        $content = $this->generateContent($validationResult);

        return str_replace('{{CONTENT}}', $content, $html);
    }

    /**
     * Generate the main content section of the HTML report.
     *
     * @param ValidationResult $validationResult The validation result to format
     *
     * @return string HTML content with validation details
     */
    private function generateContent(ValidationResult $validationResult): string
    {
        $content = [];

        // Header
        $content[] = '<div class="header">';
        $content[] = '<h1>🔗 Link Validation Report</h1>';
        $content[] = '<p class="timestamp">Generated: ' . Carbon::now()->format('Y-m-d H:i:s') . '</p>';
        $content[] = '</div>';

        // Summary
        $content[] = '<div class="summary">';
        $content[] = '<h2>📊 Summary</h2>';
        $content[] = '<div class="stats-grid">';

        $stats = $validationResult->getStatisticsSummary();
        foreach ($stats as $label => $value) {
            $labelStr = (string) $label;
            $valueStr = is_scalar($value) ? (string) $value : 'unknown';
            $content[] = '<div class="stat-item">';
            $content[] = "<span class=\"stat-label\">{$labelStr}:</span>";
            $content[] = "<span class=\"stat-value\">{$valueStr}</span>";
            $content[] = '</div>';
        }

        $content[] = '</div>';
        $content[] = '</div>';

        // Status
        $statusClass = $validationResult->isSuccessful() ? 'success' : 'error';
        $statusIcon = $validationResult->isSuccessful() ? '✅' : '❌';
        $statusText = $validationResult->isSuccessful() ? 'Success' : 'Failed';

        $content[] = "<div class=\"status {$statusClass}\">";
        $content[] = "<h2>{$statusIcon} Status: {$statusText}</h2>";

        if ($validationResult->isSuccessful()) {
            $content[] = '<p>All links validated successfully!</p>';
        } else {
            $brokenCount = $validationResult->getBrokenCount();
            $content[] = "<p>Found {$brokenCount} broken link(s).</p>";
        }

        $content[] = '</div>';

        // Broken links
        if ($validationResult->hasBrokenLinks()) {
            $content[] = '<div class="broken-links">';
            $content[] = '<h2>❌ Broken Links</h2>';

            $brokenByType = $validationResult->getBrokenLinksByType();

            foreach ($brokenByType as $type => $links) {
                $linkCount = count($links);
                $content[] = "<h3>{$type} Links ({$linkCount})</h3>";
                $content[] = '<table class="links-table">';
                $content[] = '<thead>';
                $content[] = '<tr><th>File</th><th>Link</th><th>Reason</th></tr>';
                $content[] = '</thead>';
                $content[] = '<tbody>';

                foreach ($links as $link) {
                    $file = htmlspecialchars(basename($link['file']));
                    $url = htmlspecialchars($link['url']);
                    $reason = htmlspecialchars($link['reason']);

                    $content[] = '<tr>';
                    $content[] = "<td>{$file}</td>";
                    $content[] = "<td><code>{$url}</code></td>";
                    $content[] = "<td>{$reason}</td>";
                    $content[] = '</tr>';
                }

                $content[] = '</tbody>';
                $content[] = '</table>';
            }

            $content[] = '</div>';
        }

        return implode("\n", $content);
    }

    /**
     * Get the complete HTML template with embedded CSS.
     *
     * @return string Complete HTML template with placeholder for content
     */
    private function getHtmlTemplate(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Validation Report</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header h1 {
            color: #333;
            margin-bottom: 5px;
        }
        .timestamp {
            color: #666;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .stat-label {
            display: block;
            font-weight: 600;
            color: #495057;
        }
        .stat-value {
            display: block;
            font-size: 1.2em;
            color: #007bff;
            margin-top: 5px;
        }
        .status {
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .links-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .links-table th,
        .links-table td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #dee2e6;
        }
        .links-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .links-table code {
            background: #f1f3f4;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 0.9em;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        h3 {
            color: #495057;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        {{CONTENT}}
    </div>
</body>
</html>
HTML;
    }
}
