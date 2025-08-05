<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use App\Enums\LinkType;
use App\Services\ValueObjects\ValidationResult;
use App\Services\ValueObjects\ValidationResultCollection;

/**
 * Link Validation Custom Matchers for PEST
 * 
 * Provides domain-specific matchers for link validation testing,
 * making assertions more readable and expressive.
 */

if (!function_exists('toBeValidLink')) {
    /**
     * Assert that a validation result represents a valid link
     */
    function toBeValidLink(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getStatus() === LinkStatus::VALID;
            }
            
            if (is_string($this->value)) {
                // Simple URL validation
                return filter_var($this->value, FILTER_VALIDATE_URL) !== false;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeBrokenLink')) {
    /**
     * Assert that a validation result represents a broken link
     */
    function toBeBrokenLink(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                return in_array($this->value->getStatus(), [
                    LinkStatus::BROKEN,
                    LinkStatus::NOT_FOUND,
                    LinkStatus::TIMEOUT,
                    LinkStatus::ERROR
                ], true);
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveStatus')) {
    /**
     * Assert that a validation result has a specific status
     */
    function toHaveStatus(LinkStatus $expectedStatus): Closure
    {
        return function () use ($expectedStatus) {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getStatus() === $expectedStatus;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveHttpStatus')) {
    /**
     * Assert that a validation result has a specific HTTP status code
     */
    function toHaveHttpStatus(int $expectedCode): Closure
    {
        return function () use ($expectedCode) {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getHttpStatusCode() === $expectedCode;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeSuccessfulHttpStatus')) {
    /**
     * Assert that a validation result has a successful HTTP status (2xx)
     */
    function toBeSuccessfulHttpStatus(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                $code = $this->value->getHttpStatusCode();
                return $code >= 200 && $code < 300;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeRedirectStatus')) {
    /**
     * Assert that a validation result has a redirect HTTP status (3xx)
     */
    function toBeRedirectStatus(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                $code = $this->value->getHttpStatusCode();
                return $code >= 300 && $code < 400;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeClientErrorStatus')) {
    /**
     * Assert that a validation result has a client error HTTP status (4xx)
     */
    function toBeClientErrorStatus(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                $code = $this->value->getHttpStatusCode();
                return $code >= 400 && $code < 500;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeServerErrorStatus')) {
    /**
     * Assert that a validation result has a server error HTTP status (5xx)
     */
    function toBeServerErrorStatus(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                $code = $this->value->getHttpStatusCode();
                return $code >= 500 && $code < 600;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveLinkType')) {
    /**
     * Assert that a validation result has a specific link type
     */
    function toHaveLinkType(LinkType $expectedType): Closure
    {
        return function () use ($expectedType) {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getLinkType() === $expectedType;
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeExternalLink')) {
    /**
     * Assert that a validation result represents an external link
     */
    function toBeExternalLink(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getLinkType() === LinkType::EXTERNAL;
            }
            
            if (is_string($this->value)) {
                return str_starts_with($this->value, 'http://') || str_starts_with($this->value, 'https://');
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeInternalLink')) {
    /**
     * Assert that a validation result represents an internal link
     */
    function toBeInternalLink(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getLinkType() === LinkType::INTERNAL;
            }
            
            if (is_string($this->value)) {
                return str_starts_with($this->value, '/') || str_starts_with($this->value, '#');
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeAnchorLink')) {
    /**
     * Assert that a validation result represents an anchor link
     */
    function toBeAnchorLink(): Closure
    {
        return function () {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getLinkType() === LinkType::ANCHOR;
            }
            
            if (is_string($this->value)) {
                return str_starts_with($this->value, '#');
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveResponseTime')) {
    /**
     * Assert that a validation result has a specific response time
     */
    function toHaveResponseTime(float $expectedTime, float $tolerance = 0.1): Closure
    {
        return function () use ($expectedTime, $tolerance) {
            if ($this->value instanceof ValidationResult) {
                $actualTime = $this->value->getResponseTime();
                return abs($actualTime - $expectedTime) <= $tolerance;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveResponseTimeBelow')) {
    /**
     * Assert that a validation result has response time below threshold
     */
    function toHaveResponseTimeBelow(float $maxTime): Closure
    {
        return function () use ($maxTime) {
            if ($this->value instanceof ValidationResult) {
                return $this->value->getResponseTime() < $maxTime;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveValidationError')) {
    /**
     * Assert that a validation result has a specific error message
     */
    function toHaveValidationError(string $expectedError = null): Closure
    {
        return function () use ($expectedError) {
            if ($this->value instanceof ValidationResult) {
                $error = $this->value->getErrorMessage();
                
                if ($expectedError === null) {
                    return !empty($error);
                }
                
                return str_contains($error, $expectedError);
            }
            
            return false;
        };
    }
}

if (!function_exists('toBeValidationCollection')) {
    /**
     * Assert that value is a validation result collection with specific count
     */
    function toBeValidationCollection(int $expectedCount = null): Closure
    {
        return function () use ($expectedCount) {
            if (!($this->value instanceof ValidationResultCollection)) {
                return false;
            }
            
            if ($expectedCount !== null) {
                return $this->value->count() === $expectedCount;
            }
            
            return true;
        };
    }
}

if (!function_exists('toHaveValidResults')) {
    /**
     * Assert that a validation collection has a specific number of valid results
     */
    function toHaveValidResults(int $expectedCount): Closure
    {
        return function () use ($expectedCount) {
            if ($this->value instanceof ValidationResultCollection) {
                $validCount = 0;
                foreach ($this->value as $result) {
                    if ($result->getStatus() === LinkStatus::VALID) {
                        $validCount++;
                    }
                }
                return $validCount === $expectedCount;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveBrokenResults')) {
    /**
     * Assert that a validation collection has a specific number of broken results
     */
    function toHaveBrokenResults(int $expectedCount): Closure
    {
        return function () use ($expectedCount) {
            if ($this->value instanceof ValidationResultCollection) {
                $brokenCount = 0;
                foreach ($this->value as $result) {
                    if (in_array($result->getStatus(), [
                        LinkStatus::BROKEN,
                        LinkStatus::NOT_FOUND,
                        LinkStatus::TIMEOUT,
                        LinkStatus::ERROR
                    ], true)) {
                        $brokenCount++;
                    }
                }
                return $brokenCount === $expectedCount;
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveSuccessRate')) {
    /**
     * Assert that a validation collection has a specific success rate
     */
    function toHaveSuccessRate(float $expectedRate, float $tolerance = 0.01): Closure
    {
        return function () use ($expectedRate, $tolerance) {
            if ($this->value instanceof ValidationResultCollection) {
                $total = $this->value->count();
                if ($total === 0) {
                    return $expectedRate === 0.0;
                }
                
                $validCount = 0;
                foreach ($this->value as $result) {
                    if ($result->getStatus() === LinkStatus::VALID) {
                        $validCount++;
                    }
                }
                
                $actualRate = $validCount / $total;
                return abs($actualRate - $expectedRate) <= $tolerance;
            }
            
            return false;
        };
    }
}

if (!function_exists('toContainUrl')) {
    /**
     * Assert that a validation collection contains a specific URL
     */
    function toContainUrl(string $expectedUrl): Closure
    {
        return function () use ($expectedUrl) {
            if ($this->value instanceof ValidationResultCollection) {
                foreach ($this->value as $result) {
                    if ($result->getUrl() === $expectedUrl) {
                        return true;
                    }
                }
            }
            
            return false;
        };
    }
}

if (!function_exists('toHaveAverageResponseTime')) {
    /**
     * Assert that a validation collection has a specific average response time
     */
    function toHaveAverageResponseTime(float $expectedAverage, float $tolerance = 0.1): Closure
    {
        return function () use ($expectedAverage, $tolerance) {
            if ($this->value instanceof ValidationResultCollection) {
                $total = $this->value->count();
                if ($total === 0) {
                    return false;
                }
                
                $totalTime = 0;
                foreach ($this->value as $result) {
                    $totalTime += $result->getResponseTime();
                }
                
                $actualAverage = $totalTime / $total;
                return abs($actualAverage - $expectedAverage) <= $tolerance;
            }
            
            return false;
        };
    }
}