<?php

declare(strict_types=1);

namespace Tests\Support\Builders;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationResult;

final class ValidationResultBuilder
{
    private string $url = 'https://example.com';
    private LinkStatus $status;
    private ValidationScope $scope;
    private ?string $error = null;
    private ?int $httpStatusCode = null;
    private ?string $redirectUrl = null;
    private float $responseTime = 0.01;
    /** @var array<string,mixed> */
    private array $metadata = [];
    private ?string $filePath = null;

    public function __construct()
    {
        $this->status = LinkStatus::VALID;
        $this->scope = ValidationScope::EXTERNAL;
    }

    public static function new(): self
    {
        return new self();
    }

    public function url(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function status(LinkStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function scope(ValidationScope $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function error(?string $error): self
    {
        $this->error = $error;
        return $this;
    }

    public function http(int $code): self
    {
        $this->httpStatusCode = $code;
        return $this;
    }

    public function redirect(?string $to): self
    {
        $this->redirectUrl = $to;
        return $this;
    }

    public function time(float $seconds): self
    {
        $this->responseTime = $seconds;
        return $this;
    }

    /**
     * @param array<string,mixed> $meta
     */
    public function meta(array $meta): self
    {
        $this->metadata = $meta;
        return $this;
    }

    public function file(?string $file): self
    {
        $this->filePath = $file;
        return $this;
    }

    public function build(): ValidationResult
    {
        if ($this->status === LinkStatus::VALID) {
            return ValidationResult::success(
                url: $this->url,
                scope: $this->scope,
                httpStatusCode: $this->httpStatusCode,
                responseTime: $this->responseTime,
                metadata: $this->metadata,
                filePath: $this->filePath
            );
        }

        return ValidationResult::failure(
            url: $this->url,
            scope: $this->scope,
            status: $this->status,
            error: $this->error,
            httpStatusCode: $this->httpStatusCode,
            redirectUrl: $this->redirectUrl,
            responseTime: $this->responseTime,
            metadata: $this->metadata,
            filePath: $this->filePath
        );
    }
}