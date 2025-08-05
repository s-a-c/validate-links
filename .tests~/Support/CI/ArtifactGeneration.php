<?php

declare(strict_types=1);

namespace Tests\Support\CI;

use Exception;
use RuntimeException;

/**
 * Comprehensive artifact generation for CI/CD pipelines with support for
 * multiple formats, automated report generation, and intelligent artifact
 * management with retention policies and optimization.
 * 
 * @package Tests\Support\CI
 * @author Roo
 * @since 1.0.0
 */
final class ArtifactGeneration
{
    /**
     * Artifact generation configuration.
     * 
     * @var array<string, mixed>
     */
    private static array $config = [
        'output_directory' => 'reports/artifacts',
        'enable_compression' => true,
        'enable_encryption' => false,
        'retention_days' => 30,
        'max_artifact_size' => 100 * 1024 * 1024, // 100MB
        'supported_formats' => ['json', 'xml', 'html', 'csv', 'yaml'],
        'enable_versioning' => true,
        'enable_metadata' => true,
    ];
    
    /**
     * Generated artifacts registry.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $artifactRegistry = [];
    
    /**
     * Artifact templates for different report types.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $templates = [
        'test_results' => [
            'filename' => 'test-results',
            'formats' => ['json', 'xml', 'html'],
            'schema_version' => '1.0',
            'required_fields' => ['timestamp', 'environment', 'results', 'summary'],
        ],
        'coverage_report' => [
            'filename' => 'coverage-report',
            'formats' => ['html', 'xml', 'json'],
            'schema_version' => '1.0',
            'required_fields' => ['timestamp', 'coverage_percentage', 'files', 'summary'],
        ],
        'performance_report' => [
            'filename' => 'performance-report',
            'formats' => ['json', 'html', 'csv'],
            'schema_version' => '1.0',
            'required_fields' => ['timestamp', 'benchmarks', 'metrics', 'recommendations'],
        ],
        'quality_report' => [
            'filename' => 'quality-report',
            'formats' => ['json', 'html'],
            'schema_version' => '1.0',
            'required_fields' => ['timestamp', 'quality_score', 'metrics', 'violations'],
        ],
        'deployment_report' => [
            'filename' => 'deployment-report',
            'formats' => ['json', 'html'],
            'schema_version' => '1.0',
            'required_fields' => ['timestamp', 'environment', 'gates', 'status'],
        ],
    ];
    
    /**
     * Initialize artifact generation system.
     * 
     * @param array<string, mixed> $config Configuration overrides
     * @return void
     */
    public static function initialize(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$artifactRegistry = [];
        
        // Ensure output directory exists
        self::ensureOutputDirectory();
        
        // Clean up old artifacts if retention policy is enabled
        if (self::$config['retention_days'] > 0) {
            self::cleanupOldArtifacts();
        }
    }
    
    /**
     * Generate comprehensive test results artifact.
     * 
     * @param array<string, mixed> $testResults Test execution results
     * @param array<string> $formats Output formats
     * @return array<string, string> Generated artifact paths
     */
    public static function generateTestResultsArtifact(array $testResults, array $formats = ['json', 'html']): array
    {
        $template = self::$templates['test_results'];
        $artifacts = [];
        
        // Validate required fields
        self::validateRequiredFields($testResults, $template['required_fields']);
        
        // Enhance test results with metadata
        $enhancedResults = self::enhanceTestResults($testResults);
        
        foreach ($formats as $format) {
            if (!in_array($format, $template['formats'], true)) {
                continue;
            }
            
            $filename = self::generateFilename($template['filename'], $format);
            $filepath = self::getOutputPath($filename);
            
            try {
                switch ($format) {
                    case 'json':
                        $content = self::generateJsonTestResults($enhancedResults);
                        break;
                    case 'xml':
                        $content = self::generateXmlTestResults($enhancedResults);
                        break;
                    case 'html':
                        $content = self::generateHtmlTestResults($enhancedResults);
                        break;
                    default:
                        throw new RuntimeException("Unsupported format: {$format}");
                }
                
                self::writeArtifact($filepath, $content);
                $artifacts[$format] = $filepath;
                
                // Register artifact
                self::registerArtifact('test_results', $format, $filepath, $enhancedResults);
                
            } catch (Exception $e) {
                error_log("Failed to generate test results artifact in {$format} format: " . $e->getMessage());
            }
        }
        
        return $artifacts;
    }
    
