<?php

declare(strict_types=1);

use App\Exceptions\ConfigurationException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidationException;

describe('Error Message Integration', function () {
    describe('comprehensive error message validation', function () {
        it('validates ValidationException with all criteria', function () {
            $exception = ValidationException::linkValidationFailed('https://example.com/test', 404, 1500);
            
            // Test using enhanced expectations
            expect($exception)->toHaveValidErrorMessage()
                ->and($exception)->toHaveValidErrorContext(['url', 'status_code', 'response_time', 'validation_type'])
                ->and($exception->getMessage())->toMatchErrorPattern('link_validation_failed')
                ->and($exception->getMessage())->toBeAccessibleErrorMessage()
                ->and($exception->getMessage())->toHaveGoodUX(7.0);
            
            // Test using helper functions
            expect(validateErrorMessage($exception->getMessage(), "Link validation failed for https://example.com/test (HTTP 404, 1500ms)"))->toBeTrue()
                ->and(validateErrorPattern($exception->getMessage(), 'link_validation_failed'))->toBeTrue();
            
            $contextResults = validateErrorContext($exception, [
                'url' => 'https://example.com/test',
                'status_code' => 404,
                'response_time' => 1500,
                'validation_type' => 'link_validation',
            ]);
            
            expect($contextResults['valid'])->toBeTrue()
                ->and($contextResults['context_complete'])->toBeTrue()
                ->and($contextResults['context_accurate'])->toBeTrue();
        });

        it('validates ConfigurationException with comprehensive checks', function () {
            $exception = ConfigurationException::missingFile('/config/important.json');
            
            expect($exception)->toHaveValidErrorMessage()
                ->and($exception)->toHaveValidErrorContext(['file_path', 'error_type'])
                ->and($exception->getMessage())->toMatchErrorPattern('configuration_missing_file');
            
            $uxResults = validateErrorUX($exception->getMessage());
            expect($uxResults['ux_score'])->toBeGreaterThan(6.0)
                ->and($uxResults['length_appropriate'])->toBeTrue()
                ->and($uxResults['tone_appropriate'])->toBeTrue();
            
            $accessibilityResults = validateErrorAccessibility($exception->getMessage());
            expect($accessibilityResults['screen_reader_friendly'])->toBeTrue()
                ->and($accessibilityResults['color_independent'])->toBeTrue();
        });

        it('validates SecurityException with security-specific checks', function () {
            $exception = SecurityException::blacklistedDomain('malicious.com');
            
            expect($exception)->toHaveValidErrorMessage()
                ->and($exception)->toHaveValidErrorContext(['domain', 'threat_type', 'risk_level'])
                ->and($exception->getMessage())->toMatchErrorPattern('security_blacklisted_domain');
            
            // Security exceptions should have critical severity
            expect($exception->getSeverity())->toBe('critical')
                ->and($exception->getErrorCode())->toBe('SECURITY_VIOLATION');
            
            $contextResults = validateErrorContext($exception);
            expect($contextResults['severity_appropriate'])->toBeTrue();
        });
    });

    describe('localization integration testing', function () {
        it('validates error message localization across multiple locales', function () {
            $supportedLocales = ['en', 'es', 'fr', 'de', 'ja', 'zh', 'ar', 'ru'];
            $messageKey = 'validation_failed';
            
            foreach ($supportedLocales as $locale) {
                expect($locale)->toSupportLocale();
                
                $localizationResults = validateErrorLocalization($messageKey, $locale);
                expect($localizationResults['message_exists'])->toBeTrue("Message should exist for locale {$locale}")
                    ->and($localizationResults['encoding_valid'])->toBeTrue("Encoding should be valid for locale {$locale}")
                    ->and($localizationResults['length_appropriate'])->toBeTrue("Length should be appropriate for locale {$locale}");
                
                // Test character encoding
                $localizedMessage = $localizationResults['localized_message'];
                expect($localizedMessage)->toHaveValidEncoding('UTF-8');
            }
        });

        it('validates RTL language support integration', function () {
            $rtlLocales = ['ar'];
            $testText = 'مرحبا بالعالم - اختبار النص العربي';
            
            foreach ($rtlLocales as $locale) {
                $rtlResults = testRtlSupport($testText, $locale);
                
                expect($rtlResults['is_rtl_locale'])->toBeTrue()
                    ->and($rtlResults['contains_rtl_characters'])->toBeTrue()
                    ->and($rtlResults['text_alignment'])->toBe('right');
            }
        });

        it('validates pluralization integration across languages', function () {
            $testCases = [
                ['locale' => 'en', 'counts' => [0, 1, 2, 5, 10]],
                ['locale' => 'ru', 'counts' => [0, 1, 2, 3, 4, 5, 11, 21, 22]],
                ['locale' => 'ar', 'counts' => [0, 1, 2, 3, 10, 11, 100]],
            ];

            foreach ($testCases as $testCase) {
                foreach ($testCase['counts'] as $count) {
                    $pluralResults = testPluralization($testCase['locale'], $count);
                    
                    expect($pluralResults['pluralization_supported'])->toBeTrue()
                        ->and($pluralResults['correct_form'])->not->toBeEmpty()
                        ->and($pluralResults['rule_applied'])->toBeString();
                }
            }
        });

        it('validates fallback mechanism integration', function () {
            $testCases = [
                ['primary' => 'es', 'fallback' => 'en', 'should_use_primary' => true],
                ['primary' => 'unsupported_locale', 'fallback' => 'en', 'should_use_primary' => false],
                ['primary' => 'fr', 'fallback' => 'es', 'should_use_primary' => true],
            ];

            foreach ($testCases as $testCase) {
                $fallbackResults = testFallbackMechanism(
                    'validation_failed',
                    $testCase['primary'],
                    $testCase['fallback']
                );
                
                if ($testCase['should_use_primary']) {
                    expect($fallbackResults['primary_available'])->toBeTrue()
                        ->and($fallbackResults['fallback_used'])->toBeFalse();
                } else {
                    expect($fallbackResults['primary_available'])->toBeFalse()
                        ->and($fallbackResults['fallback_used'])->toBeTrue();
                }
                
                expect($fallbackResults['final_message'])->not->toBeEmpty();
            }
        });
    });

    describe('compatibility integration testing', function () {
        it('validates current environment compatibility', function () {
            $currentPhpVersion = PHP_VERSION;
            $majorMinor = implode('.', array_slice(explode('.', $currentPhpVersion), 0, 2));
            
            expect($majorMinor)->toBeCompatibleVersion('php');
            
            $compatibilityResults = testVersionCompatibility('php', $majorMinor);
            expect($compatibilityResults['compatible'])->toBeTrue()
                ->and($compatibilityResults['supported'])->toBeTrue();
        });

        it('validates version compatibility matrix integration', function () {
            $compatibleCombinations = [
                ['php' => '8.4', 'laravel' => '12.0', 'pest' => '4.0'],
                ['php' => '8.3', 'laravel' => '12.0', 'pest' => '4.0'],
                ['php' => '8.3', 'laravel' => '11.0', 'pest' => '3.0'],
            ];

            foreach ($compatibleCombinations as $combination) {
                $matrixResults = validateCompatibilityMatrix($combination);
                
                expect($matrixResults['valid'])->toBeTrue("Combination should be valid: " . json_encode($combination))
                    ->and($matrixResults['compatible_combinations'])->not->toBeEmpty()
                    ->and($matrixResults['incompatible_combinations'])->toBeEmpty();
            }
        });

        it('validates legacy pattern detection integration', function () {
            // Create a test file with legacy patterns
            $testContent = '<?php
                use PHPUnit\Framework\TestCase;
                
                class LegacyTest extends TestCase {
                    public function testSomething() {
                        $this->expectException(Exception::class);
                        $mock = $this->createMock(SomeClass::class);
                        $this->assertSame($expected, $actual);
                    }
                }
            ';
            
            $tempFile = createTestFile('legacy_integration_test.php', $testContent);
            
            $legacyResults = validateLegacyPatterns($tempFile);
            
            expect($legacyResults['patterns_found'])->not->toBeEmpty()
                ->and($legacyResults['deprecated_patterns'])->not->toBeEmpty()
                ->and($legacyResults['migration_suggestions'])->not->toBeEmpty();
            
            // Verify specific patterns are detected
            expect($legacyResults['patterns_found'])->toContain('exception_testing')
                ->and($legacyResults['patterns_found'])->toContain('mock_creation')
                ->and($legacyResults['patterns_found'])->toContain('assertion_methods');
        });

        it('validates migration path integration', function () {
            $migrationCases = [
                ['from' => '3.0', 'to' => '4.0', 'component' => 'pest'],
                ['from' => '11.0', 'to' => '12.0', 'component' => 'laravel'],
            ];

            foreach ($migrationCases as $case) {
                $migrationResults = testMigrationPath($case['from'], $case['to'], $case['component']);
                
                expect($migrationResults['valid'])->toBeTrue()
                    ->and($migrationResults['migration_available'])->toBeTrue()
                    ->and($migrationResults['steps'])->not->toBeEmpty()
                    ->and($migrationResults['estimated_effort'])->toBeIn(['low', 'medium', 'high', 'very_high']);
            }
        });

        it('validates deprecation handling integration', function () {
            $deprecatedFeatures = ['expectException', 'createMock', 'assertSame'];
            
            foreach ($deprecatedFeatures as $feature) {
                $deprecationResults = testDeprecationHandling($feature, '3.0.0');
                
                expect($deprecationResults['deprecated'])->toBeTrue()
                    ->and($deprecationResults['replacement'])->not->toBeEmpty()
                    ->and($deprecationResults['migration_guide'])->not->toBeEmpty();
            }
        });
    });

    describe('comprehensive reporting integration', function () {
        it('generates comprehensive error validation report', function () {
            $exception = ValidationException::linkValidationFailed('https://api.example.com', 500, 3000);
            
            $report = generateErrorValidationReport($exception, ['locale' => 'en', 'message_key' => 'validation_failed']);
            
            expect($report)->toHaveKey('timestamp')
                ->and($report)->toHaveKey('exception_class')
                ->and($report)->toHaveKey('message')
                ->and($report)->toHaveKey('error_code')
                ->and($report)->toHaveKey('severity')
                ->and($report)->toHaveKey('validation_results')
                ->and($report)->toHaveKey('overall_score')
                ->and($report)->toHaveKey('recommendations');
            
            expect($report['overall_score'])->toBeGreaterThan(0)
                ->and($report['validation_results'])->toHaveKey('ux_compliance')
                ->and($report['validation_results'])->toHaveKey('accessibility')
                ->and($report['validation_results'])->toHaveKey('contextual_info')
                ->and($report['validation_results'])->toHaveKey('localization');
        });

        it('generates comprehensive compatibility report', function () {
            $currentVersions = ['php' => '8.3', 'laravel' => '11.0', 'pest' => '3.0'];
            $targetVersions = ['php' => '8.4', 'laravel' => '12.0', 'pest' => '4.0'];
            
            $report = generateCompatibilityReport($currentVersions, $targetVersions);
            
            expect($report)->toHaveKey('timestamp')
                ->and($report)->toHaveKey('current_versions')
                ->and($report)->toHaveKey('target_versions')
                ->and($report)->toHaveKey('compatibility_status')
                ->and($report)->toHaveKey('migration_required')
                ->and($report)->toHaveKey('migration_paths')
                ->and($report)->toHaveKey('recommendations');
            
            expect($report['migration_required'])->toBeTrue()
                ->and($report['compatibility_status'])->toBe('migration_required')
                ->and($report['migration_paths'])->not->toBeEmpty();
        });

        it('generates comprehensive localization report', function () {
            $locale = 'es';
            $messageKeys = ['validation_failed', 'file_not_found', 'invalid_format'];
            
            $report = generateLocalizationReport($locale, $messageKeys);
            
            expect($report)->toHaveKey('timestamp')
                ->and($report)->toHaveKey('locale')
                ->and($report)->toHaveKey('locale_support')
                ->and($report)->toHaveKey('message_tests')
                ->and($report)->toHaveKey('encoding_tests')
                ->and($report)->toHaveKey('pluralization_tests')
                ->and($report)->toHaveKey('formatting_tests')
                ->and($report)->toHaveKey('overall_score')
                ->and($report)->toHaveKey('recommendations');
            
            expect($report['overall_score'])->toBeGreaterThan(7.0)
                ->and($report['locale_support']['supported'])->toBeTrue();
        });
    });

    describe('end-to-end validation workflow', function () {
        it('validates complete error message lifecycle', function () {
            // 1. Create exception with comprehensive context
            $exception = ValidationException::linkValidationFailed(
                'https://api.example.com/v1/users/123',
                401,
                750
            );
            
            // 2. Validate basic error message properties
            expect($exception)->toHaveValidErrorMessage()
                ->and($exception)->toHaveValidErrorContext(['url', 'status_code', 'response_time', 'validation_type']);
            
            // 3. Validate message pattern and formatting
            expect($exception->getMessage())->toMatchErrorPattern('link_validation_failed')
                ->and($exception->getMessage())->toBeAccessibleErrorMessage()
                ->and($exception->getMessage())->toHaveGoodUX(7.0);
            
            // 4. Validate localization support
            $locales = ['en', 'es', 'fr'];
            foreach ($locales as $locale) {
                $localizationResults = validateErrorLocalization('validation_failed', $locale);
                expect($localizationResults['message_exists'])->toBeTrue();
            }
            
            // 5. Generate comprehensive validation report
            $report = generateErrorValidationReport($exception, ['locale' => 'en']);
            expect($report['overall_score'])->toBeGreaterThan(8.0);
            
            // 6. Validate serialization and context preservation
            $serialized = $exception->__serialize();
            expect($serialized)->toHaveKey('context')
                ->and($serialized['context'])->toHaveKey('url')
                ->and($serialized['context'])->toHaveKey('status_code');
        });

        it('validates complete compatibility validation workflow', function () {
            // 1. Test current environment compatibility
            $currentPhp = '8.4';
            expect($currentPhp)->toBeCompatibleVersion('php');
            
            // 2. Validate version matrix
            $versions = ['php' => '8.4', 'laravel' => '12.0', 'pest' => '4.0'];
            $matrixResults = validateCompatibilityMatrix($versions);
            expect($matrixResults['valid'])->toBeTrue();
            
            // 3. Test migration scenarios
            $migrationResults = testMigrationPath('3.0', '4.0', 'pest');
            expect($migrationResults['valid'])->toBeTrue();
            
            // 4. Validate legacy pattern detection
            $testContent = '<?php $this->expectException(Exception::class);';
            $tempFile = createTestFile('workflow_test.php', $testContent);
            $legacyResults = validateLegacyPatterns($tempFile);
            expect($legacyResults['patterns_found'])->not->toBeEmpty();
            
            // 5. Generate comprehensive compatibility report
            $currentVersions = ['php' => '8.3', 'laravel' => '11.0'];
            $targetVersions = ['php' => '8.4', 'laravel' => '12.0'];
            $report = generateCompatibilityReport($currentVersions, $targetVersions);
            expect($report['compatibility_status'])->toBeIn(['compatible', 'migration_required']);
        });

        it('validates complete localization validation workflow', function () {
            // 1. Test locale support
            $locale = 'ja';
            expect($locale)->toSupportLocale();
            
            // 2. Test character encoding
            $japaneseText = 'こんにちは世界 - 日本語テスト';
            expect($japaneseText)->toHaveValidEncoding('UTF-8');
            
            // 3. Test error message localization
            $localizationResults = validateErrorLocalization('validation_failed', $locale);
            expect($localizationResults['message_exists'])->toBeTrue();
            
            // 4. Test pluralization
            $pluralResults = testPluralization($locale, 5);
            expect($pluralResults['pluralization_supported'])->toBeTrue();
            
            // 5. Test date/time formatting
            $dateResults = testDateTimeFormatting($locale);
            expect($dateResults['locale_supported'])->toBeTrue();
            
            // 6. Generate comprehensive localization report
            $report = generateLocalizationReport($locale);
            expect($report['overall_score'])->toBeGreaterThan(7.0);
        });
    });
});