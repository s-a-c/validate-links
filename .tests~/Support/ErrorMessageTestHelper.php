<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Exceptions\ValidateLinksException;
use InvalidArgumentException;
use RuntimeException;

/**
 * Comprehensive error message testing utilities for validating message consistency,
 * format compliance, localization support, and user experience standards.
 *
 * This helper provides utilities for:
 * - Exact text matching and pattern validation
 * - Error message template validation
 * - Localization and internationalization testing
 * - Contextual information validation
 * - User experience consistency checks
 * - Error message accessibility compliance
 */
final class ErrorMessageTestHelper
{
    /**
     * Standard error message patterns for validation.
     *
     * @var array<string, string>
     */
    private static array $standardPatterns = [
        'link_validation_failed' => '/^Link validation failed for .+ \(HTTP \d+, \d+ms\)$/',
        'file_processing_failed' => '/^File processing failed for .+: .+$/',
        'validation_timeout' => '/^Validation timeout for .+ after \d+ seconds$/',
        'configuration_missing_file' => '/^Configuration file not found: .+$/',
        'configuration_invalid_format' => '/^Invalid configuration file format in .+\. Expected .+ format\.$/',
        'configuration_missing_key' => '/^Missing required configuration key \'.+\' in .+$/',
        'configuration_invalid_value' => '/^Invalid value for configuration key \'.+\'\. Expected .+\.$/',
        'security_threat_detected' => '/^Security threat detected at .+: .+$/',
        'security_blacklisted_domain' => '/^Access denied: .+ is on the security blacklist$/',
        'security_ssl_violation' => '/^SSL security violation for .+: .+$/',
    ];

    /**
     * Error message templates for consistency validation.
     *
     * @var array<string, array<string, mixed>>
     */
    private static array $messageTemplates = [
        'validation_error' => [
            'pattern' => '{action} failed for {target} ({details})',
            'required_placeholders' => ['action', 'target', 'details'],
            'optional_placeholders' => ['context', 'suggestion'],
        ],
        'configuration_error' => [
            'pattern' => 'Configuration error: {description}',
            'required_placeholders' => ['description'],
            'optional_placeholders' => ['file_path', 'expected_format'],
        ],
        'security_error' => [
            'pattern' => 'Security {severity}: {description}',
            'required_placeholders' => ['severity', 'description'],
            'optional_placeholders' => ['url', 'threat_type'],
        ],
    ];

    /**
     * Localization test data for multiple languages and character sets.
     *
     * @var array<string, array<string, string>>
     */
    private static array $localizationTestData = [
        'en' => [
            'validation_failed' => 'Validation failed',
            'file_not_found' => 'File not found',
            'invalid_format' => 'Invalid format',
            'access_denied' => 'Access denied',
            'timeout_occurred' => 'Timeout occurred',
        ],
        'es' => [
            'validation_failed' => 'Validación fallida',
            'file_not_found' => 'Archivo no encontrado',
            'invalid_format' => 'Formato inválido',
            'access_denied' => 'Acceso denegado',
            'timeout_occurred' => 'Tiempo de espera agotado',
        ],
        'fr' => [
            'validation_failed' => 'Validation échouée',
            'file_not_found' => 'Fichier non trouvé',
            'invalid_format' => 'Format invalide',
            'access_denied' => 'Accès refusé',
            'timeout_occurred' => 'Délai d\'attente dépassé',
        ],
        'de' => [
            'validation_failed' => 'Validierung fehlgeschlagen',
            'file_not_found' => 'Datei nicht gefunden',
            'invalid_format' => 'Ungültiges Format',
            'access_denied' => 'Zugriff verweigert',
            'timeout_occurred' => 'Zeitüberschreitung aufgetreten',
        ],
        'ja' => [
            'validation_failed' => '検証に失敗しました',
            'file_not_found' => 'ファイルが見つかりません',
            'invalid_format' => '無効な形式',
            'access_denied' => 'アクセスが拒否されました',
            'timeout_occurred' => 'タイムアウトが発生しました',
        ],
        'zh' => [
            'validation_failed' => '验证失败',
            'file_not_found' => '文件未找到',
            'invalid_format' => '格式无效',
            'access_denied' => '访问被拒绝',
            'timeout_occurred' => '超时发生',
        ],
        'ar' => [
            'validation_failed' => 'فشل التحقق',
            'file_not_found' => 'الملف غير موجود',
            'invalid_format' => 'تنسيق غير صالح',
            'access_denied' => 'تم رفض الوصول',
            'timeout_occurred' => 'حدث انتهاء المهلة',
        ],
        'ru' => [
            'validation_failed' => 'Проверка не удалась',
            'file_not_found' => 'Файл не найден',
            'invalid_format' => 'Неверный формат',
            'access_denied' => 'Доступ запрещен',
            'timeout_occurred' => 'Произошел тайм-аут',
        ],
    ];

