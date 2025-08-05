<?php

declare(strict_types=1);

namespace Tests\Support;

use Psr\Log\LogLevel;

/**
 * Advanced log verification utilities for comprehensive logging validation.
 *
 * Provides specialized utilities for:
 * - Advanced log pattern matching and content validation
 * - Performance impact measurement of logging operations
 * - Log correlation and tracing verification
 * - Structured logging compliance checking
 * - Log aggregation and analysis tools
 *
 * @package Tests\Support
 * @author Roo
 * @since 1.0.0
 */
final class LogVerificationUtilities
{
    /** @var array<string, array<string, mixed>> */
    private static array $logPatterns = [];
    
    /** @var array<string, array<string, mixed>> */
    private static array $performanceBaselines = [];
    
    /** @var array<string, int> */
    private static array $logLevelPriorities = [
        LogLevel::DEBUG => 100,
        LogLevel::INFO => 200,
        LogLevel::NOTICE => 250,
        LogLevel::WARNING => 300,
        LogLevel::ERROR => 400,
        LogLevel::CRITICAL => 500,
        LogLevel::ALERT => 550,
        LogLevel::EMERGENCY => 600,
    ];

    /**
     * Verify comprehensive log message patterns with advanced matching.
     *
     * @param array<array<string, mixed>> $logRecords Log records to verify
     * @param array<string, mixed> $patterns Pattern specifications
     * @return array{matches: array<string, array<string, mixed>>, mismatches: array<string, array<string, mixed>>, score: float}
     */
    public static function verifyLogPatterns(array $logRecords, array $patterns): array
    {
        $matches = [];
        $mismatches = [];
        $totalPatterns = count($patterns);
        $matchedPatterns = 0;
        
        foreach ($patterns as $patternName => $patternSpec) {
            $patternMatches = [];
            $patternMismatches = [];
            
            foreach ($logRecords as $record) {
                $matchResult = self::matchLogRecord($record, $patternSpec);
                
                if ($matchResult['matched']) {
                    $patternMatches[] = [
                        'record' => $record,
                        'match_details' => $matchResult['details'],
                    ];
                } else {
                    $patternMismatches[] = [
                        'record' => $record,
                        'mismatch_reasons' => $matchResult['reasons'],
                    ];
                }
            }
            
            if (!empty($patternMatches)) {
                $matches[$patternName] = $patternMatches;
                $matchedPatterns++;
            }
            
            if (!empty($patternMismatches)) {
                $mismatches[$patternName] = $patternMismatches;
            }
        }
        
        $score = $totalPatterns > 0 ? ($matchedPatterns / $totalPatterns) * 100 : 0;
        
        return [
            'matches' => $matches,
            'mismatches' => $mismatches,
            'score' => $score,
        ];
    }

    /**
     * Analyze log performance impact with detailed metrics.
     *
     * @param callable $operation Operation to measure
     * @param array<string, mixed> $config Performance measurement configuration
     * @return array{baseline: array<string, float>, with_logging: array<string, float>, impact: array<string, mixed>}
     */
    public static function analyzeLogPerformanceImpact(callable $operation, array $config = []): array
    {
        $iterations = $config['iterations'] ?? 100;
        $warmupIterations = $config['warmup_iterations'] ?? 10;
        
        // Warmup phase
        for ($i = 0; $i < $warmupIterations; $i++) {
            $operation();
        }
        
        // Baseline measurement (without logging)
        LoggingTestHelper::resetLoggingEnvironment();
        
        $baselineMetrics = self::measureOperationPerformance($operation, $iterations);
        
        // Measurement with logging enabled
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr']);
        
        $loggingMetrics = self::measureOperationPerformance($operation, $iterations);
        
        // Calculate impact
        $impact = self::calculatePerformanceImpact($baselineMetrics, $loggingMetrics);
        
        return [
            'baseline' => $baselineMetrics,
            'with_logging' => $loggingMetrics,
            'impact' => $impact,
        ];
    }

