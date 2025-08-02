<?php

declare(strict_types=1);

namespace .archive\tests.old\Support\Traits;

/**
 * Trait providing file system testing utilities for validate-links testing.
 *
 * This trait follows the established preference for trait-based composition
 * over base class inheritance, providing reusable file system utilities for
 * all test classes that need to create, manipulate, and clean up test files.
 */
trait FileTestHelpers
{
    /**
     * Create a test file with specified content.
     *
     * @param  string  $path  The relative path for the test file
     * @param  string  $content  The content to write to the file
     * @return string The full path to the created file
     */
    protected function createTestFile(string $path, string $content): string
    {
        $fullPath = $this->getTestPath($path);
        $directory = dirname((string) $fullPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
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
    protected function getTestPath(string $path): string
    {
        return __DIR__.'/../fixtures/'.mb_ltrim($path, '/');
    }

    /**
     * Create a test markdown file with optional links.
     *
     * @param  string  $path  The relative path for the markdown file
     * @param  array<string, string>  $links  Array of link text => URL pairs
     * @return string The full path to the created file
     */
    protected function createTestMarkdownFile(string $path, array $links = []): string
    {
        $content = "# Test Document\n\nThis is a test markdown file.\n\n";

        foreach ($links as $text => $url) {
            $content .= "- [{$text}]({$url})\n";
        }

        return $this->createTestFile($path, $content);
    }

    /**
     * Clean up all test files and directories.
     */
    protected function cleanupTestFiles(): void
    {
        $fixturesPath = __DIR__.'/../fixtures';
        if (is_dir($fixturesPath)) {
            $this->deleteDirectory($fixturesPath);
        }
    }

    protected function createTestDirectory(string $path): string
    {
        $fullPath = $this->getTestPath($path);
        if (! is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        return $fullPath;
    }

    protected function setUpFeatureTest(): void
    {
        // Ensure test directory exists
        $testPath = $this->getTestPath('');
        if (! is_dir($testPath)) {
            mkdir($testPath, 0755, true);
        }
    }

    protected function tearDownFeatureTest(): void
    {
        $this->cleanupTestFiles();
    }

    protected function createConfigFile(array $config, string $filename = 'validate-links.json'): string
    {
        $configPath = $this->getTestPath($filename);
        file_put_contents($configPath, json_encode($config, JSON_PRETTY_PRINT));

        return $configPath;
    }

    protected function createDocumentationSite(): void
    {
        $this->createTestDirectory('docs');
        $this->createTestDirectory('docs/guides');
        $this->createTestDirectory('docs/api');
        $this->createTestDirectory('docs/examples');

        $this->createTestFile('docs/README.md', '
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

        $this->createTestFile('docs/guides/getting-started.md', '
# Getting Started

This guide will help you get started.

## Prerequisites
- [Node.js](https://nodejs.org)
- [Git](https://git-scm.com)

## Next Steps
- [Advanced Guide](./advanced.md)
- [API Documentation](../api/reference.md)
        ');

        $this->createTestFile('docs/guides/advanced.md', '
# Advanced Guide

Advanced topics and configurations.

[Back to Getting Started](./getting-started.md)
        ');

        $this->createTestFile('docs/api/reference.md', '
# API Reference

Complete API documentation.

## Endpoints
- [Authentication](./auth.md)
- [Users](./users.md)

[Examples](../examples/basic.md)
        ');

        $this->createTestFile('docs/api/auth.md', '
# Authentication

Authentication endpoints.

[Back to API Reference](./reference.md)
        ');

        $this->createTestFile('docs/api/users.md', '
# Users API

User management endpoints.

[Back to API Reference](./reference.md)
        ');

        $this->createTestFile('docs/examples/basic.md', '
# Basic Examples

Simple usage examples.

[API Reference](../api/reference.md)
[Getting Started](../guides/getting-started.md)
        ');

        $this->createTestFile('docs/CHANGELOG.md', '
# Changelog

## v1.0.0
- Initial release
- [Documentation](./README.md)
        ');
    }

    protected function createDocumentationSiteWithBrokenLinks(): void
    {
        $this->createDocumentationSite();

        // Add broken links to existing files
        $this->createTestFile('docs/api/endpoints.md', '
# API Endpoints

Complete list of endpoints.

## Related
- [Non-existent guide](./non-existent.md)
- [Missing reference](../missing/reference.md)

## External
- [Broken API](https://broken-api.example.com)
        ');

        // Modify getting started to have broken external link
        $this->createTestFile('docs/guides/getting-started.md', '
# Getting Started

This guide will help you get started.

## Prerequisites
- [Node.js](https://nodejs.org)
- [Broken Link](https://broken-site-12345.com)

## Next Steps
- [Advanced Guide](./advanced.md)
- [API Documentation](../api/reference.md)
        ');
    }

    protected function createBlogSite(): void
    {
        $this->createTestDirectory('blog');
        $this->createTestDirectory('blog/posts');
        $this->createTestDirectory('blog/assets');

        $this->createTestFile('blog/index.md', '
# My Blog

Welcome to my blog about technology and development.

## Recent Posts
- [Getting Started with Laravel](./posts/laravel-intro.md)
- [JavaScript Best Practices](./posts/js-best-practices.md)
- [Docker for Developers](./posts/docker-guide.md)

## External Links
- [Laravel Documentation](https://laravel.com/docs)
- [MDN Web Docs](https://developer.mozilla.org)
- [Docker Hub](https://hub.docker.com)
        ');

        $this->createTestFile('blog/posts/laravel-intro.md', '
# Getting Started with Laravel

Laravel is a powerful PHP framework.

## Resources
- [Official Laravel Site](https://laravel.com)
- [Laracasts](https://laracasts.com)
- [Laravel News](https://laravel-news.com)

[Back to Blog](../index.md)
        ');

        $this->createTestFile('blog/posts/js-best-practices.md', '
# JavaScript Best Practices

Modern JavaScript development tips.

## External Resources
- [MDN JavaScript Guide](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide)
- [JavaScript.info](https://javascript.info)
- [ESLint](https://eslint.org)

[Back to Blog](../index.md)
        ');

        $this->createTestFile('blog/posts/docker-guide.md', '
# Docker for Developers

Container development made easy.

## Links
- [Docker Documentation](https://docs.docker.com)
- [Docker Compose](https://docs.docker.com/compose)
- [Dockerfile Reference](https://docs.docker.com/engine/reference/builder)

[Back to Blog](../index.md)
        ');
    }

    protected function createBlogWithSocialLinks(): void
    {
        $this->createBlogSite();

        $this->createTestFile('blog/about.md', '
# About Me

I am a developer passionate about technology.

## Connect with Me
- [Twitter](https://twitter.com/example)
- [LinkedIn](https://linkedin.com/in/example)
- [GitHub](https://github.com/example)
- [YouTube](https://youtube.com/c/example)
- [Instagram](https://instagram.com/example)

## Professional Links
- [Portfolio](https://example.dev)
- [Resume](https://example.dev/resume.pdf)
        ');
    }

    protected function createOpenSourceProject(): void
    {
        $this->createTestFile('README.md', '
# Awesome Project

[![Build Status](https://travis-ci.org/example/project.svg?branch=main)](https://travis-ci.org/example/project)
[![Coverage](https://codecov.io/gh/example/project/branch/main/graph/badge.svg)](https://codecov.io/gh/example/project)

An awesome open source project.

## Installation

```bash
npm install awesome-project
```

## Documentation

- [Getting Started](./docs/getting-started.md)
- [API Reference](./docs/api.md)
- [Contributing](./CONTRIBUTING.md)

## Links

- [Issues](https://github.com/example/project/issues)
- [Pull Requests](https://github.com/example/project/pulls)
- [Releases](https://github.com/example/project/releases)
        ');

        $this->createTestFile('CONTRIBUTING.md', '
# Contributing

We welcome contributions!

## Guidelines

- [Code of Conduct](./CODE_OF_CONDUCT.md)
- [Issue Templates](https://github.com/example/project/issues/new/choose)
- [Pull Request Template](https://github.com/example/project/compare)

## Development

See [Development Guide](./docs/development.md)
        ');

        $this->createTestFile('CODE_OF_CONDUCT.md', '
# Code of Conduct

Please be respectful and inclusive.

[Report Issues](https://github.com/example/project/issues)
        ');

        $this->createTestDirectory('docs');
        $this->createTestFile('docs/getting-started.md', '
# Getting Started

Quick start guide.

[API Reference](./api.md)
        ');

        $this->createTestFile('docs/api.md', '
# API Reference

Complete API documentation.

[Getting Started](./getting-started.md)
        ');

        $this->createTestFile('docs/development.md', '
# Development Guide

Development setup and guidelines.

[Contributing](../CONTRIBUTING.md)
        ');
    }

    protected function createGitHubStyleMarkdown(): void
    {
        $this->createTestFile('README.md', '
# Project Name

[![GitHub stars](https://img.shields.io/github/stars/example/project)](https://github.com/example/project/stargazers)
[![GitHub issues](https://img.shields.io/github/issues/example/project)](https://github.com/example/project/issues)

## Table of Contents

- [Installation](#installation)
- [Usage](#usage)
- [Contributing](#contributing)

## Installation

Install via npm:

```bash
npm install project-name
```

## Usage

Basic usage example:

```javascript
const project = require("project-name");
```

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

## Links

- [Documentation](https://example.github.io/project)
- [Issues](https://github.com/example/project/issues)
- [Wiki](https://github.com/example/project/wiki)
        ');
    }

    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
