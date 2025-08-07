#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * scripts/policy-check.php.
 *
 * Dependency-free policy validator for project guidelines.
 * PHP 8.1+
 *
 * Clickable references use [path](path:line) format as requested.
 *
 * Responsibilities:
 * - Load .ai/guidelines.md and all files under .ai/guidelines/**
 * - Build composite checksum (sha256) in deterministic order
 * - Track last modified times
 * - Validate AI-authored file headers:
 *     “Compliant with [.ai/guidelines.md](.ai/guidelines.md) v<checksum>”
 * - Detect forbidden content (e.g., api_key, token, password, bearer)
 * - Optional path policy (allow/deny) hooks
 * - Optional commit message validation when .git is present (skip gracefully if absent)
 * - Drift detection: require re-acknowledgement when checksum changes
 * - Exit non-zero on violations with actionable, clickable output
 *
 * Usage:
 *   php scripts/policy-check.php [--strict] [--paths=<glob1,glob2,...>]
 *
 * Notes:
 * - --strict enables drift enforcement and treats warnings as errors.
 * - If --paths is not provided, validator scans tracked repository files heuristically.
 * - This script is safe to run in CI and pre-commit.
 */

// ---------------------------------------------------------
// Utilities
// ---------------------------------------------------------

final class Cli
{
    public static function e(string $msg): void
    {
        fwrite(STDERR, $msg . PHP_EOL);
    }

    public static function o(string $msg): void
    {
        fwrite(STDOUT, $msg . PHP_EOL);
    }

    public static function fail(string $msg, int $code = 1): never
    {
        self::e($msg);
        exit($code);
    }
}

final class Fs
{
    public static function read(string $path): string
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            throw new RuntimeException("Unable to read file: {$path}");
        }

        return $data;
    }

    public static function tryRead(string $path): ?string
    {
        $data = @file_get_contents($path);

        return $data === false ? null : $data;
    }

    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    public static function mtime(string $path): int
    {
        $t = @filemtime($path);
        if ($t === false) {
            return 0;
        }

        return (int) $t;
    }

    /**
     * Recursively list files from a base directory, filtered by callback.
     * Returns normalized relative paths from project root.
     */
    public static function rlist(string $base, ?callable $filter = null): array
    {
        $result = [];
        if (! is_dir($base)) {
            return $result;
        }
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS),
        );
        $root = getcwd() ?: '.';
        foreach ($it as $file) {
            if (! $file->isFile()) {
                continue;
            }
            $path = self::relpath($file->getPathname(), $root);
            if ($filter && ! $filter($path)) {
                continue;
            }
            $result[] = $path;
        }

        return $result;
    }

    public static function relpath(string $path, string $root): string
    {
        $root = mb_rtrim(str_replace('\\', '/', $root), '/') . '/';
        $path = str_replace('\\', '/', $path);
        if (str_starts_with($path, $root)) {
            return mb_substr($path, mb_strlen($root));
        }

        return $path;
    }
}

final class JsonStore
{
    /** Simple JSON store to track previous checksum for drift detection. */
    public static function load(string $path): array
    {
        if (! Fs::exists($path)) {
            return [];
        }
        $raw = Fs::tryRead($path);
        if ($raw === null) {
            return [];
        }
        $data = json_decode($raw, true);

        return is_array($data) ? $data : [];
    }

    public static function save(string $path, array $data): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}

// ---------------------------------------------------------
// Guideline Loader (inline minimal helper for the CLI)
// The same logic is provided as a reusable class in tests/Support/Helpers/GuidelineLoader.php.
// ---------------------------------------------------------