    /**
     * Validate log correlation across multiple components.
     *
     * @param array<array<string, mixed>> $logRecords Log records to analyze
     * @param array<string, mixed> $correlationConfig Correlation configuration
     * @return array{correlation_chains: array<string, array<array<string, mixed>>>, orphaned_logs: array<array<string, mixed>>, correlation_score: float}
     */
    public static function validateLogCorrelation(array $logRecords, array $correlationConfig): array
    {
        $correlationField = $correlationConfig['correlation_field'] ?? 'correlation_id';
        $timeWindow = $correlationConfig['time_window'] ?? 60; // seconds
        
        $correlationChains = [];
        $orphanedLogs = [];
        $processedRecords = [];
        
        foreach ($logRecords as $index => $record) {
            if (in_array($index, $processedRecords)) {
                continue;
            }
            
            $correlationId = self::extractCorrelationId($record, $correlationField);
            
            if ($correlationId === null) {
                $orphanedLogs[] = $record;
                continue;
            }
            
            // Find all related logs within time window
            $relatedLogs = self::findRelatedLogs($logRecords, $correlationId, $correlationField, $timeWindow);
            
            if (count($relatedLogs) > 1) {
                $correlationChains[$correlationId] = $relatedLogs;
                
                // Mark all related logs as processed
                foreach ($relatedLogs as $relatedLog) {
                    $relatedIndex = array_search($relatedLog, $logRecords, true);
                    if ($relatedIndex !== false) {
                        $processedRecords[] = $relatedIndex;
                    }
                }
            } else {
                $orphanedLogs[] = $record;
            }
        }
        
        $totalLogs = count($logRecords);
        $correlatedLogs = array_sum(array_map('count', $correlationChains));
        $correlationScore = $totalLogs > 0 ? ($correlatedLogs / $totalLogs) * 100 : 0;
        
        return [
            'correlation_chains' => $correlationChains,
            'orphaned_logs' => $orphanedLogs,
            'correlation_score' => $correlationScore,
        ];
    }

    /**
     * Check structured logging compliance with schema validation.
     *
     * @param array<array<string, mixed>> $logRecords Log records to validate
     * @param array<string, mixed> $schema Expected log schema
     * @return array{compliant_logs: array<array<string, mixed>>, non_compliant_logs: array<array<string, mixed>>, compliance_score: float}
     */
    public static function checkStructuredLoggingCompliance(array $logRecords, array $schema): array
    {
        $compliantLogs = [];
        $nonCompliantLogs = [];
        
        foreach ($logRecords as $record) {
            $complianceResult = self::validateLogSchema($record, $schema);
            
            if ($complianceResult['compliant']) {
                $compliantLogs[] = $record;
            } else {
                $nonCompliantLogs[] = [
                    'record' => $record,
                    'violations' => $complianceResult['violations'],
                ];
            }
        }
        
        $totalLogs = count($logRecords);
        $complianceScore = $totalLogs > 0 ? (count($compliantLogs) / $totalLogs) * 100 : 0;
        
        return [
            'compliant_logs' => $compliantLogs,
            'non_compliant_logs' => $nonCompliantLogs,
            'compliance_score' => $complianceScore,
        ];
    }

