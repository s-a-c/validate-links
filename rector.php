<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Configure paths to analyze - only include existing directories
    $paths = [];
    $potentialPaths = [
        __DIR__ . '/app',
        __DIR__ . '/bootstrap',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/tests',
    ];

    foreach ($potentialPaths as $path) {
        if (is_dir($path)) {
            $paths[] = $path;
        }
    }

    if (empty($paths)) {
        throw new RuntimeException('No valid paths found for Rector analysis');
    }

    $rectorConfig->paths($paths);

    // Skip directories and files - comprehensive exclusions
    $skipPaths = [];
    $potentialSkipPaths = [
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/storage/logs',
        __DIR__ . '/storage/framework',
        __DIR__ . '/storage/app',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/database/migrations', // Preserve migration structure
        __DIR__ . '/tests/Fixtures', // Preserve test fixtures
    ];

    foreach ($potentialSkipPaths as $path) {
        if (is_dir($path)) {
            $skipPaths[] = $path;
        }
    }

    // Skip specific rules that may cause issues with Laravel Zero
    $skipPaths[] = AddOverrideAttributeToOverriddenMethodsRector::class;

    // Skip files with specific patterns
    $skipPaths[] = '*/tests/Fixtures/*';
    $skipPaths[] = '*/storage/rector/*';
    $skipPaths[] = '*/bootstrap/cache/*';

    $rectorConfig->skip($skipPaths);

    // Configure comprehensive rule sets for PHP 8.4+ modernization
    $rectorConfig->sets([
        // PHP version upgrade to 8.4 with all features
        LevelSetList::UP_TO_PHP_84,

        // Enhanced code quality improvements
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,
        SetList::STRICT_BOOLEANS,
        SetList::NAMING,
        SetList::CARBON,

        // Laravel-specific improvements for Laravel Zero
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
        LaravelSetList::LARAVEL_ELOQUENT_MAGIC_METHOD_TO_QUERY_BUILDER,
        LaravelSetList::LARAVEL_LEGACY_FACTORIES_TO_CLASSES,
    ]);

    // Additional specific rules for enhanced type safety
    // Note: Individual rules commented out due to version compatibility
    // $rectorConfig->rules([
    //     AddVoidReturnTypeWhereNoReturnRector::class,
    //     TypedPropertyFromStrictConstructorRector::class,
    // ]);

    // Performance and caching configuration optimized for large codebase
    // Parallel processing disabled for compatibility
    // $rectorConfig->parallel(
    //     maxNumberOfProcess: 8,
    //     jobSize: 20
    // );

    $rectorConfig->cacheDirectory(__DIR__ . '/storage/rector');
    $rectorConfig->memoryLimit('2G'); // Increased for PHP 8.4+ processing

    // Enhanced file processing
    $rectorConfig->fileExtensions(['php']);
    $rectorConfig->indent(' ', 4); // Laravel standard indentation

    // Import names configuration for cleaner code
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
    $rectorConfig->removeUnusedImports();

    // Laravel Zero specific configurations
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');
};
