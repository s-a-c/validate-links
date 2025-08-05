<?php
declare(strict_types=1);

/**
 * Compliant with [.ai/guidelines.md](.ai/guidelines.md) v<checksum>
 *
 * tests/Support/Helpers/GuidelineLoader.php
 *
 * Minimal, dependency-free loader compatible with PHP 8.1+.
 *
 * Responsibilities:
 * - Load master .ai/guidelines.md then overlay .ai/guidelines/**/*
 * - Expose:
 *   * getRules(): array<string, array{ id:string, title:string, scope:string, priority:int, file:string, start:int, end:int, text:string }>
 *   * getChecksum(): string
 *   * getLastModified(): array{ master:int, modules:int }
 *   * findById(string $id): ?array
 *
 * Clickable references are formatted as [file](file:line).
 */

namespace Tests\Support\Helpers;

final class GuidelineLoader
{
    private string $masterPath = '.ai/guidelines.md';
    private string $modulesDir = '.ai/guidelines';

    /** @var list<string> */
    private array $paths = [];

    /** @var array<string, array{ id:string, title:string, scope:string, priority:int, file:string, start:int, end:int, text:string }> */
    private array $rules = [];

    private string $checksum = '';
    /** @var array{ master:int, modules:int } */
    private array $lastModified = ['master' => 0, 'modules' => 0];

    public function __construct(?string $masterPath = null, ?string $modulesDir = null)
    {
        if ($masterPath) {
            $this->masterPath = $masterPath;
        }
        if ($modulesDir) {
            $this->modulesDir = $modulesDir;
        }
    }

    public function load(): void
    {
        $files = [];
        if (file_exists($this->masterPath)) {
            $files[] = $this->masterPath;
        }

        foreach ($this->rlist($this->modulesDir) as $f) {
            $files[] = $f;
        }

        sort($files, SORT_STRING);
        $this->paths = $files;

        $this->lastModified['master'] = file_exists($this->masterPath) ? $this->mtime($this->masterPath) : 0;

        $max = 0;
        foreach ($files as $f) {
            $t = $this->mtime($f);
            if ($t > $max) {
                $max = $t;
            }
        }
        $this->lastModified['modules'] = $max;

        $concat = '';
        foreach ($files as $f) {
            $concat .= "<FILE>{$f}\n" . ($this->tryRead($f) ?? '') . "\n";
        }
        $this->checksum = hash('sha256', $concat);

        // Parse rules
        foreach ($files as $f) {
            $content = $this->tryRead($f) ?? '';
            $lines = preg_split('/\R/u', $content) ?: [];
            $n = count($lines);

            $i = 0;
            while ($i < $n) {
                $line = $lines[$i];
                if (preg_match('/^(#{2,6})\s+(.*)$/', $line, $m)) {
                    $title = trim($m[2]);
                    $start = $i + 1;
                    $end = $this->scanUntilNextHeading($lines, $i + 1);
                    $block = implode("\n", array_slice($lines, $start, $end - $start));
                    $id = $this->extractId($block) ?? $this->makeId($title, $f, $start);
                    $scope = $this->extractKV($block, 'scope') ?? 'general';
                    $priority = (int)($this->extractKV($block, 'priority') ?? 0);

                    $this->rules[$id] = [
                        'id' => $id,
                        'title' => $title,
                        'scope' => $scope,
                        'priority' => $priority,
                        'file' => $f,
                        'start' => $start,
                        'end' => $end,
                        'text' => $block,
                    ];
                    $i = $end;
                    continue;
                }
                $i++;
            }
        }
    }

    /** @return array<string, array{ id:string, title:string, scope:string, priority:int, file:string, start:int, end:int, text:string }> */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    /** @return array{ master:int, modules:int } */
    public function getLastModified(): array
    {
        return $this->lastModified;
    }

    /** @return list<string> */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /** @return array{id:string, title:string, scope:string, priority:int, file:string, start:int, end:int, text:string}|null */
    public function findById(string $id): ?array
    {
        return $this->rules[$id] ?? null;
    }

    // --------------------------
    // Internals
    // --------------------------

    /** @return list<string> */
    private function rlist(string $base): array
    {
        $result = [];
        if (!is_dir($base)) {
            return $result;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)
        );
        $root = getcwd() ?: '.';
        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $this->relpath($file->getPathname(), $root);
            $result[] = $path;
        }
        return $result;
    }

    private function relpath(string $path, string $root): string
    {
        $root = rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, $root)) {
            return substr($path, strlen($root));
        }
        return $path;
    }

    private function mtime(string $path): int
    {
        $t = @filemtime($path);
        return $t === false ? 0 : (int)$t;
    }

    private function tryRead(string $path): ?string
    {
        $data = @file_get_contents($path);
        return $data === false ? null : $data;
    }

    private function scanUntilNextHeading(array $lines, int $from): int
    {
        $n = count($lines);
        for ($i = $from; $i < $n; $i++) {
            if (preg_match('/^#{2,6}\s+/', $lines[$i])) {
                return $i;
            }
        }
        return $n;
    }

    private function extractId(string $block): ?string
    {
        if (preg_match('/\bid\s*:\s*([A-Za-z0-9\-\._]+)/', $block, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function extractKV(string $block, string $key): ?string
    {
        if (preg_match('/\b' . preg_quote($key, '/') . '\s*:\s*([^\r\n]+)/i', $block, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    private function makeId(string $title, string $file, int $start): string
    {
        $base = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? '');
        $base = trim($base, '-');
        return ($base !== '' ? $base : 'rule') . '-' . md5($file . ':' . $start);
    }
}