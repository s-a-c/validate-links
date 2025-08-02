<?php

declare(strict_types=1);

namespace App\Enums;

use InvalidArgumentException;

enum PathType
{
    case FILE;
    case DIRECTORY;

    public static function detect(string $path): self
    {
        return match (true) {
            is_file($path) => self::FILE,
            is_dir($path) => self::DIRECTORY,
            default => throw new InvalidArgumentException("Invalid path: {$path}")
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::FILE => 'Single file',
            self::DIRECTORY => 'Directory with multiple files',
        };
    }
}
