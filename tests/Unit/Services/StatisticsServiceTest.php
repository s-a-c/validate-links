<?php

declare(strict_types=1);

use App\Services\Contracts\StatisticsInterface;
use App\Services\StatisticsService;
use App\Services\ValueObjects\ValidationStatistics;
use Carbon\Carbon;


describe('StatisticsService', function () {
    beforeEach(function () {
        // Setup Phase: Prepare test environment and dependencies

        // Create service instance
        $this->service = new StatisticsService();

        // Set up reflection for testing class characteristics
        $this->reflection = new ReflectionClass(StatisticsService::class);

        // Mock Carbon's now() function for consistent timestamps
        $this->testTimestamp = '2023-01-01 12:00:00';
        Carbon::setTestNow(Carbon::parse($this->testTimestamp));

        // Prepare comprehensive test data sets
        $this->testUrls = [
            'valid' => [
                'https://example.com',
                'https://valid1.com',
                'https://valid2.com',
                'https://test.com',
            ],
            'broken' => [
                'https://broken.com',
                'https://broken1.com',
                'https://broken2.com',
                'https://404.com',
            ],
            'special' => [
                'empty' => '',
                'unicode' => 'https://тест.рф/путь',
                'long' => 'https://example.com/'.str_repeat('very-long-path/', 100),
            ],
        ];

        $this->testFiles = [
            'markdown' => [
                '/file1.md',
                '/file2.md',
                '/path/to/file.md',
                '/nested/directory/file.md',
            ],
            'special' => [
                'empty' => '',
                'unicode' => '/путь/к/файлу.md',
                'long' => '/very/long/path/'.str_repeat('nested/directory/', 50).'file.md',
            ],
        ];

        $this->testErrors = [
            'common' => [
                '404 Not Found',
                'Connection timeout',
                'DNS resolution failed',
                'SSL certificate error',
            ],
            'special' => [
                'empty' => '',
                'unicode' => 'Ошибка с unicode: 🚫 тест',
                'special_chars' => 'Error with special chars: <>&"\'',
                'long' => str_repeat('Very long error message. ', 100),
            ],
        ];

        // Expected initial state
        $this->expectedInitialState = [
            'total' => 0,
            'valid' => 0,
            'invalid' => 0,
            'success_rate' => 0.0,
            'broken_links_count' => 0,
            'processed_files_count' => 0,
            'files_processed' => 0,
            'total_links' => 0,
        ];

        // Expected array keys for complete statistics
        $this->expectedStatisticsKeys = [
            'total', 'valid', 'invalid', 'success_rate',
            'broken_links_count', 'processed_files_count',
            'files_processed', 'total_links', 'start_time',
        ];
    });

    afterEach(function () {
        // Teardown Phase: Clean up test environment

        // Reset Carbon mock to prevent interference with other tests
        Carbon::setTestNow();

        // Clear all test data
        $this->service = null;
        $this->reflection = null;
        $this->testTimestamp = null;
        $this->testUrls = null;
        $this->testFiles = null;
        $this->testErrors = null;
        $this->expectedInitialState = null;
        $this->expectedStatisticsKeys = null;

        // Force garbage collection if available
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
    });

    describe('class structure and interface compliance', function () {
        it('implements StatisticsInterface', function () {
            expect($this->service)->toBeInstanceOf(StatisticsInterface::class);
        });

        it('is a final class', function () {
            expect($this->reflection->isFinal())->toBeTrue();
        });

        it('has proper namespace', function () {
            expect($this->reflection->getNamespaceName())->toBe('App\Services');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Services/StatisticsService.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });

        it('has all required interface methods', function () {
            $interfaceReflection = new ReflectionClass(StatisticsInterface::class);
            $interfaceMethods = $interfaceReflection->getMethods();

            foreach ($interfaceMethods as $method) {
                expect($this->reflection->hasMethod($method->getName()))->toBeTrue();
            }
        });
    });

    describe('constructor and initialization', function () {
        it('initializes with empty statistics', function () {
            $freshService = new StatisticsService();
            $stats = $freshService->getStatistics();

            foreach ($this->expectedInitialState as $key => $expectedValue) {
                expect($stats[$key])->toBe($expectedValue);
            }
        });

        it('sets start time on initialization', function () {
            // Create a fresh service after Carbon mock is set
            $freshService = new StatisticsService();
            $stats = $freshService->getStatistics();

            expect($stats)->toHaveKey('start_time')
                ->and($stats['start_time'])->toBeString()
                ->and($stats['start_time'])->toContain('2023-01-01T12:00:00');
        });

        it('initializes empty broken links array', function () {
            $brokenLinks = $this->service->getBrokenLinks();

            expect($brokenLinks)->toHaveKey('broken_links')
                ->and($brokenLinks['broken_links'])->toBe([])
                ->and($brokenLinks['broken_links'])->toBeArray();
        });

        it('initializes empty processed files array', function () {
            $processedFiles = $this->service->getProcessedFiles();

            expect($processedFiles)->toHaveKey('processed_files')
                ->and($processedFiles['processed_files'])->toBe([])
                ->and($processedFiles['processed_files'])->toBeArray();
        });

        it('has all expected statistics keys', function () {
            $stats = $this->service->getStatistics();

            foreach ($this->expectedStatisticsKeys as $key) {
                expect($stats)->toHaveKey($key);
            }
        });
    });

    describe('recordValidation method', function () {
        it('records valid validation', function () {
            $this->service->recordValidation($this->testUrls['valid'][0], true);

            $stats = $this->service->getStatistics();
            expect($stats['total'])->toBe(1)
                ->and($stats['valid'])->toBe(1)
                ->and($stats['invalid'])->toBe(0)
                ->and($stats['success_rate'])->toBe(100.0);
        });

        it('records invalid validation with error message', function () {
            $this->service->recordValidation($this->testUrls['broken'][0], false, $this->testErrors['common'][0]);

            $stats = $this->service->getStatistics();
            expect($stats['total'])->toBe(1)
                ->and($stats['valid'])->toBe(0)
                ->and($stats['invalid'])->toBe(1)
                ->and($stats['success_rate'])->toBe(0.0)
                ->and($stats['broken_links_count'])->toBe(1);
        });

        it('records invalid validation without error message', function () {
            $this->service->recordValidation($this->testUrls['broken'][0], false);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'])->toHaveCount(1)
                ->and($brokenLinks['broken_links'][0]['url'])->toBe($this->testUrls['broken'][0])
                ->and($brokenLinks['broken_links'][0]['error'])->toBeNull();
        });

        it('adds broken link with timestamp', function () {
            $this->service->recordValidation($this->testUrls['broken'][1], false, $this->testErrors['common'][1]);

            $brokenLinks = $this->service->getBrokenLinks();
            $brokenLink = $brokenLinks['broken_links'][0];

            expect($brokenLink)->toHaveKey('url', $this->testUrls['broken'][1])
                ->and($brokenLink)->toHaveKey('error', $this->testErrors['common'][1])
                ->and($brokenLink)->toHaveKey('timestamp')
                ->and($brokenLink['timestamp'])->toContain('2023-01-01T12:00:00');
        });

        it('records multiple validations correctly', function () {
            $this->service->recordValidation($this->testUrls['valid'][0], true);
            $this->service->recordValidation($this->testUrls['broken'][0], false, $this->testErrors['common'][0]);
            $this->service->recordValidation($this->testUrls['valid'][1], true);
            $this->service->recordValidation($this->testUrls['broken'][1], false, $this->testErrors['common'][1]);

            $stats = $this->service->getStatistics();
            expect($stats['total'])->toBe(4)
                ->and($stats['valid'])->toBe(2)
                ->and($stats['invalid'])->toBe(2)
                ->and($stats['success_rate'])->toBe(50.0)
                ->and($stats['broken_links_count'])->toBe(2);
        });

        it('handles empty URL', function () {
            $this->service->recordValidation($this->testUrls['special']['empty'], false, $this->testErrors['special']['empty']);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'][0]['url'])->toBe('');
        });

        it('handles very long URLs', function () {
            $this->service->recordValidation($this->testUrls['special']['long'], true);

            $stats = $this->service->getStatistics();
            expect($stats['valid'])->toBe(1);
        });

        it('handles unicode URLs', function () {
            $this->service->recordValidation($this->testUrls['special']['unicode'], false, $this->testErrors['special']['unicode']);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'][0]['url'])->toBe($this->testUrls['special']['unicode']);
        });

        it('handles special characters in error messages', function () {
            $this->service->recordValidation($this->testUrls['broken'][2], false, $this->testErrors['special']['special_chars']);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'][0]['error'])->toBe($this->testErrors['special']['special_chars']);
        });
    });

    describe('recordFileProcessed method', function () {
        it('records file processing', function () {
            $this->service->recordFileProcessed($this->testFiles['markdown'][2], 5);

            $stats = $this->service->getStatistics();
            expect($stats['files_processed'])->toBe(1)
                ->and($stats['total_links'])->toBe(5)
                ->and($stats['processed_files_count'])->toBe(1);
        });

        it('adds processed file with timestamp', function () {
            $this->service->recordFileProcessed($this->testFiles['markdown'][0], 3);

            $processedFiles = $this->service->getProcessedFiles();
            $file = $processedFiles['processed_files'][0];

            expect($file)->toHaveKey('file', $this->testFiles['markdown'][0])
                ->and($file)->toHaveKey('link_count', 3)
                ->and($file)->toHaveKey('timestamp')
                ->and($file['timestamp'])->toContain('2023-01-01T12:00:00');
        });

        it('records multiple files correctly', function () {
            $this->service->recordFileProcessed($this->testFiles['markdown'][0], 2);
            $this->service->recordFileProcessed($this->testFiles['markdown'][1], 3);
            $this->service->recordFileProcessed($this->testFiles['markdown'][3], 1);

            $stats = $this->service->getStatistics();
            expect($stats['files_processed'])->toBe(3)
                ->and($stats['total_links'])->toBe(6)
                ->and($stats['processed_files_count'])->toBe(3);
        });

        it('handles zero link count', function () {
            $this->service->recordFileProcessed('/empty-file.md', 0);

            $stats = $this->service->getStatistics();
            expect($stats['files_processed'])->toBe(1)
                ->and($stats['total_links'])->toBe(0);
        });

        it('handles negative link count', function () {
            $this->service->recordFileProcessed($this->testFiles['markdown'][1], -1);

            $stats = $this->service->getStatistics();
            expect($stats['total_links'])->toBe(-1);
        });

        it('handles empty file path', function () {
            $this->service->recordFileProcessed($this->testFiles['special']['empty'], 5);

            $processedFiles = $this->service->getProcessedFiles();
            expect($processedFiles['processed_files'][0]['file'])->toBe('');
        });

        it('handles very long file paths', function () {
            $this->service->recordFileProcessed($this->testFiles['special']['long'], 2);

            $processedFiles = $this->service->getProcessedFiles();
            expect($processedFiles['processed_files'][0]['file'])->toBe($this->testFiles['special']['long']);
        });

        it('handles unicode file paths', function () {
            $this->service->recordFileProcessed($this->testFiles['special']['unicode'], 1);

            $processedFiles = $this->service->getProcessedFiles();
            expect($processedFiles['processed_files'][0]['file'])->toBe($this->testFiles['special']['unicode']);
        });
    });

    describe('getStatistics method', function () {
        it('returns complete statistics array', function () {
            $this->service->recordValidation($this->testUrls['valid'][0], true);
            $this->service->recordValidation($this->testUrls['broken'][0], false, $this->testErrors['common'][0]);
            $this->service->recordFileProcessed($this->testFiles['markdown'][0], 2);

            $stats = $this->service->getStatistics();

            foreach ($this->expectedStatisticsKeys as $key) {
                expect($stats)->toHaveKey($key);
            }
        });

        it('calculates success rate correctly', function () {
            $this->service->recordValidation($this->testUrls['valid'][0], true);
            $this->service->recordValidation($this->testUrls['valid'][1], true);
            $this->service->recordValidation($this->testUrls['broken'][0], false);

            $stats = $this->service->getStatistics();
            expect($stats['success_rate'])->toBe(66.67);
        });

        it('returns zero success rate for no validations', function () {
            $stats = $this->service->getStatistics();
            expect($stats['success_rate'])->toBe(0.0);
        });

        it('counts broken links correctly', function () {
            $this->service->recordValidation($this->testUrls['broken'][0], false);
            $this->service->recordValidation($this->testUrls['broken'][1], false);
            $this->service->recordValidation($this->testUrls['valid'][0], true);

            $stats = $this->service->getStatistics();
            expect($stats['broken_links_count'])->toBe(2);
        });

        it('counts processed files correctly', function () {
            $this->service->recordFileProcessed($this->testFiles['markdown'][0], 1);
            $this->service->recordFileProcessed($this->testFiles['markdown'][1], 2);

            $stats = $this->service->getStatistics();
            expect($stats['processed_files_count'])->toBe(2);
        });

        it('maintains data consistency across operations', function () {
            // Record mixed operations
            $this->service->recordValidation($this->testUrls['valid'][0], true);
            $this->service->recordValidation($this->testUrls['broken'][0], false, $this->testErrors['common'][0]);
            $this->service->recordFileProcessed($this->testFiles['markdown'][0], 3);

            $stats = $this->service->getStatistics();

            expect($stats['total'])->toBe(2)
                ->and($stats['valid'])->toBe(1)
                ->and($stats['invalid'])->toBe(1)
                ->and($stats['files_processed'])->toBe(1)
                ->and($stats['total_links'])->toBe(3)
                ->and($stats['success_rate'])->toBe(50.0);
        });
    });

    describe('getBrokenLinks method', function () {
        it('returns broken links array structure', function () {
            $brokenLinks = $this->service->getBrokenLinks();

            expect($brokenLinks)->toHaveKey('broken_links')
                ->and($brokenLinks['broken_links'])->toBeArray();
        });

        it('returns empty array when no broken links', function () {
            $this->service->recordValidation('https://valid.com', true);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'])->toBe([]);
        });

        it('returns all broken links with details', function () {
            $this->service->recordValidation('https://broken1.com', false, 'Error 1');
            $this->service->recordValidation('https://broken2.com', false, 'Error 2');
            $this->service->recordValidation('https://valid.com', true);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'])->toHaveCount(2)
                ->and($brokenLinks['broken_links'][0]['url'])->toBe('https://broken1.com')
                ->and($brokenLinks['broken_links'][0]['error'])->toBe('Error 1')
                ->and($brokenLinks['broken_links'][1]['url'])->toBe('https://broken2.com')
                ->and($brokenLinks['broken_links'][1]['error'])->toBe('Error 2');
        });
    });

    describe('getProcessedFiles method', function () {
        it('returns processed files array structure', function () {
            $processedFiles = $this->service->getProcessedFiles();

            expect($processedFiles)->toHaveKey('processed_files')
                ->and($processedFiles['processed_files'])->toBeArray();
        });

        it('returns empty array when no files processed', function () {
            $processedFiles = $this->service->getProcessedFiles();
            expect($processedFiles['processed_files'])->toBe([]);
        });

        it('returns all processed files with details', function () {
            $this->service->recordFileProcessed('/file1.md', 3);
            $this->service->recordFileProcessed('/file2.md', 5);

            $processedFiles = $this->service->getProcessedFiles();
            expect($processedFiles['processed_files'])->toHaveCount(2)
                ->and($processedFiles['processed_files'][0]['file'])->toBe('/file1.md')
                ->and($processedFiles['processed_files'][0]['link_count'])->toBe(3)
                ->and($processedFiles['processed_files'][1]['file'])->toBe('/file2.md')
                ->and($processedFiles['processed_files'][1]['link_count'])->toBe(5);
        });
    });

    describe('reset method', function () {
        it('resets all statistics to initial state', function () {
            // Add some data
            $this->service->recordValidation('https://test.com', true);
            $this->service->recordFileProcessed('/file.md', 1);

            // Reset
            $this->service->reset();

            $stats = $this->service->getStatistics();
            expect($stats['total'])->toBe(0)
                ->and($stats['valid'])->toBe(0)
                ->and($stats['invalid'])->toBe(0)
                ->and($stats['files_processed'])->toBe(0)
                ->and($stats['total_links'])->toBe(0)
                ->and($stats['success_rate'])->toBe(0.0)
                ->and($stats['broken_links_count'])->toBe(0)
                ->and($stats['processed_files_count'])->toBe(0);
        });

        it('clears broken links array', function () {
            $this->service->recordValidation('https://broken.com', false, 'Error');
            $this->service->reset();

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'])->toBe([]);
        });

        it('clears processed files array', function () {
            $this->service->recordFileProcessed('/file.md', 1);
            $this->service->reset();

            $processedFiles = $this->service->getProcessedFiles();
            expect($processedFiles['processed_files'])->toBe([]);
        });

        it('updates start time on reset', function () {
            $originalStats = $this->service->getStatistics();
            $originalStartTime = $originalStats['start_time'];

            // Advance time
            Carbon::setTestNow(Carbon::parse('2023-01-01 13:00:00'));

            $this->service->reset();
            $newStats = $this->service->getStatistics();

            expect($newStats['start_time'])->not->toBe($originalStartTime)
                ->and($newStats['start_time'])->toContain('2023-01-01T13:00:00');
        });
    });

    describe('getSuccessRate method', function () {
        it('returns 0.0 for no validations', function () {
            $rate = $this->service->getSuccessRate();
            expect($rate)->toBe(0.0);
        });

        it('returns 100.0 for all valid validations', function () {
            $this->service->recordValidation('https://valid1.com', true);
            $this->service->recordValidation('https://valid2.com', true);

            $rate = $this->service->getSuccessRate();
            expect($rate)->toBe(100.0);
        });

        it('returns 0.0 for all invalid validations', function () {
            $this->service->recordValidation('https://broken1.com', false);
            $this->service->recordValidation('https://broken2.com', false);

            $rate = $this->service->getSuccessRate();
            expect($rate)->toBe(0.0);
        });

        it('calculates partial success rate correctly', function () {
            $this->service->recordValidation('https://valid.com', true);
            $this->service->recordValidation('https://broken1.com', false);
            $this->service->recordValidation('https://broken2.com', false);

            $rate = $this->service->getSuccessRate();
            expect($rate)->toBe(33.33);
        });

        it('rounds to 2 decimal places', function () {
            // Create a scenario that would result in 66.666...%
            $this->service->recordValidation('https://valid1.com', true);
            $this->service->recordValidation('https://valid2.com', true);
            $this->service->recordValidation('https://broken.com', false);

            $rate = $this->service->getSuccessRate();
            expect($rate)->toBe(66.67);
        });
    });

    describe('getStatusStatistics method', function () {
        it('returns ValidationStatistics instance', function () {
            $stats = $this->service->getStatusStatistics();
            expect($stats)->toBeInstanceOf(ValidationStatistics::class);
        });

        it('creates ValidationStatistics with correct counts', function () {
            $this->service->recordValidation($this->testUrls['valid'][0], true);
            $this->service->recordValidation($this->testUrls['valid'][1], true);
            $this->service->recordValidation($this->testUrls['broken'][0], false);

            $stats = $this->service->getStatusStatistics();

            expect($stats->getTotal())->toBe(3)
                ->and($stats->getValid())->toBe(2)
                ->and($stats->getBroken())->toBe(1);
        });

        it('sets timeouts and security risks to 0', function () {
            $this->service->recordValidation($this->testUrls['valid'][0], true);

            $stats = $this->service->getStatusStatistics();

            expect($stats->getTimeouts())->toBe(0)
                ->and($stats->getSecurityRisks())->toBe(0)
                ->and($stats->getRedirects())->toBe(0);
        });
    });

    describe('integration scenarios', function () {
        it('handles mixed validation and file processing', function () {
            // Process files
            $this->service->recordFileProcessed('/file1.md', 3);
            $this->service->recordFileProcessed('/file2.md', 2);

            // Record validations
            $this->service->recordValidation('https://valid1.com', true);
            $this->service->recordValidation('https://valid2.com', true);
            $this->service->recordValidation('https://broken.com', false, 'Not found');

            $stats = $this->service->getStatistics();

            expect($stats['files_processed'])->toBe(2)
                ->and($stats['total_links'])->toBe(5)
                ->and($stats['total'])->toBe(3)
                ->and($stats['valid'])->toBe(2)
                ->and($stats['invalid'])->toBe(1)
                ->and($stats['success_rate'])->toBe(66.67)
                ->and($stats['broken_links_count'])->toBe(1)
                ->and($stats['processed_files_count'])->toBe(2);
        });

        it('maintains data integrity across operations', function () {
            // Initial state
            expect($this->service->getStatistics()['total'])->toBe(0);

            // Add data
            $this->service->recordValidation('https://test.com', true);
            expect($this->service->getStatistics()['total'])->toBe(1);

            // Add more data
            $this->service->recordFileProcessed('/file.md', 1);
            expect($this->service->getStatistics()['files_processed'])->toBe(1);

            // Reset
            $this->service->reset();
            expect($this->service->getStatistics()['total'])->toBe(0);
            expect($this->service->getStatistics()['files_processed'])->toBe(0);
        });
    });

    describe('edge cases and error handling', function () {
        it('handles very large numbers of validations', function () {
            for ($i = 0; $i < 1000; $i++) {
                $this->service->recordValidation("https://test{$i}.com", $i % 2 === 0);
            }

            $stats = $this->service->getStatistics();
            expect($stats['total'])->toBe(1000)
                ->and($stats['valid'])->toBe(500)
                ->and($stats['invalid'])->toBe(500)
                ->and($stats['success_rate'])->toBe(50.0);
        });

        it('handles very large link counts in files', function () {
            $this->service->recordFileProcessed('/huge-file.md', 999999);

            $stats = $this->service->getStatistics();
            expect($stats['total_links'])->toBe(999999);
        });

        it('handles special characters in error messages', function () {
            $specialError = 'Error with special chars: <>&"\'';
            $this->service->recordValidation('https://test.com', false, $specialError);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'][0]['error'])->toBe($specialError);
        });

        it('handles unicode characters in error messages', function () {
            $unicodeError = 'Ошибка с unicode: 🚫 тест';
            $this->service->recordValidation('https://test.com', false, $unicodeError);

            $brokenLinks = $this->service->getBrokenLinks();
            expect($brokenLinks['broken_links'][0]['error'])->toBe($unicodeError);
        });
    });

    describe('timestamp handling', function () {
        it('uses consistent timestamp format', function () {
            $this->service->recordValidation('https://broken.com', false, 'Error');
            $this->service->recordFileProcessed('/file.md', 1);

            $brokenLinks = $this->service->getBrokenLinks();
            $processedFiles = $this->service->getProcessedFiles();

            $brokenTimestamp = $brokenLinks['broken_links'][0]['timestamp'];
            $fileTimestamp = $processedFiles['processed_files'][0]['timestamp'];

            expect($brokenTimestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/')
                ->and($fileTimestamp)->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });

        it('handles timestamp reset gracefully', function () {
            // Reset Carbon mock to use real timestamps
            Carbon::setTestNow(null);

            $this->service->recordFileProcessed('/file.md', 1);

            $processedFiles = $this->service->getProcessedFiles();
            // When Carbon mock is reset, it should still generate a valid timestamp
            expect($processedFiles['processed_files'][0]['timestamp'])->toBeString()
                ->and($processedFiles['processed_files'][0]['timestamp'])->toMatch('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/');
        });
    });
});