final class GuidelineLoaderInline
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

    public function load(): void
    {
        $files = [];
        if (Fs::exists($this->masterPath)) {
            $files[] = $this->masterPath;
        }
        foreach (Fs::rlist($this->modulesDir) as $f) {
            $files[] = $f;
        }
        sort($files, SORT_STRING);
        $this->paths = $files;

        $this->lastModified['master'] = Fs::exists($this->masterPath) ? Fs::mtime($this->masterPath) : 0;

        $max = 0;
        foreach ($files as $f) {
            $t = Fs::mtime($f);
            if ($t > $max) {
                $max = $t;
            }
        }
        $this->lastModified['modules'] = $max;

        // Compute checksum over deterministic concatenation
        $concat = '';
        foreach ($files as $f) {
            $concat .= "<FILE>{$f}\n" . Fs::tryRead($f) . "\n";
        }
        $this->checksum = hash('sha256', $concat);

        // Extract rules from markdown headings and fenced blocks
        // Minimal parser: a rule begins at a heading (## or ###) or a fenced block with id: R-... inside
        foreach ($files as $f) {
            $content = Fs::tryRead($f) ?? '';
            $lines = preg_split('/\R/u', $content) ?: [];
            $n = count($lines);

            $i = 0;
            while ($i < $n) {
                $line = $lines[$i];
                if (preg_match('/^(#{2,6})\s+(.*)$/', $line, $m)) {
                    $title = mb_trim($m[2]);
                    $start = $i + 1;
                    $end = $this->scanUntilNextHeading($lines, $i + 1);
                    $block = implode("\n", array_slice($lines, $start, $end - $start));
                    $id = $this->extractId($block) ?? $this->makeId($title, $f, $start);
                    $scope = $this->extractKV($block, 'scope') ?? 'general';
                    $priority = (int) ($this->extractKV($block, 'priority') ?? 0);
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
            return mb_trim($m[1]);
        }

        return null;
    }

    private function extractKV(string $block, string $key): ?string
    {
        if (preg_match('/\b' . preg_quote($key, '/') . '\s*:\s*([^\r\n]+)/i', $block, $m)) {
            return mb_trim($m[1]);
        }

        return null;
    }

    private function makeId(string $title, string $file, int $start): string
    {
        $base = mb_strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title) ?? '');
        $base = mb_trim($base, '-');

        return ($base !== '' ? $base : 'rule') . '-' . md5($file . ':' . $start);
    }
}

// ---------------------------------------------------------
// Policy Checker
// ---------------------------------------------------------

final class PolicyCheck
{
    private const REQUIRED_HEADER_PREFIX = 'Compliant with [.ai/guidelines.md](.ai/guidelines.md) v';

    private bool $strict;

    /** @var list<string> */
    private array $scanPaths;

    /** @var list<string> */
    private array $violations = [];

    /** @var list<string> */
    private array $warnings = [];

    private GuidelineLoaderInline $loader;

    public function __construct(bool $strict, array $scanPaths)
    {
        $this->strict = $strict;
        $this->scanPaths = $scanPaths;
        $this->loader = new GuidelineLoaderInline();
    }

    public function run(): int
    {
        $this->loader->load();
        $checksum = $this->loader->getChecksum();
        $lm = $this->loader->getLastModified();

        Cli::o('Policy: loading guidelines...');
        Cli::o('- master: ' . $lm['master'] . ' (' . date(DATE_ATOM, (int) $lm['master']) . ')');
        Cli::o('- modules: ' . $lm['modules'] . ' (' . date(DATE_ATOM, (int) $lm['modules']) . ')');
        Cli::o('- checksum: ' . $checksum);
        Cli::o('- files: ' . implode(', ', $this->loader->getPaths()));

        // Drift detection (record prior checksum)
        $statePath = '.ai/.policy-state.json';
        $state = JsonStore::load($statePath);
        $prevChecksum = (string) ($state['lastChecksum'] ?? '');

        if ($this->strict && $prevChecksum !== '' && $prevChecksum !== $checksum) {
            $this->violations[] = 'Drift detected: guidelines checksum changed without re-acknowledgement. '
                . 'Previous=' . $prevChecksum . ' Current=' . $checksum
                . ' (rule [.ai/orchestration-policy.md](.ai/orchestration-policy.md:1))';
        }

        // Scan candidate files
        $files = $this->discoverFiles($this->scanPaths);

        // Apply checks
        $this->checkHeaders($files, $checksum);
        $this->checkForbiddenContent($files);
        $this->applyPathPolicy($files);
        $this->optionalCommitMessageValidation();

        // Summarize
        $exit = 0;
        if ($this->violations !== []) {
            $exit = 1;
            Cli::e('Violations:');
            foreach ($this->violations as $v) {
                Cli::e('- ' . $v);
            }
        }
        if ($this->warnings !== []) {
            if ($this->strict) {
                $exit = 1;
            }
            Cli::e('Warnings:');
            foreach ($this->warnings as $w) {
                Cli::e('- ' . $w);
            }
        }

        // Update state only if success (or non-strict)
        if ($exit === 0) {
            JsonStore::save($statePath, [
                'lastChecksum' => $checksum,
                'savedAt' => time(),
            ]);
        }

        return $exit;
    }

