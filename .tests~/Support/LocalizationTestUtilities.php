<?php

declare(strict_types=1);

namespace Tests\Support;

use InvalidArgumentException;
use RuntimeException;

/**
 * Comprehensive localization and internationalization testing utilities for validating
 * multi-language support, character encoding, text direction, and cultural adaptations.
 *
 * This utility provides comprehensive testing for:
 * - Multi-language error message validation
 * - Character encoding and Unicode support
 * - Right-to-left (RTL) language support
 * - Cultural date, time, and number formatting
 * - Locale-specific validation rules
 * - Fallback mechanism testing
 * - Translation completeness validation
 */
final class LocalizationTestUtilities
{
    /**
     * Comprehensive locale definitions with cultural and technical metadata.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $localeDefinitions = [
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i:s',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'pluralization_rules' => 'simple',
            'character_set' => 'latin',
            'test_strings' => [
                'simple' => 'Hello World',
                'special_chars' => 'Test with "quotes" and \'apostrophes\'',
                'numbers' => 'Price: $1,234.56',
                'long_text' => 'This is a longer text to test word wrapping and text handling capabilities.',
            ],
        ],
        'es' => [
            'name' => 'Spanish',
            'native_name' => 'Español',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'pluralization_rules' => 'romance',
            'character_set' => 'latin_extended',
            'test_strings' => [
                'simple' => 'Hola Mundo',
                'special_chars' => 'Texto con acentos: café, niño, corazón',
                'numbers' => 'Precio: 1.234,56 €',
                'long_text' => 'Este es un texto más largo para probar las capacidades de manejo de texto y ajuste de líneas.',
            ],
        ],
        'fr' => [
            'name' => 'French',
            'native_name' => 'Français',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'pluralization_rules' => 'romance',
            'character_set' => 'latin_extended',
            'test_strings' => [
                'simple' => 'Bonjour le monde',
                'special_chars' => 'Texte avec accents: café, élève, français',
                'numbers' => 'Prix : 1 234,56 €',
                'long_text' => 'Ceci est un texte plus long pour tester les capacités de gestion de texte et d\'ajustement des lignes.',
            ],
        ],
        'de' => [
            'name' => 'German',
            'native_name' => 'Deutsch',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'currency_symbol' => '€',
            'currency_position' => 'after',
            'pluralization_rules' => 'germanic',
            'character_set' => 'latin_extended',
            'test_strings' => [
                'simple' => 'Hallo Welt',
                'special_chars' => 'Text mit Umlauten: Größe, Mädchen, Straße',
                'numbers' => 'Preis: 1.234,56 €',
                'long_text' => 'Dies ist ein längerer Text zum Testen der Textverarbeitungs- und Zeilenumbruchfähigkeiten.',
            ],
        ],
        'ja' => [
            'name' => 'Japanese',
            'native_name' => '日本語',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'date_format' => 'Y年m月d日',
            'time_format' => 'H:i',
            'currency_symbol' => '¥',
            'currency_position' => 'before',
            'pluralization_rules' => 'none',
            'character_set' => 'cjk',
            'test_strings' => [
                'simple' => 'こんにちは世界',
                'special_chars' => 'ひらがな、カタカナ、漢字のテスト',
                'numbers' => '価格：¥1,234',
                'long_text' => 'これは、テキスト処理と行の折り返し機能をテストするためのより長いテキストです。',
            ],
        ],
        'zh' => [
            'name' => 'Chinese (Simplified)',
            'native_name' => '简体中文',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'date_format' => 'Y年m月d日',
            'time_format' => 'H:i',
            'currency_symbol' => '¥',
            'currency_position' => 'before',
            'pluralization_rules' => 'none',
            'character_set' => 'cjk',
            'test_strings' => [
                'simple' => '你好世界',
                'special_chars' => '测试中文字符和标点符号：，。！？',
                'numbers' => '价格：¥1,234',
                'long_text' => '这是一个更长的文本，用于测试文本处理和换行功能。',
            ],
        ],
        'ar' => [
            'name' => 'Arabic',
            'native_name' => 'العربية',
            'direction' => 'rtl',
            'encoding' => 'UTF-8',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency_symbol' => 'ر.س',
            'currency_position' => 'after',
            'pluralization_rules' => 'arabic',
            'character_set' => 'arabic',
            'test_strings' => [
                'simple' => 'مرحبا بالعالم',
                'special_chars' => 'نص مع علامات ترقيم: "اقتباس" و\'فاصلة عليا\'',
                'numbers' => 'السعر: 1,234 ر.س',
                'long_text' => 'هذا نص أطول لاختبار قدرات معالجة النصوص وتغليف الأسطر.',
            ],
        ],
        'ru' => [
            'name' => 'Russian',
            'native_name' => 'Русский',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => ',',
            'thousands_separator' => ' ',
            'date_format' => 'd.m.Y',
            'time_format' => 'H:i',
            'currency_symbol' => '₽',
            'currency_position' => 'after',
            'pluralization_rules' => 'slavic',
            'character_set' => 'cyrillic',
            'test_strings' => [
                'simple' => 'Привет мир',
                'special_chars' => 'Текст с кириллицей: Москва, Санкт-Петербург',
                'numbers' => 'Цена: 1 234,56 ₽',
                'long_text' => 'Это более длинный текст для тестирования возможностей обработки текста и переноса строк.',
            ],
        ],
        'hi' => [
            'name' => 'Hindi',
            'native_name' => 'हिन्दी',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i',
            'currency_symbol' => '₹',
            'currency_position' => 'before',
            'pluralization_rules' => 'indic',
            'character_set' => 'devanagari',
            'test_strings' => [
                'simple' => 'नमस्ते दुनिया',
                'special_chars' => 'देवनागरी लिपि का परीक्षण',
                'numbers' => 'मूल्य: ₹1,234',
                'long_text' => 'यह टेक्स्ट प्रोसेसिंग और लाइन रैपिंग क्षमताओं का परीक्षण करने के लिए एक लंबा टेक्स्ट है।',
            ],
        ],
        'ko' => [
            'name' => 'Korean',
            'native_name' => '한국어',
            'direction' => 'ltr',
            'encoding' => 'UTF-8',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'date_format' => 'Y년 m월 d일',
            'time_format' => 'H:i',
            'currency_symbol' => '₩',
            'currency_position' => 'before',
            'pluralization_rules' => 'none',
            'character_set' => 'hangul',
            'test_strings' => [
                'simple' => '안녕하세요 세계',
                'special_chars' => '한글 문자와 구두점 테스트',
                'numbers' => '가격: ₩1,234',
                'long_text' => '이것은 텍스트 처리 및 줄 바꿈 기능을 테스트하기 위한 더 긴 텍스트입니다.',
            ],
        ],
    ];

    /**
     * Error message translations for testing localization.
     *
     * @var array<string, array<string, string>>
     */
    private static array $errorMessageTranslations = [
        'validation_failed' => [
            'en' => 'Validation failed',
            'es' => 'Validación fallida',
            'fr' => 'Validation échouée',
            'de' => 'Validierung fehlgeschlagen',
            'ja' => '検証に失敗しました',
            'zh' => '验证失败',
            'ar' => 'فشل التحقق',
            'ru' => 'Проверка не удалась',
            'hi' => 'सत्यापन असफल',
            'ko' => '검증 실패',
        ],
        'file_not_found' => [
            'en' => 'File not found',
            'es' => 'Archivo no encontrado',
            'fr' => 'Fichier non trouvé',
            'de' => 'Datei nicht gefunden',
            'ja' => 'ファイルが見つかりません',
            'zh' => '文件未找到',
            'ar' => 'الملف غير موجود',
            'ru' => 'Файл не найден',
            'hi' => 'फ़ाइल नहीं मिली',
            'ko' => '파일을 찾을 수 없음',
        ],
        'invalid_format' => [
            'en' => 'Invalid format',
            'es' => 'Formato inválido',
            'fr' => 'Format invalide',
            'de' => 'Ungültiges Format',
            'ja' => '無効な形式',
            'zh' => '格式无效',
            'ar' => 'تنسيق غير صالح',
            'ru' => 'Неверный формат',
            'hi' => 'अमान्य प्रारूप',
            'ko' => '잘못된 형식',
        ],
        'access_denied' => [
            'en' => 'Access denied',
            'es' => 'Acceso denegado',
            'fr' => 'Accès refusé',
            'de' => 'Zugriff verweigert',
            'ja' => 'アクセスが拒否されました',
            'zh' => '访问被拒绝',
            'ar' => 'تم رفض الوصول',
            'ru' => 'Доступ запрещен',
            'hi' => 'पहुंच अस्वीकृत',
            'ko' => '접근 거부됨',
        ],
        'timeout_occurred' => [
            'en' => 'Timeout occurred',
            'es' => 'Tiempo de espera agotado',
            'fr' => 'Délai d\'attente dépassé',
            'de' => 'Zeitüberschreitung aufgetreten',
            'ja' => 'タイムアウトが発生しました',
            'zh' => '超时发生',
            'ar' => 'حدث انتهاء المهلة',
            'ru' => 'Произошел тайм-аут',
            'hi' => 'समय समाप्ति हुई',
            'ko' => '시간 초과 발생',
        ],
    ];

