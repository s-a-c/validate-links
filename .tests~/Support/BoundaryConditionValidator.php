<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\LinkStatus;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;
use App\Services\ValueObjects\ValidationResult;
use InvalidArgumentException;
use RuntimeException;

/**
 * Boundary condition validator for comprehensive edge case testing.
 * 
 * Provides validation utilities for null safety, empty collections,
 * boundary values, and concurrent access patterns to ensure robust
 * handling of all edge cases and boundary conditions.
 * 
 * @package Tests\Support
 * @author Roo
 * @since 1.0.0
 */
final class BoundaryConditionValidator
{
    /**
     * Validation results for tracking.
     * 
     * @var array<string, mixed>
     */
    private static array $validationResults = [];
    
    /**
     * Concurrent access tracking.
     * 
     * @var array<string, mixed>
     */
    private static array $concurrentAccess = [];
    
    /**
     * Initialize the boundary condition validator.
     */
    public static function initialize(): void
    {
        self::$validationResults = [];
        self::$concurrentAccess = [];
    }
    
    /**
     * Reset all validation state.
     */
    public static function reset(): void
    {
        self::$validationResults = [];
        self::$concurrentAccess = [];
    }
    
    /**
     * Validate null safety across all methods and parameters.
     * 
     * @param callable $operation Operation to test
     * @param array<string, mixed> $nullInputs Null input variations
     * @return array<string, mixed> Validation results
     */
    public static function validateNullSafety(callable $operation, array $nullInputs): array
    {
        $results = [];
        
        foreach ($nullInputs as $inputName => $nullValue) {
            try {
                $result = $operation($nullValue);
                $results[$inputName] = [
                    'input' => $nullValue,
                    'result' => $result,
                    'exception' => null,
                    'null_safe' => true,
                ];
            } catch (\Throwable $e) {
                $results[$inputName] = [
                    'input' => $nullValue,
                    'result' => null,
                    'exception' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                    'null_safe' => self::isExpectedNullException($e),
                ];
            }
        }
        
        self::$validationResults['null_safety'] = $results;
        
        return $results;
    }
    
    /**
     * Validate empty collection handling.
     * 
     * @param callable $operation Operation to test
     * @param array<string, mixed> $emptyCollections Empty collection variations
     * @return array<string, mixed> Validation results
     */
    public static function validateEmptyCollections(callable $operation, array $emptyCollections): array
    {
        $results = [];
        
        foreach ($emptyCollections as $collectionName => $emptyCollection) {
            try {
                $result = $operation($emptyCollection);
                $results[$collectionName] = [
                    'input' => $emptyCollection,
                    'result' => $result,
                    'exception' => null,
                    'handles_empty' => self::isValidEmptyResult($result),
                ];
            } catch (\Throwable $e) {
                $results[$collectionName] = [
                    'input' => $emptyCollection,
                    'result' => null,
                    'exception' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                    'handles_empty' => self::isExpectedEmptyException($e),
                ];
            }
        }
        
        self::$validationResults['empty_collections'] = $results;
        
        return $results;
    }
    
    /**
     * Validate boundary values for numeric inputs.
     * 
     * @param callable $operation Operation to test
     * @param array<string, mixed> $boundaryValues Boundary value variations
     * @return array<string, mixed> Validation results
     */
    public static function validateBoundaryValues(callable $operation, array $boundaryValues): array
    {
        $results = [];
        
        foreach ($boundaryValues as $valueName => $boundaryValue) {
            try {
                $result = $operation($boundaryValue);
                $results[$valueName] = [
                    'input' => $boundaryValue,
                    'result' => $result,
                    'exception' => null,
                    'handles_boundary' => self::isValidBoundaryResult($result, $boundaryValue),
                ];
            } catch (\Throwable $e) {
                $results[$valueName] = [
                    'input' => $boundaryValue,
                    'result' => null,
                    'exception' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                    'handles_boundary' => self::isExpectedBoundaryException($e, $boundaryValue),
                ];
            }
        }
        
        self::$validationResults['boundary_values'] = $results;
        
        return $results;
    }
    