    /**
     * Generate coverage report artifact.
     * 
     * @param array<string, mixed> $coverageData Coverage data
     * @param array<string> $formats Output formats
     * @return array<string, string> Generated artifact paths
     */
    public static function generateCoverageArtifact(array $coverageData, array $formats = ['html', 'xml']): array
    {
        $template = self::$templates['coverage_report'];
        $artifacts = [];
        
        // Validate required fields
        self::validateRequiredFields($coverageData, $template['required_fields']);
        
        // Enhance coverage data
        $enhancedData = self::enhanceCoverageData($coverageData);
        
        foreach ($formats as $format) {
            if (!in_array($format, $template['formats'], true)) {
                continue;
            }
            
            $filename = self::generateFilename($template['filename'], $format);
            $filepath = self::getOutputPath($filename);
            
            try {
                switch ($format) {
                    case 'html':
                        $content = self::generateHtmlCoverageReport($enhancedData);
                        break;
                    case 'xml':
                        $content = self::generateXmlCoverageReport($enhancedData);
                        break;
                    case 'json':
                        $content = self::generateJsonCoverageReport($enhancedData);
                        break;
                    default:
                        throw new RuntimeException("Unsupported format: {$format}");
                }
                
                self::writeArtifact($filepath, $content);
                $artifacts[$format] = $filepath;
                
                // Register artifact
                self::registerArtifact('coverage_report', $format, $filepath, $enhancedData);
                
            } catch (Exception $e) {
                error_log("Failed to generate coverage artifact in {$format} format: " . $e->getMessage());
            }
        }
        
        return $artifacts;
    }
    
    /**
     * Generate performance report artifact.
     * 
     * @param array<string, mixed> $performanceData Performance metrics
     * @param array<string> $formats Output formats
     * @return array<string, string> Generated artifact paths
     */
    public static function generatePerformanceArtifact(array $performanceData, array $formats = ['json', 'html']): array
    {
        $template = self::$templates['performance_report'];
        $artifacts = [];
        
        // Validate required fields
        self::validateRequiredFields($performanceData, $template['required_fields']);
        
        // Enhance performance data
        $enhancedData = self::enhancePerformanceData($performanceData);
        
        foreach ($formats as $format) {
            if (!in_array($format, $template['formats'], true)) {
                continue;
            }
            
            $filename = self::generateFilename($template['filename'], $format);
            $filepath = self::getOutputPath($filename);
            
            try {
                switch ($format) {
                    case 'json':
                        $content = self::generateJsonPerformanceReport($enhancedData);
                        break;
                    case 'html':
                        $content = self::generateHtmlPerformanceReport($enhancedData);
                        break;
                    case 'csv':
                        $content = self::generateCsvPerformanceReport($enhancedData);
                        break;
                    default:
                        throw new RuntimeException("Unsupported format: {$format}");
                }
                
                self::writeArtifact($filepath, $content);
                $artifacts[$format] = $filepath;
                
                // Register artifact
                self::registerArtifact('performance_report', $format, $filepath, $enhancedData);
                
            } catch (Exception $e) {
                error_log("Failed to generate performance artifact in {$format} format: " . $e->getMessage());
            }
        }
        
        return $artifacts;
    }
    
    /**
     * Generate quality metrics artifact.
     * 
     * @param array<string, mixed> $qualityData Quality metrics
     * @param array<string> $formats Output formats
     * @return array<string, string> Generated artifact paths
     */
    public static function generateQualityArtifact(array $qualityData, array $formats = ['json', 'html']): array
    {
        $template = self::$templates['quality_report'];
        $artifacts = [];
        
        // Validate required fields
        self::validateRequiredFields($qualityData, $template['required_fields']);
        
        // Enhance quality data
        $enhancedData = self::enhanceQualityData($qualityData);
        
        foreach ($formats as $format) {
            if (!in_array($format, $template['formats'], true)) {
                continue;
            }
            
            $filename = self::generateFilename($template['filename'], $format);
            $filepath = self::getOutputPath($filename);
            
            try {
                switch ($format) {
                    case 'json':
                        $content = self::generateJsonQualityReport($enhancedData);
                        break;
                    case 'html':
                        $content = self::generateHtmlQualityReport($enhancedData);
                        break;
                    default:
                        throw new RuntimeException("Unsupported format: {$format}");
                }
                
                self::writeArtifact($filepath, $content);
                $artifacts[$format] = $filepath;
                
                // Register artifact
                self::registerArtifact('quality_report', $format, $filepath, $enhancedData);
                
            } catch (Exception $e) {
                error_log("Failed to generate quality artifact in {$format} format: " . $e->getMessage());
            }
        }
        
        return $artifacts;
    }
    
