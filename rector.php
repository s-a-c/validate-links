<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use RectorLaravel\Set\LaravelSetList;

return static function (RectorConfig $rectorConfig): void {
    // Configure paths to analyze - only include existing directories
    $paths = [];
    $potentialPaths = [
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/tests',
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

    // Skip directories and files - only include existing directories
    $skipPaths = [];
    $potentialSkipPaths = [
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
        __DIR__.'/vendor',
        __DIR__.'/node_modules',
    ];

    foreach ($potentialSkipPaths as $path) {
        if (is_dir($path)) {
            $skipPaths[] = $path;
        }
    }

    // Always skip specific rules that may cause issues
    $skipPaths[] = AddOverrideAttributeToOverriddenMethodsRector::class;

    $rectorConfig->skip($skipPaths);

    // Configure rule sets for PHP 8.4+ modernization
    $rectorConfig->sets([
        // PHP version upgrade to 8.4
        LevelSetList::UP_TO_PHP_84,

        // Code quality improvements
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
        SetList::INSTANCEOF,

        // Laravel-specific improvements
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_ARRAY_STR_FUNCTION_TO_STATIC_CALL,
        LaravelSetList::LARAVEL_FACADE_ALIASES_TO_FULL_NAMES,
    ]);

    // Performance and caching configuration
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__.'/storage/rector');
    $rectorConfig->memoryLimit('1G');

    // File extensions to process
    $rectorConfig->fileExtensions(['php']);

    // Import names configuration
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
};
