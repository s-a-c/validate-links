<?php

declare(strict_types=1);

use Tests\Support\ErrorMessageTestHelper;
use Tests\Support\LocalizationTestUtilities;

describe('Error Message Localization', function () {
    describe('locale support validation', function () {
        it('validates supported locales', function () {
            $supportedLocales = ['en', 'es', 'fr', 'de', 'ja', 'zh', 'ar', 'ru', 'hi', 'ko'];
            
            foreach ($supportedLocales as $locale) {
                $results = LocalizationTestUtilities::testLocaleSupport($locale);
                
                expect($results['supported'])->toBeTrue("Locale {$locale} should be supported")
                    ->and($results['valid_format'])->toBeTrue("Locale {$locale} should have valid format")
                    ->and($results['encoding_support'])->toBeTrue("Locale {$locale} should support UTF-8 encoding");
            }
        });

        it('validates locale format requirements', function () {
            $validFormats = ['en', 'en_US', 'fr', 'fr_CA', 'zh', 'zh_CN'];
            $invalidFormats = ['english', 'EN', 'en-US', 'fr_ca', '123', ''];
            
            foreach ($validFormats as $locale) {
                $results = LocalizationTestUtilities::testLocaleSupport($locale);
                expect($results['valid_format'])->toBeTrue("Format {$locale} should be valid");
            }
            
            foreach ($invalidFormats as $locale) {
                $results = LocalizationTestUtilities::testLocaleSupport($locale);
                expect($results['valid_format'])->toBeFalse("Format {$locale} should be invalid");
            }
        });

        it('validates character set support for different locales', function () {
            $testCases = [
                ['locale' => 'en', 'expected_charset' => 'latin'],
                ['locale' => 'es', 'expected_charset' => 'latin_extended'],
                ['locale' => 'ru', 'expected_charset' => 'cyrillic'],
                ['locale' => 'ar', 'expected_charset' => 'arabic'],
                ['locale' => 'ja', 'expected_charset' => 'cjk'],
                ['locale' => 'zh', 'expected_charset' => 'cjk'],
                ['locale' => 'hi', 'expected_charset' => 'devanagari'],
                ['locale' => 'ko', 'expected_charset' => 'hangul'],
            ];

            foreach ($testCases as $testCase) {
                $results = LocalizationTestUtilities::testLocaleSupport($testCase['locale']);
                
                expect($results['supported'])->toBeTrue()
                    ->and($results['locale_data']['character_set'])->toBe($testCase['expected_charset']);
            }
        });

        it('validates text direction support', function () {
            $ltrLocales = ['en', 'es', 'fr', 'de', 'ja', 'zh', 'ru', 'hi', 'ko'];
            $rtlLocales = ['ar'];
            
            foreach ($ltrLocales as $locale) {
                $results = LocalizationTestUtilities::testLocaleSupport($locale);
                expect($results['locale_data']['direction'])->toBe('ltr');
            }
            
            foreach ($rtlLocales as $locale) {
                $results = LocalizationTestUtilities::testLocaleSupport($locale);
                expect($results['locale_data']['direction'])->toBe('rtl');
            }
        });
    });

    describe('error message translation validation', function () {
        it('validates error message translations exist for all supported locales', function () {
            $messageKeys = ['validation_failed', 'file_not_found', 'invalid_format', 'access_denied', 'timeout_occurred'];
            $supportedLocales = LocalizationTestUtilities::getSupportedLocales();
            
            foreach ($messageKeys as $messageKey) {
                foreach ($supportedLocales as $locale) {
                    $results = LocalizationTestUtilities::testErrorMessageLocalization($messageKey, $locale);
                    
                    expect($results['message_exists'])->toBeTrue("Message '{$messageKey}' should exist for locale '{$locale}'")
                        ->and($results['encoding_valid'])->toBeTrue("Message should have valid encoding")
                        ->and($results['localized_message'])->not->toBeEmpty("Localized message should not be empty");
                }
            }
        });

        it('validates translation quality for different locales', function () {
            $messageKey = 'validation_failed';
            $testCases = [
                ['locale' => 'en', 'expected_quality' => 'good'],
                ['locale' => 'es', 'expected_quality' => 'good'],
                ['locale' => 'fr', 'expected_quality' => 'good'],
                ['locale' => 'de', 'expected_quality' => 'good'],
                ['locale' => 'ja', 'expected_quality' => 'good'],
            ];

            foreach ($testCases as $testCase) {
                $results = LocalizationTestUtilities::testErrorMessageLocalization($messageKey, $testCase['locale']);
                
                expect($results['translation_quality'])->toBe($testCase['expected_quality'])
                    ->and($results['cultural_appropriateness'])->toBe('appropriate');
            }
        });

        it('validates parameter interpolation in localized messages', function () {
            $messageKey = 'validation_failed';
            $parameters = ['url' => 'https://example.com', 'status' => '404'];
            
            foreach (LocalizationTestUtilities::getSupportedLocales() as $locale) {
                $results = LocalizationTestUtilities::testErrorMessageLocalization($messageKey, $locale, $parameters);
                
                expect($results['parameter_support'])->toBeTrue("Parameter interpolation should work for locale {$locale}");
            }
        });

        it('validates message length appropriateness across locales', function () {
            $messageKey = 'validation_failed';
            
            foreach (LocalizationTestUtilities::getSupportedLocales() as $locale) {
                $results = LocalizationTestUtilities::testErrorMessageLocalization($messageKey, $locale);
                
                expect($results['length_appropriate'])->toBeTrue("Message length should be appropriate for locale {$locale}")
                    ->and(mb_strlen($results['localized_message'], 'UTF-8'))->toBeGreaterThan(5)
                    ->and(mb_strlen($results['localized_message'], 'UTF-8'))->toBeLessThan(200);
            }
        });
    });

    describe('character encoding validation', function () {
        it('validates UTF-8 encoding for all localized messages', function () {
            $testStrings = [
                'en' => 'Hello World',
                'es' => 'Hola Mundo con acentos: café, niño',
                'fr' => 'Bonjour le monde avec accents: café, élève',
                'de' => 'Hallo Welt mit Umlauten: Größe, Mädchen',
                'ja' => 'こんにちは世界 - ひらがな、カタカナ、漢字',
                'zh' => '你好世界 - 简体中文测试',
                'ar' => 'مرحبا بالعالم - اختبار النص العربي',
                'ru' => 'Привет мир - тест кириллицы',
                'hi' => 'नमस्ते दुनिया - देवनागरी परीक्षण',
                'ko' => '안녕하세요 세계 - 한글 테스트',
            ];

            foreach ($testStrings as $locale => $text) {
                $results = LocalizationTestUtilities::testCharacterEncoding($text, 'UTF-8');
                
                expect($results['valid_encoding'])->toBeTrue("Text for locale {$locale} should be valid UTF-8")
                    ->and($results['encoding_detected'])->toBe('UTF-8')
                    ->and($results['character_count'])->toBeGreaterThan(0)
                    ->and($results['byte_count'])->toBeGreaterThanOrEqual($results['character_count']);
            }
        });

        it('validates multibyte character handling', function () {
            $multibyteCases = [
                ['text' => 'café', 'locale' => 'fr', 'expected_multibyte' => true],
                ['text' => 'Größe', 'locale' => 'de', 'expected_multibyte' => true],
                ['text' => 'こんにちは', 'locale' => 'ja', 'expected_multibyte' => true],
                ['text' => '你好', 'locale' => 'zh', 'expected_multibyte' => true],
                ['text' => 'مرحبا', 'locale' => 'ar', 'expected_multibyte' => true],
                ['text' => 'Привет', 'locale' => 'ru', 'expected_multibyte' => true],
                ['text' => 'नमस्ते', 'locale' => 'hi', 'expected_multibyte' => true],
                ['text' => '안녕', 'locale' => 'ko', 'expected_multibyte' => true],
                ['text' => 'Hello', 'locale' => 'en', 'expected_multibyte' => false],
            ];

            foreach ($multibyteCases as $case) {
                $results = LocalizationTestUtilities::testCharacterEncoding($case['text']);
                
                expect($results['multibyte_characters'])->toBe($case['expected_multibyte'])
                    ->and($results['valid_encoding'])->toBeTrue();
            }
        });

        it('validates special character identification', function () {
            $testCases = [
                ['text' => 'café élève', 'expected_chars' => ['latin_extended']],
                ['text' => 'Москва', 'expected_chars' => ['cyrillic']],
                ['text' => 'مرحبا', 'expected_chars' => ['arabic']],
                ['text' => '你好', 'expected_chars' => ['cjk']],
                ['text' => 'नमस्ते', 'expected_chars' => ['devanagari']],
                ['text' => '안녕', 'expected_chars' => ['hangul']],
            ];

            foreach ($testCases as $case) {
                $results = LocalizationTestUtilities::testCharacterEncoding($case['text']);
                
                foreach ($case['expected_chars'] as $expectedChar) {
                    expect($results['special_characters'])->toContain($expectedChar);
                }
            }
        });
    });

    describe('RTL language support', function () {
        it('validates RTL locale detection', function () {
            $rtlLocales = ['ar'];
            $ltrLocales = ['en', 'es', 'fr', 'de', 'ja', 'zh', 'ru', 'hi', 'ko'];
            
            foreach ($rtlLocales as $locale) {
                $results = LocalizationTestUtilities::testRtlSupport('مرحبا بالعالم', $locale);
                
                expect($results['is_rtl_locale'])->toBeTrue("Locale {$locale} should be RTL")
                    ->and($results['contains_rtl_characters'])->toBeTrue()
                    ->and($results['text_alignment'])->toBe('right');
            }
            
            foreach ($ltrLocales as $locale) {
                $results = LocalizationTestUtilities::testRtlSupport('Hello World', $locale);
                
                expect($results['is_rtl_locale'])->toBeFalse("Locale {$locale} should be LTR")
                    ->and($results['text_alignment'])->toBe('left');
            }
        });

        it('validates RTL character detection', function () {
            $testCases = [
                ['text' => 'مرحبا بالعالم', 'expected_rtl' => true],
                ['text' => 'שלום עולם', 'expected_rtl' => true],
                ['text' => 'Hello World', 'expected_rtl' => false],
                ['text' => 'Bonjour le monde', 'expected_rtl' => false],
                ['text' => 'こんにちは', 'expected_rtl' => false],
            ];

            foreach ($testCases as $case) {
                $results = LocalizationTestUtilities::testRtlSupport($case['text'], 'ar');
                
                expect($results['contains_rtl_characters'])->toBe($case['expected_rtl']);
            }
        });

        it('validates mixed direction text handling', function () {
            $mixedTexts = [
                'Hello مرحبا World',
                'Test 123 اختبار',
                'Email: user@example.com في النص العربي',
            ];

            foreach ($mixedTexts as $text) {
                $results = LocalizationTestUtilities::testRtlSupport($text, 'ar');
                
                expect($results['mixed_direction'])->toBeTrue()
                    ->and($results['direction_marks_needed'])->toBeTrue()
                    ->and($results['bidi_algorithm_required'])->toBeTrue();
            }
        });
    });

    describe('pluralization support', function () {
        it('validates English pluralization rules', function () {
            $testCases = [
                ['count' => 0, 'expected_rule' => 'zero'],
                ['count' => 1, 'expected_rule' => 'one'],
                ['count' => 2, 'expected_rule' => 'other'],
                ['count' => 5, 'expected_rule' => 'other'],
                ['count' => 100, 'expected_rule' => 'other'],
            ];

            foreach ($testCases as $case) {
                $results = LocalizationTestUtilities::testPluralization('en', $case['count']);
                
                expect($results['pluralization_supported'])->toBeTrue()
                    ->and($results['rule_applied'])->toBe($case['expected_rule'])
                    ->and($results['correct_form'])->not->toBeEmpty();
            }
        });

        it('validates Russian pluralization rules (complex)', function () {
            $testCases = [
                ['count' => 0, 'expected_rule' => 'zero'],
                ['count' => 1, 'expected_rule' => 'one'],
                ['count' => 2, 'expected_rule' => 'few'],
                ['count' => 3, 'expected_rule' => 'few'],
                ['count' => 4, 'expected_rule' => 'few'],
                ['count' => 5, 'expected_rule' => 'other'],
                ['count' => 11, 'expected_rule' => 'other'],
                ['count' => 21, 'expected_rule' => 'one'],
                ['count' => 22, 'expected_rule' => 'few'],
            ];

            foreach ($testCases as $case) {
                $results = LocalizationTestUtilities::testPluralization('ru', $case['count']);
                
                expect($results['pluralization_supported'])->toBeTrue()
                    ->and($results['rule_applied'])->toBe($case['expected_rule']);
            }
        });

        it('validates Arabic pluralization rules (very complex)', function () {
            $testCases = [
                ['count' => 0, 'expected_rule' => 'zero'],
                ['count' => 1, 'expected_rule' => 'one'],
                ['count' => 2, 'expected_rule' => 'two'],
                ['count' => 3, 'expected_rule' => 'few'],
                ['count' => 10, 'expected_rule' => 'few'],
                ['count' => 11, 'expected_rule' => 'many'],
                ['count' => 100, 'expected_rule' => 'other'],
            ];

            foreach ($testCases as $case) {
                $results = LocalizationTestUtilities::testPluralization('ar', $case['count']);
                
                expect($results['pluralization_supported'])->toBeTrue()
                    ->and($results['rule_applied'])->toBe($case['expected_rule']);
            }
        });

        it('validates count interpolation in plural forms', function () {
            $results = LocalizationTestUtilities::testPluralization('en', 5);
            
            expect($results['count_interpolated'])->toBeTrue()
                ->and($results['correct_form'])->toContain('5');
        });
    });

    describe('fallback mechanism validation', function () {
        it('validates fallback to English for missing translations', function () {
            $messageKey = 'validation_failed';
            $primaryLocale = 'unsupported_locale';
            $fallbackLocale = 'en';
            
            $results = LocalizationTestUtilities::testFallbackMechanism($messageKey, $primaryLocale, $fallbackLocale);
            
            expect($results['primary_available'])->toBeFalse()
                ->and($results['fallback_available'])->toBeTrue()
                ->and($results['fallback_used'])->toBeTrue()
                ->and($results['final_message'])->not->toBeEmpty()
                ->and($results['fallback_chain'])->toContain($fallbackLocale);
        });

        it('validates primary locale is used when available', function () {
            $messageKey = 'validation_failed';
            $primaryLocale = 'es';
            $fallbackLocale = 'en';
            
            $results = LocalizationTestUtilities::testFallbackMechanism($messageKey, $primaryLocale, $fallbackLocale);
            
            expect($results['primary_available'])->toBeTrue()
                ->and($results['fallback_used'])->toBeFalse()
                ->and($results['final_message'])->not->toBeEmpty()
                ->and($results['fallback_chain'])->toContain($primaryLocale);
        });

        it('validates fallback chain for completely missing messages', function () {
            $messageKey = 'nonexistent_message';
            $primaryLocale = 'es';
            $fallbackLocale = 'en';
            
            $results = LocalizationTestUtilities::testFallbackMechanism($messageKey, $primaryLocale, $fallbackLocale);
            
            expect($results['primary_available'])->toBeFalse()
                ->and($results['fallback_available'])->toBeFalse()
                ->and($results['final_message'])->toBe($messageKey); // Uses key as last resort
        });
    });

    describe('date and time formatting', function () {
        it('validates locale-specific date formatting', function () {
            $timestamp = strtotime('2024-03-15 14:30:00');
            $testCases = [
                ['locale' => 'en', 'expected_format' => 'Y-m-d'],
                ['locale' => 'es', 'expected_format' => 'd/m/Y'],
                ['locale' => 'fr', 'expected_format' => 'd/m/Y'],
                ['locale' => 'de', 'expected_format' => 'd.m.Y'],
                ['locale' => 'ja', 'expected_format' => 'Y年m月d日'],
                ['locale' => 'zh', 'expected_format' => 'Y年m月d日'],
            ];

            foreach ($testCases as $case) {
                $results = LocalizationTestUtilities::testDateTimeFormatting($case['locale'], $timestamp);
                
                expect($results['locale_supported'])->toBeTrue()
                    ->and($results['format_valid'])->toBeTrue()
                    ->and($results['formatted_date'])->not->toBeEmpty();
            }
        });

        it('validates locale-specific time formatting', function () {
            $timestamp = strtotime('2024-03-15 14:30:00');
            
            foreach (LocalizationTestUtilities::getSupportedLocales() as $locale) {
                $results = LocalizationTestUtilities::testDateTimeFormatting($locale, $timestamp);
                
                expect($results['locale_supported'])->toBeTrue()
                    ->and($results['format_valid'])->toBeTrue()
                    ->and($results['formatted_time'])->not->toBeEmpty()
                    ->and($results['formatted_datetime'])->not->toBeEmpty();
            }
        });
    });

    describe('number formatting', function () {
        it('validates locale-specific number formatting', function () {
            $number = 1234.56;
            $testCases = [
                ['locale' => 'en', 'expected_decimal' => '.', 'expected_thousands' => ','],
                ['locale' => 'es', 'expected_decimal' => ',', 'expected_thousands' => '.'],
                ['locale' => 'fr', 'expected_decimal' => ',', 'expected_thousands' => ' '],
                ['locale' => 'de', 'expected_decimal' => ',', 'expected_thousands' => '.'],
                ['locale' => 'ru', 'expected_decimal' => ',', 'expected_thousands' => ' '],
            ];

            foreach ($testCases as $case) {
                $results = LocalizationTestUtilities::testNumberFormatting($number, $case['locale']);
                
                expect($results['locale_supported'])->toBeTrue()
                    ->and($results['format_valid'])->toBeTrue()
                    ->and($results['formatted_number'])->not->toBeEmpty();
                
                if ($number >= 1000) {
                    expect($results['thousands_separator_correct'])->toBeTrue();
                }
                expect($results['decimal_separator_correct'])->toBeTrue();
            }
        });
    });

    describe('comprehensive localization reporting', function () {
        it('generates comprehensive localization report for English', function () {
            $report = LocalizationTestUtilities::generateLocalizationReport('en');
            
            expect($report['locale'])->toBe('en')
                ->and($report['locale_support']['supported'])->toBeTrue()
                ->and($report['overall_score'])->toBeGreaterThan(8.0)
                ->and($report['message_tests'])->not->toBeEmpty()
                ->and($report['encoding_tests'])->not->toBeEmpty()
                ->and($report['pluralization_tests'])->not->toBeEmpty()
                ->and($report['formatting_tests'])->not->toBeEmpty();
        });

        it('generates comprehensive localization report for Arabic (RTL)', function () {
            $report = LocalizationTestUtilities::generateLocalizationReport('ar');
            
            expect($report['locale'])->toBe('ar')
                ->and($report['locale_support']['supported'])->toBeTrue()
                ->and($report['rtl_tests']['is_rtl_locale'])->toBeTrue()
                ->and($report['overall_score'])->toBeGreaterThan(7.0);
        });

        it('generates recommendations for incomplete localization', function () {
            // Test with a locale that might have missing features
            $report = LocalizationTestUtilities::generateLocalizationReport('ko');
            
            expect($report['recommendations'])->toBeArray();
            // Recommendations should be helpful and actionable
            foreach ($report['recommendations'] as $recommendation) {
                expect($recommendation)->toBeString()
                    ->and(strlen($recommendation))->toBeGreaterThan(10);
            }
        });
    });
});