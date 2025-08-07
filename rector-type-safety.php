<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ArrowFunction\AddArrowFunctionReturnTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationBasedOnParentClassMethodRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByMethodCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ParamTypeByParentCallTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\Closure\AddClosureReturnTypeRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\PropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;
use Rector\TypeDeclaration\Rector\Property\VarAnnotationIncorrectNullableRector;

return static function (RectorConfig $rectorConfig): void {
    // Configure paths to analyze - consistent with main rector.php
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

    // Skip files with specific patterns
    $skipPaths[] = '*/tests/Fixtures/*';
    $skipPaths[] = '*/storage/rector-type-safety/*';
    $skipPaths[] = '*/bootstrap/cache/*';

    $rectorConfig->skip($skipPaths);

    // Performance and caching configuration optimized for type safety analysis
    $rectorConfig->parallel(
        maxNumberOfProcess: 6, // Slightly lower for intensive type analysis
        jobSize: 15,
        jobTimeout: 180, // Longer timeout for complex type inference
    );

    $rectorConfig->cacheDirectory(__DIR__ . '/storage/rector-type-safety');
    $rectorConfig->memoryLimit('2G'); // Increased for complex type analysis
    $rectorConfig->fileExtensions(['php']);

    // Import names configuration
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
    $rectorConfig->removeUnusedImports();

    // Enhanced Type Declaration rules for maximum type safety
    $rectorConfig->rules([
        // Core type declarations
        AddReturnTypeDeclarationRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        AddParamTypeDeclarationRector::class,
        ReturnNeverTypeRector::class,

        // Property type declarations
        AddPropertyTypeDeclarationRector::class,
        TypedPropertyFromStrictConstructorRector::class,
        PropertyTypeDeclarationRector::class,
        VarAnnotationIncorrectNullableRector::class,

        // Advanced method type inference
        AddMethodCallBasedStrictParamTypeRector::class,
        AddReturnTypeDeclarationBasedOnParentClassMethodRector::class,
        ParamTypeByMethodCallTypeRector::class,
        ParamTypeByParentCallTypeRector::class,

        // Closure and arrow function types
        AddClosureReturnTypeRector::class,
        AddArrowFunctionReturnTypeRector::class,
    ]);

    // Laravel Zero specific type safety configurations
    $rectorConfig->phpstanConfig(__DIR__ . '/phpstan.neon');

    // Enhanced indentation for Laravel standards
    $rectorConfig->indent(' ', 4);
};