    /**
     * Generate deployment gate artifact.
     * 
     * @param array<string, mixed> $gateResults Gate validation results
     * @param array<string> $formats Output formats
     * @return array<string, string> Generated artifact paths
     */
    public static function generateDeploymentArtifact(array $gateResults, array $formats = ['json', 'html']): array
    {
        $template = self::$templates['deployment_report'];
        $artifacts = [];
        
        // Validate required fields
        self::validateRequiredFields($gateResults, $template['required_fields']);
        
        // Enhance gate results
        $enhancedData = self::enhanceDeploymentData($gateResults);
        
        foreach ($formats as $format) {
            if (!in_array($format, $template['formats'], true)) {
                continue;
            }
            
            $filename = self::generateFilename($template['filename'], $format);
            $filepath = self::getOutputPath($filename);
            
            try {
                switch ($format) {
                    case 'json':
                        $content = self::generateJsonDeploymentReport($enhancedData);
                        break;
                    case 'html':
                        $content = self::generateHtmlDeploymentReport($enhancedData);
                        break;
                    default:
                        throw new RuntimeException("Unsupported format: {$format}");
                }
                
                self::writeArtifact($filepath, $content);
                $artifacts[$format] = $filepath;
                
                // Register artifact
                self::registerArtifact('deployment_report', $format, $filepath, $enhancedData);
                
            } catch (Exception $e) {
                error_log("Failed to generate deployment artifact in {$format} format: " . $e->getMessage());
            }
        }
        
        return $artifacts;
    }
    
    /**
     * Generate comprehensive artifact bundle.
     * 
     * @param array<string, mixed> $allData All test and quality data
     * @return string Path to generated bundle
     */
    public static function generateArtifactBundle(array $allData): string
    {
        $bundleName = 'test-artifacts-' . date('Y-m-d_H-i-s') . '.zip';
        $bundlePath = self::getOutputPath($bundleName);
        
        if (!class_exists('ZipArchive')) {
            throw new RuntimeException('ZipArchive extension is required for bundle generation');
        }
        
        $zip = new \ZipArchive();
        if ($zip->open($bundlePath, \ZipArchive::CREATE) !== true) {
            throw new RuntimeException("Failed to create artifact bundle: {$bundlePath}");
        }
        
        try {
            // Add all registered artifacts to bundle
            foreach (self::$artifactRegistry as $type => $artifacts) {
                foreach ($artifacts as $artifact) {
                    if (file_exists($artifact['filepath'])) {
                        $zip->addFile($artifact['filepath'], basename($artifact['filepath']));
                    }
                }
            }
            
            // Add metadata file
            $metadata = self::generateBundleMetadata($allData);
            $zip->addFromString('metadata.json', json_encode($metadata, JSON_PRETTY_PRINT));
            
            // Add README
            $readme = self::generateBundleReadme();
            $zip->addFromString('README.md', $readme);
            
            $zip->close();
            
            // Register bundle
            self::registerArtifact('bundle', 'zip', $bundlePath, $allData);
            
            return $bundlePath;
            
        } catch (Exception $e) {
            $zip->close();
            if (file_exists($bundlePath)) {
                unlink($bundlePath);
            }
            throw new RuntimeException("Failed to generate artifact bundle: " . $e->getMessage());
        }
    }
    
    /**
     * Get artifact registry.
     * 
     * @return array<string, array<string, mixed>> Artifact registry
     */
    public static function getArtifactRegistry(): array
    {
        return self::$artifactRegistry;
    }
    
    /**
     * Get artifact metadata.
     * 
     * @param string $type Artifact type
     * @param string $format Artifact format
     * @return array<string, mixed>|null Artifact metadata
     */
    public static function getArtifactMetadata(string $type, string $format): ?array
    {
        return self::$artifactRegistry[$type][$format] ?? null;
    }
    
