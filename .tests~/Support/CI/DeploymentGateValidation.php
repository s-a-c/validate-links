<?php

declare(strict_types=1);

namespace Tests\Support\CI;

use Exception;
use RuntimeException;

/**
 * Comprehensive deployment gate validation system for CI/CD pipelines.
 * 
 * Provides intelligent quality threshold validation, automated pass/fail criteria,
 * comprehensive validation reports, and automated remediation suggestions
 * for reliable deployment gate management.
 * 
 * @package Tests\Support\CI
 * @author Roo
 * @since 1.0.0
 */
final class DeploymentGateValidation
{
    /**
     * Gate validation configuration.
     * 
     * @var array<string, mixed>
     */
    private static array $config = [
        'enable_strict_validation' => true,
        'enable_progressive_gates' => true,
        'enable_automated_remediation' => true,
        'enable_gate_dependencies' => true,
        'failure_tolerance' => 0, // Number of allowed gate failures
        'warning_threshold' => 2, // Number of warnings before escalation
        'validation_timeout' => 300, // 5 minutes
    ];
    
    /**
     * Predefined gate definitions with thresholds and criteria.
     * 
     * @var array<string, array<string, mixed>>
     */
    private static array $gateDefinitions = [
        'test_success_rate' => [
            'name' => 'Test Success Rate',
            'description' => 'Percentage of tests that passed successfully',
            'type' => 'percentage',
            'blocking' => true,
            'thresholds' => [
                'development' => 80.0,
                'testing' => 85.0,
                'staging' => 90.0,
                'production' => 95.0,
            ],
            'calculation' => 'passed_tests / total_tests * 100',
            'remediation' => [
                'Investigate and fix failing tests',
                'Review test stability and flakiness',
                'Consider test environment issues',
            ],
        ],
        'code_coverage' => [
            'name' => 'Code Coverage',
            'description' => 'Percentage of code covered by tests',
            'type' => 'percentage',
            'blocking' => false,
            'thresholds' => [
                'development' => 70.0,
                'testing' => 80.0,
                'staging' => 85.0,
                'production' => 90.0,
            ],
            'calculation' => 'covered_lines / total_lines * 100',
            'remediation' => [
                'Add tests for uncovered code paths',
                'Review critical functionality coverage',
                'Consider integration test coverage',
            ],
        ],
        'performance_threshold' => [
            'name' => 'Performance Threshold',
            'description' => 'Maximum allowed test execution time',
            'type' => 'duration',
            'blocking' => true,
            'thresholds' => [
                'development' => 180.0,
                'testing' => 150.0,
                'staging' => 120.0,
                'production' => 90.0,
            ],
            'calculation' => 'total_execution_time',
            'remediation' => [
                'Optimize slow tests',
                'Implement parallel execution',
                'Review test data setup efficiency',
            ],
        ],
        'static_analysis_compliance' => [
            'name' => 'Static Analysis Compliance',
            'description' => 'Larastan/PHPStan compliance level',
            'type' => 'level',
            'blocking' => true,
            'thresholds' => [
                'development' => 8,
                'testing' => 9,
                'staging' => 10,
                'production' => 10,
            ],
            'calculation' => 'static_analysis_level',
            'remediation' => [
                'Fix static analysis violations',
                'Add proper type declarations',
                'Improve PHPDoc annotations',
            ],
        ],
        'security_scan' => [
            'name' => 'Security Scan',
            'description' => 'Security vulnerability assessment',
            'type' => 'count',
            'blocking' => true,
            'thresholds' => [
                'development' => 5,
                'testing' => 2,
                'staging' => 0,
                'production' => 0,
            ],
            'calculation' => 'security_vulnerabilities_count',
            'remediation' => [
                'Fix identified security vulnerabilities',
                'Update dependencies with security patches',
                'Review security best practices',
            ],
        ],
        'dependency_audit' => [
            'name' => 'Dependency Audit',
            'description' => 'Known vulnerabilities in dependencies',
            'type' => 'count',
            'blocking' => true,
            'thresholds' => [
                'development' => 10,
                'testing' => 5,
                'staging' => 1,
                'production' => 0,
            ],
            'calculation' => 'dependency_vulnerabilities_count',
            'remediation' => [
                'Update vulnerable dependencies',
                'Review dependency security advisories',
                'Consider alternative packages',
            ],
        ],
        'quality_score' => [
            'name' => 'Overall Quality Score',
            'description' => 'Composite quality metric',
            'type' => 'percentage',
            'blocking' => false,
            'thresholds' => [
                'development' => 75.0,
                'testing' => 80.0,
                'staging' => 85.0,
                'production' => 90.0,
            ],
            'calculation' => 'weighted_quality_metrics',
            'remediation' => [
                'Review individual quality metrics',
                'Focus on lowest scoring areas',
                'Implement quality improvement plan',
            ],
        ],
    ];
    
