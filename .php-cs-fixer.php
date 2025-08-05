<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.blade.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true)
    ->exclude([
        'bootstrap/cache',
        'storage/logs',
        'storage/framework',
        'storage/app',
        'vendor',
        'node_modules',
        'tests/Fixtures', // Preserve test fixtures
    ])
    ->notPath([
        'tests/Fixtures/*',
        'storage/rector/*',
        'storage/rector-type-safety/*',
    ]);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,

        // Array formatting - Laravel Zero optimized
        'array_syntax' => ['syntax' => 'short'],
        'array_indentation' => true,
        'trim_array_spaces' => true,
        'normalize_index_brace' => true,
        'no_multiline_whitespace_around_double_arrow' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],

        // Import and namespace handling - Enhanced for Laravel Zero
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'single_import_per_statement' => true,
        'group_import' => false,

        // Type declarations - PHPStan Level 10 compatible
        'declare_strict_types' => true,
        'strict_param' => true,
        'strict_comparison' => true,
        'native_function_type_declaration_casing' => true,

        // Method and function formatting - Laravel Zero console apps
        'method_chaining_indentation' => true,
        'multiline_whitespace_before_semicolons' => ['strategy' => 'no_multi_line'],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'single_line_throw' => false, // Allow multiline throws for better readability

        // PHPDoc handling - Critical for PHPStan Level 10
        'no_superfluous_phpdoc_tags' => [
            'allow_mixed' => true,
            'allow_unused_params' => true,  // Preserve @param for PHPStan Level 10
            'remove_inheritdoc' => false,   // Preserve @inheritdoc for PHPStan Level 10
            'allow_hidden_params' => true, // Preserve hidden @param annotations
        ],
        'phpdoc_add_missing_param_annotation' => ['only_untyped' => false],
        'phpdoc_types' => true,
        'phpdoc_var_annotation_correct_order' => true,
        'phpdoc_align' => ['align' => 'left'],
        'phpdoc_annotation_without_dot' => true,
        'phpdoc_indent' => true,
        'phpdoc_inline_tag_normalizer' => true,
        'phpdoc_no_access' => true,
        'phpdoc_no_empty_return' => false, // Keep for PHPStan Level 10
        'phpdoc_no_package' => true,
        'phpdoc_scalar' => true,
        'phpdoc_separation' => true,
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_summary' => true,
        'phpdoc_tag_type' => true,
        'phpdoc_trim' => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_var_without_name' => true,

        // Laravel Zero specific rules
        'php_unit_method_casing' => ['case' => 'camel_case'],
        'php_unit_test_annotation' => ['style' => 'prefix'],
        'php_unit_test_case_static_method_calls' => ['call_type' => 'this'],
        'php_unit_construct' => true,
        'php_unit_dedicate_assert' => ['target' => 'newest'],
        'php_unit_expectation' => ['target' => 'newest'],
        'php_unit_mock' => ['target' => 'newest'],
        'php_unit_namespaced' => ['target' => 'newest'],

        // Console command specific formatting
        'class_attributes_separation' => [
            'elements' => [
                'const' => 'one',
                'method' => 'one',
                'property' => 'one',
                'trait_import' => 'none',
            ],
        ],
        'ordered_class_elements' => [
            'order' => [
                'use_trait',
                'case',
                'constant_public',
                'constant_protected',
                'constant_private',
                'property_public',
                'property_protected',
                'property_private',
                'construct',
                'destruct',
                'magic',
                'phpunit',
                'method_public',
                'method_protected',
                'method_private',
            ],
            'sort_algorithm' => 'none',
        ],

        // Performance optimizations
        'dir_constant' => true,
        'function_to_constant' => true,
        'is_null' => true,
        'modernize_types_casting' => true,
        'no_php4_constructor' => true,
        'pow_to_exponentiation' => true,

        // Code quality - Enhanced for Laravel Zero
        'no_empty_comment' => true,
        'no_empty_phpdoc' => false,  // Preserve empty PHPDoc for PHPStan Level 10
        'no_empty_statement' => true,
        'simplified_null_return' => true,
        'ternary_to_null_coalescing' => true,
        'void_return' => true,
        'yoda_style' => ['equal' => false, 'identical' => false, 'less_and_greater' => false],
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'heredoc_to_nowdoc' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,

        // String and concatenation
        'concat_space' => ['spacing' => 'one'],
        'escape_implicit_backslashes' => true,
        'simple_to_complex_string_variable' => true,

        // Control structures - Laravel Zero console optimized
        'no_alternative_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_unneeded_control_parentheses' => [
            'statements' => ['break', 'clone', 'continue', 'echo_print', 'return', 'switch_case', 'yield'],
        ],
        'no_unneeded_curly_braces' => true,
        'switch_case_semicolon_to_colon' => true,
        'switch_case_space' => true,

        // Whitespace and formatting
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'compact_nullable_typehint' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'throw',
                'use',
            ],
        ],
        'no_spaces_around_offset' => true,
        'no_whitespace_before_comma_in_array' => true,
        'object_operator_without_whitespace' => true,
        'ternary_operator_spaces' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__ . '/storage/php-cs-fixer.cache')
    ->setUsingCache(true)
    ->setRiskyAllowed(true);