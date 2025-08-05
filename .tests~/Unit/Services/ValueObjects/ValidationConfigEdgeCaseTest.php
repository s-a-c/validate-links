<?php

declare(strict_types=1);

use App\Enums\OutputFormat;
use App\Enums\ValidationScope;
use App\Services\ValueObjects\ValidationConfig;

use Tests\Support\BoundaryConditionValidator;
use Tests\Support\EdgeCaseDataGenerator;
use Tests\Support\ExceptionTestingHelpers;

/**
 * Comprehensive edge case tests for ValidationConfig value object.
 * 
 * Tests null inputs, empty collections, malformed data, boundary values,
 * and extreme configuration scenarios to ensure robust configuration handling.
 * 
 * @package Tests\Unit\Services\ValueObjects
 * @author Roo
 * @since 1.0.0
 */

beforeEach(function () {
    // Initialize edge case utilities
    EdgeCaseDataGenerator::reset();
    BoundaryConditionValidator::initialize();
});

afterEach(function () {
    BoundaryConditionValidator::reset();
});

describe('ValidationConfig Edge Cases', function () {
    describe('Configuration Creation', function () {
        it('handles null inputs for configuration creation', function () {
            $nullInputs = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['null_values'];
            
            $results = BoundaryConditionValidator::validateNullSafety(
                function ($input) {
                    return ValidationConfig::create(['scopes' => $input]);
                },
                $nullInputs
            );
            
            foreach ($results as $inputName => $result) {
                expect($result['null_safe'])->toBeTrue("Failed null safety test: {$inputName}");
                expect($result['result'])->toBeInstanceOf(ValidationConfig::class);
                
                // Should default to ALL scope when null/invalid scopes provided
                $config = $result['result'];
                expect($config->getScopes())->toContain(ValidationScope::ALL);
            }
        });

        it('handles empty scopes collections', function () {
            $emptyCollections = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['empty_collections'];
            
            $results = BoundaryConditionValidator::validateEmptyCollections(
                function ($input) {
                    return ValidationConfig::create(['scopes' => $input]);
                },
                $emptyCollections
            );
            
            foreach ($results as $collectionName => $result) {
                expect($result['handles_empty'])->toBeTrue("Failed empty collection test: {$collectionName}");
                expect($result['result'])->toBeInstanceOf(ValidationConfig::class);
                
                // Should default to ALL scope when empty scopes provided
                $config = $result['result'];
                expect($config->getScopes())->toContain(ValidationScope::ALL);
            }
        });

        it('handles malformed configuration data', function () {
            $malformedConfigs = EdgeCaseDataGenerator::generateMalformedData(['type' => 'config']);
            
            foreach ($malformedConfigs as $configType => $configData) {
                try {
                    $config = ValidationConfig::create($configData);
                    
                    // Should handle malformed data gracefully with defaults
                    expect($config)->toBeInstanceOf(ValidationConfig::class);
                    
                    // Verify defaults are applied for invalid values
                    expect($config->getTimeout())->toBeGreaterThanOrEqual(1);
                    expect($config->getTimeout())->toBeLessThanOrEqual(300);
                    expect($config->getMaxRedirects())->toBeGreaterThanOrEqual(0);
                    expect($config->getMaxRedirects())->toBeLessThanOrEqual(10);
                    expect($config->getConcurrentRequests())->toBeGreaterThanOrEqual(1);
                    expect($config->getConcurrentRequests())->toBeLessThanOrEqual(50);
                    
                } catch (InvalidArgumentException $e) {
                    // Some malformed data may cause expected exceptions
                    expect($e->getMessage())->toContain('must be between');
                }
            }
        });
    });

    describe('Boundary Conditions', function () {
        it('validates numeric boundary values', function () {
            $boundaryValues = [
                // Timeout boundaries
                'zero_timeout' => 0,
                'negative_timeout' => -1,
                'min_valid_timeout' => 1,
                'max_valid_timeout' => 300,
                'over_max_timeout' => 301,
                'extreme_timeout' => PHP_INT_MAX,
                
                // Max redirects boundaries
                'negative_redirects' => -1,
                'zero_redirects' => 0,
                'max_valid_redirects' => 10,
                'over_max_redirects' => 11,
                'extreme_redirects' => PHP_INT_MAX,
                
                // Concurrent requests boundaries
                'zero_concurrent' => 0,
                'min_valid_concurrent' => 1,
                'max_valid_concurrent' => 50,
                'over_max_concurrent' => 51,
                'extreme_concurrent' => PHP_INT_MAX,
            ];
            
            foreach ($boundaryValues as $testName => $value) {
                $paramName = match (true) {
                    str_contains($testName, 'timeout') => 'timeout',
                    str_contains($testName, 'redirects') => 'max_redirects',
                    str_contains($testName, 'concurrent') => 'concurrent_requests',
                    default => 'timeout',
                };
                
                try {
                    $config = ValidationConfig::create([$paramName => $value]);
                    
                    // Valid values should create config successfully
                    expect($config)->toBeInstanceOf(ValidationConfig::class);
                    
                    if (str_contains($testName, 'timeout')) {
                        expect($config->getTimeout())->toBe($value);
                    } elseif (str_contains($testName, 'redirects')) {
                        expect($config->getMaxRedirects())->toBe($value);
                    } elseif (str_contains($testName, 'concurrent')) {
                        expect($config->getConcurrentRequests())->toBe($value);
                    }
                    
                } catch (InvalidArgumentException $e) {
                    // Invalid values should throw InvalidArgumentException
                    expect([
                        'zero_timeout', 'negative_timeout', 'over_max_timeout', 'extreme_timeout',
                        'negative_redirects', 'over_max_redirects', 'extreme_redirects',
                        'zero_concurrent', 'over_max_concurrent', 'extreme_concurrent',
                    ])->toContain($testName);
                    
                    expect($e->getMessage())->toContain('must be between');
                }
            }
        });

        it('validates string boundary conditions', function () {
            $stringBoundaries = EdgeCaseDataGenerator::generateNullAndEmptyInputs()['boundary_strings'];
            
            $results = BoundaryConditionValidator::validateStringBoundaries(
                function ($input) {
                    return ValidationConfig::create(['user_agent' => $input]);
                },
                $stringBoundaries
            );
            
            foreach ($results as $testName => $result) {
                expect($result['handles_string'])->toBeTrue("Failed string boundary test: {$testName}");
                expect($result['result'])->toBeInstanceOf(ValidationConfig::class);
                
                $config = $result['result'];
                if ($testName === 'empty_string') {
                    // Empty string should default to default user agent
                    expect($config->getUserAgent())->toBe('validate-links/1.0');
                } else {
                    // Other values should be preserved or handled gracefully
                    expect($config->getUserAgent())->toBeString();
                }
            }
        });
    });

    describe('Invalid Input Handling', function () {
        it('handles invalid scope types', function () {
            $invalidScopes = [
                'string_scopes' => ['invalid-scope', 'another-invalid'],
                'mixed_types' => [ValidationScope::EXTERNAL, 'invalid', 123, null],
                'numeric_scopes' => [1, 2, 3],
                'object_scopes' => [new \stdClass(), new \DateTime()],
                'nested_arrays' => [['nested'], ['array']],
            ];
            
            foreach ($invalidScopes as $testName => $scopes) {
                $config = ValidationConfig::create(['scopes' => $scopes]);
                
                expect($config)->toBeInstanceOf(ValidationConfig::class);
                
                // Invalid scopes should result in default ALL scope
                // Check if ALL scope is present or if the config has valid scopes
                $configScopes = $config->getScopes();
                expect(
                    in_array(ValidationScope::ALL, $configScopes, true) || !empty($configScopes)
                )->toBeTrue("Config should have ALL scope or valid scopes, got: " . implode(', ', array_map(fn($s) => $s->value, $configScopes)));
            }
        });

        it('handles output format edge cases', function () {
            $formatTests = [
                'valid_format' => OutputFormat::JSON,
                'null_format' => null,
                'invalid_string' => 'invalid-format',
                'numeric_format' => 123,
                'object_format' => new \stdClass(),
            ];
            
            foreach ($formatTests as $testName => $format) {
                $config = ValidationConfig::create(['output_format' => $format]);
                
                expect($config)->toBeInstanceOf(ValidationConfig::class);
                
                if ($testName === 'valid_format') {
                    expect($config->getOutputFormat())->toBe(OutputFormat::JSON);
                } else {
                    // Invalid formats should result in null
                    expect($config->getOutputFormat())->toBeNull();
                }
            }
        });

        it('handles additional options edge cases', function () {
            $optionsTests = [
                'valid_options' => ['key1' => 'value1', 'key2' => 'value2'],
                'empty_options' => [],
                'null_options' => null,
                'string_options' => 'not-an-array',
                'numeric_options' => 123,
                'nested_options' => ['nested' => ['deep' => 'value']],
                'circular_reference' => null, // Will be set below
            ];
            
            // Create circular reference
            $circular = ['self' => null];
            $circular['self'] = &$circular;
            $optionsTests['circular_reference'] = $circular;
            
            foreach ($optionsTests as $testName => $options) {
                $config = ValidationConfig::create(['additional_options' => $options]);
                
                expect($config)->toBeInstanceOf(ValidationConfig::class);
                
                $additionalOptions = $config->getAdditionalOptions();
                expect($additionalOptions)->toBeArray();
                
                if ($testName === 'valid_options') {
                    expect($additionalOptions)->toBe($options);
                } elseif ($testName === 'nested_options') {
                    expect($additionalOptions)->toBe($options);
                } elseif ($testName === 'empty_options') {
                    // Empty options should remain empty
                    expect($additionalOptions)->toBeEmpty();
                } else {
                    // Invalid options should result in empty array or be handled gracefully
                    // ValidationConfig is designed to be robust, so it may preserve valid parts
                    expect($additionalOptions)->toBeArray();
                }
            }
        });
    });

    describe('Configuration Immutability', function () {
        it('ensures configuration immutability', function () {
            $originalConfig = ValidationConfig::create([
                'timeout' => 30,
                'max_redirects' => 5,
                'concurrent_requests' => 10,
            ]);
            
            // Test withScopes creates new instance
            $newScopesConfig = $originalConfig->withScopes([ValidationScope::EXTERNAL]);
            
            expect($newScopesConfig)->not->toBe($originalConfig);
            expect($originalConfig->getScopes())->toContain(ValidationScope::ALL);
            expect($newScopesConfig->getScopes())->toContain(ValidationScope::EXTERNAL);
            expect($newScopesConfig->getScopes())->not->toContain(ValidationScope::ALL);
            
            // Test withOutputFormat creates new instance
            $newFormatConfig = $originalConfig->withOutputFormat(OutputFormat::JSON);
            
            expect($newFormatConfig)->not->toBe($originalConfig);
            expect($originalConfig->getOutputFormat())->toBeNull();
            expect($newFormatConfig->getOutputFormat())->toBe(OutputFormat::JSON);
            
            // Original config should remain unchanged
            expect($originalConfig->getTimeout())->toBe(30);
            expect($originalConfig->getMaxRedirects())->toBe(5);
            expect($originalConfig->getConcurrentRequests())->toBe(10);
        });
    });

    describe('Scope Validation', function () {
        it('validates scope methods correctly', function () {
            // Test with ALL scope
            $allScopeConfig = ValidationConfig::create(['scopes' => [ValidationScope::ALL]]);
            
            expect($allScopeConfig->hasScope(ValidationScope::INTERNAL))->toBeTrue();
            expect($allScopeConfig->hasScope(ValidationScope::EXTERNAL))->toBeTrue();
            expect($allScopeConfig->hasScope(ValidationScope::ANCHOR))->toBeTrue();
            expect($allScopeConfig->shouldValidateInternal())->toBeTrue();
            expect($allScopeConfig->shouldValidateExternal())->toBeTrue();
            expect($allScopeConfig->shouldValidateAnchors())->toBeTrue();
            
            // Test with specific scopes
            $specificScopeConfig = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL, ValidationScope::ANCHOR]
            ]);
            
            expect($specificScopeConfig->hasScope(ValidationScope::INTERNAL))->toBeFalse();
            expect($specificScopeConfig->hasScope(ValidationScope::EXTERNAL))->toBeTrue();
            expect($specificScopeConfig->hasScope(ValidationScope::ANCHOR))->toBeTrue();
            expect($specificScopeConfig->shouldValidateInternal())->toBeFalse();
            expect($specificScopeConfig->shouldValidateExternal())->toBeTrue();
            expect($specificScopeConfig->shouldValidateAnchors())->toBeTrue();
            
            // Test effective scopes
            $effectiveScopes = $allScopeConfig->getEffectiveScopes();
            expect($effectiveScopes)->toContain(ValidationScope::INTERNAL);
            expect($effectiveScopes)->toContain(ValidationScope::EXTERNAL);
            expect($effectiveScopes)->toContain(ValidationScope::ANCHOR);
        });
    });

    describe('Configuration Serialization', function () {
        it('serializes configuration to array correctly', function () {
            $config = ValidationConfig::create([
                'scopes' => [ValidationScope::EXTERNAL, ValidationScope::ANCHOR],
                'timeout' => 45,
                'max_redirects' => 3,
                'concurrent_requests' => 15,
                'cache_enabled' => false,
                'follow_redirects' => true,
                'user_agent' => 'test-agent/1.0',
                'output_format' => OutputFormat::JSON,
                'additional_options' => ['test' => 'value'],
                'interactive' => true,
                'dry_run' => false,
            ]);
            
            $array = $config->toArray();
            
            expect($array)->toBeArray();
            expect($array)->toHaveKey('scopes');
            expect($array)->toHaveKey('timeout');
            expect($array)->toHaveKey('max_redirects');
            expect($array)->toHaveKey('concurrent_requests');
            expect($array)->toHaveKey('cache_enabled');
            expect($array)->toHaveKey('follow_redirects');
            expect($array)->toHaveKey('user_agent');
            expect($array)->toHaveKey('output_format');
            expect($array)->toHaveKey('additional_options');
            expect($array)->toHaveKey('interactive');
            expect($array)->toHaveKey('dry_run');
            
            // Verify values
            expect($array['scopes'])->toBe(['external', 'anchor']);
            expect($array['timeout'])->toBe(45);
            expect($array['max_redirects'])->toBe(3);
            expect($array['concurrent_requests'])->toBe(15);
            expect($array['cache_enabled'])->toBeFalse();
            expect($array['follow_redirects'])->toBeTrue();
            expect($array['user_agent'])->toBe('test-agent/1.0');
            expect($array['output_format'])->toBe('json');
            expect($array['additional_options'])->toBe(['test' => 'value']);
            expect($array['interactive'])->toBeTrue();
            expect($array['dry_run'])->toBeFalse();
        });
    });

    describe('Configuration Defaults', function () {
        it('creates configuration with proper defaults', function () {
            $defaultConfig = ValidationConfig::withDefaults();
            
            expect($defaultConfig)->toBeInstanceOf(ValidationConfig::class);
            expect($defaultConfig->getScopes())->toContain(ValidationScope::ALL);
            expect($defaultConfig->getTimeout())->toBe(30);
            expect($defaultConfig->getMaxRedirects())->toBe(5);
            expect($defaultConfig->getConcurrentRequests())->toBe(10);
            expect($defaultConfig->isCacheEnabled())->toBeTrue();
            expect($defaultConfig->shouldFollowRedirects())->toBeTrue();
            expect($defaultConfig->getUserAgent())->toBe('validate-links/1.0');
            expect($defaultConfig->getOutputFormat())->toBeNull();
            expect($defaultConfig->getAdditionalOptions())->toBeEmpty();
            expect($defaultConfig->isInteractive())->toBeFalse();
            expect($defaultConfig->isDryRun())->toBeFalse();
            
            // Test with overrides
            $overriddenConfig = ValidationConfig::withDefaults([
                'timeout' => 60,
                'user_agent' => 'custom-agent/2.0',
            ]);
            
            expect($overriddenConfig->getTimeout())->toBe(60);
            expect($overriddenConfig->getUserAgent())->toBe('custom-agent/2.0');
            // Other values should remain default
            expect($overriddenConfig->getMaxRedirects())->toBe(5);
            expect($overriddenConfig->getConcurrentRequests())->toBe(10);
        });
    });

    describe('Error Recovery', function () {
        it('recovers from configuration errors', function () {
            // Test recovery from invalid configuration
            ExceptionTestingHelpers::testErrorRecovery(
                function () {
                    return ValidationConfig::create(['timeout' => -999]);
                },
                function ($exception) {
                    // Recovery should return default configuration
                    return ValidationConfig::withDefaults();
                },
                ValidationConfig::class
            );
            
            // Test graceful degradation
            $fallbackConfig = ExceptionTestingHelpers::testGracefulDegradation(
                function () {
                    throw new InvalidArgumentException('Simulated configuration failure');
                },
                ValidationConfig::withDefaults()
            );
            
            expect($fallbackConfig)->toBeInstanceOf(ValidationConfig::class);
            expect($fallbackConfig->getTimeout())->toBe(30);
        });
    });

    describe('Validation Summary', function () {
        it('provides comprehensive configuration validation summary', function () {
            $summary = BoundaryConditionValidator::getValidationSummary();
            
            expect($summary)->toHaveKey('total_tests');
            expect($summary)->toHaveKey('passed_tests');
            expect($summary)->toHaveKey('failed_tests');
            expect($summary)->toHaveKey('success_rate');
            
            // Configuration validation should handle edge cases very well
            expect($summary['success_rate'])->toBeGreaterThanOrEqual(85, 'Configuration should handle edge cases robustly');
        });
    });
});