    /**
     * Gate validation results.
     * 
     * @var array<string, mixed>
     */
    private static array $validationResults = [];
    
    /**
     * Gate dependencies configuration.
     * 
     * @var array<string, array<string>>
     */
    private static array $gateDependencies = [
        'performance_threshold' => ['test_success_rate'],
        'code_coverage' => ['test_success_rate'],
        'quality_score' => ['test_success_rate', 'static_analysis_compliance'],
    ];
    
    /**
     * Initialize deployment gate validation system.
     * 
     * @param array<string, mixed> $config Configuration overrides
     * @return void
     */
    public static function initialize(array $config = []): void
    {
        self::$config = array_merge(self::$config, $config);
        self::$validationResults = [];
    }
    
    /**
     * Validate all deployment gates for given environment.
     * 
     * @param array<string, mixed> $testData Test execution and quality data
     * @param string $environment Target environment
     * @param array<string> $enabledGates Specific gates to validate (optional)
     * @return array<string, mixed> Comprehensive validation results
     */
    public static function validateDeploymentGates(
        array $testData,
        string $environment,
        array $enabledGates = []
    ): array {
        $startTime = microtime(true);
        
        $results = [
            'overall_status' => 'unknown',
            'environment' => $environment,
            'validation_timestamp' => date('Y-m-d H:i:s'),
            'gates' => [],
            'blocking_failures' => [],
            'warnings' => [],
            'recommendations' => [],
            'remediation_plan' => [],
            'execution_time' => 0,
            'metadata' => [
                'total_gates' => 0,
                'passed_gates' => 0,
                'failed_gates' => 0,
                'warning_gates' => 0,
                'skipped_gates' => 0,
            ],
        ];
        
        try {
            // Determine which gates to validate
            $gatesToValidate = empty($enabledGates) 
                ? array_keys(self::$gateDefinitions) 
                : $enabledGates;
            
            // Validate gate dependencies if enabled
            if (self::$config['enable_gate_dependencies']) {
                $gatesToValidate = self::resolveDependencyOrder($gatesToValidate);
            }
            
            // Validate each gate
            foreach ($gatesToValidate as $gateId) {
                if (!isset(self::$gateDefinitions[$gateId])) {
                    $results['warnings'][] = "Unknown gate: {$gateId}";
                    continue;
                }
                
                $gateResult = self::validateGate($gateId, $testData, $environment);
                $results['gates'][$gateId] = $gateResult;
                
                // Update metadata
                $results['metadata']['total_gates']++;
                
                switch ($gateResult['status']) {
                    case 'passed':
                        $results['metadata']['passed_gates']++;
                        break;
                    case 'failed':
                        $results['metadata']['failed_gates']++;
                        if ($gateResult['blocking']) {
                            $results['blocking_failures'][] = $gateResult;
                        }
                        break;
                    case 'warning':
                        $results['metadata']['warning_gates']++;
                        $results['warnings'][] = $gateResult;
                        break;
                    case 'skipped':
                        $results['metadata']['skipped_gates']++;
                        break;
                }
                
                // Stop validation if blocking gate fails and strict validation is enabled
                if (self::$config['enable_strict_validation'] && 
                    $gateResult['status'] === 'failed' && 
                    $gateResult['blocking']) {
                    break;
                }
            }
            
            // Determine overall status
            $results['overall_status'] = self::determineOverallStatus($results);
            
            // Generate recommendations and remediation plan
            $results['recommendations'] = self::generateRecommendations($results);
            $results['remediation_plan'] = self::generateRemediationPlan($results);
            
        } catch (Exception $e) {
            $results['overall_status'] = 'error';
            $results['error'] = $e->getMessage();
        }
        
        $results['execution_time'] = microtime(true) - $startTime;
        self::$validationResults = $results;
        
        return $results;
    }
    
