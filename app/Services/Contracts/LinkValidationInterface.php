<?php

declare(strict_types=1);

namespace App\Services\Contracts;

use App\Enums\ValidationScope;
use App\Services\ValueObjects\LinkCollection;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;
use App\Services\ValueObjects\ValidationStatistics;

interface LinkValidationInterface
{
    /**
     * Validate a single link.
     */
    public function validateLink(string $url, ValidationConfig $validationConfig): ValidationResult;

    /**
     * Validate multiple links concurrently.
     *
     * @param string[] $urls
     */
    public function validateLinks(array $urls, ValidationConfig $validationConfig): ValidationResultCollection;

    /**
     * Validate links in a file.
     */
    public function validateFile(string $filePath, ValidationConfig $validationConfig): ValidationResultCollection;

    /**
     * Extract links from content.
     */
    public function extractLinks(string $content): LinkCollection;

    /**
     * Check if URL is valid format.
     */
    public function isValidUrl(string $url): bool;

    /**
     * Normalize URL for validation.
     */
    public function normalizeUrl(string $url): string;

    /**
     * Get supported validation scopes.
     *
     * @return ValidationScope[]
     */
    public function getSupportedScopes(): array;

    /**
     * Check if a specific scope is supported.
     */
    public function supportsScope(ValidationScope $validationScope): bool;

    /**
     * Validate links by specific scopes.
     *
     * @param ValidationScope[] $scopes
     * @param string[] $targets
     */
    public function validateByScopes(array $scopes, array $targets, ValidationConfig $validationConfig): ValidationResult;

    /**
     * Get validation statistics grouped by LinkStatus.
     */
    public function getStatusStatistics(): ValidationStatistics;

    /**
     * Set validation timeout.
     */
    public function setTimeout(int $seconds): void;

    /**
     * Validate multiple files.
     *
     * @param string[] $files
     */
    public function validateFiles(array $files, ValidationConfig $validationConfig): ValidationResultCollection;
}
