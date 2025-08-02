<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Factories;

/**
 * Factory for creating link data structures for testing.
 *
 * Provides convenient methods for creating realistic link data
 * with different types and scenarios.
 */
final class LinkDataFactory
{
    /**
     * Create a basic link data structure.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function create(array $overrides = []): array
    {
        $defaults = [
            'url' => 'https://example.com',
            'text' => 'Example Link',
            'title' => null,
            'line_number' => 1,
            'column' => 1,
            'file_path' => 'test.md',
            'type' => 'external',
            'anchor' => null,
        ];

        return array_merge($defaults, $overrides);
    }

    /**
     * Create an external HTTP link.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function external(array $overrides = []): array
    {
        return self::create(array_merge([
            'url' => 'https://external.example.com',
            'text' => 'External Link',
            'type' => 'external',
        ], $overrides));
    }

    /**
     * Create an internal relative link.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function internal(array $overrides = []): array
    {
        return self::create(array_merge([
            'url' => './internal-page.md',
            'text' => 'Internal Link',
            'type' => 'internal',
        ], $overrides));
    }

    /**
     * Create an anchor link.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function anchor(array $overrides = []): array
    {
        return self::create(array_merge([
            'url' => '#section-heading',
            'text' => 'Section Link',
            'type' => 'anchor',
            'anchor' => 'section-heading',
        ], $overrides));
    }

    /**
     * Create a mailto link.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function mailto(array $overrides = []): array
    {
        return self::create(array_merge([
            'url' => 'mailto:test@example.com',
            'text' => 'Email Link',
            'type' => 'mailto',
        ], $overrides));
    }

    /**
     * Create a file link.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function file(array $overrides = []): array
    {
        return self::create(array_merge([
            'url' => './assets/document.pdf',
            'text' => 'PDF Document',
            'type' => 'file',
        ], $overrides));
    }

    /**
     * Create an image link.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function image(array $overrides = []): array
    {
        return self::create(array_merge([
            'url' => './images/screenshot.png',
            'text' => 'Screenshot',
            'type' => 'image',
        ], $overrides));
    }

    /**
     * Create a link with specific line/column position.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function atPosition(int $line, int $column, array $overrides = []): array
    {
        return self::create(array_merge([
            'line_number' => $line,
            'column' => $column,
        ], $overrides));
    }

    /**
     * Create a link in a specific file.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function inFile(string $filePath, array $overrides = []): array
    {
        return self::create(array_merge([
            'file_path' => $filePath,
        ], $overrides));
    }

    /**
     * Create a collection of links for testing.
     *
     * @param  array<string, mixed>  $overrides
     * @return list<array<string, mixed>>
     */
    public static function collection(int $count = 5, array $overrides = []): array
    {
        $links = [];

        for ($i = 0; $i < $count; $i++) {
            $links[] = self::create(array_merge([
                'url' => "https://example.com/page-{$i}",
                'text' => "Link {$i}",
                'line_number' => $i + 1,
            ], $overrides));
        }

        return $links;
    }

    /**
     * Create a mixed collection of different link types.
     *
     * @return list<array<string, mixed>>
     */
    public static function mixedCollection(): array
    {
        return [
            self::external(['url' => 'https://github.com', 'text' => 'GitHub']),
            self::internal(['url' => './readme.md', 'text' => 'README']),
            self::anchor(['url' => '#installation', 'text' => 'Installation']),
            self::mailto(['url' => 'mailto:support@example.com', 'text' => 'Support']),
            self::file(['url' => './docs/guide.pdf', 'text' => 'User Guide']),
            self::image(['url' => './assets/logo.png', 'text' => 'Logo']),
        ];
    }

    /**
     * Create broken link data (for testing error scenarios).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function broken(array $overrides = []): array
    {
        return self::create(array_merge([
            'url' => 'https://broken.example.com/404',
            'text' => 'Broken Link',
            'type' => 'external',
        ], $overrides));
    }

    /**
     * Create link data with title attribute.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public static function withTitle(string $title, array $overrides = []): array
    {
        return self::create(array_merge([
            'title' => $title,
        ], $overrides));
    }
}