    /**
     * User experience guidelines for error messages.
     *
     * @var array<string, mixed>
     */
    private static array $uxGuidelines = [
        'max_length' => 200,
        'min_length' => 10,
        'tone' => 'professional',
        'clarity_score_min' => 7.0,
        'actionability_required' => true,
        'technical_jargon_limit' => 3,
        'accessibility_compliant' => true,
        'reading_level_max' => 8, // Grade level
    ];

    /**
     * Validate error message against exact text pattern.
     *
     * @param string $actualMessage The actual error message
     * @param string $expectedMessage The expected exact message
     * @param bool $caseSensitive Whether comparison should be case-sensitive
     * @return bool True if messages match exactly
     */
    public static function validateExactText(
        string $actualMessage,
        string $expectedMessage,
        bool $caseSensitive = true
    ): bool {
        if (!$caseSensitive) {
            return strtolower($actualMessage) === strtolower($expectedMessage);
        }

        return $actualMessage === $expectedMessage;
    }

    /**
     * Validate error message against regex pattern.
     *
     * @param string $message The error message to validate
     * @param string $pattern The regex pattern to match
     * @return bool True if message matches pattern
     */
    public static function validatePattern(string $message, string $pattern): bool
    {
        return preg_match($pattern, $message) === 1;
    }

    /**
     * Validate error message against standard patterns.
     *
     * @param string $message The error message to validate
     * @param string $messageType The type of message (e.g., 'link_validation_failed')
     * @return bool True if message matches standard pattern
     */
    public static function validateStandardPattern(string $message, string $messageType): bool
    {
        if (!isset(self::$standardPatterns[$messageType])) {
            throw new InvalidArgumentException("Unknown message type: {$messageType}");
        }

        return self::validatePattern($message, self::$standardPatterns[$messageType]);
    }

    /**
     * Validate error message template compliance.
     *
     * @param string $message The error message to validate
     * @param string $templateType The template type to validate against
     * @param array<string, string> $placeholders The placeholder values used
     * @return array<string, mixed> Validation results
     */
    public static function validateTemplate(
        string $message,
        string $templateType,
        array $placeholders = []
    ): array {
        if (!isset(self::$messageTemplates[$templateType])) {
            throw new InvalidArgumentException("Unknown template type: {$templateType}");
        }

        $template = self::$messageTemplates[$templateType];
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'template_compliance' => true,
            'placeholder_usage' => [],
        ];

        // Check required placeholders
        foreach ($template['required_placeholders'] as $placeholder) {
            if (!isset($placeholders[$placeholder])) {
                $results['valid'] = false;
                $results['errors'][] = "Missing required placeholder: {$placeholder}";
            } else {
                $results['placeholder_usage'][$placeholder] = 'required_present';
            }
        }

