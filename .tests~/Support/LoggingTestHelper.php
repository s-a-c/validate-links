<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\Log;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\LogLevel;

/**
 * Comprehensive logging test helper for Laravel Zero with Log facade mocking.
 *
 * Provides utilities for:
 * - Laravel Zero compatible log capture via facade mocking
 * - Log level validation across all application components
 * - Message content assertion with exact text matching and pattern validation
 * - Structured logging format verification and compliance
 * - Performance impact assessment of logging operations
 * - Log rotation, archiving, and cleanup procedure testing
 *
 * @package Tests\Support
 * @author Roo
 * @since 1.0.0
 */
final class LoggingTestHelper
{
    /** @var TestHandler[] */
    private static array $testHandlers = [];
    
    /** @var array<string, mixed> */
    private static array $originalLogConfig = [];
    
    /** @var array<string, float> */
    private static array $performanceMetrics = [];
    
    /** @var array<string, int> */
    private static array $logCounts = [];
    
    /** @var Logger[] */
    private static array $mockLoggers = [];
    
    /** @var mixed */
    private static $originalLogBinding = null;

    /**
     * Initialize Laravel Zero logging test environment with facade mocking.
     *
     * @param array<string> $channels Channels to capture (default: ['single', 'stderr'])
     * @return void
     */
    public static function initializeLoggingCapture(array $channels = ['single', 'stderr']): void
    {
        // Always reset first to prevent accumulation
        self::resetLoggingEnvironment();
        
        // Store original state
        self::$originalLogBinding = app()->bound('log') ? app('log') : null;
        
        // Clear previous state (redundant but safe)
        self::$testHandlers = [];
        self::$performanceMetrics = [];
        self::$logCounts = [];
        self::$mockLoggers = [];
        
        // Create mock loggers with TestHandlers for each channel
        foreach ($channels as $channel) {
            $testHandler = new TestHandler();
            self::$testHandlers[$channel] = $testHandler;
            
            $mockLogger = new Logger($channel);
            $mockLogger->pushHandler($testHandler);
            self::$mockLoggers[$channel] = $mockLogger;
        }
        
        // Create a mock LogManager that returns our test loggers
        $mockLogManager = new class(self::$mockLoggers) {
            private array $loggers;
            private string $defaultChannel = 'single';
            
            public function __construct(array $loggers)
            {
                $this->loggers = $loggers;
            }
            
            public function channel(string $channel = null)
            {
                $channel = $channel ?? $this->defaultChannel;
                return $this->loggers[$channel] ?? $this->loggers[$this->defaultChannel];
            }
            
            public function driver(string $driver = null)
            {
                return $this->channel($driver);
            }
            
            public function getDefaultDriver(): string
            {
                return $this->defaultChannel;
            }
            
            // Forward all log methods to the default channel
            public function emergency($message, array $context = []): void
            {
                $this->channel()->emergency($message, $context);
            }
            
            public function alert($message, array $context = []): void
            {
                $this->channel()->alert($message, $context);
            }
            
            public function critical($message, array $context = []): void
            {
                $this->channel()->critical($message, $context);
            }
            
            public function error($message, array $context = []): void
            {
                $this->channel()->error($message, $context);
            }
            
            public function warning($message, array $context = []): void
            {
                $this->channel()->warning($message, $context);
            }
            
            public function notice($message, array $context = []): void
            {
                $this->channel()->notice($message, $context);
            }
            
            public function info($message, array $context = []): void
            {
                $this->channel()->info($message, $context);
            }
            
            public function debug($message, array $context = []): void
            {
                $this->channel()->debug($message, $context);
            }
            
            public function log($level, $message, array $context = []): void
            {
                $this->channel()->log($level, $message, $context);
            }
        };
        
        // Replace the Log facade binding
        app()->instance('log', $mockLogManager);
        Log::swap($mockLogManager);
    }

