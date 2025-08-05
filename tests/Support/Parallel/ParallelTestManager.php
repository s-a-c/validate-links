<?php

declare(strict_types=1);

namespace Tests\Support\Parallel;

/**
 * Parallel Test Manager for PEST
 * 
 * Provides configuration and management for parallel test execution,
 * ensuring proper test isolation, database transaction handling,
 * and parallel-safe test data management.
 */
class ParallelTestManager
{
    /**
     * Parallel execution configuration
     */
    private static array $config = [
        'max_processes' => 4,
        'timeout' => 60,
        'memory_limit' => '512M',
        'isolation_mode' => 'process',
        'database_strategy' => 'transaction',
    ];

    /**
     * Process-specific data
     */
    private static array $processData = [];

    /**
     * Shared resources registry
     */
    private static array $sharedResources = [];

    /**
     * Process synchronization locks
     */
    private static array $locks = [];

    /**
     * Initialize parallel testing environment
     */
    public static function initialize(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
        
        // Set process-specific configuration
        self::setupProcessEnvironment();
        
        // Initialize database connections for parallel execution
        self::initializeDatabaseConnections();
        
        // Setup process isolation
        self::setupProcessIsolation();
    }

    /**
     * Setup process-specific environment
     */
    private static function setupProcessEnvironment(): void
    {
        $processId = getmypid();
        $workerId = $_ENV['PEST_WORKER_ID'] ?? 0;
        
        self::$processData = [
            'process_id' => $processId,
            'worker_id' => $workerId,
            'started_at' => microtime(true),
            'memory_limit' => self::$config['memory_limit'],
        ];
        
        // Set memory limit for this process
        ini_set('memory_limit', self::$config['memory_limit']);
        
        // Set unique temp directory for this process
        $tempDir = sys_get_temp_dir() . '/pest_worker_' . $workerId;
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        putenv("TMPDIR={$tempDir}");
    }

    /**
     * Initialize database connections for parallel execution
     */
    private static function initializeDatabaseConnections(): void
    {
        if (self::$config['database_strategy'] === 'separate_db') {
            // Create separate database for each worker
            self::createWorkerDatabase();
        } elseif (self::$config['database_strategy'] === 'transaction') {
            // Use transaction-based isolation
            self::setupTransactionIsolation();
        }
    }

    /**
     * Create separate database for worker
     */
    private static function createWorkerDatabase(): void
    {
        $workerId = self::$processData['worker_id'];
        $dbName = 'test_worker_' . $workerId;
        
        // Store database name for this worker
        self::$processData['database_name'] = $dbName;
        
        // Update database configuration
        config(['database.connections.testing.database' => $dbName]);
    }

    /**
     * Setup transaction-based isolation
     */
    private static function setupTransactionIsolation(): void
    {
        // Use in-memory SQLite with unique connection per worker
        $workerId = self::$processData['worker_id'];
        $dbPath = ':memory:';
        
        config([
            'database.connections.testing.database' => $dbPath,
            'database.connections.testing.prefix' => 'worker_' . $workerId . '_',
        ]);
    }

    /**
     * Setup process isolation
     */
    private static function setupProcessIsolation(): void
    {
        // Isolate random number generation
        mt_srand(self::$processData['process_id']);
        
        // Setup process-specific cache
        self::setupProcessCache();
        
        // Initialize process-specific session storage
        self::setupProcessSession();
    }

    /**
     * Setup process-specific cache
     */
    private static function setupProcessCache(): void
    {
        $workerId = self::$processData['worker_id'];
        
        config([
            'cache.stores.array.prefix' => 'worker_' . $workerId . '_',
            'cache.stores.file.path' => storage_path('framework/cache/worker_' . $workerId),
        ]);
    }

    /**
     * Setup process-specific session
     */
    private static function setupProcessSession(): void
    {
        $workerId = self::$processData['worker_id'];
        
        config([
            'session.files' => storage_path('framework/sessions/worker_' . $workerId),
        ]);
    }