    /**
     * Clean up artifacts older than retention period.
     * 
     * @return int Number of artifacts cleaned up
     */
    public static function cleanupOldArtifacts(): int
    {
        $outputDir = self::getOutputDirectory();
        $retentionDays = self::$config['retention_days'];
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);
        $cleanedCount = 0;
        
        if (!is_dir($outputDir)) {
            return 0;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($outputDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getMTime() < $cutoffTime) {
                if (unlink($file->getPathname())) {
                    $cleanedCount++;
                }
            }
        }
        
        return $cleanedCount;
    }
    
    /**
     * Configure artifact generation.
     * 
     * @param array<string, mixed> $config Configuration overrides
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Generate artifacts from test results.
     *
     * @param array<string, mixed> $data Test results and configuration
     * @return array<string, mixed> Artifact generation results
     */
    public static function generateArtifacts(array $data): array
    {
        $startTime = microtime(true);
        
        $result = [
            'artifacts_generated' => [],
            'generation_status' => 'unknown',
            'formats' => $data['formats'] ?? ['json'],
            'include_metrics' => $data['include_metrics'] ?? false,
            'generation_time' => 0,
            'output_directory' => self::$config['output_directory'],
        ];
        
        try {
            $testResults = $data['test_results'] ?? [];
            $formats = $data['formats'] ?? ['json'];
            
            foreach ($formats as $format) {
                $artifactPath = self::generateArtifactForFormat($format, $testResults);
                if ($artifactPath) {
                    $result['artifacts_generated'][$format] = $artifactPath;
                }
            }
            
            $result['generation_status'] = 'completed';
            
        } catch (\Exception $e) {
            $result['generation_status'] = 'failed';
            $result['error'] = $e->getMessage();
        }
        
        $result['generation_time'] = microtime(true) - $startTime;
        
        return $result;
    }
    
    /**
     * Generate artifact for specific format.
     *
     * @param string $format Output format
     * @param array<string, mixed> $testResults Test results
     * @return string|null Generated artifact path
     */
    private static function generateArtifactForFormat(string $format, array $testResults): ?string
    {
        $outputDir = self::getOutputDirectory();
        $timestamp = date('Y-m-d_H-i-s');
        
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0755, true);
        }
        
        switch ($format) {
            case 'json':
                $filePath = $outputDir . "/test-results-{$timestamp}.json";
                file_put_contents($filePath, json_encode($testResults, JSON_PRETTY_PRINT));
                return $filePath;
                
            case 'html':
                $filePath = $outputDir . "/test-results-{$timestamp}.html";
                $htmlContent = self::generateSimpleHtmlReport($testResults);
                file_put_contents($filePath, $htmlContent);
                return $filePath;
                
            case 'xml':
                $filePath = $outputDir . "/test-results-{$timestamp}.xml";
                $xmlContent = self::generateSimpleXmlReport($testResults);
                file_put_contents($filePath, $xmlContent);
                return $filePath;
                
            default:
                return null;
        }
    }
    
    /**
     * Generate simple HTML report.
     *
     * @param array<string, mixed> $testResults Test results
     * @return string HTML content
     */
    private static function generateSimpleHtmlReport(array $testResults): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Test Results</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Test Results Report</h1>\n";
        $html .= "<p>Generated at: " . date('Y-m-d H:i:s') . "</p>\n";
        $html .= "<pre>" . json_encode($testResults, JSON_PRETTY_PRINT) . "</pre>\n";
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate simple XML report.
     *
     * @param array<string, mixed> $testResults Test results
     * @return string XML content
     */
    private static function generateSimpleXmlReport(array $testResults): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<testResults>\n";
        $xml .= "  <timestamp>" . date('Y-m-d H:i:s') . "</timestamp>\n";
        $xml .= "  <data>" . htmlspecialchars(json_encode($testResults)) . "</data>\n";
        $xml .= "</testResults>\n";
        
        return $xml;
    }
    
    /**
     * Reset artifact generation state.
     *
     * @return void
     */
    public static function reset(): void
    {
        self::$artifactRegistry = [];
    }
    
    /**
     * Ensure output directory exists.
     * 
     * @return void
     */
    private static function ensureOutputDirectory(): void
    {
        $outputDir = self::getOutputDirectory();
        
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new RuntimeException("Failed to create output directory: {$outputDir}");
            }
        }
        
        if (!is_writable($outputDir)) {
            throw new RuntimeException("Output directory is not writable: {$outputDir}");
        }
    }
    
    /**
     * Get output directory path.
     * 
     * @return string Output directory path
     */
    private static function getOutputDirectory(): string
    {
        $baseDir = getcwd();
        $outputDir = self::$config['output_directory'];
        
        if (str_starts_with($outputDir, '/')) {
            return $outputDir;
        }
        
        return $baseDir . '/' . $outputDir;
    }
    
    /**
     * Get full output path for filename.
     * 
     * @param string $filename Filename
     * @return string Full output path
     */
    private static function getOutputPath(string $filename): string
    {
        return self::getOutputDirectory() . '/' . $filename;
    }
    
    /**
     * Generate filename with timestamp and format.
     * 
     * @param string $baseName Base filename
     * @param string $format File format
     * @return string Generated filename
     */
    private static function generateFilename(string $baseName, string $format): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        
        if (self::$config['enable_versioning']) {
            return "{$baseName}_{$timestamp}.{$format}";
        }
        
        return "{$baseName}.{$format}";
    }
    
    /**
     * Write artifact content to file.
     * 
     * @param string $filepath File path
     * @param string $content File content
     * @return void
     */
    private static function writeArtifact(string $filepath, string $content): void
    {
        // Check file size limit
        if (strlen($content) > self::$config['max_artifact_size']) {
            throw new RuntimeException("Artifact size exceeds maximum allowed size");
        }
        
        // Write content
        if (file_put_contents($filepath, $content) === false) {
            throw new RuntimeException("Failed to write artifact: {$filepath}");
        }
        
        // Compress if enabled
        if (self::$config['enable_compression'] && function_exists('gzencode')) {
            $compressedPath = $filepath . '.gz';
            $compressedContent = gzencode($content, 9);
            if ($compressedContent !== false) {
                file_put_contents($compressedPath, $compressedContent);
            }
        }
    }
    
    /**
     * Register generated artifact.
     * 
     * @param string $type Artifact type
     * @param string $format Artifact format
     * @param string $filepath Artifact file path
     * @param array<string, mixed> $data Source data
     * @return void
     */
    private static function registerArtifact(string $type, string $format, string $filepath, array $data): void
    {
        if (!isset(self::$artifactRegistry[$type])) {
            self::$artifactRegistry[$type] = [];
        }
        
        self::$artifactRegistry[$type][$format] = [
            'filepath' => $filepath,
            'format' => $format,
            'size' => file_exists($filepath) ? filesize($filepath) : 0,
            'created_at' => date('Y-m-d H:i:s'),
            'checksum' => file_exists($filepath) ? md5_file($filepath) : null,
            'metadata' => self::$config['enable_metadata'] ? self::generateArtifactMetadata($type, $data) : [],
        ];
    }
    
    /**
     * Generate artifact metadata.
     * 
     * @param string $type Artifact type
     * @param array<string, mixed> $data Source data
     * @return array<string, mixed> Artifact metadata
     */
    private static function generateArtifactMetadata(string $type, array $data): array
    {
        return [
            'type' => $type,
            'schema_version' => self::$templates[$type]['schema_version'] ?? '1.0',
            'generator' => 'ArtifactGeneration',
            'generator_version' => '1.0.0',
            'environment' => getenv('APP_ENV') ?: 'unknown',
            'php_version' => PHP_VERSION,
            'data_summary' => self::generateDataSummary($data),
        ];
    }
    
    /**
     * Generate data summary for metadata.
     * 
     * @param array<string, mixed> $data Source data
     * @return array<string, mixed> Data summary
     */
    private static function generateDataSummary(array $data): array
    {
        return [
            'record_count' => is_countable($data) ? count($data) : 1,
            'data_size' => strlen(serialize($data)),
            'has_errors' => isset($data['errors']) && !empty($data['errors']),
            'timestamp' => $data['timestamp'] ?? date('Y-m-d H:i:s'),
        ];
    }
    
    /**
     * Validate required fields in data.
     * 
     * @param array<string, mixed> $data Data to validate
     * @param array<string> $requiredFields Required field names
     * @return void
     * @throws RuntimeException If required fields are missing
     */
    private static function validateRequiredFields(array $data, array $requiredFields): void
    {
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            throw new RuntimeException('Missing required fields: ' . implode(', ', $missingFields));
        }
    }
    
    /**
     * Enhance test results with additional metadata.
     * 
     * @param array<string, mixed> $testResults Original test results
     * @return array<string, mixed> Enhanced test results
     */
    private static function enhanceTestResults(array $testResults): array
    {
        $enhanced = $testResults;
        
        // Add generation metadata
        $enhanced['metadata'] = [
            'generated_at' => date('Y-m-d H:i:s'),
            'generator' => 'ArtifactGeneration',
            'version' => '1.0.0',
            'environment' => getenv('APP_ENV') ?: 'unknown',
        ];
        
        // Calculate additional metrics
        if (isset($testResults['total_tests'], $testResults['passed_tests'])) {
            $enhanced['calculated_metrics'] = [
                'success_rate' => $testResults['total_tests'] > 0 
                    ? ($testResults['passed_tests'] / $testResults['total_tests']) * 100 
                    : 0,
                'failure_rate' => $testResults['total_tests'] > 0 
                    ? (($testResults['failed_tests'] ?? 0) / $testResults['total_tests']) * 100 
                    : 0,
            ];
        }
        
        return $enhanced;
    }
    
    /**
     * Enhance coverage data with additional metadata.
     * 
     * @param array<string, mixed> $coverageData Original coverage data
     * @return array<string, mixed> Enhanced coverage data
     */
    private static function enhanceCoverageData(array $coverageData): array
    {
        $enhanced = $coverageData;
        
        // Add generation metadata
        $enhanced['metadata'] = [
            'generated_at' => date('Y-m-d H:i:s'),
            'generator' => 'ArtifactGeneration',
            'version' => '1.0.0',
            'environment' => getenv('APP_ENV') ?: 'unknown',
        ];
        
        return $enhanced;
    }
    
    /**
     * Enhance performance data with additional metadata.
     * 
     * @param array<string, mixed> $performanceData Original performance data
     * @return array<string, mixed> Enhanced performance data
     */
    private static function enhancePerformanceData(array $performanceData): array
    {
        $enhanced = $performanceData;
        
        // Add generation metadata
        $enhanced['metadata'] = [
            'generated_at' => date('Y-m-d H:i:s'),
            'generator' => 'ArtifactGeneration',
            'version' => '1.0.0',
            'environment' => getenv('APP_ENV') ?: 'unknown',
        ];
        
        return $enhanced;
    }
    
    /**
     * Enhance quality data with additional metadata.
     * 
     * @param array<string, mixed> $qualityData Original quality data
     * @return array<string, mixed> Enhanced quality data
     */
    private static function enhanceQualityData(array $qualityData): array
    {
        $enhanced = $qualityData;
        
        // Add generation metadata
        $enhanced['metadata'] = [
            'generated_at' => date('Y-m-d H:i:s'),
            'generator' => 'ArtifactGeneration',
            'version' => '1.0.0',
            'environment' => getenv('APP_ENV') ?: 'unknown',
        ];
        
        return $enhanced;
    }
    
    /**
     * Enhance deployment data with additional metadata.
     * 
     * @param array<string, mixed> $deploymentData Original deployment data
     * @return array<string, mixed> Enhanced deployment data
     */
    private static function enhanceDeploymentData(array $deploymentData): array
    {
        $enhanced = $deploymentData;
        
        // Add generation metadata
        $enhanced['metadata'] = [
            'generated_at' => date('Y-m-d H:i:s'),
            'generator' => 'ArtifactGeneration',
            'version' => '1.0.0',
            'environment' => getenv('APP_ENV') ?: 'unknown',
        ];
        
        return $enhanced;
    }
    
    /**
     * Generate JSON test results.
     * 
     * @param array<string, mixed> $data Test results data
     * @return string JSON content
     */
    private static function generateJsonTestResults(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Generate XML test results.
     * 
     * @param array<string, mixed> $data Test results data
     * @return string XML content
     */
    private static function generateXmlTestResults(array $data): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<testResults>\n";
        $xml .= "  <summary>\n";
        $xml .= "    <totalTests>{$data['total_tests']}</totalTests>\n";
        $xml .= "    <passedTests>{$data['passed_tests']}</passedTests>\n";
        $xml .= "    <failedTests>{$data['failed_tests']}</failedTests>\n";
        $xml .= "    <executionTime>{$data['execution_time']}</executionTime>\n";
        $xml .= "  </summary>\n";
        $xml .= "</testResults>\n";
        
        return $xml;
    }
    
    /**
     * Generate HTML test results.
     * 
     * @param array<string, mixed> $data Test results data
     * @return string HTML content
     */
    private static function generateHtmlTestResults(array $data): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Test Results</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.success{color:green;}.failure{color:red;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Test Results Report</h1>\n";
        $html .= "<p>Generated at: " . ($data['metadata']['generated_at'] ?? date('Y-m-d H:i:s')) . "</p>\n";
        
        $html .= "<h2>Summary</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Metric</th><th>Value</th></tr>\n";
        $html .= "<tr><td>Total Tests</td><td>{$data['total_tests']}</td></tr>\n";
        $html .= "<tr><td>Passed Tests</td><td class='success'>{$data['passed_tests']}</td></tr>\n";
        $html .= "<tr><td>Failed Tests</td><td class='failure'>{$data['failed_tests']}</td></tr>\n";
        $html .= "<tr><td>Execution Time</td><td>" . number_format($data['execution_time'], 2) . "s</td></tr>\n";
        
        if (isset($data['calculated_metrics'])) {
            $html .= "<tr><td>Success Rate</td><td>" . number_format($data['calculated_metrics']['success_rate'], 1) . "%</td></tr>\n";
        }
        
        $html .= "</table>\n";
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate HTML coverage report.
     *
     * @param array<string, mixed> $data Coverage data
     * @return string HTML content
     */
    private static function generateHtmlCoverageReport(array $data): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Coverage Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.high{color:green;}.medium{color:orange;}.low{color:red;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Code Coverage Report</h1>\n";
        $html .= "<p>Generated at: " . ($data['metadata']['generated_at'] ?? date('Y-m-d H:i:s')) . "</p>\n";
        
        $html .= "<h2>Summary</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Metric</th><th>Value</th></tr>\n";
        $html .= "<tr><td>Coverage Percentage</td><td>" . number_format($data['coverage_percentage'], 2) . "%</td></tr>\n";
        $html .= "</table>\n";
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate XML coverage report.
     *
     * @param array<string, mixed> $data Coverage data
     * @return string XML content
     */
    private static function generateXmlCoverageReport(array $data): string
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<coverage generated=\"" . time() . "\">\n";
        $xml .= "  <project timestamp=\"" . time() . "\">\n";
        $xml .= "    <metrics coverage=\"{$data['coverage_percentage']}\"/>\n";
        $xml .= "  </project>\n";
        $xml .= "</coverage>\n";
        
        return $xml;
    }
    
    /**
     * Generate JSON coverage report.
     *
     * @param array<string, mixed> $data Coverage data
     * @return string JSON content
     */
    private static function generateJsonCoverageReport(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Generate JSON performance report.
     *
     * @param array<string, mixed> $data Performance data
     * @return string JSON content
     */
    private static function generateJsonPerformanceReport(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Generate HTML performance report.
     *
     * @param array<string, mixed> $data Performance data
     * @return string HTML content
     */
    private static function generateHtmlPerformanceReport(array $data): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Performance Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.good{color:green;}.warning{color:orange;}.critical{color:red;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Performance Report</h1>\n";
        $html .= "<p>Generated at: " . ($data['metadata']['generated_at'] ?? date('Y-m-d H:i:s')) . "</p>\n";
        
        $html .= "<h2>Benchmarks</h2>\n";
        if (isset($data['benchmarks'])) {
            $html .= "<table>\n";
            $html .= "<tr><th>Benchmark</th><th>Duration</th><th>Status</th></tr>\n";
            foreach ($data['benchmarks'] as $name => $benchmark) {
                $duration = number_format($benchmark['duration'] ?? 0, 3);
                $status = $benchmark['status'] ?? 'unknown';
                $html .= "<tr><td>{$name}</td><td>{$duration}s</td><td>{$status}</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate CSV performance report.
     *
     * @param array<string, mixed> $data Performance data
     * @return string CSV content
     */
    private static function generateCsvPerformanceReport(array $data): string
    {
        $csv = "Benchmark,Duration,Memory,Status\n";
        
        if (isset($data['benchmarks'])) {
            foreach ($data['benchmarks'] as $name => $benchmark) {
                $duration = number_format($benchmark['duration'] ?? 0, 3);
                $memory = number_format(($benchmark['memory'] ?? 0) / 1024 / 1024, 2);
                $status = $benchmark['status'] ?? 'unknown';
                $csv .= "{$name},{$duration},{$memory},{$status}\n";
            }
        }
        
        return $csv;
    }
    
    /**
     * Generate JSON quality report.
     *
     * @param array<string, mixed> $data Quality data
     * @return string JSON content
     */
    private static function generateJsonQualityReport(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Generate HTML quality report.
     *
     * @param array<string, mixed> $data Quality data
     * @return string HTML content
     */
    private static function generateHtmlQualityReport(array $data): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Quality Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.excellent{color:green;}.good{color:blue;}.warning{color:orange;}.critical{color:red;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Quality Report</h1>\n";
        $html .= "<p>Generated at: " . ($data['metadata']['generated_at'] ?? date('Y-m-d H:i:s')) . "</p>\n";
        
        $html .= "<h2>Quality Score</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Metric</th><th>Score</th></tr>\n";
        $html .= "<tr><td>Overall Quality Score</td><td>" . number_format($data['quality_score'], 1) . "%</td></tr>\n";
        $html .= "</table>\n";
        
        if (isset($data['violations']) && !empty($data['violations'])) {
            $html .= "<h2>Violations</h2>\n";
            $html .= "<table>\n";
            $html .= "<tr><th>Type</th><th>Count</th><th>Severity</th></tr>\n";
            foreach ($data['violations'] as $violation) {
                $html .= "<tr><td>{$violation['type']}</td><td>{$violation['count']}</td><td>{$violation['severity']}</td></tr>\n";
            }
            $html .= "</table>\n";
        }
        
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate JSON deployment report.
     *
     * @param array<string, mixed> $data Deployment data
     * @return string JSON content
     */
    private static function generateJsonDeploymentReport(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
    
    /**
     * Generate HTML deployment report.
     *
     * @param array<string, mixed> $data Deployment data
     * @return string HTML content
     */
    private static function generateHtmlDeploymentReport(array $data): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Deployment Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.passed{color:green;}.failed{color:red;}.warning{color:orange;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Deployment Gate Report</h1>\n";
        $html .= "<p>Generated at: " . ($data['metadata']['generated_at'] ?? date('Y-m-d H:i:s')) . "</p>\n";
        
        $html .= "<h2>Gate Status</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Gate</th><th>Status</th><th>Threshold</th><th>Actual</th></tr>\n";
        
        if (isset($data['gates'])) {
            foreach ($data['gates'] as $gate) {
                $status = $gate['passed'] ? 'PASSED' : 'FAILED';
                $statusClass = $gate['passed'] ? 'passed' : 'failed';
                $html .= "<tr><td>{$gate['name']}</td><td class='{$statusClass}'>{$status}</td><td>{$gate['threshold']}</td><td>{$gate['actual']}</td></tr>\n";
            }
        }
        
        $html .= "</table>\n";
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate bundle metadata.
     *
     * @param array<string, mixed> $data All data
     * @return array<string, mixed> Bundle metadata
     */
    private static function generateBundleMetadata(array $data): array
    {
        return [
            'bundle_version' => '1.0.0',
            'generated_at' => date('Y-m-d H:i:s'),
            'generator' => 'ArtifactGeneration',
            'environment' => getenv('APP_ENV') ?: 'unknown',
            'artifacts' => array_keys(self::$artifactRegistry),
            'total_artifacts' => count(self::$artifactRegistry),
            'data_summary' => self::generateDataSummary($data),
        ];
    }
    
    /**
     * Generate bundle README.
     *
     * @return string README content
     */
    private static function generateBundleReadme(): string
    {
        $readme = "# Test Artifacts Bundle\n\n";
        $readme .= "This bundle contains comprehensive test execution artifacts generated by the CI/CD pipeline.\n\n";
        $readme .= "## Contents\n\n";
        
        foreach (self::$artifactRegistry as $type => $artifacts) {
            $readme .= "### {$type}\n\n";
            foreach ($artifacts as $format => $artifact) {
                $filename = basename($artifact['filepath']);
                $readme .= "- `{$filename}` - {$format} format\n";
            }
            $readme .= "\n";
        }
        
        $readme .= "## Metadata\n\n";
        $readme .= "- Generated at: " . date('Y-m-d H:i:s') . "\n";
        $readme .= "- Environment: " . (getenv('APP_ENV') ?: 'unknown') . "\n";
        $readme .= "- Generator: ArtifactGeneration v1.0.0\n";
        
        return $readme;
    }
}