    /**
     * Capture log output from a callable with performance monitoring.
     *
     * @param callable $callback The code to execute while capturing logs
     * @param string $channel The log channel to capture from
     * @return array{logs: array<array<string, mixed>>, performance: array<string, float>}
     */
    public static function captureLogOutput(callable $callback, string $channel = 'single'): array
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Ensure test handler exists
        if (!isset(self::$testHandlers[$channel])) {
            self::initializeLoggingCapture([$channel]);
        }
        
        $testHandler = self::$testHandlers[$channel];
        $testHandler->clear();
        
        // Execute the callback
        $result = $callback();
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        // Collect performance metrics and convert LogRecord objects to arrays
        $records = $testHandler->getRecords();
        $arrayRecords = [];
        
        foreach ($records as $record) {
            if ($record instanceof \Monolog\LogRecord) {
                // Convert LogRecord to array format for compatibility
                $arrayRecords[] = [
                    'message' => $record->message,
                    'level_name' => $record->level->getName(),
                    'level' => $record->level->value,
                    'datetime' => $record->datetime,
                    'context' => $record->context,
                    'extra' => $record->extra,
                    'channel' => $record->channel,
                ];
            } else {
                // Already an array
                $arrayRecords[] = $record;
            }
        }
        
        $performance = [
            'execution_time' => $endTime - $startTime,
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(true),
            'log_count' => count($arrayRecords),
        ];
        
        self::$performanceMetrics[$channel] = $performance;
        