    /**
     * Discover files to scan. Heuristic: include project PHP, MD, YAML, JSON, TS files under common dirs,
     * excluding vendor/node_modules/storage/build artifacts.
     *
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function discoverFiles(array $paths): array
    {
        if ($paths !== []) {
            $out = [];
            foreach ($paths as $g) {
                foreach (glob($g, GLOB_BRACE) ?: [] as $p) {
                    if (is_file($p)) {
                        $out[] = $p;
                    }
                }
            }
            sort($out, SORT_STRING);

            return $out;
        }

        $dirs = ['.', 'app', 'config', 'docs', 'tests', 'scripts', '.github'];
        $allowExt = ['php', 'md', 'yml', 'yaml', 'json', 'ts', 'tsx'];
        $deny = '#^(vendor|node_modules|storage|public|dist|build|coverage|reports/rector/cache)/#';

        $files = [];
        foreach ($dirs as $d) {
            foreach (Fs::rlist($d) as $f) {
                if (preg_match($deny, $f)) {
                    continue;
                }
                $ext = mb_strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (in_array($ext, $allowExt, true)) {
                    $files[] = $f;
                }
            }
        }
        sort($files, SORT_STRING);

        return $files;
    }

    /**
     * Header requirement:
     * File must contain the exact header line (anywhere):
     *   Compliant with [.ai/guidelines.md](.ai/guidelines.md) v<checksum>
     *
     * Applies to AI-authored files. Heuristic: we enforce on files under tests/Support/Helpers/, scripts/, docs/,
     * .github/workflows/, and any markdown or PHP files modified.
     *
     * @param list<string> $files
     */
    private function checkHeaders(array $files, string $checksum): void
    {
        $required = self::REQUIRED_HEADER_PREFIX . $checksum;

        foreach ($files as $f) {
            $ext = mb_strtolower(pathinfo($f, PATHINFO_EXTENSION));
            $isCandidate =
                $ext === 'md' ||
                $ext === 'php' ||
                str_starts_with($f, '.github/workflows/') ||
                str_starts_with($f, 'scripts/');

            if (! $isCandidate) {
                continue;
            }
            $content = Fs::tryRead($f) ?? '';
            if ($content === '') {
                continue;
            }

            if (! str_contains($content, $required)) {
                // Allow root guidelines themselves not to require self-ack
                if ($f === '.ai/guidelines.md' || str_starts_with($f, '.ai/guidelines/')) {
                    continue;
                }
                $this->violations[] =
                    "Missing acknowledgement header in {$this->link($f, 1)} " .
                    "(expected “{$required}”) (rule [.ai/orchestration-policy.md](.ai/orchestration-policy.md:20))";
            }
        }
    }