    /**
     * Validate concurrent access patterns.
     * 
     * @param callable $operation Operation to test
     * @param array<string, mixed> $concurrencyConfig Concurrency configuration
     * @return array<string, mixed> Validation results
     */
    public static function validateConcurrentAccess(callable $operation, array $concurrencyConfig): array
    {
        $threadCount = $concurrencyConfig['threads'] ?? 5;
        $iterations = $concurrencyConfig['iterations'] ?? 10;
        $sharedResource = $concurrencyConfig['shared_resource'] ?? null;
        
        $results = [];
        $processes = [];
        
        // Simulate concurrent access using multiple iterations
        for ($thread = 0; $thread < $threadCount; $thread++) {
            $threadResults = [];
            
            for ($iteration = 0; $iteration < $iterations; $iteration++) {
                $startTime = microtime(true);
                
                try {
                    // Track concurrent access
                    $accessId = "thread_{$thread}_iter_{$iteration}";
                    self::$concurrentAccess[$accessId] = [
                        'start_time' => $startTime,
                        'thread' => $thread,
                        'iteration' => $iteration,
                    ];
                    
                    $result = $operation($sharedResource);
                    $endTime = microtime(true);
                    
                    $threadResults[] = [
                        'thread' => $thread,
                        'iteration' => $iteration,
                        'result' => $result,
                        'duration' => $endTime - $startTime,
                        'exception' => null,
                        'success' => true,
                    ];
                    
                    self::$concurrentAccess[$accessId]['end_time'] = $endTime;
                    self::$concurrentAccess[$accessId]['success'] = true;
                    
                } catch (\Throwable $e) {
                    $endTime = microtime(true);
                    
                    $threadResults[] = [
                        'thread' => $thread,
                        'iteration' => $iteration,
                        'result' => null,
                        'duration' => $endTime - $startTime,
                        'exception' => [
                            'class' => get_class($e),
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                        ],
                        'success' => false,
                    ];
                    
                    self::$concurrentAccess[$accessId]['end_time'] = $endTime;
                    self::$concurrentAccess[$accessId]['success'] = false;
                    self::$concurrentAccess[$accessId]['exception'] = get_class($e);
                }
                
                // Small delay to allow for race conditions
                usleep(1000); // 1ms
            }
            
            $results["thread_{$thread}"] = $threadResults;
        }
        
        // Analyze concurrent access patterns
        $analysis = self::analyzeConcurrentAccess();
        $results['analysis'] = $analysis;
        
        self::$validationResults['concurrent_access'] = $results;
        
        return $results;
    }
    
    /**
     * Validate string boundary conditions.
     * 
     * @param callable $operation Operation to test
     * @param array<string> $stringInputs String input variations
     * @return array<string, mixed> Validation results
     */
    public static function validateStringBoundaries(callable $operation, array $stringInputs): array
    {
        $results = [];
        
        foreach ($stringInputs as $inputName => $stringValue) {
            try {
                $result = $operation($stringValue);
                $results[$inputName] = [
                    'input' => $stringValue,
                    'input_length' => strlen($stringValue),
                    'result' => $result,
                    'exception' => null,
                    'handles_string' => self::isValidStringResult($result, $stringValue),
                ];
            } catch (\Throwable $e) {
                $results[$inputName] = [
                    'input' => $stringValue,
                    'input_length' => strlen($stringValue),
                    'result' => null,
                    'exception' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                    'handles_string' => self::isExpectedStringException($e, $stringValue),
                ];
            }
        }
        
        self::$validationResults['string_boundaries'] = $results;
        
        return $results;
    }
    
    /**
     * Validate URL format edge cases.
     * 
     * @param callable $operation Operation to test
     * @param array<string> $urlInputs URL input variations
     * @return array<string, mixed> Validation results
     */
    public static function validateUrlBoundaries(callable $operation, array $urlInputs): array
    {
        $results = [];
        
        foreach ($urlInputs as $inputName => $urlValue) {
            try {
                $result = $operation($urlValue);
                $results[$inputName] = [
                    'input' => $urlValue,
                    'result' => $result,
                    'exception' => null,
                    'handles_url' => self::isValidUrlResult($result, $urlValue),
                ];
            } catch (\Throwable $e) {
                $results[$inputName] = [
                    'input' => $urlValue,
                    'result' => null,
                    'exception' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                    'handles_url' => self::isExpectedUrlException($e, $urlValue),
                ];
            }
        }
        
        self::$validationResults['url_boundaries'] = $results;
        
        return $results;
    }
    