    /**
     * Check if test is safe for parallel execution
     */
    public static function isParallelSafe(array $testTags): bool
    {
        $unsafeTags = [
            'sequential',
            'external',
            'database_migration',
            'file_system',
            'global_state',
        ];
        
        foreach ($unsafeTags as $unsafeTag) {
            if (in_array($unsafeTag, $testTags, true)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Acquire lock for shared resource
     */
    public static function acquireLock(string $resource, int $timeout = 30): bool
    {
        $lockFile = sys_get_temp_dir() . '/pest_lock_' . md5($resource);
        $startTime = time();
        
        while (time() - $startTime < $timeout) {
            $handle = fopen($lockFile, 'c');
            if ($handle && flock($handle, LOCK_EX | LOCK_NB)) {
                self::$locks[$resource] = $handle;
                return true;
            }
            
            if ($handle) {
                fclose($handle);
            }
            
            usleep(100000); // 100ms
        }
        
        return false;
    }

    /**
     * Release lock for shared resource
     */
    public static function releaseLock(string $resource): bool
    {
        if (!isset(self::$locks[$resource])) {
            return false;
        }
        
        $handle = self::$locks[$resource];
        flock($handle, LOCK_UN);
        fclose($handle);
        
        unset(self::$locks[$resource]);
        
        $lockFile = sys_get_temp_dir() . '/pest_lock_' . md5($resource);
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
        
        return true;
    }

    /**
     * Execute callback with exclusive access to resource
     */
    public static function withExclusiveAccess(string $resource, callable $callback, int $timeout = 30): mixed
    {
        if (!self::acquireLock($resource, $timeout)) {
            throw new \RuntimeException("Failed to acquire lock for resource: {$resource}");
        }
        
        try {
            return $callback();
        } finally {
            self::releaseLock($resource);
        }
    }

    /**
     * Create parallel-safe test data
     */
    public static function createTestData(string $type, array $data = []): array
    {
        $workerId = self::$processData['worker_id'];
        $timestamp = microtime(true);
        
        $baseData = [
            'worker_id' => $workerId,
            'process_id' => self::$processData['process_id'],
            'created_at' => $timestamp,
            'unique_suffix' => $workerId . '_' . (int)($timestamp * 1000),
        ];
        
        return array_merge($baseData, $data);
    }

    /**
     * Get unique identifier for this worker
     */
    public static function getWorkerIdentifier(): string
    {
        return 'worker_' . self::$processData['worker_id'] . '_' . self::$processData['process_id'];
    }

    /**
     * Setup database transaction for test isolation
     */
    public static function beginTestTransaction(): void
    {
        if (self::$config['database_strategy'] === 'transaction') {
            \DB::beginTransaction();
        }
    }

    /**
     * Rollback database transaction for test cleanup
     */
    public static function rollbackTestTransaction(): void
    {
        if (self::$config['database_strategy'] === 'transaction') {
            \DB::rollBack();
        }
    }

    /**
     * Get parallel execution statistics
     */
    public static function getStatistics(): array
    {
        return [
            'worker_id' => self::$processData['worker_id'] ?? 'unknown',
            'process_id' => self::$processData['process_id'] ?? 'unknown',
            'uptime' => microtime(true) - (self::$processData['started_at'] ?? microtime(true)),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'active_locks' => count(self::$locks),
            'shared_resources' => count(self::$sharedResources),
        ];
    }

    /**
     * Register shared resource
     */
    public static function registerSharedResource(string $name, mixed $resource): void
    {
        self::$sharedResources[$name] = $resource;
    }

    /**
     * Get shared resource
     */
    public static function getSharedResource(string $name): mixed
    {
        return self::$sharedResources[$name] ?? null;
    }

    /**
     * Cleanup parallel testing environment
     */
    public static function cleanup(): void
    {
        // Release all locks
        foreach (array_keys(self::$locks) as $resource) {
            self::releaseLock($resource);
        }
        
        // Cleanup worker-specific temp directory
        $workerId = self::$processData['worker_id'] ?? 0;
        $tempDir = sys_get_temp_dir() . '/pest_worker_' . $workerId;
        if (is_dir($tempDir)) {
            self::removeDirectoryRecursively($tempDir);
        }
        
        // Cleanup worker database if using separate databases
        if (self::$config['database_strategy'] === 'separate_db') {
            self::cleanupWorkerDatabase();
        }
        
        // Reset state
        self::$processData = [];
        self::$sharedResources = [];
        self::$locks = [];
    }

    /**
     * Cleanup worker database
     */
    private static function cleanupWorkerDatabase(): void
    {
        $dbName = self::$processData['database_name'] ?? null;
        if ($dbName) {
            // Drop worker database
            \DB::statement("DROP DATABASE IF EXISTS {$dbName}");
        }
    }

    /**
     * Remove directory recursively
     */
    private static function removeDirectoryRecursively(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? self::removeDirectoryRecursively($path) : unlink($path);
        }
        rmdir($dir);
    }

    /**
     * Get optimal number of parallel processes
     */
    public static function getOptimalProcessCount(): int
    {
        $cpuCount = (int) shell_exec('nproc') ?: 4;
        $memoryLimit = self::parseMemoryLimit(ini_get('memory_limit'));
        $processMemory = self::parseMemoryLimit(self::$config['memory_limit']);
        
        $maxProcessesByCpu = $cpuCount;
        $maxProcessesByMemory = $memoryLimit > 0 ? (int) ($memoryLimit / $processMemory) : $cpuCount;
        
        return min($maxProcessesByCpu, $maxProcessesByMemory, self::$config['max_processes']);
    }

    /**
     * Parse memory limit string to bytes
     */
    private static function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $unit = strtolower(substr($limit, -1));
        $value = (int) substr($limit, 0, -1);
        
        switch ($unit) {
            case 'g':
                return $value * 1024 * 1024 * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'k':
                return $value * 1024;
            default:
                return (int) $limit;
        }
    }

    /**
     * Validate parallel testing configuration
     */
    public static function validateConfiguration(): array
    {
        $issues = [];
        
        if (self::$config['max_processes'] < 1) {
            $issues[] = 'max_processes must be at least 1';
        }
        
        if (self::$config['timeout'] < 10) {
            $issues[] = 'timeout should be at least 10 seconds';
        }
        
        if (!in_array(self::$config['isolation_mode'], ['process', 'thread'], true)) {
            $issues[] = 'isolation_mode must be either "process" or "thread"';
        }
        
        if (!in_array(self::$config['database_strategy'], ['transaction', 'separate_db', 'none'], true)) {
            $issues[] = 'database_strategy must be "transaction", "separate_db", or "none"';
        }
        
        return $issues;
    }

    /**
     * Get configuration
     */
    public static function getConfiguration(): array
    {
        return self::$config;
    }

    /**
     * Update configuration
     */
    public static function updateConfiguration(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }
}