<?php

declare(strict_types=1);

use App\Services\Contracts\GitHubAnchorInterface;
use App\Services\GitHubAnchorService;


describe('GitHubAnchorService', function () {
    beforeEach(function () {
        // Setup Phase: Prepare test environment and dependencies

        // Create service instance - no constructor dependencies
        $this->service = new GitHubAnchorService();

        // Set up reflection for testing class characteristics
        $this->reflection = new ReflectionClass(GitHubAnchorService::class);

        // Prepare test data sets for various scenarios
        $this->testHeadings = [
            'simple' => 'Simple Heading',
            'with_spaces' => 'Heading With Multiple Spaces',
            'with_special_chars' => 'Heading! With@ Special# Characters$',
            'with_markdown' => 'Heading with **bold** and *italic* text',
            'with_code' => 'Heading with `code` and ~strikethrough~',
            'with_numbers' => 'Heading 123 with Numbers 456',
            'with_hyphens' => 'Heading-with-existing-hyphens',
            'with_unicode' => 'Heading with émojis 🚀 and ñoñó',
            'empty' => '',
            'only_special' => '!@#$%^&*()',
            'only_spaces' => '   ',
            'mixed_case' => 'MiXeD CaSe HeAdInG',
        ];

        // Test markdown content with various heading formats
        $this->testContent = [
            'simple_headings' => "# Main Title\n## Section One\n### Subsection\n#### Details",
            'mixed_headings' => "# Introduction\n\nSome text here.\n\n## Getting Started\n\nMore text.\n\n### Installation\n\n#### Prerequisites",
            'with_anchors' => "# Main\n\nSee [link](#section-one) for details.\n\n## Section One\n\nContent here.",
            'with_html_anchors' => "# Title\n\n<a name=\"custom-anchor\">Custom</a>\n\n<a id=\"another-anchor\">Another</a>",
            'empty' => '',
            'no_headings' => 'Just some regular text without any headings.',
            'duplicate_headings' => "# Same Title\n\n## Same Title\n\n### Same Title",
            'complex_markdown' => "# Main **Bold** Title\n\n## Section with `code`\n\n### Sub with *italic*",
        ];

        // Test anchors for validation
        $this->testAnchors = [
            'valid' => [
                'main-title',
                'section-one',
                'subsection',
                'details',
            ],
            'invalid' => [
                'non-existent',
                'wrong-anchor',
                'missing-section',
            ],
            'with_hash' => [
                '#main-title',
                '#section-one',
                '#subsection',
            ],
            'mixed_case' => [
                'Main-Title',
                'SECTION-ONE',
                'SubSection',
            ],
        ];

        // Expected results for anchor generation
        $this->expectedAnchors = [
            'Simple Heading' => 'simple-heading',
            'Heading With Multiple Spaces' => 'heading-with-multiple-spaces',
            'Heading! With@ Special# Characters$' => 'heading-with-special-characters',
            'Heading with **bold** and *italic* text' => 'heading-with-bold-and-italic-text',
            'Heading with `code` and ~strikethrough~' => 'heading-with-code-and-strikethrough',
            'Heading 123 with Numbers 456' => 'heading-123-with-numbers-456',
            'Heading-with-existing-hyphens' => 'heading-with-existing-hyphens',
            'MiXeD CaSe HeAdInG' => 'mixed-case-heading',
            '' => '',
            '!@#$%^&*()' => '',
            '   ' => '',
        ];
    });

    afterEach(function () {
        // Teardown Phase: Clean up test environment

        // Clear test data to prevent memory leaks
        $this->service = null;
        $this->reflection = null;
        $this->testHeadings = null;
        $this->testContent = null;
        $this->testAnchors = null;
        $this->expectedAnchors = null;

        // Force garbage collection if available
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    });

    describe('service instantiation and interface compliance', function () {
        it('implements GitHubAnchorInterface', function () {
            expect($this->service)->toBeInstanceOf(GitHubAnchorInterface::class);
        });

        it('is a final class', function () {
            expect($this->reflection->isFinal())->toBeTrue();
        });

        it('has no constructor dependencies', function () {
            $constructor = $this->reflection->getConstructor();
            expect($constructor)->toBeNull();
        });

        it('implements all required interface methods', function () {
            $interfaceReflection = new ReflectionClass(GitHubAnchorInterface::class);
            $interfaceMethods = $interfaceReflection->getMethods();

            foreach ($interfaceMethods as $method) {
                expect($this->reflection->hasMethod($method->getName()))->toBeTrue();
            }
        });
    });

    describe('generateAnchor method', function () {
        it('generates correct anchors for simple headings', function () {
            $result = $this->service->generateAnchor($this->testHeadings['simple']);
            expect($result)->toBe($this->expectedAnchors['Simple Heading']);
        });

        it('handles multiple spaces correctly', function () {
            $result = $this->service->generateAnchor($this->testHeadings['with_spaces']);
            expect($result)->toBe($this->expectedAnchors['Heading With Multiple Spaces']);
        });

        it('removes special characters', function () {
            $result = $this->service->generateAnchor($this->testHeadings['with_special_chars']);
            expect($result)->toBe($this->expectedAnchors['Heading! With@ Special# Characters$']);
        });

        it('removes markdown formatting', function () {
            $result = $this->service->generateAnchor($this->testHeadings['with_markdown']);
            expect($result)->toBe($this->expectedAnchors['Heading with **bold** and *italic* text']);
        });

        it('removes code and strikethrough formatting', function () {
            $result = $this->service->generateAnchor($this->testHeadings['with_code']);
            expect($result)->toBe($this->expectedAnchors['Heading with `code` and ~strikethrough~']);
        });

        it('preserves numbers in anchors', function () {
            $result = $this->service->generateAnchor($this->testHeadings['with_numbers']);
            expect($result)->toBe($this->expectedAnchors['Heading 123 with Numbers 456']);
        });

        it('handles existing hyphens correctly', function () {
            $result = $this->service->generateAnchor($this->testHeadings['with_hyphens']);
            expect($result)->toBe($this->expectedAnchors['Heading-with-existing-hyphens']);
        });

        it('converts mixed case to lowercase', function () {
            $result = $this->service->generateAnchor($this->testHeadings['mixed_case']);
            expect($result)->toBe($this->expectedAnchors['MiXeD CaSe HeAdInG']);
        });

        it('handles empty strings', function () {
            $result = $this->service->generateAnchor($this->testHeadings['empty']);
            expect($result)->toBe($this->expectedAnchors['']);
        });

        it('handles strings with only special characters', function () {
            $result = $this->service->generateAnchor($this->testHeadings['only_special']);
            expect($result)->toBe($this->expectedAnchors['!@#$%^&*()']);
        });

        it('handles strings with only spaces', function () {
            $result = $this->service->generateAnchor($this->testHeadings['only_spaces']);
            expect($result)->toBe($this->expectedAnchors['   ']);
        });
    });

    describe('extractAnchors method', function () {
        it('extracts anchors from simple headings', function () {
            $result = $this->service->extractAnchors($this->testContent['simple_headings']);

            expect($result)->toContain('main-title')
                ->and($result)->toContain('section-one')
                ->and($result)->toContain('subsection')
                ->and($result)->toContain('details');
        });

        it('extracts anchors from mixed content', function () {
            $result = $this->service->extractAnchors($this->testContent['mixed_headings']);

            expect($result)->toContain('introduction')
                ->and($result)->toContain('getting-started')
                ->and($result)->toContain('installation')
                ->and($result)->toContain('prerequisites');
        });

        it('extracts explicit anchor links', function () {
            $result = $this->service->extractAnchors($this->testContent['with_anchors']);

            expect($result)->toContain('main')
                ->and($result)->toContain('section-one');
        });

        it('extracts HTML anchor tags', function () {
            $result = $this->service->extractAnchors($this->testContent['with_html_anchors']);

            expect($result)->toContain('title')
                ->and($result)->toContain('custom-anchor')
                ->and($result)->toContain('another-anchor');
        });

        it('returns empty array for content without headings', function () {
            $result = $this->service->extractAnchors($this->testContent['no_headings']);
            expect($result)->toBeEmpty();
        });

        it('returns empty array for empty content', function () {
            $result = $this->service->extractAnchors($this->testContent['empty']);
            expect($result)->toBeEmpty();
        });

        it('handles duplicate headings correctly', function () {
            $result = $this->service->extractAnchors($this->testContent['duplicate_headings']);

            // Should return unique anchors only
            $uniqueAnchors = array_unique($result);
            expect(count($result))->toBe(count($uniqueAnchors));
        });

        it('extracts anchors from complex markdown', function () {
            $result = $this->service->extractAnchors($this->testContent['complex_markdown']);

            expect($result)->toContain('main-bold-title')
                ->and($result)->toContain('section-with-code')
                ->and($result)->toContain('sub-with-italic');
        });
    });

    describe('validateAnchor method', function () {
        it('validates existing anchors correctly', function () {
            foreach ($this->testAnchors['valid'] as $anchor) {
                $result = $this->service->validateAnchor($this->testContent['simple_headings'], $anchor);
                expect($result)->toBeTrue();
            }
        });

        it('rejects non-existent anchors', function () {
            foreach ($this->testAnchors['invalid'] as $anchor) {
                $result = $this->service->validateAnchor($this->testContent['simple_headings'], $anchor);
                expect($result)->toBeFalse();
            }
        });

        it('handles anchors with hash prefix', function () {
            foreach ($this->testAnchors['with_hash'] as $anchor) {
                $result = $this->service->validateAnchor($this->testContent['simple_headings'], $anchor);
                expect($result)->toBeTrue();
            }
        });

        it('performs case-insensitive validation', function () {
            foreach ($this->testAnchors['mixed_case'] as $anchor) {
                $result = $this->service->validateAnchor($this->testContent['simple_headings'], $anchor);
                expect($result)->toBeTrue();
            }
        });

        it('returns false for empty content', function () {
            $result = $this->service->validateAnchor($this->testContent['empty'], 'any-anchor');
            expect($result)->toBeFalse();
        });
    });

    describe('normalizeAnchor method', function () {
        it('removes hash prefix', function () {
            $result = $this->service->normalizeAnchor('#test-anchor');
            expect($result)->toBe('test-anchor');
        });

        it('converts to lowercase', function () {
            $result = $this->service->normalizeAnchor('TEST-ANCHOR');
            expect($result)->toBe('test-anchor');
        });

        it('handles anchors without hash', function () {
            $result = $this->service->normalizeAnchor('test-anchor');
            expect($result)->toBe('test-anchor');
        });

        it('handles empty strings', function () {
            $result = $this->service->normalizeAnchor('');
            expect($result)->toBe('');
        });

        it('handles multiple hash prefixes', function () {
            $result = $this->service->normalizeAnchor('##test-anchor');
            expect($result)->toBe('test-anchor');
        });
    });
});
