<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddParamTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnNeverTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromStrictConstructorRector;

return static function (RectorConfig $rectorConfig): void {
    // Configure paths to analyze (consistent with main rector.php)
    $rectorConfig->paths([
        __DIR__.'/app',
        __DIR__.'/bootstrap',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/tests',
    ]);

    // Skip directories and files (consistent with main rector.php)
    $rectorConfig->skip([
        // Skip cache and vendor directories
        __DIR__.'/bootstrap/cache',
        __DIR__.'/storage',
        __DIR__.'/vendor',
        __DIR__.'/node_modules',

        // Skip migration files to avoid breaking schema definitions
        __DIR__.'/database/migrations',
    ]);

    // Performance and caching configuration
    $rectorConfig->parallel();
    $rectorConfig->cacheDirectory(__DIR__.'/storage/rector-type-safety');
    $rectorConfig->memoryLimit('1G');
    $rectorConfig->fileExtensions(['php']);

    // Import names configuration
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    // Type Declaration rules for enhanced type safety
    $rectorConfig->rules([
        // Add return types to methods
        AddReturnTypeDeclarationRector::class,
        AddVoidReturnTypeWhereNoReturnRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        AddParamTypeDeclarationRector::class,
        ReturnNeverTypeRector::class,

        // Add property types
        AddPropertyTypeDeclarationRector::class,
        TypedPropertyFromStrictConstructorRector::class,
    ]);
};
