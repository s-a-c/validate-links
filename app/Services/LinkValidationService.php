<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\LinkStatus;
use App\Enums\LinkType;
use App\Enums\ValidationScope;
use App\Exceptions\SecurityException;
use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\SecurityValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;
use App\Services\ValueObjects\ValidationSummary;
use Exception;
use RuntimeException;

final class LinkValidationService implements LinkValidationInterface
{
    public ?GitHubAnchorInterface $gitHubAnchorService = null;

    public function __construct(
        private readonly SecurityValidationInterface $security
    ) {}

    public function validateFile(string $filePath, ValidationConfig $config): ValidationResultCollection
    {
        // Remove union type handling
        $results = [];

        if (! file_exists($filePath)) {
            return ValidationResultCollection::fromArray([
                ValidationResult::create(
                    url: $filePath,
                    status: LinkStatus::BROKEN,
                    message: 'File not found'
                ),
            ]);
        }

        // Validate file path security
        if (! $this->security->validatePath($filePath)) {
            throw new SecurityException("Invalid file path: {$filePath}");
        }

        if (! is_readable($filePath)) {
            throw new RuntimeException("Unable to read file: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            throw new RuntimeException("Unable to read file: {$filePath}");
        }
        $links = $this->extractLinks($content);
        $categorized = $this->categorizeLinks($links);

        if ($config->shouldValidateInternal()) {
            $internalResults = $this->validateInternalLinks(
                $categorized['internal'],
                dirname($filePath)
            );
            $results = array_merge($results, $internalResults);
        }

        if ($config->shouldValidateExternal()) {
            $externalResults = $this->validateExternalLinksInternal($categorized['external']);
            $results = array_merge($results, $externalResults);
        }

        if ($config->shouldValidateAnchors()) {
            $anchorResults = $this->validateAnchorLinks($categorized['anchor'], $content);
            $results = array_merge($results, $anchorResults);
        }

        return ValidationResultCollection::fromArray($results);
    }

    /**
     * Validate file and return summary format.
     */
    public function validateFileSummary(string $filePath, ValidationConfig $config): ValidationSummary
    {
        $results = $this->validateFile($filePath, $config);

        // Calculate statistics from results
        $total = $results->count();
        $valid = $results->getSuccessful()->count();
        $broken = $results->getFailed()->count();

        $statistics = ValidationStatistics::fromCounts(
            total: $total,
            valid: $valid,
            broken: $broken,
            timeouts: 0,
            securityRisks: 0,
            redirects: 0
        );

        return ValidationSummary::create(
            filePath: $filePath,
            results: $results,
            statistics: $statistics
        );
    }

    public function validateLink(string $url, ValidationConfig $config): ValidationResult
    {
        $linkType = $this->classifyLink($url);

        return match ($linkType) {
            LinkType::ANCHOR => $this->validateAnchorLink($url, $config),
            LinkType::EXTERNAL => $this->validateExternalLink($url, $config),
            LinkType::INTERNAL, LinkType::CROSS_REFERENCE => $this->validateInternalLink($url, $config),
        };
    }

    /**
     * Validate an anchor link.
     */
    private function validateAnchorLink(string $url, ValidationConfig $config): ValidationResult
    {
        if ($config->shouldValidateAnchors()) {
            return new ValidationResult(
                url: $url,
                status: LinkStatus::VALID,
                scope: ValidationScope::ANCHOR,
                responseTime: 0.0
            );
        }

        // If validation is not enabled for anchors, mark as valid (skipped)
        return new ValidationResult(
            url: $url,
            status: LinkStatus::VALID,
            scope: ValidationScope::ANCHOR,
            responseTime: 0.0
        );
    }

    /**
     * Validate an external link.
     */
    private function validateExternalLink(string $url, ValidationConfig $config): ValidationResult
    {
        // Check for non-HTTP protocols first
        if (!str_starts_with($url, 'http://') && !str_starts_with($url, 'https://')) {
            return new ValidationResult(
                url: $url,
                status: LinkStatus::INVALID_FORMAT,
                scope: ValidationScope::EXTERNAL,
                error: 'Invalid URL format',
                responseTime: 0.0
            );
        }

        // Validate URL format for HTTP/HTTPS links
        if (!$this->isValidUrl($url)) {
            return new ValidationResult(
                url: $url,
                status: LinkStatus::INVALID_FORMAT,
                scope: ValidationScope::EXTERNAL,
                error: 'Invalid URL format',
                responseTime: 0.0
            );
        }

        if ($config->shouldValidateExternal()) {
            return $this->validateSingleExternalLink($url);
        }

        // If validation is not enabled, mark as valid (skipped)
        return new ValidationResult(
            url: $url,
            status: LinkStatus::VALID,
            scope: ValidationScope::EXTERNAL,
            responseTime: 0.0
        );
    }

    /**
     * Validate an internal link.
     */
    private function validateInternalLink(string $url, ValidationConfig $config): ValidationResult
    {
        // Check if it looks like a malformed URL (contains spaces, invalid characters for file paths)
        if (preg_match('/\s/', $url) || preg_match('/[<>"|?*]/', $url) || (!str_contains($url, '/') && !str_contains($url, '.') && mb_strlen($url) > 0)) {
            return new ValidationResult(
                url: $url,
                status: LinkStatus::INVALID_FORMAT,
                scope: ValidationScope::INTERNAL,
                error: 'Invalid URL format',
                responseTime: 0.0
            );
        }

        if ($config->shouldValidateInternal()) {
            return $this->validateSingleInternalLink($url);
        }

        // If validation is not enabled, mark as valid (skipped)
        return new ValidationResult(
            url: $url,
            status: LinkStatus::VALID,
            scope: ValidationScope::INTERNAL,
            responseTime: 0.0
        );
    }

    public function validateLinks(array $urls, ValidationConfig $config): ValidationResultCollection
    {
        $results = [];

        foreach ($urls as $url) {
            $results[] = $this->validateLink($url, $config);
        }

        return ValidationResultCollection::fromArray($results);
    }

    /**
     * @return array<array{url: string, text: string, line: int}>
     */
    public function extractLinks(string $content): array
    {
        $links = [];
        $lines = explode("\n", $content);

        // Extract markdown links [text](url)
        foreach ($lines as $lineNumber => $line) {
            preg_match_all('/\[([^\]]*)\]\(([^)]+)\)/', $line, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $links[] = [
                    'url' => $match[2],
                    'text' => $match[1],
                    'line' => $lineNumber + 1,
                ];
            }

            // Extract HTML links <a href="url">text</a>
            preg_match_all('/<a\s+href=["\']([^"\']+)["\'][^>]*>([^<]*)<\/a>/i', $line, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $links[] = [
                    'url' => $match[1],
                    'text' => $match[2],
                    'line' => $lineNumber + 1,
                ];
            }
        }

        return $links;
    }

    /**
     * Classify a URL into its LinkType.
     */
    public function classifyLink(string $url): LinkType
    {
        if (str_starts_with($url, '#')) {
            return LinkType::ANCHOR;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return LinkType::EXTERNAL;
        }

        // URLs with protocols other than http/https should be treated as external
        // (they will be marked as invalid format during validation)
        if (str_contains($url, '://') || preg_match('/^[a-z]+:/i', $url)) {
            return LinkType::EXTERNAL;
        }

        // Check if it's a cross-reference (internal link with anchor)
        if (str_contains($url, '#') && !str_starts_with($url, '#')) {
            return LinkType::CROSS_REFERENCE;
        }

        return LinkType::INTERNAL;
    }

    /**
     * @param  array<array{url: string, text: string, line: int}>  $links
     * @return array<string, array<array{url: string, text: string, line: int}>>
     */
    public function categorizeLinks(array $links): array
    {
        $categorized = [
            'internal' => [],
            'external' => [],
            'anchor' => [],
        ];

        foreach ($links as $link) {
            $url = $link['url'];
            $linkType = $this->classifyLink($url);

            match ($linkType) {
                LinkType::ANCHOR => $categorized['anchor'][] = $link,
                LinkType::EXTERNAL => $categorized['external'][] = $link,
                LinkType::INTERNAL, LinkType::CROSS_REFERENCE => $categorized['internal'][] = $link,
            };
        }

        return $categorized;
    }

    /**
     * @param  string[]  $files
     * @return list<array<string, mixed>>
     */
    public function validateCrossReferences(array $files): array
    {
        $allAnchors = $this->extractAllAnchors($files);
        $crossReferences = [];

        foreach ($files as $file) {
            $links = $this->extractLinks($file);

            foreach ($links as $link) {
                if ($this->isCrossReference($link)) {
                    $crossReferences[] = $this->validateCrossReference($link, $allAnchors);
                }
            }
        }

        return $crossReferences;
    }

    public function isValidUrl(string $url): bool
    {
        // First try direct validation for ASCII URLs
        if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
            return true;
        }

        // For URLs that might contain unicode characters, try encoding them
        $encodedUrl = $this->encodeUnicodeUrl($url);

        return filter_var($encodedUrl, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Encode unicode characters in URL path and query components.
     */
    private function encodeUnicodeUrl(string $url): string
    {
        // Parse the URL to handle each component separately
        $parsed = parse_url($url);

        if ($parsed === false) {
            return $url; // Return original if parsing fails
        }

        // Reconstruct URL with encoded components
        $result = '';

        // Scheme (http, https, etc.)
        if (isset($parsed['scheme'])) {
            $result .= $parsed['scheme'] . '://';
        }

        // Host (should remain as-is for IDN domains)
        if (isset($parsed['host'])) {
            $result .= $parsed['host'];
        }

        // Port
        if (isset($parsed['port'])) {
            $result .= ':' . $parsed['port'];
        }

        // Path (encode unicode characters)
        if (isset($parsed['path'])) {
            // Split path into segments and encode each segment
            $pathSegments = explode('/', $parsed['path']);
            $encodedSegments = array_map(fn($segment) => rawurlencode($segment), $pathSegments);
            $result .= implode('/', $encodedSegments);
        }

        // Query (encode unicode characters)
        if (isset($parsed['query'])) {
            // Parse query parameters and encode values
            parse_str($parsed['query'], $queryParams);
            $encodedParams = [];
            foreach ($queryParams as $key => $value) {
                $encodedParams[rawurlencode($key)] = rawurlencode($value);
            }
            $result .= '?' . http_build_query($encodedParams, '', '&', PHP_QUERY_RFC3986);
        }

        // Fragment (encode unicode characters)
        if (isset($parsed['fragment'])) {
            $result .= '#' . rawurlencode($parsed['fragment']);
        }

        return $result;
    }

    public function normalizeUrl(string $url): string
    {
        return mb_trim($url);
    }

    public function getSupportedScopes(): array
    {
        return [
            ValidationScope::INTERNAL,
            ValidationScope::EXTERNAL,
            ValidationScope::ANCHOR,
            ValidationScope::IMAGE,
            ValidationScope::ALL,
        ];
    }

    public function supportsScope(ValidationScope $scope): bool
    {
        return in_array($scope, $this->getSupportedScopes(), true);
    }

    public function validateByScopes(array $scopes, array $targets, ValidationConfig $config): ValidationResult
    {
        // For now, just validate the first target with the first scope
        if ($targets === []) {
            return new ValidationResult(
                url: '',
                status: LinkStatus::INVALID_FORMAT,
                scope: ValidationScope::ALL,
                error: 'No targets provided',
                responseTime: 0.0
            );
        }

        $firstTarget = $targets[0];

        return $this->validateLink($firstTarget, $config);
    }

    public function getStatusStatistics(): ValidationStatistics
    {
        // TODO: Implement proper statistics collection
        return ValidationStatistics::fromCounts(
            total: 0,
            valid: 0,
            broken: 0,
            timeouts: 0,
            securityRisks: 0
        );
    }

    public function setTimeout(int $seconds): void
    {
        // Store timeout setting for future use
        // For now, this is a no-op as we use simple validation
    }

    public function validateFiles(array $files, ValidationConfig $config): ValidationResultCollection
    {
        $allResults = [];

        foreach ($files as $file) {
            $fileResults = $this->validateFile($file, $config);
            foreach ($fileResults as $result) {
                $allResults[] = $result;
            }
        }

        return ValidationResultCollection::fromArray($allResults);
    }

    /**
     * Validate external links.
     *
     * @param  array<string>  $urls
     */
    public function validateExternalLinks(array $urls, ValidationConfig $config): ValidationResultCollection
    {
        $results = [];

        foreach ($urls as $url) {
            if ($this->isValidUrl($url)) {
                $results[] = $this->validateSingleExternalLink($url);
            }
        }

        return ValidationResultCollection::fromArray($results);
    }

    /**
     * Validate internal links.
     *
     * @param  array<array{url: string, text: string, line: int}>  $links
     * @return ValidationResult[]
     */
    private function validateInternalLinks(array $links, string $basePath): array
    {
        $results = [];

        foreach ($links as $link) {
            $url = $link['url'];
            $filePath = $basePath.'/'.mb_ltrim($url, './');

            if (file_exists($filePath)) {
                $results[] = ValidationResult::success($url, ValidationScope::INTERNAL);
            } else {
                $results[] = ValidationResult::failure(
                    $url,
                    ValidationScope::INTERNAL,
                    LinkStatus::NOT_FOUND,
                    'File not found'
                );
            }
        }

        return $results;
    }

    /**
     * Validate external links (internal method).
     *
     * @param  array<array{url: string, text: string, line: int}>  $links
     * @return ValidationResult[]
     */
    private function validateExternalLinksInternal(array $links): array
    {
        $results = [];

        foreach ($links as $link) {
            $results[] = $this->validateSingleExternalLink($link['url']);
        }

        return $results;
    }

    /**
     * Validate anchor links.
     *
     * @param  array<array{url: string, text: string, line: int}>  $links
     * @return ValidationResult[]
     */
    private function validateAnchorLinks(array $links, string $content): array
    {
        $results = [];

        foreach ($links as $link) {
            $anchor = mb_ltrim($link['url'], '#');

            // Simple check if anchor exists in content
            if (str_contains($content, "id=\"{$anchor}\"") || str_contains($content, "#{$anchor}")) {
                $results[] = ValidationResult::success($link['url'], ValidationScope::ANCHOR);
            } else {
                $results[] = ValidationResult::failure(
                    $link['url'],
                    ValidationScope::ANCHOR,
                    LinkStatus::NOT_FOUND,
                    'Anchor not found'
                );
            }
        }

        return $results;
    }

    /**
     * Validate a single external link.
     */
    private function validateSingleExternalLink(string $url): ValidationResult
    {
        $startTime = microtime(true);

        try {
            // Check security validation first
            if (! $this->security->validateUrl($url)) {
                return new ValidationResult(
                    url: $url,
                    status: LinkStatus::SECURITY_VIOLATION,
                    scope: ValidationScope::EXTERNAL,
                    error: 'URL failed security validation',
                    responseTime: microtime(true) - $startTime
                );
            }

            // Simple HTTP request to validate external link
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 10,
                    'user_agent' => 'Link Validator/1.0',
                    'follow_location' => true,
                    'max_redirects' => 5,
                ],
            ]);

            $headers = @get_headers($url, true, $context);
            $responseTime = microtime(true) - $startTime;

            if ($headers === false) {
                return new ValidationResult(
                    url: $url,
                    status: LinkStatus::BROKEN,
                    scope: ValidationScope::EXTERNAL,
                    error: 'Failed to connect to URL',
                    responseTime: $responseTime
                );
            }

            // Parse HTTP status code
            $statusLine = \is_string($headers[0]) ? $headers[0] : '';
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
            $httpCode = isset($matches[1]) ? (int) $matches[1] : 0;
            if ($httpCode >= 200 && $httpCode < 400) {
                return new ValidationResult(
                    url: $url,
                    status: LinkStatus::VALID,
                    scope: ValidationScope::EXTERNAL,
                    httpStatusCode: $httpCode,
                    responseTime: $responseTime
                );
            }
            $status = match (true) {
                $httpCode === 404 => LinkStatus::NOT_FOUND,
                $httpCode === 403 => LinkStatus::FORBIDDEN,
                $httpCode >= 500 => LinkStatus::BROKEN,
                default => LinkStatus::BROKEN
            };

            return new ValidationResult(
                url: $url,
                status: $status,
                scope: ValidationScope::EXTERNAL,
                error: "HTTP {$httpCode}",
                httpStatusCode: $httpCode,
                responseTime: $responseTime
            );

        } catch (Exception $e) {
            return new ValidationResult(
                url: $url,
                status: LinkStatus::BROKEN,
                scope: ValidationScope::EXTERNAL,
                error: $e->getMessage(),
                responseTime: microtime(true) - $startTime
            );
        }
    }

    /**
     * Validate a single internal link.
     */
    private function validateSingleInternalLink(string $url): ValidationResult
    {
        $startTime = microtime(true);

        try {
            // Normalize the path
            $filePath = mb_ltrim($url, './');

            // Check security validation first
            if (! $this->security->validatePath($filePath)) {
                return new ValidationResult(
                    url: $url,
                    status: LinkStatus::SECURITY_VIOLATION,
                    scope: ValidationScope::INTERNAL,
                    error: 'Path failed security validation',
                    responseTime: microtime(true) - $startTime
                );
            }

            // Simple file existence check for internal links
            $responseTime = microtime(true) - $startTime;

            // Check if file exists
            if (file_exists($filePath)) {
                return new ValidationResult(
                    url: $url,
                    status: LinkStatus::VALID,
                    scope: ValidationScope::INTERNAL,
                    responseTime: $responseTime
                );
            }

            return new ValidationResult(
                url: $url,
                status: LinkStatus::NOT_FOUND,
                scope: ValidationScope::INTERNAL,
                error: 'File not found',
                responseTime: $responseTime
            );

        } catch (Exception $e) {
            return new ValidationResult(
                url: $url,
                status: LinkStatus::BROKEN,
                scope: ValidationScope::INTERNAL,
                error: $e->getMessage(),
                responseTime: microtime(true) - $startTime
            );
        }
    }

    /**
     * @param  string[]  $files
     * @return array<string, string[]>
     */
    private function extractAllAnchors(array $files): array
    {
        $anchors = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                // Skip files that can't be read
                continue;
            }
            $fileAnchors = $this->gitHubAnchorService?->extractAnchors($content) ?? [];
            $anchors[$file] = $fileAnchors;
        }

        return $anchors;
    }

    /**
     * @param  array<string, mixed>  $link
     * @param  array<string, string[]>  $allAnchors
     * @return array<string, mixed>
     */
    private function validateCrossReference(array $link, array $allAnchors): array
    {
        // Implementation for cross-reference validation
        $url = is_string($link['url'] ?? '') ? $link['url'] : '';
        assert(is_string($url)); // PHPStan assertion for type narrowing

        $targetFile = $this->resolveTargetFile($url);
        $targetAnchor = $this->extractAnchorFromUrl($url);

        if (! isset($allAnchors[$targetFile])) {
            return [
                'url' => $link['url'],
                'status' => 'broken',
                'reason' => 'Target file not found',
            ];
        }

        if ($targetAnchor && ! in_array($targetAnchor, $allAnchors[$targetFile])) {
            return [
                'url' => $link['url'],
                'status' => 'broken',
                'reason' => 'Target anchor not found',
            ];
        }

        return [
            'url' => $link['url'],
            'status' => 'valid',
            'reason' => null,
        ];
    }

    /**
     * Check if a link is a cross-reference.
     *
     * @param  array<string, mixed>  $link
     */
    private function isCrossReference(array $link): bool
    {
        $url = is_string($link['url'] ?? '') ? $link['url'] : '';
        assert(is_string($url)); // PHPStan type assertion

        // Cross-references are internal links that contain anchors
        return ! str_starts_with($url, 'http') && str_contains($url, '#');
    }

    /**
     * Resolve target file from URL.
     */
    private function resolveTargetFile(string $url): string
    {
        // Extract file path before anchor
        $parts = explode('#', $url);

        return $parts[0];
    }

    /**
     * Extract anchor from URL.
     */
    private function extractAnchorFromUrl(string $url): ?string
    {
        $parts = explode('#', $url);

        return $parts[1] ?? null;
    }
}