    /**
     * Aggregate and analyze log statistics.
     *
     * @param array<array<string, mixed>> $logRecords Log records to analyze
     * @return array<string, mixed> Comprehensive log statistics
     */
    public static function aggregateLogStatistics(array $logRecords): array
    {
        $statistics = [
            'total_logs' => count($logRecords),
            'level_distribution' => [],
            'time_distribution' => [],
            'component_distribution' => [],
            'error_patterns' => [],
            'performance_metrics' => [],
        ];
        
        $levelCounts = [];
        $componentCounts = [];
        $hourlyDistribution = [];
        $errorPatterns = [];
        
        foreach ($logRecords as $record) {
            // Level distribution
            $level = strtolower($record['level_name'] ?? 'unknown');
            $levelCounts[$level] = ($levelCounts[$level] ?? 0) + 1;
            
            // Component distribution
            $component = $record['context']['component'] ?? $record['channel'] ?? 'unknown';
            $componentCounts[$component] = ($componentCounts[$component] ?? 0) + 1;
            
            // Time distribution
            if (isset($record['datetime'])) {
                $hour = $record['datetime']->format('H');
                $hourlyDistribution[$hour] = ($hourlyDistribution[$hour] ?? 0) + 1;
            }
            
            // Error pattern analysis
            if (in_array($level, ['error', 'critical', 'alert', 'emergency'])) {
                $message = $record['message'] ?? '';
                $pattern = self::extractErrorPattern($message);
                $errorPatterns[$pattern] = ($errorPatterns[$pattern] ?? 0) + 1;
            }
        }
        
        $statistics['level_distribution'] = $levelCounts;
        $statistics['component_distribution'] = $componentCounts;
        $statistics['time_distribution'] = $hourlyDistribution;
        $statistics['error_patterns'] = $errorPatterns;
        
        // Calculate additional metrics
        $statistics['error_rate'] = self::calculateErrorRate($levelCounts, $statistics['total_logs']);
        $statistics['most_active_component'] = self::findMostActive($componentCounts);
        $statistics['peak_hour'] = self::findPeakHour($hourlyDistribution);
        
        return $statistics;
    }

    /**
     * Validate log level hierarchy and consistency.
     *
     * @param array<array<string, mixed>> $logRecords Log records to validate
     * @return array{hierarchy_violations: array<array<string, mixed>>, consistency_score: float}
     */
    public static function validateLogLevelHierarchy(array $logRecords): array
    {
        $violations = [];
        $totalChecks = 0;
        $validChecks = 0;
        
        // Sort records by timestamp
        usort($logRecords, function ($a, $b) {
            $timeA = $a['datetime'] ?? new \DateTime();
            $timeB = $b['datetime'] ?? new \DateTime();
            return $timeA <=> $timeB;
        });
        
        for ($i = 1; $i < count($logRecords); $i++) {
            $prevRecord = $logRecords[$i - 1];
            $currentRecord = $logRecords[$i];
            
            $prevLevel = strtolower($prevRecord['level_name'] ?? 'info');
            $currentLevel = strtolower($currentRecord['level_name'] ?? 'info');
            
            $prevPriority = self::$logLevelPriorities[$prevLevel] ?? 200;
            $currentPriority = self::$logLevelPriorities[$currentLevel] ?? 200;
            
            // Check for inappropriate level escalation
            if ($currentPriority > $prevPriority + 200) { // Significant jump
                $timeDiff = self::calculateTimeDifference($prevRecord, $currentRecord);
                
                if ($timeDiff < 1.0) { // Within 1 second
                    $violations[] = [
                        'type' => 'rapid_escalation',
                        'previous_record' => $prevRecord,
                        'current_record' => $currentRecord,
                        'time_difference' => $timeDiff,
                        'level_jump' => $currentPriority - $prevPriority,
                    ];
                }
            }
            
            $totalChecks++;
            if (empty($violations) || count($violations) < $totalChecks * 0.1) {
                $validChecks++;
            }
        }
        
        $consistencyScore = $totalChecks > 0 ? ($validChecks / $totalChecks) * 100 : 100;
        
        return [
            'hierarchy_violations' => $violations,
            'consistency_score' => $consistencyScore,
        ];
    }