    /**
     * Forbidden content detectors.
     * Case-insensitive scan for keywords common to secret material.
     *
     * @param list<string> $files
     */
    private function checkForbiddenContent(array $files): void
    {
        $patterns = [
            '/api[_-]?key\s*[:=]\s*[A-Za-z0-9_\-]{12,}/i',
            '/bearer\s+[A-Za-z0-9_\-\.=]{12,}/i',
            '/password\s*[:=]\s*[^,\s]{6,}/i',
            '/secret\s*[:=]\s*[^,\s]{6,}/i',
            '/token\s*[:=]\s*[A-Za-z0-9_\-\.=]{12,}/i',
        ];

        foreach ($files as $f) {
            $content = Fs::tryRead($f) ?? '';
            if ($content === '') {
                continue;
            }
            $lines = preg_split('/\R/u', $content) ?: [];
            foreach ($lines as $idx => $line) {
                foreach ($patterns as $rx) {
                    if (preg_match($rx, $line)) {
                        // Try map to a guideline rule (security). We cite a default file/line if unknown.
                        $ruleRef = $this->findRuleRef('security') ?? ['.ai/guidelines/security.md', 42];
                        $this->violations[] =
                            "Secret-like token found in {$this->link($f, $idx + 1)} " .
                            "(rule [{$ruleRef[0]}]({$ruleRef[0]}:{$ruleRef[1]}))";
                    }
                }
            }
        }
    }

    /**
     * Simple allow/deny path hooks:
     * - Deny committing files under tests/Support/Fixtures/ with *.secrets.*
     * - Allow everything else by default.
     *
     * @param list<string> $files
     */
    private function applyPathPolicy(array $files): void
    {
        foreach ($files as $f) {
            if (str_starts_with($f, 'tests/Support/Fixtures/') && preg_match('/\.secrets?\./i', $f)) {
                $this->violations[] =
                    "Path policy violation: disallowed file {$this->link($f, 1)} " .
                    '(rule [.ai/guidelines/path-policy.md](.ai/guidelines/path-policy.md:10))';
            }
        }
    }

    /**
     * Optional commit message validation.
     * If .git exists and HEAD is available, check last commit message contains the checksum when strict.
     * Skips gracefully in CI with shallow clones.
     */
    private function optionalCommitMessageValidation(): void
    {
        if (! is_dir('.git')) {
            return;
        }
        $head = @shell_exec('git log -1 --pretty=%B 2>/dev/null');
        if ($head === null) {
            return;
        }
        $head = mb_trim($head);
        if ($head === '') {
            return;
        }
        // Tolerant check: if strict, require presence of "guidelines v<hex>" mention
        if ($this->strict && ! preg_match('/guidelines\s+v[0-9a-f]{40,64}/i', $head)) {
            $this->warnings[] = 'Commit message does not mention current guidelines checksum '
                . '(rule [.ai/orchestration-policy.md](.ai/orchestration-policy.md:36))';
        }
    }

    private function link(string $file, int $line): string
    {
        return "[{$file}]({$file}:{$line})";
    }

    /**
     * Attempts to find a rule reference by scope or id, for better citations.
     * Returns [file, line].
     *
     * @return array{0:string,1:int}|null
     */
    private function findRuleRef(string $key): ?array
    {
        $rules = $this->loader->getRules();
        foreach ($rules as $r) {
            if (str_contains(mb_strtolower($r['scope']), mb_strtolower($key)) || mb_strtolower($r['id']) === mb_strtolower($key)) {
                return [$r['file'], max(1, $r['start'])];
            }
        }

        return null;
    }
}

// ---------------------------------------------------------
// Entry
// ---------------------------------------------------------

/**
 * Parse args.
 */
$strict = in_array('--strict', $argv, true);
$pathsArg = array_values(array_filter($argv, fn ($a) => str_starts_with((string) $a, '--paths=')));
$paths = [];
if ($pathsArg !== []) {
    $csv = mb_substr((string) $pathsArg[0], mb_strlen('--paths='));
    foreach (explode(',', $csv) as $g) {
        $g = mb_trim($g);
        if ($g !== '') {
            $paths[] = $g;
        }
    }
}

try {
    $exit = (new PolicyCheck($strict, $paths))->run();
    exit($exit);
} catch (Throwable $e) {
    Cli::fail('Unhandled error: ' . $e->getMessage());
}