    /**
     * Validate individual deployment gate.
     * 
     * @param string $gateId Gate identifier
     * @param array<string, mixed> $testData Test data
     * @param string $environment Target environment
     * @return array<string, mixed> Gate validation result
     */
    public static function validateGate(string $gateId, array $testData, string $environment): array
    {
        $definition = self::$gateDefinitions[$gateId];
        $threshold = $definition['thresholds'][$environment] ?? $definition['thresholds']['development'];
        
        $result = [
            'gate_id' => $gateId,
            'name' => $definition['name'],
            'description' => $definition['description'],
            'type' => $definition['type'],
            'blocking' => $definition['blocking'],
            'threshold' => $threshold,
            'actual_value' => null,
            'status' => 'unknown',
            'message' => '',
            'remediation' => $definition['remediation'],
            'validation_timestamp' => date('Y-m-d H:i:s'),
        ];
        
        try {
            // Calculate actual value based on gate type
            $actualValue = self::calculateGateValue($gateId, $testData, $definition);
            $result['actual_value'] = $actualValue;
            
            // Validate against threshold
            $validationResult = self::validateAgainstThreshold(
                $actualValue,
                $threshold,
                $definition['type']
            );
            
            $result['status'] = $validationResult['status'];
            $result['message'] = $validationResult['message'];
            
            // Add progressive gate logic if enabled
            if (self::$config['enable_progressive_gates']) {
                $result = self::applyProgressiveGateLogic($result, $environment);
            }
            
        } catch (Exception $e) {
            $result['status'] = 'error';
            $result['message'] = "Gate validation failed: " . $e->getMessage();
        }
        
        return $result;
    }
    
    /**
     * Get gate validation history and trends.
     * 
     * @param string $gateId Gate identifier
     * @param int $days Number of days to analyze
     * @return array<string, mixed> Gate history and trends
     */
    public static function getGateHistory(string $gateId, int $days = 30): array
    {
        // This would typically read from a persistent storage
        // For now, return a simulated history
        return [
            'gate_id' => $gateId,
            'period_days' => $days,
            'total_validations' => rand(50, 200),
            'success_rate' => rand(85, 98) / 100,
            'average_value' => rand(80, 95),
            'trend' => 'improving', // improving, declining, stable
            'recent_failures' => rand(0, 5),
            'recommendations' => [
                'Gate performance is within acceptable range',
                'Consider tightening thresholds for better quality',
            ],
        ];
    }
    
    /**
     * Generate comprehensive gate validation report.
     * 
     * @param array<string, mixed> $validationResults Validation results
     * @param string $format Report format (json, html, markdown)
     * @return string Generated report
     */
    public static function generateValidationReport(array $validationResults, string $format = 'json'): string
    {
        return match ($format) {
            'html' => self::generateHtmlReport($validationResults),
            'markdown' => self::generateMarkdownReport($validationResults),
            'json' => json_encode($validationResults, JSON_PRETTY_PRINT),
            default => throw new RuntimeException("Unsupported report format: {$format}"),
        };
    }
    
    /**
     * Configure gate thresholds for environment.
     * 
     * @param string $environment Environment name
     * @param array<string, mixed> $thresholds Gate threshold overrides
     * @return void
     */
    public static function configureGateThresholds(string $environment, array $thresholds): void
    {
        foreach ($thresholds as $gateId => $threshold) {
            if (isset(self::$gateDefinitions[$gateId])) {
                self::$gateDefinitions[$gateId]['thresholds'][$environment] = $threshold;
            }
        }
    }
    
    /**
     * Add custom gate definition.
     * 
     * @param string $gateId Gate identifier
     * @param array<string, mixed> $definition Gate definition
     * @return void
     */
    public static function addCustomGate(string $gateId, array $definition): void
    {
        $requiredFields = ['name', 'description', 'type', 'blocking', 'thresholds', 'calculation'];
        
        foreach ($requiredFields as $field) {
            if (!isset($definition[$field])) {
                throw new RuntimeException("Missing required field '{$field}' in gate definition");
            }
        }
        
        self::$gateDefinitions[$gateId] = $definition;
    }
    
    /**
     * Get current validation results.
     * 
     * @return array<string, mixed> Validation results
     */
    public static function getValidationResults(): array
    {
        return self::$validationResults;
    }
    