        return [
            'logs' => $arrayRecords,
            'performance' => $performance,
            'result' => $result,
        ];
    }

    /**
     * Validate log level usage across captured logs.
     *
     * @param string $expectedLevel Expected log level (debug, info, warning, error, critical)
     * @param string $channel Log channel to validate
     * @return bool True if all logs match expected level
     */
    public static function validateLogLevel(string $expectedLevel, string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        $records = self::$testHandlers[$channel]->getRecords();
        
        foreach ($records as $record) {
            if (strtolower($record['level_name']) !== strtolower($expectedLevel)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Assert specific log message with exact text matching.
     *
     * @param string $expectedMessage Expected log message
     * @param string $level Expected log level
     * @param string $channel Log channel to search
     * @return bool True if message found at specified level
     */
    public static function assertLogMessage(string $expectedMessage, string $level = 'info', string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        $records = self::$testHandlers[$channel]->getRecords();
        
        foreach ($records as $record) {
            if (strtolower($record['level_name']) === strtolower($level) && 
                $record['message'] === $expectedMessage) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Assert log message pattern with regex validation.
     *
     * @param string $pattern Regex pattern to match
     * @param string $level Expected log level
     * @param string $channel Log channel to search
     * @return bool True if pattern matches any log message
     */
    public static function assertLogMessagePattern(string $pattern, string $level = 'info', string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        $records = self::$testHandlers[$channel]->getRecords();
        
        foreach ($records as $record) {
            if (strtolower($record['level_name']) === strtolower($level) && 
                preg_match($pattern, $record['message'])) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Verify structured logging format consistency.
     *
     * @param array<string, mixed> $expectedStructure Expected log structure
     * @param string $channel Log channel to validate
     * @return bool True if all logs match expected structure
     */
    public static function verifyLogFormat(array $expectedStructure, string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        $records = self::$testHandlers[$channel]->getRecords();
        
        foreach ($records as $record) {
            foreach ($expectedStructure as $key => $expectedType) {
                if (!isset($record[$key])) {
                    return false;
                }
                
                $actualType = gettype($record[$key]);
                if ($actualType !== $expectedType) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Validate log context and metadata inclusion.
     *
     * @param array<string, mixed> $expectedContext Expected context data
     * @param string $channel Log channel to validate
     * @return bool True if context matches expectations
     */
    public static function validateLogContext(array $expectedContext, string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        $records = self::$testHandlers[$channel]->getRecords();
        
        foreach ($records as $record) {
            $context = $record['context'] ?? [];
            
            foreach ($expectedContext as $key => $expectedValue) {
                if (!isset($context[$key]) || $context[$key] !== $expectedValue) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Test log filtering and conditional logging behavior.
     *
     * @param string $minLevel Minimum log level to filter
     * @param string $channel Log channel to test
     * @return bool True if filtering works correctly
     */
    public static function testLogFiltering(string $minLevel, string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        // Use Monolog's actual level values
        $levelPriorities = [
            'debug' => 100,
            'info' => 200,
            'notice' => 250,
            'warning' => 300,
            'error' => 400,
            'critical' => 500,
            'alert' => 550,
            'emergency' => 600,
        ];
        
        $minPriority = $levelPriorities[strtolower($minLevel)] ?? 200;
        $records = self::getLogRecords($channel);
        
        // If no records, filtering is technically working (nothing to filter)
        if (empty($records)) {
            return true;
        }
        
        // Check that all records are at or above the minimum level
        foreach ($records as $record) {
            $recordPriority = $record['level'] ?? 200;
            if ($recordPriority < $minPriority) {
                return false; // Found log below minimum level
            }
        }
        
        return true;
    }

    /**
     * Assess performance impact of logging operations.
     *
     * @param string $channel Log channel to assess
     * @return array<string, mixed> Performance assessment results
     */
    public static function assessLoggingPerformance(string $channel = 'single'): array
    {
        $metrics = self::$performanceMetrics[$channel] ?? [];
        
        $assessment = [
            'execution_time' => $metrics['execution_time'] ?? 0.0,
            'memory_usage' => $metrics['memory_usage'] ?? 0,
            'peak_memory' => $metrics['peak_memory'] ?? 0,
            'log_count' => $metrics['log_count'] ?? 0,
            'avg_time_per_log' => 0.0,
            'memory_per_log' => 0.0,
            'performance_rating' => 'unknown',
        ];
        
        if ($assessment['log_count'] > 0) {
            $assessment['avg_time_per_log'] = $assessment['execution_time'] / $assessment['log_count'];
            $assessment['memory_per_log'] = $assessment['memory_usage'] / $assessment['log_count'];
        }
        
        // Performance rating based on thresholds
        $avgTime = $assessment['avg_time_per_log'];
        $assessment['performance_rating'] = match (true) {
            $avgTime < 0.001 => 'excellent',
            $avgTime < 0.005 => 'good',
            $avgTime < 0.01 => 'acceptable',
            $avgTime < 0.05 => 'poor',
            default => 'critical',
        };
        
        return $assessment;
    }

    /**
     * Validate timestamp format and timezone consistency.
     *
     * @param string $expectedFormat Expected timestamp format (e.g., 'Y-m-d H:i:s')
     * @param string $expectedTimezone Expected timezone
     * @param string $channel Log channel to validate
     * @return bool True if timestamps are consistent
     */
    public static function validateTimestampFormat(string $expectedFormat, string $expectedTimezone = 'UTC', string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        $records = self::getLogRecords($channel); // Use the converted array format
        
        foreach ($records as $record) {
            $datetime = $record['datetime'] ?? null;
            
            if (!$datetime instanceof \DateTimeInterface) {
                return false;
            }
            
            // Validate format
            $formatted = $datetime->format($expectedFormat);
            $parsed = \DateTime::createFromFormat($expectedFormat, $formatted);
            
            if (!$parsed || $parsed->format($expectedFormat) !== $formatted) {
                return false;
            }
            
            // Validate timezone
            if ($datetime->getTimezone()->getName() !== $expectedTimezone) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Test log correlation and tracing validation.
     *
     * @param string $correlationId Expected correlation ID
     * @param string $channel Log channel to validate
     * @return bool True if correlation is consistent
     */
    public static function validateLogCorrelation(string $correlationId, string $channel = 'single'): bool
    {
        if (!isset(self::$testHandlers[$channel])) {
            return false;
        }
        
        $records = self::getLogRecords($channel); // Use the converted array format
        
        foreach ($records as $record) {
            $context = $record['context'] ?? [];
            $recordCorrelationId = $context['correlation_id'] ?? $context['trace_id'] ?? null;
            
            if ($recordCorrelationId !== $correlationId) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Get all captured log records for a channel.
     *
     * @param string $channel Log channel to retrieve
     * @return array<array<string, mixed>> All log records
     */
    public static function getLogRecords(string $channel = 'single'): array
    {
        if (!isset(self::$testHandlers[$channel])) {
            return [];
        }
        
        $records = self::$testHandlers[$channel]->getRecords();
        $arrayRecords = [];
        
        foreach ($records as $record) {
            if ($record instanceof \Monolog\LogRecord) {
                // Convert LogRecord to array format for compatibility
                $arrayRecords[] = [
                    'message' => $record->message,
                    'level_name' => $record->level->getName(),
                    'level' => $record->level->value,
                    'datetime' => $record->datetime,
                    'context' => $record->context,
                    'extra' => $record->extra,
                    'channel' => $record->channel,
                ];
            } else {
                // Already an array
                $arrayRecords[] = $record;
            }
        }
        
        return $arrayRecords;
    }

    /**
     * Get log records filtered by level.
     *
     * @param string $level Log level to filter by
     * @param string $channel Log channel to search
     * @return array<array<string, mixed>> Filtered log records
     */
    public static function getLogRecordsByLevel(string $level, string $channel = 'single'): array
    {
        $records = self::getLogRecords($channel);
        
        $filtered = array_filter($records, function ($record) use ($level) {
            return strtolower($record['level_name'] ?? '') === strtolower($level);
        });
        
        // Re-index the array to ensure sequential keys
        return array_values($filtered);
    }

    /**
     * Count log records by level.
     *
     * @param string $channel Log channel to count
     * @return array<string, int> Count of records by level
     */
    public static function countLogsByLevel(string $channel = 'single'): array
    {
        $records = self::getLogRecords($channel);
        $counts = [];
        
        foreach ($records as $record) {
            $level = strtolower($record['level_name']);
            $counts[$level] = ($counts[$level] ?? 0) + 1;
        }
        
        return $counts;
    }

    /**
     * Clear all captured logs for a channel.
     *
     * @param string $channel Log channel to clear
     * @return void
     */
    public static function clearLogs(string $channel = 'single'): void
    {
        if (isset(self::$testHandlers[$channel])) {
            self::$testHandlers[$channel]->clear();
        }
    }

    /**
     * Clear all captured logs and reset test environment.
     *
     * @return void
     */
    public static function resetLoggingEnvironment(): void
    {
        // Clear all test handlers
        foreach (self::$testHandlers as $handler) {
            try {
                $handler->clear();
            } catch (\Throwable $e) {
                // Ignore cleanup errors to prevent hanging
            }
        }
        
        // Restore original Log facade binding safely
        try {
            if (self::$originalLogBinding !== null && app()->bound('log')) {
                app()->instance('log', self::$originalLogBinding);
                Log::swap(self::$originalLogBinding);
            }
        } catch (\Throwable $e) {
            // Ignore restoration errors to prevent hanging
        }
        
        // Force reset all state regardless of errors
        self::$testHandlers = [];
        self::$performanceMetrics = [];
        self::$logCounts = [];
        self::$mockLoggers = [];
        self::$originalLogBinding = null;
        
        // Force garbage collection to free memory
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    }

    /**
     * Validate log rotation and archiving behavior.
     *
     * @param string $logPath Path to log file
     * @param int $maxSize Maximum log file size in bytes
     * @return bool True if rotation works correctly
     */
    public static function validateLogRotation(string $logPath, int $maxSize): bool
    {
        if (!file_exists($logPath)) {
            return false;
        }
        
        $fileSize = filesize($logPath);
        
        // Check if file size is within acceptable limits
        return $fileSize <= $maxSize;
    }

    /**
     * Test log cleanup procedures.
     *
     * @param string $logDirectory Directory containing log files
     * @param int $retentionDays Number of days to retain logs
     * @return bool True if cleanup works correctly
     */
    public static function testLogCleanup(string $logDirectory, int $retentionDays): bool
    {
        if (!is_dir($logDirectory)) {
            return false;
        }
        
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $files = glob($logDirectory . '/*.log');
        
        foreach ($files as $file) {
            $fileTime = filemtime($file);
            if ($fileTime !== false && $fileTime < $cutoffTime) {
                // Old file should have been cleaned up
                return false;
            }
        }
        
        return true;
    }
}