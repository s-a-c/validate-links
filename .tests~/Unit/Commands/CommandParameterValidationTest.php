<?php

declare(strict_types=1);

use Tests\Support\CommandTestHelper;
use Tests\Support\LoggingTestHelper;
use App\Commands\ValidateCommand;
use App\Commands\ReportCommand;
use App\Commands\ConfigCommand;
use App\Commands\FixCommand;
use App\Services\Contracts\LinkValidationInterface;
use App\Services\Contracts\ReportingInterface;

/**
 * Comprehensive command parameter validation test suite.
 *
 * Tests:
 * - Parameter boundary testing with edge cases and invalid inputs
 * - Input validation for all command parameters across all commands
 * - Parameter type checking and conversion validation
 * - Required parameter validation and optional parameter handling
 * - Parameter dependency validation and constraint checking
 * - Parameter format validation and sanitization
 *
 * @package Tests\Unit\Commands
 * @author Roo
 * @since 1.0.0
 */

describe('Command Parameter Validation', function () {
    beforeEach(function () {
        LoggingTestHelper::initializeLoggingCapture(['single', 'stderr']);
        CommandTestHelper::clearResults();
    });

    afterEach(function () {
        LoggingTestHelper::resetLoggingEnvironment();
        CommandTestHelper::clearResults();
    });

    describe('ValidateCommand Parameter Boundaries', function () {
        it('validates validate command parameter boundaries comprehensively', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ValidateCommand($linkValidation, $reporting);

            $comprehensiveBoundaryTests = [
                'timeout' => [
                    // Valid boundaries
                    ['name' => 'minimum_valid', 'value' => 1, 'expected_valid' => true],
                    ['name' => 'typical_low', 'value' => 5, 'expected_valid' => true],
                    ['name' => 'default_value', 'value' => 30, 'expected_valid' => true],
                    ['name' => 'typical_high', 'value' => 120, 'expected_valid' => true],
                    ['name' => 'maximum_valid', 'value' => 300, 'expected_valid' => true],
                    
                    // Invalid boundaries
                    ['name' => 'zero', 'value' => 0, 'expected_valid' => false],
                    ['name' => 'negative_small', 'value' => -1, 'expected_valid' => false],
                    ['name' => 'negative_large', 'value' => -100, 'expected_valid' => false],
                    ['name' => 'above_maximum', 'value' => 301, 'expected_valid' => false],
                    ['name' => 'extremely_high', 'value' => 9999, 'expected_valid' => false],
                    
                    // Type validation
                    ['name' => 'string_numeric', 'value' => '30', 'expected_valid' => true],
                    ['name' => 'string_non_numeric', 'value' => 'abc', 'expected_valid' => false],
                    ['name' => 'float_value', 'value' => 30.5, 'expected_valid' => true],
                    ['name' => 'boolean_true', 'value' => true, 'expected_valid' => false],
                    ['name' => 'boolean_false', 'value' => false, 'expected_valid' => false],
                    ['name' => 'null_value', 'value' => null, 'expected_valid' => false],
                    ['name' => 'array_value', 'value' => [30], 'expected_valid' => false],
                ],
                
                'concurrent' => [
                    // Valid boundaries
                    ['name' => 'minimum_valid', 'value' => 1, 'expected_valid' => true],
                    ['name' => 'low_concurrency', 'value' => 3, 'expected_valid' => true],
                    ['name' => 'default_value', 'value' => 10, 'expected_valid' => true],
                    ['name' => 'high_concurrency', 'value' => 25, 'expected_valid' => true],
                    ['name' => 'maximum_valid', 'value' => 50, 'expected_valid' => true],
                    
                    // Invalid boundaries
                    ['name' => 'zero', 'value' => 0, 'expected_valid' => false],
                    ['name' => 'negative', 'value' => -5, 'expected_valid' => false],
                    ['name' => 'above_maximum', 'value' => 51, 'expected_valid' => false],
                    ['name' => 'extremely_high', 'value' => 1000, 'expected_valid' => false],
                    
                    // Edge cases
                    ['name' => 'string_valid', 'value' => '10', 'expected_valid' => true],
                    ['name' => 'string_invalid', 'value' => 'many', 'expected_valid' => false],
                    ['name' => 'float_rounded', 'value' => 10.9, 'expected_valid' => true],
                ],
                
                'max-redirects' => [
                    // Valid boundaries
                    ['name' => 'minimum_valid', 'value' => 0, 'expected_valid' => true],
                    ['name' => 'typical_low', 'value' => 3, 'expected_valid' => true],
                    ['name' => 'default_value', 'value' => 5, 'expected_valid' => true],
                    ['name' => 'typical_high', 'value' => 10, 'expected_valid' => true],
                    ['name' => 'maximum_valid', 'value' => 20, 'expected_valid' => true],
                    
                    // Invalid boundaries
                    ['name' => 'negative', 'value' => -1, 'expected_valid' => false],
                    ['name' => 'above_maximum', 'value' => 21, 'expected_valid' => false],
                    ['name' => 'extremely_high', 'value' => 100, 'expected_valid' => false],
                ],
                
                'scope' => [
                    // Valid single scopes
                    ['name' => 'internal', 'value' => 'internal', 'expected_valid' => true],
                    ['name' => 'external', 'value' => 'external', 'expected_valid' => true],
                    ['name' => 'anchor', 'value' => 'anchor', 'expected_valid' => true],
                    ['name' => 'image', 'value' => 'image', 'expected_valid' => true],
                    ['name' => 'all', 'value' => 'all', 'expected_valid' => true],
                    
                    // Valid multiple scopes
                    ['name' => 'two_scopes', 'value' => 'internal,external', 'expected_valid' => true],
                    ['name' => 'three_scopes', 'value' => 'internal,external,anchor', 'expected_valid' => true],
                    ['name' => 'all_scopes', 'value' => 'internal,external,anchor,image', 'expected_valid' => true],
                    ['name' => 'spaces_around', 'value' => ' internal , external ', 'expected_valid' => true],
                    
                    // Invalid scopes
                    ['name' => 'invalid_single', 'value' => 'invalid', 'expected_valid' => false],
                    ['name' => 'mixed_valid_invalid', 'value' => 'internal,invalid', 'expected_valid' => false],
                    ['name' => 'empty_string', 'value' => '', 'expected_valid' => false],
                    ['name' => 'only_commas', 'value' => ',,,', 'expected_valid' => false],
                    ['name' => 'duplicate_scopes', 'value' => 'internal,internal', 'expected_valid' => true], // Duplicates allowed
                ],
                
                'format' => [
                    // Valid formats
                    ['name' => 'console', 'value' => 'console', 'expected_valid' => true],
                    ['name' => 'json', 'value' => 'json', 'expected_valid' => true],
                    ['name' => 'html', 'value' => 'html', 'expected_valid' => true],
                    ['name' => 'markdown', 'value' => 'markdown', 'expected_valid' => true],
                    
                    // Invalid formats
                    ['name' => 'xml', 'value' => 'xml', 'expected_valid' => false],
                    ['name' => 'csv', 'value' => 'csv', 'expected_valid' => false],
                    ['name' => 'yaml', 'value' => 'yaml', 'expected_valid' => false],
                    ['name' => 'empty_string', 'value' => '', 'expected_valid' => false],
                    ['name' => 'numeric', 'value' => 123, 'expected_valid' => false],
                    
                    // Case sensitivity
                    ['name' => 'uppercase', 'value' => 'JSON', 'expected_valid' => false],
                    ['name' => 'mixed_case', 'value' => 'Html', 'expected_valid' => false],
                ],
                
                'user-agent' => [
                    // Valid user agents
                    ['name' => 'default', 'value' => 'validate-links/1.0', 'expected_valid' => true],
                    ['name' => 'short', 'value' => 'test', 'expected_valid' => true],
                    ['name' => 'browser_like', 'value' => 'Mozilla/5.0 (compatible; validate-links)', 'expected_valid' => true],
                    ['name' => 'with_version', 'value' => 'MyBot/2.1 (+http://example.com/bot)', 'expected_valid' => true],
                    ['name' => 'max_length', 'value' => str_repeat('a', 200), 'expected_valid' => true],
                    
                    // Invalid user agents
                    ['name' => 'too_long', 'value' => str_repeat('a', 201), 'expected_valid' => false],
                    ['name' => 'empty_string', 'value' => '', 'expected_valid' => false],
                    ['name' => 'numeric', 'value' => 123, 'expected_valid' => false],
                    ['name' => 'boolean', 'value' => true, 'expected_valid' => false],
                    
                    // Special characters
                    ['name' => 'with_unicode', 'value' => 'test-agent-你好', 'expected_valid' => true],
                    ['name' => 'with_symbols', 'value' => 'test-agent-!@#$%', 'expected_valid' => true],
                    ['name' => 'with_newlines', 'value' => "test\nagent", 'expected_valid' => true],
                ],
            ];

            foreach ($comprehensiveBoundaryTests as $parameter => $tests) {
                $results = CommandTestHelper::testParameterBoundaries($command, [$parameter => $tests]);
                
                expect($results['results'])->toHaveKey($parameter, 
                    "Results missing for parameter: {$parameter}");
                
                foreach ($tests as $test) {
                    $testName = $test['name'];
                    expect($results['results'][$parameter])->toHaveKey($testName, 
                        "Test result missing for {$parameter}.{$testName}");
                    
                    $testResult = $results['results'][$parameter][$testName];
                    expect($testResult['actual_valid'])->toBe($test['expected_valid'], 
                        "Boundary test failed for {$parameter}.{$testName}: " .
                        "expected {$test['expected_valid']}, got {$testResult['actual_valid']} " .
                        "for value: " . json_encode($test['value']));
                }
            }
        });
    });

    describe('Parameter Type Validation', function () {
        it('validates parameter type checking and conversion', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ValidateCommand($linkValidation, $reporting);

            $typeTests = [
                'numeric_parameters' => [
                    'timeout' => [
                        ['value' => 30, 'type' => 'integer', 'expected_valid' => true],
                        ['value' => '30', 'type' => 'string', 'expected_valid' => true], // Should convert
                        ['value' => 30.0, 'type' => 'double', 'expected_valid' => true], // Should convert
                        ['value' => '30.5', 'type' => 'string', 'expected_valid' => true], // Should convert
                        ['value' => 'abc', 'type' => 'string', 'expected_valid' => false],
                        ['value' => [], 'type' => 'array', 'expected_valid' => false],
                        ['value' => true, 'type' => 'boolean', 'expected_valid' => false],
                    ],
                    'concurrent' => [
                        ['value' => 10, 'type' => 'integer', 'expected_valid' => true],
                        ['value' => '10', 'type' => 'string', 'expected_valid' => true],
                        ['value' => 10.9, 'type' => 'double', 'expected_valid' => true], // Should round
                        ['value' => 'ten', 'type' => 'string', 'expected_valid' => false],
                    ],
                ],
                'string_parameters' => [
                    'scope' => [
                        ['value' => 'all', 'type' => 'string', 'expected_valid' => true],
                        ['value' => 123, 'type' => 'integer', 'expected_valid' => false],
                        ['value' => true, 'type' => 'boolean', 'expected_valid' => false],
                        ['value' => [], 'type' => 'array', 'expected_valid' => false],
                    ],
                    'format' => [
                        ['value' => 'json', 'type' => 'string', 'expected_valid' => true],
                        ['value' => 123, 'type' => 'integer', 'expected_valid' => false],
                        ['value' => null, 'type' => 'NULL', 'expected_valid' => false],
                    ],
                    'user-agent' => [
                        ['value' => 'test-agent', 'type' => 'string', 'expected_valid' => true],
                        ['value' => 123, 'type' => 'integer', 'expected_valid' => false],
                        ['value' => 12.34, 'type' => 'double', 'expected_valid' => false],
                    ],
                ],
                'boolean_parameters' => [
                    'no-cache' => [
                        ['value' => true, 'type' => 'boolean', 'expected_valid' => true],
                        ['value' => false, 'type' => 'boolean', 'expected_valid' => true],
                        ['value' => 'true', 'type' => 'string', 'expected_valid' => true], // Should convert
                        ['value' => 'false', 'type' => 'string', 'expected_valid' => true], // Should convert
                        ['value' => 1, 'type' => 'integer', 'expected_valid' => true], // Should convert
                        ['value' => 0, 'type' => 'integer', 'expected_valid' => true], // Should convert
                        ['value' => 'yes', 'type' => 'string', 'expected_valid' => true], // Should convert
                        ['value' => 'no', 'type' => 'string', 'expected_valid' => true], // Should convert
                        ['value' => 'invalid', 'type' => 'string', 'expected_valid' => false],
                    ],
                    'follow-redirects' => [
                        ['value' => true, 'type' => 'boolean', 'expected_valid' => true],
                        ['value' => false, 'type' => 'boolean', 'expected_valid' => true],
                        ['value' => 'on', 'type' => 'string', 'expected_valid' => true],
                        ['value' => 'off', 'type' => 'string', 'expected_valid' => true],
                    ],
                ],
            ];

            foreach ($typeTests as $category => $parameterTests) {
                foreach ($parameterTests as $parameter => $tests) {
                    $boundaryTests = array_map(function ($test, $index) {
                        return [
                            'name' => "type_test_{$index}_{$test['type']}",
                            'value' => $test['value'],
                            'expected_valid' => $test['expected_valid'],
                        ];
                    }, $tests, array_keys($tests));

                    $results = CommandTestHelper::testParameterBoundaries($command, [$parameter => $boundaryTests]);
                    
                    foreach ($boundaryTests as $test) {
                        $testName = $test['name'];
                        $testResult = $results['results'][$parameter][$testName];
                        
                        expect($testResult['actual_valid'])->toBe($test['expected_valid'], 
                            "Type validation failed for {$parameter}.{$testName} in {$category}: " .
                            "expected {$test['expected_valid']}, got {$testResult['actual_valid']} " .
                            "for value: " . json_encode($test['value']));
                    }
                }
            }
        });
    });

    describe('Required and Optional Parameters', function () {
        it('validates required and optional parameters', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ValidateCommand($linkValidation, $reporting);

            $definition = $command->getDefinition();

            // Test required arguments
            $pathsArgument = $definition->getArgument('paths');
            expect($pathsArgument->isRequired())->toBeFalse(
                'Paths argument should not be required (has default)');

            // Test optional parameters with defaults
            $optionalParametersWithDefaults = [
                'scope' => 'all',
                'format' => 'console',
                'concurrent' => '10',
                'timeout' => '30',
                'max-redirects' => '5',
                'user-agent' => 'validate-links/1.0',
            ];

            foreach ($optionalParametersWithDefaults as $option => $expectedDefault) {
                $optionDef = $definition->getOption($option);
                expect($optionDef->isValueRequired())->toBeFalse(
                    "Option '{$option}' should not require a value");
                expect($optionDef->getDefault())->toBe($expectedDefault, 
                    "Option '{$option}' should have default value '{$expectedDefault}'");
            }

            // Test boolean flags (no value required)
            $booleanFlags = ['no-cache', 'follow-redirects', 'interactive', 'ci', 'fail-fast', 'check-external'];
            
            foreach ($booleanFlags as $flag) {
                if ($definition->hasOption($flag)) {
                    $optionDef = $definition->getOption($flag);
                    expect($optionDef->isValueRequired())->toBeFalse(
                        "Boolean flag '{$flag}' should not require a value");
                }
            }

            // Test optional parameters that can accept values
            $optionalWithValues = ['output', 'filter'];
            
            foreach ($optionalWithValues as $option) {
                if ($definition->hasOption($option)) {
                    $optionDef = $definition->getOption($option);
                    expect($optionDef->acceptValue())->toBeTrue(
                        "Option '{$option}' should accept a value");
                }
            }
        });
    });

    describe('Parameter Dependencies and Constraints', function () {
        it('validates parameter dependencies and constraints', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ValidateCommand($linkValidation, $reporting);

            $dependencyTests = [
                'format_output_dependency' => [
                    'description' => 'JSON format should work with output file',
                    'options' => ['format' => 'json', 'output' => './report.json'],
                    'expected_valid' => true,
                ],
                'html_format_output_dependency' => [
                    'description' => 'HTML format should work with output file',
                    'options' => ['format' => 'html', 'output' => './report.html'],
                    'expected_valid' => true,
                ],
                'console_format_with_output' => [
                    'description' => 'Console format with output should be valid',
                    'options' => ['format' => 'console', 'output' => './report.txt'],
                    'expected_valid' => true,
                ],
                'concurrent_timeout_relationship' => [
                    'description' => 'High concurrency with reasonable timeout',
                    'options' => ['concurrent' => '50', 'timeout' => '60'],
                    'expected_valid' => true,
                ],
                'concurrent_timeout_mismatch' => [
                    'description' => 'High concurrency with very short timeout',
                    'options' => ['concurrent' => '50', 'timeout' => '1'],
                    'expected_valid' => true, // Valid but might be inefficient
                ],
                'redirects_follow_dependency' => [
                    'description' => 'Max redirects with follow redirects enabled',
                    'options' => ['max-redirects' => '10', 'follow-redirects' => true],
                    'expected_valid' => true,
                ],
                'redirects_without_follow' => [
                    'description' => 'Max redirects without follow redirects',
                    'options' => ['max-redirects' => '10'],
                    'expected_valid' => true, // Valid, redirects setting ignored
                ],
                'cache_external_dependency' => [
                    'description' => 'No cache with external link checking',
                    'options' => ['no-cache' => true, 'check-external' => true],
                    'expected_valid' => true,
                ],
                'interactive_ci_conflict' => [
                    'description' => 'Interactive mode with CI mode',
                    'options' => ['interactive' => true, 'ci' => true],
                    'expected_valid' => true, // Both can be set, behavior determined by command
                ],
                'scope_external_dependency' => [
                    'description' => 'External scope with external checking',
                    'options' => ['scope' => 'external', 'check-external' => true],
                    'expected_valid' => true,
                ],
            ];

            foreach ($dependencyTests as $testName => $test) {
                $validation = CommandTestHelper::validateCommandOptions($command, $test['options']);
                
                expect($validation['valid'])->toBe($test['expected_valid'], 
                    "Dependency test '{$testName}' failed: {$test['description']}. " .
                    "Expected {$test['expected_valid']}, got {$validation['valid']}. " .
                    "Errors: " . implode(', ', $validation['errors']));
            }
        });
    });

    describe('Parameter Format and Sanitization', function () {
        it('validates parameter format and sanitization', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ValidateCommand($linkValidation, $reporting);

            $formatTests = [
                'user_agent_formats' => [
                    'parameter' => 'user-agent',
                    'tests' => [
                        ['name' => 'standard_format', 'value' => 'validate-links/1.0', 'expected_valid' => true],
                        ['name' => 'browser_format', 'value' => 'Mozilla/5.0 (compatible)', 'expected_valid' => true],
                        ['name' => 'bot_format', 'value' => 'MyBot/1.0 (+http://example.com)', 'expected_valid' => true],
                        ['name' => 'with_spaces', 'value' => 'My Bot 1.0', 'expected_valid' => true],
                        ['name' => 'with_unicode', 'value' => 'Bot-测试-1.0', 'expected_valid' => true],
                        ['name' => 'with_special_chars', 'value' => 'Bot!@#$%^&*()', 'expected_valid' => true],
                        ['name' => 'very_long', 'value' => str_repeat('a', 200), 'expected_valid' => true],
                        ['name' => 'too_long', 'value' => str_repeat('a', 201), 'expected_valid' => false],
                        ['name' => 'empty', 'value' => '', 'expected_valid' => false],
                        ['name' => 'only_spaces', 'value' => '   ', 'expected_valid' => false],
                    ],
                ],
                'scope_formats' => [
                    'parameter' => 'scope',
                    'tests' => [
                        ['name' => 'single_valid', 'value' => 'internal', 'expected_valid' => true],
                        ['name' => 'multiple_valid', 'value' => 'internal,external', 'expected_valid' => true],
                        ['name' => 'with_spaces', 'value' => ' internal , external ', 'expected_valid' => true],
                        ['name' => 'all_scopes', 'value' => 'internal,external,anchor,image', 'expected_valid' => true],
                        ['name' => 'duplicate_scopes', 'value' => 'internal,internal,external', 'expected_valid' => true],
                        ['name' => 'invalid_scope', 'value' => 'invalid', 'expected_valid' => false],
                        ['name' => 'mixed_valid_invalid', 'value' => 'internal,invalid', 'expected_valid' => false],
                        ['name' => 'empty_scope', 'value' => '', 'expected_valid' => false],
                        ['name' => 'only_commas', 'value' => ',,,', 'expected_valid' => false],
                        ['name' => 'trailing_comma', 'value' => 'internal,external,', 'expected_valid' => true],
                        ['name' => 'leading_comma', 'value' => ',internal,external', 'expected_valid' => true],
                    ],
                ],
                'numeric_formats' => [
                    'parameter' => 'timeout',
                    'tests' => [
                        ['name' => 'integer', 'value' => 30, 'expected_valid' => true],
                        ['name' => 'string_integer', 'value' => '30', 'expected_valid' => true],
                        ['name' => 'float', 'value' => 30.5, 'expected_valid' => true],
                        ['name' => 'string_float', 'value' => '30.5', 'expected_valid' => true],
                        ['name' => 'scientific_notation', 'value' => '3e1', 'expected_valid' => true],
                        ['name' => 'leading_zeros', 'value' => '030', 'expected_valid' => true],
                        ['name' => 'with_spaces', 'value' => ' 30 ', 'expected_valid' => true],
                        ['name' => 'non_numeric', 'value' => 'abc', 'expected_valid' => false],
                        ['name' => 'mixed_alphanumeric', 'value' => '30abc', 'expected_valid' => false],
                        ['name' => 'empty_string', 'value' => '', 'expected_valid' => false],
                    ],
                ],
            ];

            foreach ($formatTests as $category => $categoryData) {
                $parameter = $categoryData['parameter'];
                $tests = $categoryData['tests'];
                
                $boundaryTests = array_map(function ($test) {
                    return [
                        'name' => $test['name'],
                        'value' => $test['value'],
                        'expected_valid' => $test['expected_valid'],
                    ];
                }, $tests);

                $results = CommandTestHelper::testParameterBoundaries($command, [$parameter => $boundaryTests]);
                
                foreach ($boundaryTests as $test) {
                    $testName = $test['name'];
                    $testResult = $results['results'][$parameter][$testName];
                    
                    expect($testResult['actual_valid'])->toBe($test['expected_valid'], 
                        "Format test failed for {$parameter}.{$testName} in {$category}: " .
                        "expected {$test['expected_valid']}, got {$testResult['actual_valid']} " .
                        "for value: " . json_encode($test['value']));
                }
            }
        });
    });

    describe('ReportCommand Parameters', function () {
        it('validates report command parameters', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ReportCommand($linkValidation, $reporting);

            $reportParameterTests = [
                'format' => [
                    ['name' => 'html', 'value' => 'html', 'expected_valid' => true],
                    ['name' => 'markdown', 'value' => 'markdown', 'expected_valid' => true],
                    ['name' => 'json', 'value' => 'json', 'expected_valid' => true],
                    ['name' => 'invalid', 'value' => 'pdf', 'expected_valid' => false],
                ],
                'output' => [
                    ['name' => 'valid_path', 'value' => './report.html', 'expected_valid' => true],
                    ['name' => 'nested_path', 'value' => './reports/detailed.html', 'expected_valid' => true],
                    ['name' => 'absolute_path', 'value' => '/tmp/report.html', 'expected_valid' => true],
                    ['name' => 'empty_string', 'value' => '', 'expected_valid' => false],
                ],
                'detailed' => [
                    ['name' => 'true', 'value' => true, 'expected_valid' => true],
                    ['name' => 'false', 'value' => false, 'expected_valid' => true],
                    ['name' => 'string_true', 'value' => 'true', 'expected_valid' => true],
                    ['name' => 'string_false', 'value' => 'false', 'expected_valid' => true],
                ],
            ];

            foreach ($reportParameterTests as $parameter => $tests) {
                $results = CommandTestHelper::testParameterBoundaries($command, [$parameter => $tests]);
                
                foreach ($tests as $test) {
                    $testName = $test['name'];
                    $testResult = $results['results'][$parameter][$testName];
                    
                    expect($testResult['actual_valid'])->toBe($test['expected_valid'],
                        "ReportCommand parameter test failed for {$parameter}.{$testName}: " .
                        "expected {$test['expected_valid']}, got {$testResult['actual_valid']} " .
                        "for value: " . json_encode($test['value']));
                }
            }
        });
    });

    describe('Performance Testing', function () {
        it('validates parameter performance with large datasets', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ValidateCommand($linkValidation, $reporting);

            // Generate large parameter sets
            $largeParameterSets = [
                'many_paths' => [
                    'paths' => array_fill(0, 1000, './test.md'),
                    '--scope' => 'all',
                    '--format' => 'json',
                ],
                'complex_scope' => [
                    'paths' => ['./test.md'],
                    '--scope' => str_repeat('internal,external,', 100) . 'anchor',
                    '--format' => 'console',
                ],
                'long_user_agent' => [
                    'paths' => ['./test.md'],
                    '--user-agent' => str_repeat('test-agent-', 20),
                    '--scope' => 'all',
                ],
            ];

            foreach ($largeParameterSets as $setName => $parameters) {
                $startTime = microtime(true);
                $validation = CommandTestHelper::validateCommandOptions($command, $parameters);
                $endTime = microtime(true);

                $validationTime = $endTime - $startTime;

                expect($validationTime)->toBeLessThan(1.0,
                    "Parameter validation should be fast even with large datasets for {$setName}");
                
                // Large datasets might be valid or invalid depending on the specific constraints
                expect($validation['errors'])->toBeArray(
                    "Validation should return error array for {$setName}");
            }
        });
    });

    describe('Edge Cases', function () {
        it('validates parameters with edge cases', function () {
            $linkValidation = mock(LinkValidationInterface::class);
            $reporting = mock(ReportingInterface::class);
            $command = new ValidateCommand($linkValidation, $reporting);

            $edgeCases = [
                'unicode_paths' => [
                    'paths' => ['./测试文件.md', './файл.md', './ファイル.md'],
                    '--scope' => 'all',
                    'expected_valid' => true,
                ],
                'special_char_paths' => [
                    'paths' => ['./file with spaces.md', './file-with-dashes.md', './file_with_underscores.md'],
                    '--scope' => 'all',
                    'expected_valid' => true,
                ],
                'very_long_paths' => [
                    'paths' => ['./' . str_repeat('very-long-filename-', 20) . '.md'],
                    '--scope' => 'all',
                    'expected_valid' => true,
                ],
                'empty_paths_array' => [
                    'paths' => [],
                    '--scope' => 'all',
                    'expected_valid' => true, // Should use default
                ],
                'null_like_values' => [
                    'paths' => ['./test.md'],
                    '--timeout' => '0',
                    '--concurrent' => '0',
                    'expected_valid' => false,
                ],
                'extreme_values' => [
                    'paths' => ['./test.md'],
                    '--timeout' => '999999',
                    '--concurrent' => '999999',
                    'expected_valid' => false,
                ],
                'mixed_case_options' => [
                    'paths' => ['./test.md'],
                    '--scope' => 'Internal,External', // Mixed case
                    '--format' => 'JSON', // Uppercase
                    'expected_valid' => false, // Should be case sensitive
                ],
            ];

            foreach ($edgeCases as $caseName => $testCase) {
                $expectedValid = $testCase['expected_valid'];
                unset($testCase['expected_valid']);
                
                $validation = CommandTestHelper::validateCommandOptions($command, $testCase);
                
                expect($validation['valid'])->toBe($expectedValid,
                    "Edge case '{$caseName}' validation failed: " .
                    "expected {$expectedValid}, got {$validation['valid']}. " .
                    "Errors: " . implode(', ', $validation['errors']));
            }
        });
    });
});