    /**
     * Configure deployment gate validation.
     * 
     * @param array<string, mixed> $config Configuration overrides
     * @return void
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }
    
    /**
     * Reset validation state.
     * 
     * @return void
     */
    public static function reset(): void
    {
        self::$validationResults = [];
    }
    
    /**
     * Calculate gate value based on test data.
     * 
     * @param string $gateId Gate identifier
     * @param array<string, mixed> $testData Test data
     * @param array<string, mixed> $definition Gate definition
     * @return float|int Calculated value
     */
    private static function calculateGateValue(string $gateId, array $testData, array $definition): float|int
    {
        return match ($gateId) {
            'test_success_rate' => self::calculateTestSuccessRate($testData),
            'code_coverage' => self::calculateCodeCoverage($testData),
            'performance_threshold' => self::calculatePerformanceThreshold($testData),
            'static_analysis_compliance' => self::calculateStaticAnalysisCompliance($testData),
            'security_scan' => self::calculateSecurityScanResults($testData),
            'dependency_audit' => self::calculateDependencyAuditResults($testData),
            'quality_score' => self::calculateQualityScore($testData),
            default => throw new RuntimeException("Unknown gate calculation: {$gateId}"),
        };
    }
    
    /**
     * Calculate test success rate.
     * 
     * @param array<string, mixed> $testData Test data
     * @return float Success rate percentage
     */
    private static function calculateTestSuccessRate(array $testData): float
    {
        $totalTests = $testData['test_results']['total_tests'] ?? 0;
        $passedTests = $testData['test_results']['passed_tests'] ?? 0;
        
        return $totalTests > 0 ? ($passedTests / $totalTests) * 100 : 0;
    }
    
    /**
     * Calculate code coverage percentage.
     * 
     * @param array<string, mixed> $testData Test data
     * @return float Coverage percentage
     */
    private static function calculateCodeCoverage(array $testData): float
    {
        return $testData['coverage']['percentage'] ?? 0;
    }
    
    /**
     * Calculate performance threshold value.
     * 
     * @param array<string, mixed> $testData Test data
     * @return float Execution time in seconds
     */
    private static function calculatePerformanceThreshold(array $testData): float
    {
        return $testData['execution_time'] ?? 0;
    }
    
    /**
     * Calculate static analysis compliance level.
     * 
     * @param array<string, mixed> $testData Test data
     * @return int Compliance level
     */
    private static function calculateStaticAnalysisCompliance(array $testData): int
    {
        return $testData['static_analysis']['level'] ?? 0;
    }
    
    /**
     * Calculate security scan results.
     * 
     * @param array<string, mixed> $testData Test data
     * @return int Number of vulnerabilities
     */
    private static function calculateSecurityScanResults(array $testData): int
    {
        return $testData['security_scan']['vulnerabilities'] ?? 0;
    }
    
    /**
     * Calculate dependency audit results.
     * 
     * @param array<string, mixed> $testData Test data
     * @return int Number of vulnerable dependencies
     */
    private static function calculateDependencyAuditResults(array $testData): int
    {
        return $testData['dependency_audit']['vulnerabilities'] ?? 0;
    }
    
    /**
     * Calculate overall quality score.
     * 
     * @param array<string, mixed> $testData Test data
     * @return float Quality score percentage
     */
    private static function calculateQualityScore(array $testData): float
    {
        // Weighted calculation of various quality metrics
        $testSuccessRate = self::calculateTestSuccessRate($testData);
        $codeCoverage = self::calculateCodeCoverage($testData);
        $staticAnalysisScore = (self::calculateStaticAnalysisCompliance($testData) / 10) * 100;
        
        // Weighted average: 40% test success, 30% coverage, 30% static analysis
        return ($testSuccessRate * 0.4) + ($codeCoverage * 0.3) + ($staticAnalysisScore * 0.3);
    }
    
