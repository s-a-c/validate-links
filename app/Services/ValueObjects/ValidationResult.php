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

    public function __construct(
        private string $url,
        private LinkStatus $status,
        private ValidationScope $scope,
        private ?string $error = null,
        private ?int $httpStatusCode = null,
        private ?string $redirectUrl = null,
        private float $responseTime = 0.0,
        private ValidationMetadata $metadata = new ValidationMetadata(),
        private ?string $filePath = null,
    ) {
        // Initialize public properties for formatter compatibility
        $this->broken = $this->initializeBrokenLinksArray();
        $this->files = $this->initializeFilesArray();
        $this->links = $this->initializeLinksArray();
        $this->duration = $this->initializeDuration();
    }

    /**
     * Magic getter for backward compatibility with formatter properties.
     *
     * @return array<mixed>|float|null
     */
    public function __get(string $name): array|float|null
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
     */
    public static function success(
        string $url,
        ValidationScope $scope,
        ?int $httpStatusCode = null,
        float $responseTime = 0.0,
        ?ValidationMetadata $metadata = null,
        ?string $filePath = null,
    ): self {
        return new self(
            url: $url,
            status: LinkStatus::VALID,
            scope: $scope,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime,
            metadata: $metadata ?? new ValidationMetadata(),
            filePath: $filePath,
        );
    }

    /**
     * Create a failed validation result.
     */
    public static function failure(
        string $url,
        ValidationScope $scope,
        LinkStatus $status,
        ?string $error = null,
        ?int $httpStatusCode = null,
        ?string $redirectUrl = null,
        float $responseTime = 0.0,
        ?ValidationMetadata $metadata = null,
        ?string $filePath = null,
    ): self {
        return new self(
            url: $url,
            status: $status,
            scope: $scope,
            error: $error,
            httpStatusCode: $httpStatusCode,
            redirectUrl: $redirectUrl,
            responseTime: $responseTime,
            metadata: $metadata ?? new ValidationMetadata(),
            filePath: $filePath,
        );
    }

    /**
     * Create an aggregate result from multiple validation results.
     *
     * @param ValidationResult[] $results
     * @param array<string, mixed> $summary
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

        $metadata = ValidationMetadata::fromArray([
            'total_results' => $totalResults,
            'broken_count' => $brokenCount,
            'summary' => $summary,
            'results' => $results,
        ]);

        // Create an aggregate result representing the overall validation
        return new self(
            url: "aggregate-{$totalResults}-results",
            status: $allValid ? LinkStatus::VALID : LinkStatus::BROKEN,
            scope: ValidationScope::ALL,
            metadata: $metadata,
        );
    }

    /**
     * Create a validation result with basic parameters.
     */
    public static function create(
        string $url,
        LinkStatus $status,
        string $message = '',
        ValidationScope $scope = ValidationScope::ALL,
        ?int $httpStatusCode = null,
        float $responseTime = 0.0,
        ?ValidationMetadata $metadata = null,
    ): self {
        return new self(
            url: $url,
            status: $status,
            scope: $scope,
            error: $message ?: null,
            httpStatusCode: $httpStatusCode,
            responseTime: $responseTime,
            metadata: $metadata ?? new ValidationMetadata(),
        );
    }

    /**
     * Create a validation result from array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $url = $data['url'] ?? '';
        $metadataData = $data['metadata'] ?? [];
        /** @var array<string, mixed> $metadataArray */
        $metadataArray = is_array($metadataData) ? $metadataData : [];
        $metadata = $metadataArray !== []
            ? ValidationMetadata::fromArray($metadataArray)
            : new ValidationMetadata();

        return new self(
            url: is_string($url) ? $url : '',
            status: isset($data['status']) ? LinkStatus::from(is_string($data['status']) || is_int($data['status']) ? $data['status'] : '') : LinkStatus::BROKEN,
            scope: isset($data['scope']) ? ValidationScope::from(is_string($data['scope']) || is_int($data['scope']) ? $data['scope'] : '') : ValidationScope::ALL,
            error: isset($data['error']) && is_string($data['error']) ? $data['error'] : null,
            httpStatusCode: isset($data['http_status_code']) && is_int($data['http_status_code']) ? $data['http_status_code'] : null,
            redirectUrl: isset($data['redirect_url']) && is_string($data['redirect_url']) ? $data['redirect_url'] : null,
            responseTime: isset($data['response_time']) && (is_float($data['response_time']) || is_int($data['response_time'])) ? (float) $data['response_time'] : 0.0,
            metadata: $metadata,
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

    public function getMetadata(): ValidationMetadata
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
            'metadata' => $this->metadata->toArray(),
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
        return $this->metadata->getSummary();
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
        return $this->metadata->getBrokenCount() > 0;
    }

    /**
     * Get count of broken links (for aggregate results).
     */
    public function getBrokenCount(): int
    {
        return $this->metadata->getBrokenCount();
    }

    /**
     * Get broken links by type (for aggregate results).
     *
     * @return array<string, array<array{url: string, file: string, reason: string}>>
     */
    public function getBrokenLinksByType(): array
    {
        return $this->metadata->getBrokenLinksByType();
    }

    /**
     * Get total links count (for aggregate results).
     */
    public function getTotalLinks(): int
    {
        return $this->metadata->getTotalResults();
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
        return $this->metadata->getBrokenLinksArray();
    }

    /**
     * Initialize files array for formatters.
     *
     * @return array<string>
     */
    private function initializeFilesArray(): array
    {
        return $this->metadata->getFilesArray();
    }

    /**
     * Initialize links array for formatters.
     *
     * @return array<array{url: string, status: string, scope: string, is_valid: bool, response_time: float}>
     */
    private function initializeLinksArray(): array
    {
        return $this->metadata->getLinksArray();
    }

    /**
     * Initialize duration for formatters.
     */
    private function initializeDuration(): float
    {
        return $this->metadata->getDuration();
    }

    /**
     * Get broken links as array (for formatters).
     *
     * @return array<array{url: string, file: string, reason: string}>
     */
    private function getBrokenLinksArray(): array
    {
        return $this->metadata->getBrokenLinksArray();
    }

    /**
     * Get files array (for formatters).
     *
     * @return array<string>
     */
    private function getFilesArray(): array
    {
        return $this->metadata->getFilesArray();
    }

    /**
     * Get links array (for formatters).
     *
     * @return array<array{url: string, status: string, scope: string, is_valid: bool, response_time: float}>
     */
    private function getLinksArray(): array
    {
        return $this->metadata->getLinksArray();
    }

    /**
     * Get duration (for formatters).
     */
    private function getDuration(): float
    {
        return $this->metadata->getDuration();
    }
}
