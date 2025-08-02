<?php

declare(strict_types=1);

namespace .archive\tests.old\Unit\Contracts;

final class ValidationResult
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    private function __construct(
        private readonly string $url,
        private readonly string $status,
        private readonly string $scope,
        private readonly ?string $error = null,
        private readonly ?int $httpStatusCode = null,
        private readonly ?string $redirectUrl = null,
        private readonly float $responseTime = 0.0,
        private readonly array $metadata = []
    ) {}

    /**
     * Create a successful validation result.
     *
     * @param  string  $url  The validated URL
     * @param  string  $scope  The validation scope (use ValidationScope constants)
     * @param  int|null  $httpStatusCode  HTTP status code (optional)
     * @param  float  $responseTime  Response time in seconds
     * @param  array<string, mixed>  $metadata  Additional metadata
     */
    public static function success(
        string $url,
        string $scope,
        ?int $httpStatusCode = 200,
        float $responseTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            url: $url,
            status: LinkStatus::VALID,
            scope: $scope,
            error: null,
            httpStatusCode: $httpStatusCode,
            redirectUrl: null,
            responseTime: $responseTime,
            metadata: $metadata
        );
    }

    /**
     * Create a failed validation result.
     *
     * @param  string  $url  The validated URL
     * @param  string  $scope  The validation scope (use ValidationScope constants)
     * @param  string  $status  The failure status (use LinkStatus constants)
     * @param  string|null  $error  Error message
     * @param  int|null  $httpStatusCode  HTTP status code (optional)
     * @param  string|null  $redirectUrl  Redirect URL if applicable
     * @param  float  $responseTime  Response time in seconds
     * @param  array<string, mixed>  $metadata  Additional metadata
     */
    public static function failure(
        string $url,
        string $scope,
        string $status,
        ?string $error = null,
        ?int $httpStatusCode = null,
        ?string $redirectUrl = null,
        float $responseTime = 0.0,
        array $metadata = []
    ): self {
        return new self(
            url: $url,
            status: $status,
            scope: $scope,
            error: $error,
            httpStatusCode: $httpStatusCode,
            redirectUrl: $redirectUrl,
            responseTime: $responseTime,
            metadata: $metadata
        );
    }

    /**
     * Get the URL.
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the status.
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Get the scope.
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * Check if the validation was successful.
     */
    public function isValid(): bool
    {
        return $this->status === LinkStatus::VALID;
    }

    /**
     * Get the error message.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get the HTTP status code.
     */
    public function getHttpStatusCode(): ?int
    {
        return $this->httpStatusCode;
    }

    /**
     * Get the redirect URL.
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * Get the response time.
     */
    public function getResponseTime(): float
    {
        return $this->responseTime;
    }

    /**
     * Get the metadata.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