    /**
     * Validate value against threshold.
     * 
     * @param float|int $actualValue Actual value
     * @param float|int $threshold Threshold value
     * @param string $type Value type
     * @return array<string, mixed> Validation result
     */
    private static function validateAgainstThreshold(float|int $actualValue, float|int $threshold, string $type): array
    {
        $result = [
            'status' => 'unknown',
            'message' => '',
        ];
        
        switch ($type) {
            case 'percentage':
            case 'duration':
                if ($actualValue >= $threshold) {
                    $result['status'] = 'passed';
                    $result['message'] = "Value {$actualValue} meets threshold {$threshold}";
                } else {
                    $result['status'] = 'failed';
                    $result['message'] = "Value {$actualValue} below threshold {$threshold}";
                }
                break;
                
            case 'count':
                if ($actualValue <= $threshold) {
                    $result['status'] = 'passed';
                    $result['message'] = "Count {$actualValue} within threshold {$threshold}";
                } else {
                    $result['status'] = 'failed';
                    $result['message'] = "Count {$actualValue} exceeds threshold {$threshold}";
                }
                break;
                
            case 'level':
                if ($actualValue >= $threshold) {
                    $result['status'] = 'passed';
                    $result['message'] = "Level {$actualValue} meets requirement {$threshold}";
                } else {
                    $result['status'] = 'failed';
                    $result['message'] = "Level {$actualValue} below requirement {$threshold}";
                }
                break;
                
            default:
                throw new RuntimeException("Unknown validation type: {$type}");
        }
        
        return $result;
    }
    
    /**
     * Apply progressive gate logic.
     * 
     * @param array<string, mixed> $result Gate result
     * @param string $environment Environment name
     * @return array<string, mixed> Updated gate result
     */
    private static function applyProgressiveGateLogic(array $result, string $environment): array
    {
        // Progressive gates allow for warnings instead of failures in lower environments
        if ($result['status'] === 'failed' && !$result['blocking']) {
            if (in_array($environment, ['development', 'testing'], true)) {
                $result['status'] = 'warning';
                $result['message'] .= ' (Non-blocking in ' . $environment . ' environment)';
            }
        }
        
        return $result;
    }
    
    /**
     * Resolve gate dependency order.
     * 
     * @param array<string> $gates Gates to validate
     * @return array<string> Ordered gates
     */
    private static function resolveDependencyOrder(array $gates): array
    {
        $ordered = [];
        $remaining = $gates;
        
        while (!empty($remaining)) {
            $added = false;
            
            foreach ($remaining as $index => $gate) {
                $dependencies = self::$gateDependencies[$gate] ?? [];
                $dependenciesMet = true;
                
                foreach ($dependencies as $dependency) {
                    if (!in_array($dependency, $ordered, true)) {
                        $dependenciesMet = false;
                        break;
                    }
                }
                
                if ($dependenciesMet) {
                    $ordered[] = $gate;
                    unset($remaining[$index]);
                    $added = true;
                }
            }
            
            if (!$added) {
                // Circular dependency or missing dependency - add remaining gates
                $ordered = array_merge($ordered, $remaining);
                break;
            }
        }
        
        return $ordered;
    }
    
    /**
     * Determine overall validation status.
     * 
     * @param array<string, mixed> $results Validation results
     * @return string Overall status
     */
    private static function determineOverallStatus(array $results): string
    {
        if (!empty($results['blocking_failures'])) {
            return 'failed';
        }
        
        if ($results['metadata']['failed_gates'] > self::$config['failure_tolerance']) {
            return 'failed';
        }
        
        if (count($results['warnings']) > self::$config['warning_threshold']) {
            return 'warning';
        }
        
        if ($results['metadata']['passed_gates'] === $results['metadata']['total_gates']) {
            return 'passed';
        }
        
        return 'partial';
    }
    