    /**
     * Test log rotation and archiving mechanisms.
     *
     * @param string $logPath Path to log file
     * @param array<string, mixed> $rotationConfig Rotation configuration
     * @return array{rotation_working: bool, archive_working: bool, cleanup_working: bool, issues: array<string>}
     */
    public static function testLogRotationMechanisms(string $logPath, array $rotationConfig): array
    {
        $issues = [];
        $rotationWorking = true;
        $archiveWorking = true;
        $cleanupWorking = true;
        
        // Test file size rotation
        if (isset($rotationConfig['max_size'])) {
            $maxSize = $rotationConfig['max_size'];
            
            if (file_exists($logPath)) {
                $currentSize = filesize($logPath);
                
                if ($currentSize > $maxSize) {
                    $issues[] = "Log file exceeds maximum size: {$currentSize} > {$maxSize}";
                    $rotationWorking = false;
                }
            }
        }
        
        // Test archive directory
        if (isset($rotationConfig['archive_path'])) {
            $archivePath = $rotationConfig['archive_path'];
            
            if (!is_dir($archivePath)) {
                $issues[] = "Archive directory does not exist: {$archivePath}";
                $archiveWorking = false;
            } elseif (!is_writable($archivePath)) {
                $issues[] = "Archive directory is not writable: {$archivePath}";
                $archiveWorking = false;
            }
        }
        
        // Test cleanup mechanism
        if (isset($rotationConfig['retention_days'])) {
            $retentionDays = $rotationConfig['retention_days'];
            $archivePath = $rotationConfig['archive_path'] ?? dirname($logPath);
            
            $oldFiles = self::findOldLogFiles($archivePath, $retentionDays);
            
            if (!empty($oldFiles)) {
                $issues[] = "Old log files found that should have been cleaned up: " . implode(', ', $oldFiles);
                $cleanupWorking = false;
            }
        }
        
        return [
            'rotation_working' => $rotationWorking,
            'archive_working' => $archiveWorking,
            'cleanup_working' => $cleanupWorking,
            'issues' => $issues,
        ];
    }

    /**
     * Match a log record against a pattern specification.
     *
     * @param array<string, mixed> $record Log record
     * @param array<string, mixed> $patternSpec Pattern specification
     * @return array{matched: bool, details: array<string, mixed>, reasons: array<string>}
     */
    private static function matchLogRecord(array $record, array $patternSpec): array
    {
        $matched = true;
        $details = [];
        $reasons = [];
        
        // Check level
        if (isset($patternSpec['level'])) {
            $expectedLevel = strtolower($patternSpec['level']);
            $actualLevel = strtolower($record['level_name'] ?? '');
            
            if ($actualLevel !== $expectedLevel) {
                $matched = false;
                $reasons[] = "Level mismatch: expected '{$expectedLevel}', got '{$actualLevel}'";
            } else {
                $details['level_match'] = true;
            }
        }
        
        // Check message pattern
        if (isset($patternSpec['message_pattern'])) {
            $pattern = $patternSpec['message_pattern'];
            $message = $record['message'] ?? '';
            
            if (!preg_match($pattern, $message)) {
                $matched = false;
                $reasons[] = "Message pattern mismatch: '{$pattern}' not found in '{$message}'";
            } else {
                $details['message_match'] = true;
            }
        }
        
        // Check context requirements
        if (isset($patternSpec['context'])) {
            $requiredContext = $patternSpec['context'];
            $actualContext = $record['context'] ?? [];
            
            foreach ($requiredContext as $key => $expectedValue) {
                if (!isset($actualContext[$key])) {
                    $matched = false;
                    $reasons[] = "Missing context key: '{$key}'";
                } elseif ($actualContext[$key] !== $expectedValue) {
                    $matched = false;
                    $reasons[] = "Context value mismatch for '{$key}': expected '{$expectedValue}', got '{$actualContext[$key]}'";
                } else {
                    $details['context_matches'][$key] = true;
                }
            }
        }
        
        return [
            'matched' => $matched,
            'details' => $details,
            'reasons' => $reasons,
        ];
    }

    /**
     * Measure operation performance with detailed metrics.
     *
     * @param callable $operation Operation to measure
     * @param int $iterations Number of iterations
     * @return array<string, float> Performance metrics
     */
    private static function measureOperationPerformance(callable $operation, int $iterations): array
    {
        $times = [];
        $memoryUsages = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            $startMemory = memory_get_usage(true);
            
            $operation();
            
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            
            $times[] = $endTime - $startTime;
            $memoryUsages[] = $endMemory - $startMemory;
        }
        
