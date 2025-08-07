<?php

declare(strict_types=1);

namespace App\Enums;

enum FileType: string
{
    case MARKDOWN = 'md';
    case MARKDOWN_FULL = 'markdown';
    case HTML = 'html';
    case HTML_SHORT = 'htm';

    public static function isSupported(string $extension): bool
    {
        return match ($extension) {
            self::MARKDOWN->value,
            self::MARKDOWN_FULL->value,
            self::HTML->value,
            self::HTML_SHORT->value => true,
            default => false,
        };
    }

    /**
     * @return string[]
     */
    public static function getSupportedExtensions(): array
    {
        return array_map(fn (FileType $fileType) => $fileType->value, self::cases());
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::MARKDOWN => 'Markdown files (.md)',
            self::MARKDOWN_FULL => 'Markdown files (.markdown)',
            self::HTML => 'HTML files (.html)',
            self::HTML_SHORT => 'HTML files (.htm)',
        };
    }
}