        // Check optional placeholders
        foreach ($template['optional_placeholders'] as $placeholder) {
            if (isset($placeholders[$placeholder])) {
                $results['placeholder_usage'][$placeholder] = 'optional_present';
            }
        }

        // Validate template pattern compliance
        $expectedPattern = $template['pattern'];
        foreach ($placeholders as $key => $value) {
            $expectedPattern = str_replace("{{$key}}", preg_quote($value, '/'), $expectedPattern);
        }

        // Convert template pattern to regex
        $regexPattern = '/^' . str_replace(['{', '}'], ['[^{]*', '[^}]*'], $expectedPattern) . '$/';
        if (!preg_match($regexPattern, $message)) {
            $results['template_compliance'] = false;
            $results['warnings'][] = 'Message does not follow template pattern';
        }

        return $results;
    }

    /**
     * Validate error message parameter substitution.
     *
     * @param string $template The message template
     * @param array<string, string> $parameters The parameters to substitute
     * @param string $expectedResult The expected result after substitution
     * @return array<string, mixed> Validation results
     */
    public static function validateParameterSubstitution(
        string $template,
        array $parameters,
        string $expectedResult
    ): array {
        $results = [
            'valid' => true,
            'errors' => [],
            'substituted_message' => '',
            'parameter_usage' => [],
            'unused_parameters' => [],
            'missing_parameters' => [],
        ];

        $substitutedMessage = $template;
        $usedParameters = [];

        // Find all placeholders in template
        preg_match_all('/\{([^}]+)\}/', $template, $matches);
        $templatePlaceholders = $matches[1];

        // Substitute parameters
        foreach ($parameters as $key => $value) {
            $placeholder = "{{$key}}";
            if (strpos($substitutedMessage, $placeholder) !== false) {
                $substitutedMessage = str_replace($placeholder, $value, $substitutedMessage);
                $usedParameters[] = $key;
                $results['parameter_usage'][$key] = 'used';
            } else {
                $results['unused_parameters'][] = $key;
            }
        }

        // Check for missing parameters
        foreach ($templatePlaceholders as $placeholder) {
            if (!isset($parameters[$placeholder])) {
                $results['missing_parameters'][] = $placeholder;
                $results['valid'] = false;
                $results['errors'][] = "Missing parameter for placeholder: {$placeholder}";
            }
        }

        $results['substituted_message'] = $substitutedMessage;

        // Validate against expected result
        if ($substitutedMessage !== $expectedResult) {
            $results['valid'] = false;
            $results['errors'][] = 'Substituted message does not match expected result';
        }

        return $results;
    }

    /**
     * Validate error message localization support.
     *
     * @param string $messageKey The message key for localization
     * @param string $locale The locale to test
     * @param array<string, string> $parameters Optional parameters for message
     * @return array<string, mixed> Localization validation results
     */
    public static function validateLocalization(
        string $messageKey,
        string $locale,
        array $parameters = []
    ): array {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'locale_supported' => false,
            'message_available' => false,
            'character_encoding' => 'unknown',
            'text_direction' => 'ltr',
            'localized_message' => '',
        ];

        // Check if locale is supported
        if (!isset(self::$localizationTestData[$locale])) {
            $results['valid'] = false;
            $results['errors'][] = "Unsupported locale: {$locale}";
            return $results;
        }

        $results['locale_supported'] = true;

        // Check if message key exists for locale
        if (!isset(self::$localizationTestData[$locale][$messageKey])) {
            $results['valid'] = false;
            $results['errors'][] = "Message key '{$messageKey}' not found for locale '{$locale}'";
            return $results;
        }

        $results['message_available'] = true;
        $localizedMessage = self::$localizationTestData[$locale][$messageKey];
        $results['localized_message'] = $localizedMessage;

        // Validate character encoding
        if (mb_check_encoding($localizedMessage, 'UTF-8')) {
            $results['character_encoding'] = 'UTF-8';
        } else {
            $results['warnings'][] = 'Message may have encoding issues';
        }

        // Determine text direction (simplified check)
        $rtlLocales = ['ar', 'he', 'fa', 'ur'];
        if (in_array($locale, $rtlLocales, true)) {
            $results['text_direction'] = 'rtl';
        }

        // Validate Unicode support
        if (preg_match('/[\x{0080}-\x{FFFF}]/u', $localizedMessage)) {
            $results['unicode_characters'] = true;
        }

        return $results;
    }

    /**
     * Validate error message contextual information.
     *
     * @param ValidateLinksException $exception The exception to validate
     * @param array<string, mixed> $expectedContext Expected context data
     * @return array<string, mixed> Context validation results
     */
    public static function validateContextualInformation(
        ValidateLinksException $exception,
        array $expectedContext = []
    ): array {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'context_complete' => true,
            'context_accurate' => true,
            'metadata_present' => false,
            'correlation_data' => false,
            'severity_appropriate' => true,
        ];

        $actualContext = $exception->getContext();

        // Validate required context fields
        $requiredFields = ['error_type', 'timestamp'];
        foreach ($requiredFields as $field) {
            if (!isset($actualContext[$field])) {
                $results['warnings'][] = "Missing recommended context field: {$field}";
            }
        }

        // Validate expected context
        foreach ($expectedContext as $key => $expectedValue) {
            if (!isset($actualContext[$key])) {
                $results['valid'] = false;
                $results['errors'][] = "Missing expected context key: {$key}";
                $results['context_complete'] = false;
            } elseif ($actualContext[$key] !== $expectedValue) {
                $results['valid'] = false;
                $results['errors'][] = "Context value mismatch for key '{$key}': expected '{$expectedValue}', got '{$actualContext[$key]}'";
                $results['context_accurate'] = false;
            }
        }

        // Check for metadata presence
        if (isset($actualContext['metadata']) && is_array($actualContext['metadata'])) {
            $results['metadata_present'] = true;
        }

        // Check for correlation data
        $correlationFields = ['request_id', 'trace_id', 'session_id'];
        foreach ($correlationFields as $field) {
            if (isset($actualContext[$field])) {
                $results['correlation_data'] = true;
                break;
            }
        }

        // Validate error severity appropriateness
        $severity = $exception->getSeverity();
        $errorCode = $exception->getErrorCode();
        
        $severityMapping = [
            'VALIDATION_ERROR' => ['low', 'medium'],
            'CONFIGURATION_ERROR' => ['medium', 'high'],
            'SECURITY_VIOLATION' => ['high', 'critical'],
        ];

        if (isset($severityMapping[$errorCode])) {
            if (!in_array($severity, $severityMapping[$errorCode], true)) {
                $results['severity_appropriate'] = false;
                $results['warnings'][] = "Severity '{$severity}' may not be appropriate for error code '{$errorCode}'";
            }
        }

        return $results;
    }

    /**
     * Validate error message user experience compliance.
     *
     * @param string $message The error message to validate
     * @param array<string, mixed> $customGuidelines Optional custom UX guidelines
     * @return array<string, mixed> UX validation results
     */
    public static function validateUserExperience(
        string $message,
        array $customGuidelines = []
    ): array {
        $guidelines = array_merge(self::$uxGuidelines, $customGuidelines);
        
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'ux_score' => 0.0,
            'length_appropriate' => true,
            'tone_appropriate' => true,
            'clarity_score' => 0.0,
            'actionability_score' => 0.0,
            'accessibility_compliant' => true,
            'reading_level' => 0,
        ];

        // Validate message length
        $messageLength = strlen($message);
        if ($messageLength < $guidelines['min_length']) {
            $results['valid'] = false;
            $results['errors'][] = "Message too short: {$messageLength} characters (minimum: {$guidelines['min_length']})";
            $results['length_appropriate'] = false;
        } elseif ($messageLength > $guidelines['max_length']) {
            $results['warnings'][] = "Message may be too long: {$messageLength} characters (recommended maximum: {$guidelines['max_length']})";
        }

        // Validate tone (simplified check)
        $professionalWords = ['error', 'failed', 'invalid', 'unable', 'please', 'ensure'];
        $casualWords = ['oops', 'uh-oh', 'whoops', 'darn', 'yikes'];
        
        $professionalCount = 0;
        $casualCount = 0;
        
        foreach ($professionalWords as $word) {
            if (stripos($message, $word) !== false) {
                $professionalCount++;
            }
        }
        
        foreach ($casualWords as $word) {
            if (stripos($message, $word) !== false) {
                $casualCount++;
            }
        }

        if ($guidelines['tone'] === 'professional' && $casualCount > 0) {
            $results['warnings'][] = 'Message contains casual language that may not be appropriate for professional tone';
            $results['tone_appropriate'] = false;
        }

        // Calculate clarity score (simplified)
        $clarityFactors = [
            'sentence_count' => substr_count($message, '.') + substr_count($message, '!') + substr_count($message, '?'),
            'word_count' => str_word_count($message),
            'complex_words' => 0, // Would need more sophisticated analysis
        ];

        $avgWordsPerSentence = $clarityFactors['sentence_count'] > 0 
            ? $clarityFactors['word_count'] / $clarityFactors['sentence_count'] 
            : $clarityFactors['word_count'];

        // Simple clarity scoring (0-10 scale)
        if ($avgWordsPerSentence <= 15) {
            $results['clarity_score'] = 8.0;
        } elseif ($avgWordsPerSentence <= 25) {
            $results['clarity_score'] = 6.0;
        } else {
            $results['clarity_score'] = 4.0;
        }

        if ($results['clarity_score'] < $guidelines['clarity_score_min']) {
            $results['warnings'][] = "Message clarity score ({$results['clarity_score']}) below recommended minimum ({$guidelines['clarity_score_min']})";
        }

        // Check actionability
        $actionableWords = ['check', 'verify', 'ensure', 'try', 'contact', 'review', 'update', 'configure'];
        $actionabilityScore = 0;
        
        foreach ($actionableWords as $word) {
            if (stripos($message, $word) !== false) {
                $actionabilityScore += 2;
            }
        }

        $results['actionability_score'] = min(10, $actionabilityScore);

        if ($guidelines['actionability_required'] && $results['actionability_score'] < 2) {
            $results['warnings'][] = 'Message lacks actionable guidance for users';
        }

        // Calculate overall UX score
        $results['ux_score'] = ($results['clarity_score'] + $results['actionability_score']) / 2;

        return $results;
    }

    /**
     * Validate error message accessibility compliance.
     *
     * @param string $message The error message to validate
     * @return array<string, mixed> Accessibility validation results
     */
    public static function validateAccessibility(string $message): array
    {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'screen_reader_friendly' => true,
            'color_independent' => true,
            'plain_language' => true,
            'abbreviation_explained' => true,
        ];

        // Check for screen reader unfriendly elements
        $problematicPatterns = [
            '/[A-Z]{3,}/' => 'Excessive uppercase may be difficult for screen readers',
            '/\d{4,}/' => 'Long numbers should be broken up for screen readers',
            '/[^\w\s\.\,\!\?\:\;\-\(\)]/' => 'Special characters may not be read properly',
        ];

        foreach ($problematicPatterns as $pattern => $warning) {
            if (preg_match($pattern, $message)) {
                $results['warnings'][] = $warning;
                $results['screen_reader_friendly'] = false;
            }
        }

        // Check for color-dependent language
        $colorWords = ['red', 'green', 'blue', 'yellow', 'orange', 'purple', 'pink', 'gray', 'grey'];
        foreach ($colorWords as $color) {
            if (stripos($message, $color) !== false) {
                $results['warnings'][] = 'Message may rely on color information that is not accessible to all users';
                $results['color_independent'] = false;
                break;
            }
        }

        // Check for plain language
        $complexWords = ['utilize', 'facilitate', 'implement', 'instantiate', 'initialize'];
        foreach ($complexWords as $word) {
            if (stripos($message, $word) !== false) {
                $results['warnings'][] = 'Consider using simpler language for better accessibility';
                $results['plain_language'] = false;
                break;
            }
        }

        // Check for unexplained abbreviations
        if (preg_match('/\b[A-Z]{2,}\b/', $message, $matches)) {
            $abbreviations = array_unique($matches);
            foreach ($abbreviations as $abbr) {
                if (!in_array($abbr, ['HTTP', 'HTTPS', 'URL', 'SSL', 'TLS', 'API'], true)) {
                    $results['warnings'][] = "Abbreviation '{$abbr}' should be explained for accessibility";
                    $results['abbreviation_explained'] = false;
                }
            }
        }

        return $results;
    }

    /**
     * Get all available standard message patterns.
     *
     * @return array<string, string> Standard patterns
     */
    public static function getStandardPatterns(): array
    {
        return self::$standardPatterns;
    }

    /**
     * Get all available message templates.
     *
     * @return array<string, array<string, mixed>> Message templates
     */
    public static function getMessageTemplates(): array
    {
        return self::$messageTemplates;
    }

    /**
     * Get supported locales for testing.
     *
     * @return array<string> Supported locale codes
     */
    public static function getSupportedLocales(): array
    {
        return array_keys(self::$localizationTestData);
    }

    /**
     * Get UX guidelines for error messages.
     *
     * @return array<string, mixed> UX guidelines
     */
    public static function getUxGuidelines(): array
    {
        return self::$uxGuidelines;
    }

    /**
     * Generate comprehensive error message validation report.
     *
     * @param ValidateLinksException $exception The exception to validate
     * @param array<string, mixed> $validationConfig Validation configuration
     * @return array<string, mixed> Comprehensive validation report
     */
    public static function generateValidationReport(
        ValidateLinksException $exception,
        array $validationConfig = []
    ): array {
        $message = $exception->getMessage();
        $report = [
            'timestamp' => date('c'),
            'exception_class' => get_class($exception),
            'message' => $message,
            'error_code' => $exception->getErrorCode(),
            'severity' => $exception->getSeverity(),
            'validation_results' => [],
            'overall_score' => 0.0,
            'recommendations' => [],
        ];

        // Run all validations
        $validations = [
            'ux_compliance' => self::validateUserExperience($message),
            'accessibility' => self::validateAccessibility($message),
            'contextual_info' => self::validateContextualInformation($exception),
        ];

        // Add localization validation if locale specified
        if (isset($validationConfig['locale'])) {
            $validations['localization'] = self::validateLocalization(
                $validationConfig['message_key'] ?? 'validation_failed',
                $validationConfig['locale']
            );
        }

        $report['validation_results'] = $validations;

        // Calculate overall score
        $scores = [];
        foreach ($validations as $validation) {
            if (isset($validation['ux_score'])) {
                $scores[] = $validation['ux_score'];
            } elseif (isset($validation['valid']) && $validation['valid']) {
                $scores[] = 10.0;
            } else {
                $scores[] = 0.0;
            }
        }

        $report['overall_score'] = count($scores) > 0 ? array_sum($scores) / count($scores) : 0.0;

        // Generate recommendations
        foreach ($validations as $type => $validation) {
            if (isset($validation['warnings'])) {
                foreach ($validation['warnings'] as $warning) {
                    $report['recommendations'][] = "{$type}: {$warning}";
                }
            }
            if (isset($validation['errors'])) {
                foreach ($validation['errors'] as $error) {
                    $report['recommendations'][] = "{$type} ERROR: {$error}";
                }
            }
        }

        return $report;
    }
}