    /**
     * Pluralization test data for different languages.
     *
     * @var array<string, array<string, array<string, string>>>
     */
    private static array $pluralizationTestData = [
        'en' => [
            'simple' => [
                'zero' => '0 items',
                'one' => '1 item',
                'other' => '{count} items',
            ],
            'complex' => [
                'zero' => 'No files found',
                'one' => 'Found 1 file',
                'other' => 'Found {count} files',
            ],
        ],
        'ru' => [
            'simple' => [
                'zero' => '0 элементов',
                'one' => '1 элемент',
                'few' => '{count} элемента',
                'many' => '{count} элементов',
                'other' => '{count} элементов',
            ],
        ],
        'ar' => [
            'simple' => [
                'zero' => '0 عناصر',
                'one' => 'عنصر واحد',
                'two' => 'عنصران',
                'few' => '{count} عناصر',
                'many' => '{count} عنصراً',
                'other' => '{count} عنصر',
            ],
        ],
    ];

    /**
     * Test locale support and validation.
     *
     * @param string $locale The locale to test
     * @return array<string, mixed> Locale validation results
     */
    public static function testLocaleSupport(string $locale): array
    {
        $results = [
            'supported' => false,
            'valid_format' => false,
            'locale_data' => null,
            'encoding_support' => false,
            'direction_support' => false,
            'character_set_support' => false,
            'formatting_support' => false,
            'warnings' => [],
            'errors' => [],
        ];

        // Validate locale format
        if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $locale)) {
            $results['valid_format'] = true;
        } else {
            $results['errors'][] = "Invalid locale format: {$locale}";
            return $results;
        }

        // Extract base locale (e.g., 'en' from 'en_US')
        $baseLocale = substr($locale, 0, 2);

        // Check if locale is supported
        if (isset(self::$localeDefinitions[$baseLocale])) {
            $results['supported'] = true;
            $results['locale_data'] = self::$localeDefinitions[$baseLocale];
            
            // Test encoding support
            $encoding = $results['locale_data']['encoding'];
            if (in_array($encoding, mb_list_encodings(), true)) {
                $results['encoding_support'] = true;
            } else {
                $results['warnings'][] = "Encoding {$encoding} may not be supported";
            }

            // Test direction support
            $direction = $results['locale_data']['direction'];
            $results['direction_support'] = in_array($direction, ['ltr', 'rtl'], true);

            // Test character set support
            $characterSet = $results['locale_data']['character_set'];
            $results['character_set_support'] = self::testCharacterSetSupport($characterSet);

            // Test formatting support
            $results['formatting_support'] = self::testFormattingSupport($results['locale_data']);
        } else {
            $results['errors'][] = "Locale {$locale} is not supported";
        }

        return $results;
    }

    /**
     * Test error message localization.
     *
     * @param string $messageKey The message key to test
     * @param string $locale The target locale
     * @param array<string, string> $parameters Optional parameters for message interpolation
     * @return array<string, mixed> Localization test results
     */
    public static function testErrorMessageLocalization(
        string $messageKey,
        string $locale,
        array $parameters = []
    ): array {
        $results = [
            'message_exists' => false,
            'translation_quality' => 'unknown',
            'localized_message' => '',
            'parameter_support' => true,
            'encoding_valid' => false,
            'length_appropriate' => true,
            'cultural_appropriateness' => 'unknown',
            'warnings' => [],
            'errors' => [],
        ];

        $baseLocale = substr($locale, 0, 2);

        // Check if message exists for locale
        if (!isset(self::$errorMessageTranslations[$messageKey])) {
            $results['errors'][] = "Message key '{$messageKey}' not found";
            return $results;
        }

        if (!isset(self::$errorMessageTranslations[$messageKey][$baseLocale])) {
            $results['errors'][] = "Translation for '{$messageKey}' not available in locale '{$locale}'";
            return $results;
        }

        $results['message_exists'] = true;
        $localizedMessage = self::$errorMessageTranslations[$messageKey][$baseLocale];
        $results['localized_message'] = $localizedMessage;

        // Test encoding validity
        if (mb_check_encoding($localizedMessage, 'UTF-8')) {
            $results['encoding_valid'] = true;
        } else {
            $results['errors'][] = 'Message contains invalid UTF-8 encoding';
        }

        // Test parameter interpolation
        if (!empty($parameters)) {
            $interpolatedMessage = self::interpolateParameters($localizedMessage, $parameters);
            if ($interpolatedMessage === $localizedMessage && strpos($localizedMessage, '{') !== false) {
                $results['parameter_support'] = false;
                $results['warnings'][] = 'Message may not support parameter interpolation';
            }
            $results['localized_message'] = $interpolatedMessage;
        }

        // Test message length appropriateness
        $messageLength = mb_strlen($localizedMessage, 'UTF-8');
        if ($messageLength > 200) {
            $results['length_appropriate'] = false;
            $results['warnings'][] = 'Message may be too long for UI display';
        } elseif ($messageLength < 5) {
            $results['length_appropriate'] = false;
            $results['warnings'][] = 'Message may be too short to be meaningful';
        }

        // Assess translation quality (simplified)
        $results['translation_quality'] = self::assessTranslationQuality($localizedMessage, $baseLocale);

        // Assess cultural appropriateness (simplified)
        $results['cultural_appropriateness'] = self::assessCulturalAppropriateness($localizedMessage, $baseLocale);

        return $results;
    }

    /**
     * Test character encoding support.
     *
     * @param string $text The text to test
     * @param string $encoding The target encoding
     * @return array<string, mixed> Encoding test results
     */
    public static function testCharacterEncoding(string $text, string $encoding = 'UTF-8'): array
    {
        $results = [
            'valid_encoding' => false,
            'encoding_detected' => '',
            'conversion_possible' => false,
            'character_count' => 0,
            'byte_count' => 0,
            'multibyte_characters' => false,
            'special_characters' => [],
            'warnings' => [],
            'errors' => [],
        ];

        // Test encoding validity
        if (mb_check_encoding($text, $encoding)) {
            $results['valid_encoding'] = true;
        } else {
            $results['errors'][] = "Text is not valid {$encoding}";
        }

        // Detect actual encoding
        $detectedEncoding = mb_detect_encoding($text, mb_detect_order(), true);
        if ($detectedEncoding) {
            $results['encoding_detected'] = $detectedEncoding;
        }

        // Test conversion possibility
        if ($detectedEncoding && $detectedEncoding !== $encoding) {
            $converted = mb_convert_encoding($text, $encoding, $detectedEncoding);
            if ($converted !== false) {
                $results['conversion_possible'] = true;
            }
        }

        // Count characters and bytes
        $results['character_count'] = mb_strlen($text, $encoding);
        $results['byte_count'] = strlen($text);

        // Check for multibyte characters
        if ($results['byte_count'] > $results['character_count']) {
            $results['multibyte_characters'] = true;
        }

        // Identify special characters
        $results['special_characters'] = self::identifySpecialCharacters($text);

        return $results;
    }

    /**
     * Test right-to-left (RTL) language support.
     *
     * @param string $text The text to test
     * @param string $locale The locale to test
     * @return array<string, mixed> RTL support test results
     */
    public static function testRtlSupport(string $text, string $locale): array
    {
        $results = [
            'is_rtl_locale' => false,
            'contains_rtl_characters' => false,
            'mixed_direction' => false,
            'direction_marks_needed' => false,
            'bidi_algorithm_required' => false,
            'text_alignment' => 'left',
            'warnings' => [],
            'errors' => [],
        ];

        $baseLocale = substr($locale, 0, 2);

        // Check if locale is RTL
        if (isset(self::$localeDefinitions[$baseLocale])) {
            $localeData = self::$localeDefinitions[$baseLocale];
            $results['is_rtl_locale'] = $localeData['direction'] === 'rtl';
            $results['text_alignment'] = $localeData['direction'] === 'rtl' ? 'right' : 'left';
        }

        // Check for RTL characters in text
        if (preg_match('/[\x{0590}-\x{05FF}\x{0600}-\x{06FF}\x{0700}-\x{074F}]/u', $text)) {
            $results['contains_rtl_characters'] = true;
        }

        // Check for mixed direction text
        $hasLtrChars = preg_match('/[A-Za-z0-9]/', $text);
        if ($results['contains_rtl_characters'] && $hasLtrChars) {
            $results['mixed_direction'] = true;
            $results['direction_marks_needed'] = true;
            $results['bidi_algorithm_required'] = true;
            $results['warnings'][] = 'Mixed direction text may require special handling';
        }

        // Additional RTL-specific validations
        if ($results['is_rtl_locale'] && !$results['contains_rtl_characters']) {
            $results['warnings'][] = 'RTL locale but text contains no RTL characters';
        }

        return $results;
    }

    /**
     * Test pluralization rules for a locale.
     *
     * @param string $locale The locale to test
     * @param int $count The count to test pluralization for
     * @param string $messageKey The message key for pluralization
     * @return array<string, mixed> Pluralization test results
     */
    public static function testPluralization(string $locale, int $count, string $messageKey = 'simple'): array
    {
        $results = [
            'pluralization_supported' => false,
            'correct_form' => '',
            'rule_applied' => '',
            'count_interpolated' => false,
            'warnings' => [],
            'errors' => [],
        ];

        $baseLocale = substr($locale, 0, 2);

        // Check if pluralization data exists
        if (!isset(self::$pluralizationTestData[$baseLocale])) {
            $results['errors'][] = "No pluralization data for locale: {$locale}";
            return $results;
        }

        if (!isset(self::$pluralizationTestData[$baseLocale][$messageKey])) {
            $results['errors'][] = "No pluralization data for message key: {$messageKey}";
            return $results;
        }

        $results['pluralization_supported'] = true;
        $pluralData = self::$pluralizationTestData[$baseLocale][$messageKey];

        // Determine correct plural form
        $pluralRule = self::determinePluralRule($count, $baseLocale);
        $results['rule_applied'] = $pluralRule;

        if (isset($pluralData[$pluralRule])) {
            $results['correct_form'] = $pluralData[$pluralRule];
        } elseif (isset($pluralData['other'])) {
            $results['correct_form'] = $pluralData['other'];
            $results['warnings'][] = "Using 'other' form as fallback for rule: {$pluralRule}";
        } else {
            $results['errors'][] = "No plural form available for rule: {$pluralRule}";
            return $results;
        }

        // Test count interpolation
        if (strpos($results['correct_form'], '{count}') !== false) {
            $results['correct_form'] = str_replace('{count}', (string)$count, $results['correct_form']);
            $results['count_interpolated'] = true;
        }

        return $results;
    }

    /**
     * Test fallback mechanism for missing translations.
     *
     * @param string $messageKey The message key to test
     * @param string $primaryLocale The primary locale
     * @param string $fallbackLocale The fallback locale
     * @return array<string, mixed> Fallback test results
     */
    public static function testFallbackMechanism(
        string $messageKey,
        string $primaryLocale,
        string $fallbackLocale = 'en'
    ): array {
        $results = [
            'primary_available' => false,
            'fallback_available' => false,
            'fallback_used' => false,
            'final_message' => '',
            'fallback_chain' => [],
            'warnings' => [],
            'errors' => [],
        ];

        $primaryBase = substr($primaryLocale, 0, 2);
        $fallbackBase = substr($fallbackLocale, 0, 2);

        // Check primary locale
        if (isset(self::$errorMessageTranslations[$messageKey][$primaryBase])) {
            $results['primary_available'] = true;
            $results['final_message'] = self::$errorMessageTranslations[$messageKey][$primaryBase];
            $results['fallback_chain'][] = $primaryLocale;
        } else {
            // Check fallback locale
            if (isset(self::$errorMessageTranslations[$messageKey][$fallbackBase])) {
                $results['fallback_available'] = true;
                $results['fallback_used'] = true;
                $results['final_message'] = self::$errorMessageTranslations[$messageKey][$fallbackBase];
                $results['fallback_chain'][] = $fallbackLocale;
                $results['warnings'][] = "Using fallback locale {$fallbackLocale} for message key {$messageKey}";
            } else {
                $results['errors'][] = "No translation available in primary ({$primaryLocale}) or fallback ({$fallbackLocale}) locales";
                $results['final_message'] = $messageKey; // Use key as last resort
            }
        }

        return $results;
    }

    /**
     * Test date and time formatting for a locale.
     *
     * @param string $locale The locale to test
     * @param int|null $timestamp Optional timestamp (defaults to current time)
     * @return array<string, mixed> Date/time formatting test results
     */
    public static function testDateTimeFormatting(string $locale, ?int $timestamp = null): array
    {
        $timestamp = $timestamp ?? time();
        $baseLocale = substr($locale, 0, 2);

        $results = [
            'locale_supported' => false,
            'formatted_date' => '',
            'formatted_time' => '',
            'formatted_datetime' => '',
            'format_valid' => false,
            'cultural_appropriate' => true,
            'warnings' => [],
            'errors' => [],
        ];

        if (!isset(self::$localeDefinitions[$baseLocale])) {
            $results['errors'][] = "Locale {$locale} not supported";
            return $results;
        }

        $results['locale_supported'] = true;
        $localeData = self::$localeDefinitions[$baseLocale];

        try {
            // Format date
            $results['formatted_date'] = date($localeData['date_format'], $timestamp);
            
            // Format time
            $results['formatted_time'] = date($localeData['time_format'], $timestamp);
            
            // Format datetime
            $results['formatted_datetime'] = date($localeData['date_format'] . ' ' . $localeData['time_format'], $timestamp);
            
            $results['format_valid'] = true;
        } catch (\Exception $e) {
            $results['errors'][] = "Date/time formatting failed: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Test number formatting for a locale.
     *
     * @param float $number The number to format
     * @param string $locale The locale to test
     * @return array<string, mixed> Number formatting test results
     */
    public static function testNumberFormatting(float $number, string $locale): array
    {
        $baseLocale = substr($locale, 0, 2);

        $results = [
            'locale_supported' => false,
            'formatted_number' => '',
            'decimal_separator_correct' => false,
            'thousands_separator_correct' => false,
            'format_valid' => false,
            'warnings' => [],
            'errors' => [],
        ];

        if (!isset(self::$localeDefinitions[$baseLocale])) {
            $results['errors'][] = "Locale {$locale} not supported";
            return $results;
        }

        $results['locale_supported'] = true;
        $localeData = self::$localeDefinitions[$baseLocale];

        try {
            // Format number according to locale
            $formattedNumber = number_format(
                $number,
                2,
                $localeData['decimal_separator'],
                $localeData['thousands_separator']
            );
            
            $results['formatted_number'] = $formattedNumber;
            $results['format_valid'] = true;
            
            // Validate separators
            if (strpos($formattedNumber, $localeData['decimal_separator']) !== false) {
                $results['decimal_separator_correct'] = true;
            }
            
            if ($number >= 1000 && strpos($formattedNumber, $localeData['thousands_separator']) !== false) {
                $results['thousands_separator_correct'] = true;
            }
        } catch (\Exception $e) {
            $results['errors'][] = "Number formatting failed: " . $e->getMessage();
        }

        return $results;
    }

    /**
     * Generate comprehensive localization test report.
     *
     * @param string $locale The locale to test
     * @param array<string> $messageKeys Message keys to test
     * @return array<string, mixed> Comprehensive localization report
     */
    public static function generateLocalizationReport(string $locale, array $messageKeys = []): array
    {
        if (empty($messageKeys)) {
            $messageKeys = array_keys(self::$errorMessageTranslations);
        }

        $report = [
            'timestamp' => date('c'),
            'locale' => $locale,
            'locale_support' => self::testLocaleSupport($locale),
            'message_tests' => [],
            'encoding_tests' => [],
            'rtl_tests' => [],
            'pluralization_tests' => [],
            'formatting_tests' => [],
            'overall_score' => 0.0,
            'recommendations' => [],
        ];

        // Test each message key
        foreach ($messageKeys as $messageKey) {
            $report['message_tests'][$messageKey] = self::testErrorMessageLocalization($messageKey, $locale);
        }

        // Test encoding with sample text
        $baseLocale = substr($locale, 0, 2);
        if (isset(self::$localeDefinitions[$baseLocale])) {
            $testStrings = self::$localeDefinitions[$baseLocale]['test_strings'];
            foreach ($testStrings as $type => $text) {
                $report['encoding_tests'][$type] = self::testCharacterEncoding($text);
            }

            // Test RTL support
            $report['rtl_tests'] = self::testRtlSupport($testStrings['simple'], $locale);

            // Test pluralization
            for ($i = 0; $i <= 5; $i++) {
                $report['pluralization_tests'][$i] = self::testPluralization($locale, $i);
            }

            // Test formatting
            $report['formatting_tests']['datetime'] = self::testDateTimeFormatting($locale);
            $report['formatting_tests']['number'] = self::testNumberFormatting(1234.56, $locale);
        }

        // Calculate overall score
        $scores = [];
        
        // Score locale support
        $scores[] = $report['locale_support']['supported'] ? 10 : 0;
        
        // Score message translations
        $messageScores = [];
        foreach ($report['message_tests'] as $test) {
            $messageScores[] = $test['message_exists'] && $test['encoding_valid'] ? 10 : 0;
        }
        if (!empty($messageScores)) {
            $scores[] = array_sum($messageScores) / count($messageScores);
        }

        // Score encoding tests
        $encodingScores = [];
        foreach ($report['encoding_tests'] as $test) {
            $encodingScores[] = $test['valid_encoding'] ? 10 : 0;
        }
        if (!empty($encodingScores)) {
            $scores[] = array_sum($encodingScores) / count($encodingScores);
        }

        $report['overall_score'] = !empty($scores) ? array_sum($scores) / count($scores) : 0;

        // Generate recommendations
        if (!$report['locale_support']['supported']) {
            $report['recommendations'][] = 'Add support for this locale';
        }

        foreach ($report['message_tests'] as $key => $test) {
            if (!$test['message_exists']) {
                $report['recommendations'][] = "Add translation for message key: {$key}";
            }
        }

        if ($report['rtl_tests']['is_rtl_locale'] && !$report['rtl_tests']['contains_rtl_characters']) {
            $report['recommendations'][] = 'Ensure RTL text rendering is properly supported';
        }

        return $report;
    }

    /**
     * Helper method to interpolate parameters in a message.
     *
     * @param string $message The message template
     * @param array<string, string> $parameters The parameters to interpolate
     * @return string The interpolated message
     */
    private static function interpolateParameters(string $message, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $message = str_replace("{{$key}}", $value, $message);
        }
        return $message;
    }

    /**
     * Helper method to test character set support.
     *
     * @param string $characterSet The character set to test
     * @return bool Whether the character set is supported
     */
    private static function testCharacterSetSupport(string $characterSet): bool
    {
        $supportedSets = ['latin', 'latin_extended', 'cyrillic', 'arabic', 'cjk', 'devanagari', 'hangul'];
        return in_array($characterSet, $supportedSets, true);
    }

    /**
     * Helper method to test formatting support.
     *
     * @param array<string, mixed> $localeData The locale data
     * @return bool Whether formatting is supported
     */
    private static function testFormattingSupport(array $localeData): bool
    {
        $requiredFields = ['date_format', 'time_format', 'decimal_separator', 'thousands_separator'];
        foreach ($requiredFields as $field) {
            if (!isset($localeData[$field])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Helper method to assess translation quality.
     *
     * @param string $message The translated message
     * @param string $locale The locale
     * @return string Quality assessment
     */
    private static function assessTranslationQuality(string $message, string $locale): string
    {
        // Simplified quality assessment
        $length = mb_strlen($message, 'UTF-8');
        
        if ($length < 5) {
            return 'poor';
        } elseif ($length > 100) {
            return 'verbose';
        } else {
            return 'good';
        }
    }

    /**
     * Helper method to assess cultural appropriateness.
     *
     * @param string $message The message to assess
     * @param string $locale The locale
     * @return string Cultural appropriateness assessment
     */
    private static function assessCulturalAppropriateness(string $message, string $locale): string
    {
        // Simplified cultural assessment
        // In practice, this would involve more sophisticated analysis
        return 'appropriate';
    }

    /**
     * Helper method to identify special characters in text.
     *
     * @param string $text The text to analyze
     * @return array<string> List of special character types found
     */
    private static function identifySpecialCharacters(string $text): array
    {
        $specialChars = [];
        
        if (preg_match('/[\x{0080}-\x{00FF}]/u', $text)) {
            $specialChars[] = 'latin_extended';
        }
        
        if (preg_match('/[\x{0400}-\x{04FF}]/u', $text)) {
            $specialChars[] = 'cyrillic';
        }
        
        if (preg_match('/[\x{0600}-\x{06FF}]/u', $text)) {
            $specialChars[] = 'arabic';
        }
        
        if (preg_match('/[\x{4E00}-\x{9FFF}]/u', $text)) {
            $specialChars[] = 'cjk';
        }
        
        if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            $specialChars[] = 'devanagari';
        }
        
        if (preg_match('/[\x{AC00}-\x{D7AF}]/u', $text)) {
            $specialChars[] = 'hangul';
        }
        
        return $specialChars;
    }

    /**
     * Helper method to determine plural rule for a count and locale.
     *
     * @param int $count The count
     * @param string $locale The locale
     * @return string The plural rule
     */
    private static function determinePluralRule(int $count, string $locale): string
    {
        // Simplified plural rule determination
        // In practice, this would use proper CLDR plural rules
        
        if ($count === 0) {
            return 'zero';
        } elseif ($count === 1) {
            return 'one';
        } elseif ($locale === 'ru' && $count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 10 || $count % 100 >= 20)) {
            return 'few';
        } elseif ($locale === 'ar' && $count === 2) {
            return 'two';
        } elseif ($locale === 'ar' && $count >= 3 && $count <= 10) {
            return 'few';
        } elseif ($locale === 'ar' && $count >= 11) {
            return 'many';
        } else {
            return 'other';
        }
    }

    /**
     * Get all supported locales.
     *
     * @return array<string> List of supported locale codes
     */
    public static function getSupportedLocales(): array
    {
        return array_keys(self::$localeDefinitions);
    }

    /**
     * Get locale definition for a specific locale.
     *
     * @param string $locale The locale code
     * @return array<string, mixed>|null Locale definition or null if not found
     */
    public static function getLocaleDefinition(string $locale): ?array
    {
        $baseLocale = substr($locale, 0, 2);
        return self::$localeDefinitions[$baseLocale] ?? null;
    }

    /**
     * Get available error message translations.
     *
     * @return array<string, array<string, string>> Error message translations
     */
    public static function getErrorMessageTranslations(): array
    {
        return self::$errorMessageTranslations;
    }

    /**
     * Get pluralization test data.
     *
     * @return array<string, array<string, array<string, string>>> Pluralization data
     */
    public static function getPluralizationTestData(): array
    {
        return self::$pluralizationTestData;
    }
}