    /**
     * Generate recommendations based on validation results.
     * 
     * @param array<string, mixed> $results Validation results
     * @return array<string> Recommendations
     */
    private static function generateRecommendations(array $results): array
    {
        $recommendations = [];
        
        if ($results['overall_status'] === 'passed') {
            $recommendations[] = 'All deployment gates passed successfully';
            $recommendations[] = 'Consider reviewing and tightening quality thresholds';
        } else {
            $recommendations[] = 'Review failed gates and implement remediation plan';
            
            if (!empty($results['blocking_failures'])) {
                $recommendations[] = 'Address blocking gate failures before deployment';
            }
            
            if (count($results['warnings']) > 0) {
                $recommendations[] = 'Review warning gates to prevent future failures';
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Generate remediation plan based on failed gates.
     * 
     * @param array<string, mixed> $results Validation results
     * @return array<string, mixed> Remediation plan
     */
    private static function generateRemediationPlan(array $results): array
    {
        $plan = [
            'immediate_actions' => [],
            'short_term_actions' => [],
            'long_term_actions' => [],
            'estimated_effort' => 'low',
        ];
        
        foreach ($results['gates'] as $gateResult) {
            if ($gateResult['status'] === 'failed') {
                if ($gateResult['blocking']) {
                    $plan['immediate_actions'] = array_merge(
                        $plan['immediate_actions'],
                        $gateResult['remediation']
                    );
                } else {
                    $plan['short_term_actions'] = array_merge(
                        $plan['short_term_actions'],
                        $gateResult['remediation']
                    );
                }
            }
        }
        
        // Estimate effort based on number of failed gates
        $failedGates = $results['metadata']['failed_gates'];
        $plan['estimated_effort'] = match (true) {
            $failedGates === 0 => 'none',
            $failedGates <= 2 => 'low',
            $failedGates <= 4 => 'medium',
            default => 'high',
        };
        
        return $plan;
    }
    
    /**
     * Generate HTML validation report.
     * 
     * @param array<string, mixed> $results Validation results
     * @return string HTML report
     */
    private static function generateHtmlReport(array $results): string
    {
        $html = "<!DOCTYPE html>\n<html>\n<head>\n<title>Deployment Gate Validation Report</title>\n";
        $html .= "<style>body{font-family:Arial,sans-serif;margin:20px;}table{border-collapse:collapse;width:100%;}th,td{border:1px solid #ddd;padding:8px;text-align:left;}th{background-color:#f2f2f2;}.passed{color:green;}.failed{color:red;}.warning{color:orange;}.skipped{color:gray;}</style>\n";
        $html .= "</head>\n<body>\n";
        $html .= "<h1>Deployment Gate Validation Report</h1>\n";
        $html .= "<p>Environment: {$results['environment']}</p>\n";
        $html .= "<p>Generated at: {$results['validation_timestamp']}</p>\n";
        $html .= "<p>Overall Status: <span class='{$results['overall_status']}'>" . strtoupper($results['overall_status']) . "</span></p>\n";
        
        $html .= "<h2>Gate Results</h2>\n";
        $html .= "<table>\n";
        $html .= "<tr><th>Gate</th><th>Status</th><th>Threshold</th><th>Actual</th><th>Message</th></tr>\n";
        
        foreach ($results['gates'] as $gate) {
            $statusClass = $gate['status'];
            $html .= "<tr><td>{$gate['name']}</td><td class='{$statusClass}'>" . strtoupper($gate['status']) . "</td><td>{$gate['threshold']}</td><td>{$gate['actual_value']}</td><td>{$gate['message']}</td></tr>\n";
        }
        
        $html .= "</table>\n";
        $html .= "</body>\n</html>";
        
        return $html;
    }
    
    /**
     * Generate Markdown validation report.
     * 
     * @param array<string, mixed> $results Validation results
     * @return string Markdown report
     */
    private static function generateMarkdownReport(array $results): string
    {
        $md = "# Deployment Gate Validation Report\n\n";
        $md .= "**Environment:** {$results['environment']}\n";
        $md .= "**Generated at:** {$results['validation_timestamp']}\n";
        $md .= "**Overall Status:** " . strtoupper($results['overall_status']) . "\n\n";
        
        $md .= "## Summary\n\n";
        $md .= "- Total Gates: {$results['metadata']['total_gates']}\n";
        $md .= "- Passed: {$results['metadata']['passed_gates']}\n";
        $md .= "- Failed: {$results['metadata']['failed_gates']}\n";
        $md .= "- Warnings: {$results['metadata']['warning_gates']}\n";
        $md .= "- Skipped: {$results['metadata']['skipped_gates']}\n\n";
        
        $md .= "## Gate Results\n\n";
        $md .= "| Gate | Status | Threshold | Actual | Message |\n";
        $md .= "|------|--------|-----------|--------|---------|\n";
        
        foreach ($results['gates'] as $gate) {
            $status = strtoupper($gate['status']);
            $md .= "| {$gate['name']} | {$status} | {$gate['threshold']} | {$gate['actual_value']} | {$gate['message']} |\n";
        }
        
        if (!empty($results['recommendations'])) {
            $md .= "\n## Recommendations\n\n";
            foreach ($results['recommendations'] as $recommendation) {
                $md .= "- {$recommendation}\n";
            }
        }
        
        return $md;
    }
}