        return [
            'avg_time' => array_sum($times) / count($times),
            'min_time' => min($times),
            'max_time' => max($times),
            'avg_memory' => array_sum($memoryUsages) / count($memoryUsages),
            'min_memory' => min($memoryUsages),
            'max_memory' => max($memoryUsages),
            'total_iterations' => $iterations,
        ];
    }

    /**
     * Calculate performance impact between baseline and logging measurements.
     *
     * @param array<string, float> $baseline Baseline metrics
     * @param array<string, float> $withLogging Metrics with logging
     * @return array<string, mixed> Impact analysis
     */
    private static function calculatePerformanceImpact(array $baseline, array $withLogging): array
    {
        $timeImpact = (($withLogging['avg_time'] - $baseline['avg_time']) / $baseline['avg_time']) * 100;
        $memoryImpact = (($withLogging['avg_memory'] - $baseline['avg_memory']) / max($baseline['avg_memory'], 1)) * 100;
        
        return [
            'time_impact_percent' => $timeImpact,
            'memory_impact_percent' => $memoryImpact,
            'time_overhead_ms' => ($withLogging['avg_time'] - $baseline['avg_time']) * 1000,
            'memory_overhead_bytes' => $withLogging['avg_memory'] - $baseline['avg_memory'],
            'performance_rating' => self::calculatePerformanceRating($timeImpact, $memoryImpact),
        ];
    }

    /**
     * Calculate performance rating based on impact percentages.
     *
     * @param float $timeImpact Time impact percentage
     * @param float $memoryImpact Memory impact percentage
     * @return string Performance rating
     */
    private static function calculatePerformanceRating(float $timeImpact, float $memoryImpact): string
    {
        $maxImpact = max($timeImpact, $memoryImpact);
        
        return match (true) {
            $maxImpact < 5 => 'excellent',
            $maxImpact < 15 => 'good',
            $maxImpact < 30 => 'acceptable',
            $maxImpact < 50 => 'poor',
            default => 'critical',
        };
    }

    /**
     * Extract correlation ID from log record.
     *
     * @param array<string, mixed> $record Log record
     * @param string $correlationField Correlation field name
     * @return string|null Correlation ID or null if not found
     */
    private static function extractCorrelationId(array $record, string $correlationField): ?string
    {
        // Check in context first
        if (isset($record['context'][$correlationField])) {
            return (string)$record['context'][$correlationField];
        }
        
        // Check in extra fields
        if (isset($record['extra'][$correlationField])) {
            return (string)$record['extra'][$correlationField];
        }
        
        // Check in message for correlation patterns
        $message = $record['message'] ?? '';
        if (preg_match('/\b' . preg_quote($correlationField) . '[:\s=]+([a-zA-Z0-9\-_]+)/', $message, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Find related logs by correlation ID within time window.
     *
     * @param array<array<string, mixed>> $logRecords All log records
     * @param string $correlationId Correlation ID to search for
     * @param string $correlationField Correlation field name
     * @param int $timeWindow Time window in seconds
     * @return array<array<string, mixed>> Related log records
     */
    private static function findRelatedLogs(array $logRecords, string $correlationId, string $correlationField, int $timeWindow): array
    {
        $relatedLogs = [];
        $baseTime = null;
        
        foreach ($logRecords as $record) {
            $recordCorrelationId = self::extractCorrelationId($record, $correlationField);
            
            if ($recordCorrelationId === $correlationId) {
                $recordTime = $record['datetime'] ?? new \DateTime();
                
                if ($baseTime === null) {
                    $baseTime = $recordTime;
                    $relatedLogs[] = $record;
                } else {
                    $timeDiff = abs($recordTime->getTimestamp() - $baseTime->getTimestamp());
                    
                    if ($timeDiff <= $timeWindow) {
                        $relatedLogs[] = $record;
                    }
                }
            }
        }
        
        return $relatedLogs;
    }

    /**
     * Validate log record against schema.
     *
     * @param array<string, mixed> $record Log record
     * @param array<string, mixed> $schema Expected schema
     * @return array{compliant: bool, violations: array<string>}
     */
    private static function validateLogSchema(array $record, array $schema): array
    {
        $violations = [];
        $compliant = true;
        
        // Check required fields
        if (isset($schema['required_fields'])) {
            foreach ($schema['required_fields'] as $field) {
                if (!isset($record[$field])) {
                    $violations[] = "Missing required field: {$field}";
                    $compliant = false;
                }
            }
        }
        
        // Check field types
        if (isset($schema['field_types'])) {
            foreach ($schema['field_types'] as $field => $expectedType) {
                if (isset($record[$field])) {
                    $actualType = gettype($record[$field]);
                    
                    if ($actualType !== $expectedType) {
                        $violations[] = "Field '{$field}' type mismatch: expected {$expectedType}, got {$actualType}";
                        $compliant = false;
                    }
                }
            }
        }
        
        return [
            'compliant' => $compliant,
            'violations' => $violations,
        ];
    }

    /**
     * Extract error pattern from error message.
     *
     * @param string $message Error message
     * @return string Error pattern
     */
    private static function extractErrorPattern(string $message): string
    {
        // Remove specific details to create a pattern
        $pattern = preg_replace('/\b\d+\b/', 'N', $message); // Replace numbers
        $pattern = preg_replace('/\b[a-f0-9]{8,}\b/i', 'HEX', $pattern); // Replace hex values
        $pattern = preg_replace('/\b\w+@\w+\.\w+\b/', 'EMAIL', $pattern); // Replace emails
        $pattern = preg_replace('/\bhttps?:\/\/\S+\b/', 'URL', $pattern); // Replace URLs
        
        return $pattern;
    }

    /**
     * Calculate error rate from level counts.
     *
     * @param array<string, int> $levelCounts Level distribution
     * @param int $totalLogs Total number of logs
     * @return float Error rate percentage
     */
    private static function calculateErrorRate(array $levelCounts, int $totalLogs): float
    {
        $errorLevels = ['error', 'critical', 'alert', 'emergency'];
        $errorCount = 0;
        
        foreach ($errorLevels as $level) {
            $errorCount += $levelCounts[$level] ?? 0;
        }
        
        return $totalLogs > 0 ? ($errorCount / $totalLogs) * 100 : 0;
    }

    /**
     * Find most active component.
     *
     * @param array<string, int> $componentCounts Component distribution
     * @return string Most active component
     */
    private static function findMostActive(array $componentCounts): string
    {
        if (empty($componentCounts)) {
            return 'none';
        }
        
        return array_key_first(array_slice(arsort($componentCounts) ? $componentCounts : [], 0, 1, true));
    }

    /**
     * Find peak hour from time distribution.
     *
     * @param array<string, int> $hourlyDistribution Hourly distribution
     * @return string Peak hour
     */
    private static function findPeakHour(array $hourlyDistribution): string
    {
        if (empty($hourlyDistribution)) {
            return 'none';
        }
        
        arsort($hourlyDistribution);
        return array_key_first($hourlyDistribution) . ':00';
    }

    /**
     * Calculate time difference between log records.
     *
     * @param array<string, mixed> $record1 First record
     * @param array<string, mixed> $record2 Second record
     * @return float Time difference in seconds
     */
    private static function calculateTimeDifference(array $record1, array $record2): float
    {
        $time1 = $record1['datetime'] ?? new \DateTime();
        $time2 = $record2['datetime'] ?? new \DateTime();
        
        return abs($time2->getTimestamp() - $time1->getTimestamp());
    }

    /**
     * Find old log files that should have been cleaned up.
     *
     * @param string $directory Directory to search
     * @param int $retentionDays Retention period in days
     * @return array<string> Old log files
     */
    private static function findOldLogFiles(string $directory, int $retentionDays): array
    {
        $oldFiles = [];
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        
        if (!is_dir($directory)) {
            return $oldFiles;
        }
        
        $files = glob($directory . '/*.log*');
        
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            
            if ($fileTime !== false && $fileTime < $cutoffTime) {
                $oldFiles[] = basename($file);
            }
        }
        
        return $oldFiles;
    }
}