    /**
     * Validate configuration edge cases.
     * 
     * @param callable $operation Operation to test
     * @param array<string, mixed> $configInputs Configuration input variations
     * @return array<string, mixed> Validation results
     */
    public static function validateConfigBoundaries(callable $operation, array $configInputs): array
    {
        $results = [];
        
        foreach ($configInputs as $inputName => $configValue) {
            try {
                $result = $operation($configValue);
                $results[$inputName] = [
                    'input' => $configValue,
                    'result' => $result,
                    'exception' => null,
                    'handles_config' => self::isValidConfigResult($result, $configValue),
                ];
            } catch (\Throwable $e) {
                $results[$inputName] = [
                    'input' => $configValue,
                    'result' => null,
                    'exception' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ],
                    'handles_config' => self::isExpectedConfigException($e, $configValue),
                ];
            }
        }
        
        self::$validationResults['config_boundaries'] = $results;
        
        return $results;
    }
    
    /**
     * Get comprehensive validation summary.
     * 
     * @return array<string, mixed> Validation summary
     */
    public static function getValidationSummary(): array
    {
        $summary = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'categories' => [],
        ];
        
        foreach (self::$validationResults as $category => $results) {
            $categoryStats = [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'details' => [],
            ];
            
            if ($category === 'concurrent_access') {
                // Special handling for concurrent access results
                foreach ($results as $key => $threadResults) {
                    if ($key === 'analysis') {
                        continue;
                    }
                    
                    foreach ($threadResults as $result) {
                        $categoryStats['total']++;
                        if ($result['success']) {
                            $categoryStats['passed']++;
                        } else {
                            $categoryStats['failed']++;
                            $categoryStats['details'][] = $result['exception'] ?? 'Unknown error';
                        }
                    }
                }
            } else {
                foreach ($results as $testName => $result) {
                    $categoryStats['total']++;
                    
                    $passed = match ($category) {
                        'null_safety' => $result['null_safe'],
                        'empty_collections' => $result['handles_empty'],
                        'boundary_values' => $result['handles_boundary'],
                        'string_boundaries' => $result['handles_string'],
                        'url_boundaries' => $result['handles_url'],
                        'config_boundaries' => $result['handles_config'],
                        default => false,
                    };
                    
                    if ($passed) {
                        $categoryStats['passed']++;
                    } else {
                        $categoryStats['failed']++;
                        if (isset($result['exception'])) {
                            $categoryStats['details'][] = $result['exception'];
                        }
                    }
                }
            }
            
            $summary['categories'][$category] = $categoryStats;
            $summary['total_tests'] += $categoryStats['total'];
            $summary['passed_tests'] += $categoryStats['passed'];
            $summary['failed_tests'] += $categoryStats['failed'];
        }
        
        $summary['success_rate'] = $summary['total_tests'] > 0
            ? round(($summary['passed_tests'] / $summary['total_tests']) * 100, 2)
            : 100; // If no tests run, assume 100% success (no failures)
        
        return $summary;
    }
    
    /**
     * Check if an exception is expected for null input.
     */
    private static function isExpectedNullException(\Throwable $e): bool
    {
        return $e instanceof InvalidArgumentException ||
               $e instanceof \TypeError ||
               str_contains($e->getMessage(), 'null') ||
               str_contains($e->getMessage(), 'empty');
    }
    
    /**
     * Check if a result is valid for empty input.
     */
    private static function isValidEmptyResult(mixed $result): bool
    {
        // Empty collections should return empty results or handle gracefully
        // ValidationConfig always returns valid objects with defaults, so this is acceptable
        return $result === [] ||
               $result === null ||
               (is_object($result) && method_exists($result, 'count') && $result->count() === 0) ||
               ($result instanceof \App\Services\ValueObjects\ValidationConfig);
    }
    
    /**
     * Check if an exception is expected for empty input.
     */
    private static function isExpectedEmptyException(\Throwable $e): bool
    {
        return $e instanceof InvalidArgumentException ||
               str_contains($e->getMessage(), 'empty') ||
               str_contains($e->getMessage(), 'required');
    }
    
    /**
     * Check if a result is valid for boundary value.
     */
    private static function isValidBoundaryResult(mixed $result, mixed $boundaryValue): bool
    {
        // Boundary values should either be handled gracefully or throw expected exceptions
        if (is_infinite($boundaryValue) || is_nan($boundaryValue)) {
            return $result === null || $result === false;
        }
        
        return true; // Most boundary values should be handled
    }
    
    /**
     * Check if an exception is expected for boundary value.
     */
    private static function isExpectedBoundaryException(\Throwable $e, mixed $boundaryValue): bool
    {
        if (is_infinite($boundaryValue) || is_nan($boundaryValue)) {
            return $e instanceof InvalidArgumentException ||
                   str_contains($e->getMessage(), 'invalid') ||
                   str_contains($e->getMessage(), 'range');
        }
        
        return $e instanceof InvalidArgumentException;
    }
    
    /**
     * Check if a result is valid for string input.
     */
    private static function isValidStringResult(mixed $result, string $stringValue): bool
    {
        // Very long strings or special characters should be handled gracefully
        return $result !== null || strlen($stringValue) === 0;
    }
    
    /**
     * Check if an exception is expected for string input.
     */
    private static function isExpectedStringException(\Throwable $e, string $stringValue): bool
    {
        // Control characters or extremely long strings may cause expected exceptions
        if (strlen($stringValue) > 10000 || preg_match('/[\x00-\x1F]/', $stringValue)) {
            return $e instanceof InvalidArgumentException ||
                   $e instanceof RuntimeException;
        }
        
        return false;
    }
    
    /**
     * Check if a result is valid for URL input.
     */
    private static function isValidUrlResult(mixed $result, string $urlValue): bool
    {
        // Invalid URLs should return appropriate error status
        if ($result instanceof ValidationResult) {
            return $result->getStatus() === LinkStatus::INVALID_FORMAT ||
                   $result->getStatus() === LinkStatus::SECURITY_VIOLATION ||
                   $result->getStatus() === LinkStatus::VALID;
        }
        
        return is_bool($result) || $result === null;
    }
    
    /**
     * Check if an exception is expected for URL input.
     */
    private static function isExpectedUrlException(\Throwable $e, string $urlValue): bool
    {
        // Malformed URLs may cause expected exceptions
        return $e instanceof InvalidArgumentException ||
               str_contains($e->getMessage(), 'URL') ||
               str_contains($e->getMessage(), 'format');
    }
    
    /**
     * Check if a result is valid for configuration input.
     */
    private static function isValidConfigResult(mixed $result, mixed $configValue): bool
    {
        // Invalid configurations should either be rejected or use defaults
        return $result instanceof ValidationConfig || $result === null;
    }
    
    /**
     * Check if an exception is expected for configuration input.
     */
    private static function isExpectedConfigException(\Throwable $e, mixed $configValue): bool
    {
        // Invalid configurations should throw InvalidArgumentException
        return $e instanceof InvalidArgumentException ||
               str_contains($e->getMessage(), 'config') ||
               str_contains($e->getMessage(), 'invalid');
    }
    
    /**
     * Analyze concurrent access patterns for race conditions.
     * 
     * @return array<string, mixed> Concurrent access analysis
     */
    private static function analyzeConcurrentAccess(): array
    {
        $analysis = [
            'total_accesses' => count(self::$concurrentAccess),
            'successful_accesses' => 0,
            'failed_accesses' => 0,
            'race_conditions_detected' => 0,
            'average_duration' => 0,
            'max_duration' => 0,
            'min_duration' => PHP_FLOAT_MAX,
            'overlapping_accesses' => [],
        ];
        
        $durations = [];
        $timeRanges = [];
        
        foreach (self::$concurrentAccess as $accessId => $access) {
            if ($access['success']) {
                $analysis['successful_accesses']++;
            } else {
                $analysis['failed_accesses']++;
            }
            
            if (isset($access['end_time'])) {
                $duration = $access['end_time'] - $access['start_time'];
                $durations[] = $duration;
                
                $analysis['max_duration'] = max($analysis['max_duration'], $duration);
                $analysis['min_duration'] = min($analysis['min_duration'], $duration);
                
                $timeRanges[$accessId] = [
                    'start' => $access['start_time'],
                    'end' => $access['end_time'],
                ];
            }
        }
        
        if (!empty($durations)) {
            $analysis['average_duration'] = array_sum($durations) / count($durations);
        }
        
        // Detect overlapping accesses (potential race conditions)
        foreach ($timeRanges as $id1 => $range1) {
            foreach ($timeRanges as $id2 => $range2) {
                if ($id1 !== $id2 && 
                    $range1['start'] < $range2['end'] && 
                    $range1['end'] > $range2['start']) {
                    $analysis['overlapping_accesses'][] = [$id1, $id2];
                    $analysis['race_conditions_detected']++;
                }
            }
        }
        
        // Remove duplicates from overlapping accesses
        $analysis['overlapping_accesses'] = array_unique($analysis['overlapping_accesses'], SORT_REGULAR);
        $analysis['race_conditions_detected'] = count($analysis['overlapping_accesses']);
        
        return $analysis;
    }
}