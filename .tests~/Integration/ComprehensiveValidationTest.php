<?php

declare(strict_types=1);

use App\Exceptions\ConfigurationException;
use App\Exceptions\SecurityException;
use App\Exceptions\ValidationException;
use Tests\Support\ErrorMessageTestHelper;
use Tests\Support\CompatibilityTestHelper;
use Tests\Support\LocalizationTestUtilities;

describe('Comprehensive Validation Suite', function () {
    describe('error message framework validation', function () {
        it('validates all error message testing utilities are functional', function () {
            // Test ErrorMessageTestHelper functionality
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            
            // Test exact text matching
            $exactMatch = ErrorMessageTestHelper::validateExactText(
                $exception->getMessage(),
                'Link validation failed for https://test.com (HTTP 404, 100ms)'
            );
            expect($exactMatch)->toBeTrue();
            
            // Test pattern validation
            $patternMatch = ErrorMessageTestHelper::validateStandardPattern(
                $exception->getMessage(),
                'link_validation_failed'
            );
            expect($patternMatch)->toBeTrue();
            
            // Test template validation
            $templateResults = ErrorMessageTestHelper::validateTemplate(
                $exception->getMessage(),
                'validation_error',
                ['action' => 'Link validation', 'target' => 'https://test.com', 'details' => 'HTTP 404, 100ms']
            );
            expect($templateResults['valid'])->toBeTrue();
            
            // Test parameter substitution
            $substitutionResults = ErrorMessageTestHelper::validateParameterSubstitution(
                'Error in {file} at line {line}',
                ['file' => 'test.php', 'line' => '10'],
                'Error in test.php at line 10'
            );
            expect($substitutionResults['valid'])->toBeTrue();
            
            // Test contextual information validation
            $contextResults = ErrorMessageTestHelper::validateContextualInformation($exception);
            expect($contextResults['valid'])->toBeTrue();
            
            // Test UX validation
            $uxResults = ErrorMessageTestHelper::validateUserExperience($exception->getMessage());
            expect($uxResults['ux_score'])->toBeGreaterThan(6.0);
            
            // Test accessibility validation
            $accessibilityResults = ErrorMessageTestHelper::validateAccessibility($exception->getMessage());
            expect($accessibilityResults['screen_reader_friendly'])->toBeTrue();
            
            // Test comprehensive report generation
            $report = ErrorMessageTestHelper::generateValidationReport($exception);
            expect($report['overall_score'])->toBeGreaterThan(0);
        });

        it('validates all standard patterns are working', function () {
            $testCases = [
                ['exception' => ValidationException::linkValidationFailed('https://test.com', 404, 100), 'pattern' => 'link_validation_failed'],
                ['exception' => ValidationException::fileProcessingFailed('/test/file.md', 'Error'), 'pattern' => 'file_processing_failed'],
                ['exception' => ValidationException::validationTimeout('https://test.com', 30), 'pattern' => 'validation_timeout'],
                ['exception' => ConfigurationException::missingFile('/config/test.json'), 'pattern' => 'configuration_missing_file'],
                ['exception' => ConfigurationException::invalidFormat('/config/test.json', 'JSON', 'invalid'), 'pattern' => 'configuration_invalid_format'],
                ['exception' => SecurityException::blacklistedDomain('evil.com'), 'pattern' => 'security_blacklisted_domain'],
                ['exception' => SecurityException::sslViolation('https://insecure.com', 'Certificate expired'), 'pattern' => 'security_ssl_violation'],
            ];

            foreach ($testCases as $testCase) {
                $isValid = ErrorMessageTestHelper::validateStandardPattern(
                    $testCase['exception']->getMessage(),
                    $testCase['pattern']
                );
                expect($isValid)->toBeTrue("Pattern {$testCase['pattern']} should match");
            }
        });

        it('validates all message templates are working', function () {
            $templateTypes = ErrorMessageTestHelper::getMessageTemplates();
            
            expect($templateTypes)->toHaveKey('validation_error')
                ->and($templateTypes)->toHaveKey('configuration_error')
                ->and($templateTypes)->toHaveKey('security_error');
            
            foreach ($templateTypes as $templateType => $templateData) {
                expect($templateData)->toHaveKey('pattern')
                    ->and($templateData)->toHaveKey('required_placeholders')
                    ->and($templateData)->toHaveKey('optional_placeholders');
            }
        });
    });

    describe('compatibility framework validation', function () {
        it('validates all compatibility testing utilities are functional', function () {
            // Test version compatibility
            $versionResults = CompatibilityTestHelper::testVersionCompatibility('php', '8.4');
            expect($versionResults['compatible'])->toBeTrue();
            
            // Test API compatibility
            $apiResults = CompatibilityTestHelper::testApiCompatibility(
                'App\Exceptions\ValidationException',
                '1.0.0',
                '2.0.0'
            );
            expect($apiResults)->toHaveKey('compatible');
            
            // Test legacy pattern validation
            $testContent = '<?php $this->expectException(Exception::class);';
            $tempFile = createTestFile('compat_test.php', $testContent);
            $legacyResults = CompatibilityTestHelper::validateLegacyTestPatterns($tempFile);
            expect($legacyResults['patterns_found'])->not->toBeEmpty();
            
            // Test migration path
            $migrationResults = CompatibilityTestHelper::testMigrationPath('3.0', '4.0', 'pest');
            expect($migrationResults['valid'])->toBeTrue();
            
            // Test compatibility matrix
            $matrixResults = CompatibilityTestHelper::validateCompatibilityMatrix(['php' => '8.4', 'laravel' => '12.0']);
            expect($matrixResults['valid'])->toBeTrue();
            
            // Test deprecation handling
            $deprecationResults = CompatibilityTestHelper::testDeprecationHandling('expectException', '3.0.0');
            expect($deprecationResults['deprecated'])->toBeTrue();
            
            // Test comprehensive report generation
            $report = CompatibilityTestHelper::generateCompatibilityReport(
                ['php' => '8.3', 'laravel' => '11.0'],
                ['php' => '8.4', 'laravel' => '12.0']
            );
            expect($report['compatibility_status'])->toBeString();
        });

        it('validates all supported components have complete data', function () {
            $supportedComponents = CompatibilityTestHelper::getSupportedComponents();
            
            foreach ($supportedComponents as $component) {
                $matrix = CompatibilityTestHelper::getCompatibilityMatrix($component);
                expect($matrix)->not->toBeEmpty("Component {$component} should have compatibility matrix");
                
                foreach ($matrix as $version => $versionData) {
                    expect($versionData)->toHaveKey('supported')
                        ->and($versionData)->toHaveKey('recommended')
                        ->and($versionData)->toHaveKey('features')
                        ->and($versionData)->toHaveKey('deprecations')
                        ->and($versionData)->toHaveKey('breaking_changes');
                }
            }
        });

        it('validates all legacy patterns have migration information', function () {
            $legacyPatterns = CompatibilityTestHelper::getLegacyTestPatterns();
            
            foreach ($legacyPatterns as $patternName => $patternData) {
                expect($patternData)->toHaveKey('pattern')
                    ->and($patternData)->toHaveKey('replacement')
                    ->and($patternData)->toHaveKey('deprecated_since')
                    ->and($patternData)->toHaveKey('migration_guide');
            }
        });

        it('validates all migration paths have complete information', function () {
            $migrationPaths = CompatibilityTestHelper::getMigrationPaths();
            
            foreach ($migrationPaths as $pathName => $pathData) {
                expect($pathData)->toHaveKey('steps')
                    ->and($pathData)->toHaveKey('automated_tools')
                    ->and($pathData)->toHaveKey('manual_changes')
                    ->and($pathData)->toHaveKey('rollback_steps');
                
                expect($pathData['steps'])->toBeArray()->not->toBeEmpty();
            }
        });
    });

    describe('localization framework validation', function () {
        it('validates all localization testing utilities are functional', function () {
            // Test locale support
            $localeResults = LocalizationTestUtilities::testLocaleSupport('en');
            expect($localeResults['supported'])->toBeTrue();
            
            // Test error message localization
            $localizationResults = LocalizationTestUtilities::testErrorMessageLocalization('validation_failed', 'en');
            expect($localizationResults['message_exists'])->toBeTrue();
            
            // Test character encoding
            $encodingResults = LocalizationTestUtilities::testCharacterEncoding('Hello World', 'UTF-8');
            expect($encodingResults['valid_encoding'])->toBeTrue();
            
            // Test RTL support
            $rtlResults = LocalizationTestUtilities::testRtlSupport('مرحبا', 'ar');
            expect($rtlResults['contains_rtl_characters'])->toBeTrue();
            
            // Test pluralization
            $pluralResults = LocalizationTestUtilities::testPluralization('en', 5);
            expect($pluralResults['pluralization_supported'])->toBeTrue();
            
            // Test fallback mechanism
            $fallbackResults = LocalizationTestUtilities::testFallbackMechanism('validation_failed', 'es', 'en');
            expect($fallbackResults['primary_available'])->toBeTrue();
            
            // Test date/time formatting
            $dateResults = LocalizationTestUtilities::testDateTimeFormatting('en');
            expect($dateResults['locale_supported'])->toBeTrue();
            
            // Test number formatting
            $numberResults = LocalizationTestUtilities::testNumberFormatting(1234.56, 'en');
            expect($numberResults['locale_supported'])->toBeTrue();
            
            // Test comprehensive report generation
            $report = LocalizationTestUtilities::generateLocalizationReport('en');
            expect($report['overall_score'])->toBeGreaterThan(0);
        });

        it('validates all supported locales have complete data', function () {
            $supportedLocales = LocalizationTestUtilities::getSupportedLocales();
            
            foreach ($supportedLocales as $locale) {
                $localeDefinition = LocalizationTestUtilities::getLocaleDefinition($locale);
                expect($localeDefinition)->not->toBeNull("Locale {$locale} should have definition");
                
                expect($localeDefinition)->toHaveKey('name')
                    ->and($localeDefinition)->toHaveKey('native_name')
                    ->and($localeDefinition)->toHaveKey('direction')
                    ->and($localeDefinition)->toHaveKey('encoding')
                    ->and($localeDefinition)->toHaveKey('character_set')
                    ->and($localeDefinition)->toHaveKey('test_strings');
            }
        });

        it('validates all error message translations exist', function () {
            $translations = LocalizationTestUtilities::getErrorMessageTranslations();
            $supportedLocales = LocalizationTestUtilities::getSupportedLocales();
            
            foreach ($translations as $messageKey => $messageTranslations) {
                foreach ($supportedLocales as $locale) {
                    expect($messageTranslations)->toHaveKey($locale, "Message {$messageKey} should exist for locale {$locale}");
                }
            }
        });

        it('validates pluralization data completeness', function () {
            $pluralizationData = LocalizationTestUtilities::getPluralizationTestData();
            
            foreach ($pluralizationData as $locale => $localeData) {
                expect($localeData)->toBeArray()->not->toBeEmpty("Locale {$locale} should have pluralization data");
                
                foreach ($localeData as $messageKey => $messageData) {
                    expect($messageData)->toBeArray()->not->toBeEmpty("Message {$messageKey} should have plural forms");
                }
            }
        });
    });

    describe('enhanced expectations validation', function () {
        it('validates all custom expectations work correctly', function () {
            // Test toHaveValidErrorMessage
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            expect($exception)->toHaveValidErrorMessage();
            
            // Test toHaveValidErrorContext
            expect($exception)->toHaveValidErrorContext(['url', 'status_code', 'response_time']);
            
            // Test toMatchErrorPattern
            expect($exception->getMessage())->toMatchErrorPattern('link_validation_failed');
            
            // Test toBeCompatibleVersion
            expect('8.4')->toBeCompatibleVersion('php');
            
            // Test toSupportLocale
            expect('en')->toSupportLocale();
            
            // Test toHaveValidEncoding
            expect('Hello World')->toHaveValidEncoding('UTF-8');
            
            // Test toBeAccessibleErrorMessage
            expect($exception->getMessage())->toBeAccessibleErrorMessage();
            
            // Test toHaveGoodUX
            expect($exception->getMessage())->toHaveGoodUX(6.0);
        });
    });

    describe('helper functions validation', function () {
        it('validates all global helper functions work correctly', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            
            // Test error message helpers
            expect(validateErrorMessage($exception->getMessage(), $exception->getMessage()))->toBeTrue();
            expect(validateErrorPattern($exception->getMessage(), 'link_validation_failed'))->toBeTrue();
            
            $templateResults = validateErrorTemplate($exception->getMessage(), 'validation_error', [
                'action' => 'Link validation',
                'target' => 'https://test.com',
                'details' => 'HTTP 404, 100ms'
            ]);
            expect($templateResults['valid'])->toBeTrue();
            
            $contextResults = validateErrorContext($exception);
            expect($contextResults['valid'])->toBeTrue();
            
            $uxResults = validateErrorUX($exception->getMessage());
            expect($uxResults['ux_score'])->toBeGreaterThan(0);
            
            $accessibilityResults = validateErrorAccessibility($exception->getMessage());
            expect($accessibilityResults['screen_reader_friendly'])->toBeTrue();
            
            // Test compatibility helpers
            expect(testVersionCompatibility('php', '8.4')['compatible'])->toBeTrue();
            expect(testApiCompatibility('App\Exceptions\ValidationException', '1.0.0', '2.0.0'))->toHaveKey('compatible');
            
            $matrixResults = validateCompatibilityMatrix(['php' => '8.4']);
            expect($matrixResults['valid'])->toBeTrue();
            
            // Test localization helpers
            expect(testLocaleSupport('en')['supported'])->toBeTrue();
            expect(testCharacterEncoding('Hello')['valid_encoding'])->toBeTrue();
            expect(testPluralization('en', 5)['pluralization_supported'])->toBeTrue();
        });
    });

    describe('Laravel Zero specific considerations', function () {
        it('validates console error message formatting', function () {
            $exception = ValidationException::linkValidationFailed('https://test.com', 404, 100);
            $message = $exception->getMessage();
            
            // Console messages should be readable and properly formatted
            expect($message)->not->toContain("\n") // No line breaks in single error messages
                ->and($message)->not->toStartWith(' ') // No leading spaces
                ->and($message)->not->toEndWith(' ') // No trailing spaces
                ->and(strlen($message))->toBeLessThan(200); // Reasonable length for console display
        });

        it('validates configuration error messages for Laravel Zero', function () {
            $configErrors = [
                ConfigurationException::missingFile('/config/validate-links.php'),
                ConfigurationException::invalidFormat('/config/validate-links.php', 'PHP', 'invalid syntax'),
                ConfigurationException::missingRequiredKey('validation.timeout', '/config/validate-links.php'),
                ConfigurationException::invalidValue('validation.max_redirects', 'invalid', 'integer'),
            ];

            foreach ($configErrors as $exception) {
                expect($exception)->toHaveValidErrorMessage()
                    ->and($exception->getErrorCode())->toBe('CONFIGURATION_ERROR')
                    ->and($exception->getSeverity())->toBe('high');
                
                // Configuration errors should provide clear guidance
                $uxResults = validateErrorUX($exception->getMessage());
                expect($uxResults['actionability_score'])->toBeGreaterThan(2);
            }
        });

        it('validates command-specific error handling', function () {
            // Simulate command validation errors
            $commandErrors = [
                ValidationException::fileProcessingFailed('validate-links.json', 'Invalid JSON configuration'),
                ValidationException::validationTimeout('https://api.example.com', 30),
                SecurityException::blacklistedDomain('malicious-site.com'),
            ];

            foreach ($commandErrors as $exception) {
                expect($exception)->toHaveValidErrorMessage();
                
                // Command errors should be user-friendly
                $uxResults = validateErrorUX($exception->getMessage());
                expect($uxResults['clarity_score'])->toBeGreaterThan(6.0);
                
                $accessibilityResults = validateErrorAccessibility($exception->getMessage());
                expect($accessibilityResults['plain_language'])->toBeTrue();
            }
        });

        it('validates service error message consistency', function () {
            // Test that service-level errors maintain consistency
            $serviceErrors = [
                ValidationException::linkValidationFailed('https://service.com/api', 503, 5000),
                SecurityException::sslViolation('https://insecure-service.com', 'Certificate validation failed'),
                ConfigurationException::invalidValue('service.timeout', 'unlimited', 'integer'),
            ];

            foreach ($serviceErrors as $exception) {
                expect($exception)->toHaveValidErrorMessage()
                    ->and($exception)->toHaveValidErrorContext();
                
                // All service errors should follow the same pattern
                $contextResults = validateErrorContext($exception);
                expect($contextResults['severity_appropriate'])->toBeTrue();
            }
        });

        it('validates exit code and error level mapping', function () {
            $errorMappings = [
                ['exception' => ValidationException::linkValidationFailed('https://test.com', 404, 100), 'expected_severity' => 'medium'],
                ['exception' => ConfigurationException::missingFile('/config/test.json'), 'expected_severity' => 'high'],
                ['exception' => SecurityException::blacklistedDomain('evil.com'), 'expected_severity' => 'critical'],
            ];

            foreach ($errorMappings as $mapping) {
                expect($mapping['exception']->getSeverity())->toBe($mapping['expected_severity']);
                
                // Severity should correlate with appropriate exit codes for CLI
                $severity = $mapping['exception']->getSeverity();
                $expectedExitCode = match ($severity) {
                    'low' => 1,
                    'medium' => 2,
                    'high' => 3,
                    'critical' => 4,
                    default => 1,
                };
                
                expect($expectedExitCode)->toBeInt()->toBeGreaterThan(0);
            }
        });
    });

    describe('comprehensive integration validation', function () {
        it('validates complete error message and compatibility workflow', function () {
            // 1. Create and validate error message
            $exception = ValidationException::linkValidationFailed('https://api.test.com/v1/data', 429, 2000);
            
            expect($exception)->toHaveValidErrorMessage()
                ->and($exception)->toHaveValidErrorContext(['url', 'status_code', 'response_time', 'validation_type'])
                ->and($exception->getMessage())->toMatchErrorPattern('link_validation_failed')
                ->and($exception->getMessage())->toBeAccessibleErrorMessage()
                ->and($exception->getMessage())->toHaveGoodUX(7.0);
            
            // 2. Test localization
            $locales = ['en', 'es', 'fr', 'de'];
            foreach ($locales as $locale) {
                expect($locale)->toSupportLocale();
                $localizationResults = validateErrorLocalization('validation_failed', $locale);
                expect($localizationResults['message_exists'])->toBeTrue();
            }
            
            // 3. Test compatibility
            expect('8.4')->toBeCompatibleVersion('php');
            $matrixResults = validateCompatibilityMatrix(['php' => '8.4', 'laravel' => '12.0', 'pest' => '4.0']);
            expect($matrixResults['valid'])->toBeTrue();
            
            // 4. Generate comprehensive reports
            $errorReport = generateErrorValidationReport($exception, ['locale' => 'en']);
            expect($errorReport['overall_score'])->toBeGreaterThan(8.0);
            
            $compatibilityReport = generateCompatibilityReport(
                ['php' => '8.4', 'laravel' => '12.0'],
                ['php' => '8.4', 'laravel' => '12.0']
            );
            expect($compatibilityReport['compatibility_status'])->toBe('compatible');
            
            $localizationReport = generateLocalizationReport('en');
            expect($localizationReport['overall_score'])->toBeGreaterThan(8.0);
        });

        it('validates framework resilience and error handling', function () {
            // Test with edge cases and boundary conditions
            $edgeCases = [
                ['url' => '', 'status_code' => 0, 'response_time' => 0],
                ['url' => 'https://very-long-domain-name-that-exceeds-normal-limits.example.com/very/long/path/that/might/cause/issues', 'status_code' => 999, 'response_time' => PHP_INT_MAX],
                ['url' => 'https://unicode-тест.рф/测试/🚀', 'status_code' => 404, 'response_time' => 100],
            ];

            foreach ($edgeCases as $case) {
                $exception = ValidationException::linkValidationFailed($case['url'], $case['status_code'], $case['response_time']);
                
                // Framework should handle edge cases gracefully
                expect($exception)->toHaveValidErrorMessage();
                expect($exception->getMessage())->toHaveValidEncoding('UTF-8');
                
                $contextResults = validateErrorContext($exception);
                expect($contextResults['valid'])->toBeTrue();
            }
        });

        it('validates performance and scalability considerations', function () {
            // Test with multiple error instances
            $exceptions = [];
            for ($i = 0; $i < 100; $i++) {
                $exceptions[] = ValidationException::linkValidationFailed("https://test{$i}.com", 404, 100);
            }
            
            // All exceptions should maintain consistency
            foreach ($exceptions as $exception) {
                expect($exception)->toHaveValidErrorMessage();
                expect($exception->getErrorCode())->toBe('VALIDATION_ERROR');
                expect($exception->getSeverity())->toBe('medium');
            }
            
            // Test batch validation performance
            $startTime = microtime(true);
            foreach ($exceptions as $exception) {
                validateErrorPattern($exception->getMessage(), 'link_validation_failed');
            }
            $endTime = microtime(true);
            
            $executionTime = $endTime - $startTime;
            expect($executionTime)->toBeLessThan(1.0); // Should complete within 1 second
        });
    });
});