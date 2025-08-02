<?php

declare(strict_types=1);

namespace App\Services\ValueObjects;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use RuntimeException;
use Stringable;

final readonly class ValidationResult implements Stringable
{
    /**
     * @var array<array{url: string, file: string, reason: string}>
     */
    public array $broken;

    /**
     * @var array<string>
     */
    public array $files;

    /**
     * @var array<array{url: string, status: string, scope: string, is_valid: bool, response_time: float}>
     */
    public array $links;

    public float $duration;

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        private string $url,
        private LinkStatus $status,
        private ValidationScope $scope,
        private ?string $error = null,
        private ?int $httpStatusCode = null,
        private ?string $redirectUrl = null,
        private float $responseTime = 0.0,
        private array $metadata = [],
        private ?string $filePath = null
    ) {
        // Initialize public properties for formatter compatibility
        $this->broken = $this->initializeBrokenLinksArray();
        $this->files = $this->initializeFilesArray();
        $this->links = $this->initializeLinksArray();
        $this->duration = $this->initializeDuration();
    }

    /**
     * Magic getter for backward compatibility with formatter properties.
     */
    public function __get(string $name): mixed
    {
        return match ($name) {
            'broken' => $this->getBrokenLinksArray(),
            'files' => $this->getFilesArray(),
            'links' => $this->getLinksArray(),
            'duration' => $this->getDuration(),
            default => null,
        };
    }

    /**
     * Get string representation of the validation result.
     */
    public function __toString(): string
    {
        $status = $this->status->value;
        $scope = $this->scope->value;

        return "ValidationResult[{$this->url}] - Status: {$status}, Scope: {$scope}";
    }

    /**
     * Create a successful validation result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function success(
        string $url,
        ValidationScope $scope,
        ?int $httpStatusCode = null,
        float $responseTime = 0.0,
        array $metadata = [],
        ?string $filePath = null
    ): self {
        return new self(
            url: $url,
            status: LinkStatus::VALID,
            scope: $scope,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime,
            metadata: $metadata,
            filePath: $filePath
        );
    }

    /**
     * Create a failed validation result.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function failure(
        string $url,
        ValidationScope $scope,
        LinkStatus $status,
        ?string $error = null,
        ?int $httpStatusCode = null,
        ?string $redirectUrl = null,
        float $responseTime = 0.0,
        array $metadata = [],
        ?string $filePath = null
    ): self {
        return new self(
            url: $url,
            status: $status,
            scope: $scope,
            error: $error,
            httpStatusCode: $httpStatusCode,
            redirectUrl: $redirectUrl,
            responseTime: $responseTime,
            metadata: $metadata,
            filePath: $filePath
        );
    }

    /**
     * Create an aggregate result from multiple validation results.
     *
     * @param  ValidationResult[]  $results
     * @param  array<string, mixed>  $summary
     */
    public static function aggregate(array $results, array $summary = []): self
    {
        $totalResults = count($results);
        $brokenCount = 0;
        $allValid = true;

        foreach ($results as $result) {
            if (! $result->isValid()) {
                $brokenCount++;
                $allValid = false;
            }
        }

        // Create an aggregate result representing the overall validation
        return new self(
            url: "aggregate-{$totalResults}-results",
            status: $allValid ? LinkStatus::VALID : LinkStatus::BROKEN,
            scope: ValidationScope::ALL,
            metadata: [
                'total_results' => $totalResults,
                'broken_count' => $brokenCount,
                'summary' => $summary,
                'results' => $results,
            ]
        );
    }

    /**
     * Create a validation result with basic parameters.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function create(
        string $url,
        LinkStatus $status,
        string $message = '',
        ValidationScope $scope = ValidationScope::ALL,
        ?int $httpStatusCode = null,
        float $responseTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            url: $url,
            status: $status,
            scope: $scope,
            error: $message ?: null,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime,
            metadata: $metadata
        );
    }

    /**
     * Create a validation result from array data.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $url = $data['url'] ?? '';
        $metadata = $data['metadata'] ?? [];
        /** @var array<string, mixed> $metadata */

        return new self(
            url: is_string($url) ? $url : '',
            status: isset($data['status']) ? LinkStatus::from(is_string($data['status']) || is_int($data['status']) ? $data['status'] : '') : LinkStatus::BROKEN,
            scope: isset($data['scope']) ? ValidationScope::from(is_string($data['scope']) || is_int($data['scope']) ? $data['scope'] : '') : ValidationScope::ALL,
            error: isset($data['error']) && (is_string($data['error']) || is_null($data['error'])) ? $data['error'] : null,
            httpStatusCode: isset($data['http_status_code']) && (is_int($data['http_status_code']) || is_null($data['http_status_code'])) ? $data['http_status_code'] : null,
            redirectUrl: isset($data['redirect_url']) && (is_string($data['redirect_url']) || is_null($data['redirect_url'])) ? $data['redirect_url'] : null,
            responseTime: isset($data['response_time']) && (is_float($data['response_time']) || is_int($data['response_time'])) ? (float) $data['response_time'] : 0.0,
            metadata: is_array($metadata) ? $metadata : []
        );
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getStatus(): LinkStatus
    {
        return $this->status;
    }

    public function getScope(): ValidationScope
    {
        return $this->scope;
    }

    public function isValid(): bool
    {
        return $this->status === LinkStatus::VALID;
    }

    public function isBroken(): bool
    {
        return $this->status->isBroken();
    }

    public function isTemporary(): bool
    {
        return $this->status->isTemporary();
    }

    public function isSecurityIssue(): bool
    {
        return $this->status->isSecurityIssue();
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    public function getResponseTime(): float
    {
        return $this->responseTime;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    /**
     * Get severity level from status.
     */
    public function getSeverity(): int
    {
        return $this->status->getSeverity();
    }

    /**
     * Get recommended action from status.
     */
    public function getRecommendedAction(): string
    {
        return $this->status->getRecommendedAction();
    }

    /**
     * Get console color for display.
     */
    public function getConsoleColor(): string
    {
        return $this->status->getConsoleColor();
    }

    /**
     * Get formatted display text.
     */
    public function getFormattedDisplay(): string
    {
        return $this->status->getFormattedDisplay();
    }

    /**
     * Check if this result should be retried.
     */
    public function shouldRetry(): bool
    {
        return $this->status->shouldRetry();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'status' => $this->status->value,
            'scope' => $this->scope->value,
            'is_valid' => $this->isValid(),
            'is_broken' => $this->isBroken(),
            'error' => $this->error,
            'http_status_code' => $this->httpStatusCode,
            'redirect_url' => $this->redirectUrl,
            'response_time' => $this->responseTime,
            'severity' => $this->getSeverity(),
            'recommended_action' => $this->getRecommendedAction(),
            'metadata' => $this->metadata,
            'file_path' => $this->filePath,
        ];
    }

    /**
     * Convert to JSON representation.
     */
    public function toJson(): string
    {
        $json = json_encode($this->toArray(), JSON_PRETTY_PRINT);
        if ($json === false) {
            throw new RuntimeException('Failed to encode validation result to JSON');
        }

        return $json;
    }

    /**
     * Check if this is a successful validation (for aggregate results).
     */
    public function isSuccessful(): bool
    {
        return $this->isValid();
    }

    /**
     * Get summary data (for aggregate results).
     *
     * @return array<string, mixed>
     */
    public function getSummary(): array
    {
        $summary = $this->metadata['summary'] ?? [];
        if (! is_array($summary)) {
            return [];
        }

        /** @var array<string, mixed> $summary */
        return $summary;
    }

    /**
     * Get statistics summary (for aggregate results).
     *
     * @return array<string, mixed>
     */
    public function getStatisticsSummary(): array
    {
        return $this->getSummary();
    }

    /**
     * Check if there are broken links (for aggregate results).
     */
    public function hasBrokenLinks(): bool
    {
        return ($this->metadata['broken_count'] ?? 0) > 0;
    }

    /**
     * Get count of broken links (for aggregate results).
     */
    public function getBrokenCount(): int
    {
        $count = $this->metadata['broken_count'] ?? 0;

        return is_int($count) ? $count : 0;
    }

    /**
     * Get broken links by type (for aggregate results).
     *
     * @return array<string, array<array{url: string, file: string, reason: string}>>
     */
    public function getBrokenLinksByType(): array
    {
        $results = $this->metadata['results'] ?? [];
        $brokenByType = [];

        if (! is_array($results)) {
            return $brokenByType;
        }

        foreach ($results as $result) {
            if ($result instanceof self && ! $result->isValid()) {
                $type = $result->getScope()->value;
                if (! isset($brokenByType[$type])) {
                    $brokenByType[$type] = [];
                }
                $brokenByType[$type][] = [
                    'url' => $result->getUrl(),
                    'file' => 'unknown', // Would need file context
                    'reason' => $result->getError() ?? 'Validation failed',
                ];
            }
        }

        return $brokenByType;
    }

    /**
     * Get total links count (for aggregate results).
     */
    public function getTotalLinks(): int
    {
        $total = $this->metadata['total_results'] ?? 0;

        return is_int($total) ? $total : 0;
    }

    /**
     * Get success rate (for aggregate results).
     */
    public function getSuccessRate(): float
    {
        $total = $this->getTotalLinks();
        $broken = $this->getBrokenCount();

        return $total > 0 ? round((($total - $broken) / $total) * 100, 2) : 0.0;
    }

    /**
     * Initialize broken links array for formatters.
     *
     * @return array<array{url: string, file: string, reason: string}>
     */
    private function initializeBrokenLinksArray(): array
    {
        $results = $this->metadata['results'] ?? [];
        $broken = [];

        if (is_array($results)) {
            foreach ($results as $result) {
                if ($result instanceof self && ! $result->isValid()) {
                    $broken[] = [
                        'url' => $result->getUrl(),
                        'file' => 'unknown',
                        'reason' => $result->getError() ?? 'Validation failed',
                    ];
                }
            }
        }

        return $broken;
    }

    /**
     * Initialize files array for formatters.
     *
     * @return array<string>
     */
    private function initializeFilesArray(): array
    {
        $files = $this->metadata['files'] ?? [];

        if (! is_array($files)) {
            return [];
        }

        // Ensure all elements are strings
        return array_filter($files, 'is_string');
    }

    /**
     * Initialize links array for formatters.
     *
     * @return array<array{url: string, status: string, scope: string, is_valid: bool, response_time: float}>
     */
    private function initializeLinksArray(): array
    {
        $results = $this->metadata['results'] ?? [];
        $links = [];

        if (is_array($results)) {
            foreach ($results as $result) {
                if ($result instanceof self) {
                    $links[] = [
                        'url' => $result->getUrl(),
                        'status' => $result->getStatus()->value,
                        'scope' => $result->getScope()->value,
                        'is_valid' => $result->isValid(),
                        'response_time' => $result->getResponseTime(),
                    ];
                }
            }
        }

        return $links;
    }

    /**
     * Initialize duration for formatters.
     */
    private function initializeDuration(): float
    {
        $duration = $this->metadata['duration'] ?? 0.0;

        return is_float($duration) || is_int($duration) ? (float) $duration : 0.0;
    }

    /**
     * Get broken links as array (for formatters).
     *
     * @return array<array{url: string, file: string, reason: string}>
     */
    private function getBrokenLinksArray(): array
    {
        $results = $this->metadata['results'] ?? [];
        $broken = [];

        if (! is_array($results)) {
            return $broken;
        }

        foreach ($results as $result) {
            if ($result instanceof self && ! $result->isValid()) {
                $broken[] = [
                    'url' => $result->getUrl(),
                    'file' => 'unknown', // Would need file context
                    'reason' => $result->getError() ?? 'Validation failed',
                ];
            }
        }

        return $broken;
    }

    /**
     * Get files array (for formatters).
     *
     * @return array<string>
     */
    private function getFilesArray(): array
    {
        $files = $this->metadata['files'] ?? [];

        if (! is_array($files)) {
            return [];
        }

        // Ensure all elements are strings
        return array_filter($files, 'is_string');
    }

    /**
     * Get links array (for formatters).
     *
     * @return array<array{url: string, status: string, scope: string, is_valid: bool, response_time: float}>
     */
    private function getLinksArray(): array
    {
        $results = $this->metadata['results'] ?? [];
        $links = [];

        if (! is_array($results)) {
            return $links;
        }

        foreach ($results as $result) {
            if ($result instanceof self) {
                $links[] = [
                    'url' => $result->getUrl(),
                    'status' => $result->getStatus()->value,
                    'scope' => $result->getScope()->value,
                    'is_valid' => $result->isValid(),
                    'response_time' => $result->getResponseTime(),
                ];
            }
        }

        return $links;
    }

    /**
     * Get duration (for formatters).
     */
    private function getDuration(): float
    {
        $duration = $this->metadata['duration'] ?? 0.0;

        return is_numeric($duration) ? (float) $duration : 0.0;
    }
}
