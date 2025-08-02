<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\PathType;
use Exception;
use InvalidArgumentException;

/**
 * Comprehensive unit tests for PathType enum.
 *
 * Tests all enum cases, methods, and edge cases with 100% coverage.
 * Validates path type detection and description methods.
 */
describe('PathType Enum', function () {

    describe('Enum Cases', function () {
        it('has all expected enum cases', function () {
            $expectedCases = [
                'FILE',
                'DIRECTORY',
            ];

            $actualCases = array_map(fn ($case) => $case->name, PathType::cases());

            expect($actualCases)->toBe($expectedCases);
        });

        it('is a unit enum without backing values', function () {
            foreach (PathType::cases() as $pathType) {
                expect($pathType)->toBeInstanceOf(PathType::class);
                // Unit enums don't have a value property
                expect(property_exists($pathType, 'value'))->toBeFalse();
            }
        });
    });

    describe('detect() method', function () {
        it('detects file paths correctly', function () {
            // Create a temporary file for testing
            $tempFile = tempnam(sys_get_temp_dir(), 'pathtype_test_');

            expect(PathType::detect($tempFile))->toBe(PathType::FILE);

            // Clean up
            unlink($tempFile);
        });

        it('detects directory paths correctly', function () {
            // Use system temp directory which should always exist
            $tempDir = sys_get_temp_dir();

            expect(PathType::detect($tempDir))->toBe(PathType::DIRECTORY);
        });

        it('throws exception for non-existent paths', function () {
            $nonExistentPath = '/this/path/should/not/exist/'.uniqid();

            expect(fn () => PathType::detect($nonExistentPath))
                ->toThrow(InvalidArgumentException::class, "Invalid path: {$nonExistentPath}");
        });

        it('throws exception for empty path', function () {
            expect(fn () => PathType::detect(''))
                ->toThrow(InvalidArgumentException::class, 'Invalid path: ');
        });

        it('handles current directory correctly', function () {
            expect(PathType::detect('.'))->toBe(PathType::DIRECTORY);
        });

        it('handles parent directory correctly', function () {
            expect(PathType::detect('..'))->toBe(PathType::DIRECTORY);
        });
    });

    describe('getDescription() method', function () {
        it('returns correct descriptions for all path types', function () {
            expect(PathType::FILE->getDescription())->toBe('Single file');
            expect(PathType::DIRECTORY->getDescription())->toBe('Directory with multiple files');
        });

        it('returns non-empty descriptions for all cases', function () {
            foreach (PathType::cases() as $pathType) {
                expect($pathType->getDescription())->toBeString()
                    ->and($pathType->getDescription())->not->toBeEmpty();
            }
        });

        it('returns meaningful descriptions', function () {
            foreach (PathType::cases() as $pathType) {
                $description = $pathType->getDescription();

                expect(mb_strlen($description))->toBeGreaterThan(5);
            }
        });
    });

    describe('Type Safety', function () {
        it('enum cases are of correct type', function () {
            foreach (PathType::cases() as $pathType) {
                expect($pathType)->toBeInstanceOf(PathType::class);
            }
        });

        it('static methods return correct types', function () {
            // Create a temporary file for testing
            $tempFile = tempnam(sys_get_temp_dir(), 'pathtype_test_');

            expect(PathType::detect($tempFile))->toBeInstanceOf(PathType::class);

            // Clean up
            unlink($tempFile);
        });

        it('instance methods return correct types', function () {
            $pathType = PathType::FILE;

            expect($pathType->getDescription())->toBeString();
        });
    });

    describe('Enum Completeness', function () {
        it('has exactly 2 enum cases', function () {
            expect(PathType::cases())->toHaveCount(2);
        });

        it('all cases have unique names', function () {
            $names = array_map(fn ($case) => $case->name, PathType::cases());
            $uniqueNames = array_unique($names);

            expect(count($names))->toBe(count($uniqueNames));
        });
    });

    describe('Business Logic Validation', function () {
        it('correctly identifies file type', function () {
            // Create a temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'pathtype_test_');

            $detectedType = PathType::detect($tempFile);

            expect($detectedType)->toBe(PathType::FILE);
            expect($detectedType->getDescription())->toContain('file');

            // Clean up
            unlink($tempFile);
        });

        it('correctly identifies directory type', function () {
            $tempDir = sys_get_temp_dir();

            $detectedType = PathType::detect($tempDir);

            expect($detectedType)->toBe(PathType::DIRECTORY);
            expect($detectedType->getDescription())->toContain('Directory');
        });
    });

    describe('Edge Cases', function () {
        it('handles all enum cases without exceptions', function () {
            foreach (PathType::cases() as $pathType) {
                expect(fn () => $pathType->getDescription())->not->toThrow(Exception::class);
            }
        });

        it('methods are deterministic', function () {
            foreach (PathType::cases() as $pathType) {
                $description1 = $pathType->getDescription();
                $description2 = $pathType->getDescription();

                expect($description1)->toBe($description2);
            }
        });

        it('detect method handles various path formats', function () {
            // Test with different valid paths
            $validPaths = [
                sys_get_temp_dir(),  // Directory
                '.',                 // Current directory
                '..',               // Parent directory
            ];

            foreach ($validPaths as $path) {
                expect(fn () => PathType::detect($path))->not->toThrow(Exception::class);
                expect(PathType::detect($path))->toBeInstanceOf(PathType::class);
            }
        });

        it('detect method throws for invalid paths', function () {
            $invalidPaths = [
                '/nonexistent/path/'.uniqid(),
                'invalid_file_'.uniqid(),
                '/dev/null/invalid',  // Can't be a directory under a device file
            ];

            foreach ($invalidPaths as $path) {
                expect(fn () => PathType::detect($path))->toThrow(InvalidArgumentException::class);
            }
        });
    });

    describe('Integration with File System', function () {
        it('works with real file system operations', function () {
            // Create temporary file and directory
            $tempFile = tempnam(sys_get_temp_dir(), 'pathtype_test_');
            $tempDir = sys_get_temp_dir().'/pathtype_test_'.uniqid();
            mkdir($tempDir);

            // Test detection
            expect(PathType::detect($tempFile))->toBe(PathType::FILE);
            expect(PathType::detect($tempDir))->toBe(PathType::DIRECTORY);

            // Clean up
            unlink($tempFile);
            rmdir($tempDir);
        });

        it('handles symbolic links appropriately', function () {
            // Skip if not on Unix-like system
            if (PHP_OS_FAMILY !== 'Linux' && PHP_OS_FAMILY !== 'Darwin') {
                expect(true)->toBeTrue(); // Skip test

                return;
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'pathtype_test_');
            $linkPath = sys_get_temp_dir().'/pathtype_link_'.uniqid();

            // Create symbolic link
            if (symlink($tempFile, $linkPath)) {
                expect(PathType::detect($linkPath))->toBe(PathType::FILE);

                // Clean up
                unlink($linkPath);
            }

            unlink($tempFile);
        });
    });
});
