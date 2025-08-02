<?php

declare(strict_types=1);

/**
 * Pest-compatible helper functions converted from PHPUnit traits.
 * These functions work in Pest closures without requiring $this context.
 */

/**
 * Create a test file with specified content.
 *
 * @param  string  $path  The relative path for the test file
 * @param  string  $content  The content to write to the file
 * @return string The full path to the created file
 */
function createTestFile(string $path, string $content): string
{
    $fullPath = getTestPath($path);
    $directory = dirname($fullPath);

    if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
        throw new RuntimeException("Failed to create directory: {$directory}");
    }

    file_put_contents($fullPath, $content);

    return $fullPath;
}

/**
 * Get the full path for a test file.
 *
 * @param  string  $path  The relative path
 * @return string The full path to the test file
 */
function getTestPath(string $path): string
{
    return __DIR__.'/fixtures/'.mb_ltrim($path, '/');
}

/**
 * Create a test markdown file with optional links.
 *
 * @param  string  $path  The relative path for the markdown file
 * @param  array<string, string>  $links  Array of link text => URL pairs
 * @return string The full path to the created file
 */
function createTestMarkdownFile(string $path, array $links = []): string
{
    $content = "# Test Document\n\nThis is a test markdown file.\n\n";

    foreach ($links as $text => $url) {
        $content .= "- [{$text}]({$url})\n";
    }

    return createTestFile($path, $content);
}

/**
 * Clean up all test files and directories.
 */
function cleanupTestFiles(): void
{
    $fixturesPath = __DIR__.'/fixtures';
    if (is_dir($fixturesPath)) {
        deleteDirectory($fixturesPath);
    }
}

/**
 * Create a test directory.
 *
 * @param  string  $path  The relative path for the directory
 * @return string The full path to the created directory
 */
function createTestDirectory(string $path): string
{
    $fullPath = getTestPath($path);
    if (! mkdir($fullPath, 0755, true) && ! is_dir($fullPath)) {
        throw new RuntimeException("Failed to create directory: {$fullPath}");
    }

    return $fullPath;
}

/**
 * Create a configuration file for testing.
 *
 * @param  array<string, mixed>  $config  The configuration array
 * @param  string  $filename  The filename for the config file
 * @return string The full path to the created config file
 */
function createConfigFile(array $config, string $filename = 'validate-links.json'): string
{
    $configPath = getTestPath($filename);
    file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));

    return $configPath;
}

/**
 * Create a sample validation configuration for testing.
 *
 * @return array<string, mixed> The sample configuration
 */
function createSampleValidationConfig(): array
{
    return [
        'scope' => 'all',
        'format' => 'console',
        'timeout' => 30,
        'max_redirects' => 5,
        'user_agent' => 'ValidateLinks/1.0',
        'exclude_patterns' => [],
        'include_patterns' => ['*.md', '*.html'],
        'follow_redirects' => true,
        'check_anchors' => true,
        'parallel_requests' => 10,
    ];
}

/**
 * Recursively delete a directory and all its contents.
 *
 * @param  string  $dir  The directory path to delete
 */
function deleteDirectory(string $dir): void
{
    if (! is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir) ?: [], ['.', '..']);

    foreach ($files as $file) {
        $path = $dir.DIRECTORY_SEPARATOR.$file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}

/**
 * Set up feature test environment.
 */
function setUpFeatureTest(): void
{
    // Ensure test directory exists
    $testPath = getTestPath('');
    if (! mkdir($testPath, 0755, true) && ! is_dir($testPath)) {
        throw new RuntimeException("Failed to create test directory: {$testPath}");
    }
}

/**
 * Tear down feature test environment.
 */
function tearDownFeatureTest(): void
{
    cleanupTestFiles();
}

/**
 * Create a documentation site structure for testing.
 */
function createDocumentationSite(): void
{
    createTestDirectory('docs');
    createTestDirectory('docs/guides');
    createTestDirectory('docs/api');
    createTestDirectory('docs/examples');

    createTestFile('docs/README.md', '
# Documentation

Welcome to our documentation.

## Quick Links
- [Getting Started](./guides/getting-started.md)
- [API Reference](./api/reference.md)
- [Examples](./examples/basic.md)

## External Resources
- [Official Website](https://example.com)
- [GitHub Repository](https://github.com/example/project)
    ');

    createTestFile('docs/guides/getting-started.md', '
# Getting Started

This guide will help you get started with our project.

## Installation
Follow these steps to install the project.

## Configuration
Configure the project using these settings.

## Next Steps
- [Advanced Usage](../api/reference.md)
- [Examples](../examples/basic.md)
    ');

    createTestFile('docs/api/reference.md', '
# API Reference

Complete API documentation.

## Classes
- [MainClass](./classes/main.md)
- [HelperClass](./classes/helper.md)

## Methods
Detailed method documentation.
    ');

    createTestFile('docs/examples/basic.md', '
# Basic Examples

Here are some basic examples.

## Example 1
Basic usage example.

## Example 2
Advanced usage example.
    ');
}

/**
 * Create a blog site structure for testing.
 */
function createBlogSite(): void
{
    createTestDirectory('blog');
    createTestDirectory('blog/posts');
    createTestDirectory('blog/categories');

    createTestFile('blog/index.md', '
# Blog

Welcome to our blog.

## Recent Posts
- [First Post](./posts/first-post.md)
- [Second Post](./posts/second-post.md)

## Categories
- [Technology](./categories/technology.md)
- [News](./categories/news.md)
    ');

    createTestFile('blog/posts/first-post.md', '
# First Post

This is our first blog post.

## Content
Some interesting content here.

## Related
- [Second Post](./second-post.md)
- [Technology Category](../categories/technology.md)
    ');
}

/**
 * Create an open source project structure for testing.
 */
function createOpenSourceProject(): void
{
    createTestDirectory('project');
    createTestDirectory('project/src');
    createTestDirectory('project/docs');
    createTestDirectory('project/tests.old');

    createTestFile('project/README.md', '
# Open Source Project

An awesome open source project.

## Features
- Feature 1
- Feature 2
- Feature 3

## Documentation
- [Installation](./docs/installation.md)
- [Usage](./docs/usage.md)
- [API](./docs/api.md)

## Contributing
- [Contributing Guide](./CONTRIBUTING.md)
- [Code of Conduct](./CODE_OF_CONDUCT.md)

## Links
- [GitHub](https://github.com/example/project)
- [Issues](https://github.com/example/project/issues)
- [Wiki](https://github.com/example/project/wiki)
    ');

    createTestFile('project/CONTRIBUTING.md', '
# Contributing

Thank you for contributing!

## Guidelines
Please follow these guidelines.

## Process
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## Resources
- [Development Setup](./docs/development.md)
- [Testing Guide](./docs/testing.md)
    ');
}
