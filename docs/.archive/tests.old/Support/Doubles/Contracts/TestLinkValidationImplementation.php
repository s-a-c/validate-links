<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Doubles\Contracts;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;

/**
 * Test implementation extracted from tests.old/Unit/Contracts/LinkValidationInterfaceTest.php
 */
final class TestLinkValidationImplementation implements LinkValidationInterface
{
    public function validateLink(string $url, ValidationConfig $config): ValidationResult
    {
        // Suppress unused parameter warnings for test implementation
        unset($config);

        return new ValidationResult($url, LinkStatus::VALID, ValidationScope::EXTERNAL);
    }

    public function validateLinks(array $urls, ValidationConfig $config): array
    {
        return array_map(fn ($url): ValidationResult => $this->validateLink($url, $config), $urls);
    }

    public function validateFile(string $filePath, ValidationConfig $config): array
    {
        // Suppress unused parameter warnings for test implementation
        unset($filePath, $config);

        return [
            new ValidationResult('https://example.com', LinkStatus::VALID, ValidationScope::EXTERNAL),
        ];
    }

    public function validateFiles(array $files, ValidationConfig $config): array
    {
        // Suppress unused parameter warnings for test implementation
        unset($files, $config);

        return [
            new ValidationResult('https://example.com', LinkStatus::VALID, ValidationScope::EXTERNAL),
        ];
    }

    public function extractLinks(string $content): array
    {
        // Suppress unused parameter warning for test implementation
        unset($content);

        return [
            ['url' => 'https://example.com', 'text' => 'Example', 'line' => 1],
        ];
    }

    public function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    public function normalizeUrl(string $url): string
    {
        return $url;
    }

    public function getSupportedScopes(): array
    {
        return [ValidationScope::EXTERNAL, ValidationScope::INTERNAL];
    }

    public function supportsScope(ValidationScope $scope): bool
    {
        return in_array($scope, $this->getSupportedScopes());
    }

    public function validateByScopes(array $scopes, array $targets, ValidationConfig $config): ValidationResult
    {
        // Suppress unused parameter warnings for test implementation
        unset($scopes, $targets, $config);

        return new ValidationResult('test', LinkStatus::VALID, ValidationScope::EXTERNAL);
    }

    public function getStatusStatistics(): array
    {
        return [LinkStatus::VALID->value => 1];
    }

    public function setTimeout(int $seconds): void
    {
        // Suppress unused parameter warning for test implementation
        unset($seconds);

        // Test implementation - no-op
    }
}
