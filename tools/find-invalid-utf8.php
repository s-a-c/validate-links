<?php

declare(strict_types=1);

/**
 * Script to find invalid UTF-8 content in PHP files
 */
function findInvalidUtf8InDirectory(string $dir): void
{
    if (! is_dir($dir)) {
        echo "Directory $dir does not exist.\n";

        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    $foundIssues = false;

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        $filepath = $file->getPathname();
        $content = file_get_contents($filepath);

        if ($content === false) {
            echo "Could not read file: $filepath\n";

            continue;
        }

        // Check if the entire file content is valid UTF-8
        if (! mb_check_encoding($content, 'UTF-8')) {
            $foundIssues = true;
            echo "\n❌ Invalid UTF-8 found in: $filepath\n";

            // Check each line to find the problematic ones
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                if (! mb_check_encoding($line, 'UTF-8')) {
                    $lineNumber = $lineNum + 1;
                    echo "  📍 Line $lineNumber: ";

                    // Show visible characters and hex for non-printable
                    $displayLine = '';
                    for ($i = 0; $i < mb_strlen($line); $i++) {
                        $char = $line[$i];
                        if (ord($char) < 32 || ord($char) > 126) {
                            $displayLine .= sprintf('\\x%02X', ord($char));
                        } else {
                            $displayLine .= $char;
                        }
                    }

                    echo "$displayLine\n";

                    // Also show raw hex
                    echo '    Hex: '.bin2hex($line)."\n";
                }
            }
        }

        // Also check for common problematic patterns
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $content)) {
            if (! $foundIssues) {
                $foundIssues = true;
                echo "\n⚠️  Control characters found in: $filepath\n";
            }
        }
    }

    if (! $foundIssues) {
        echo "✅ No invalid UTF-8 content found in $dir\n";
    }
}

function findInvalidUtf8InSpecificFiles(array $files): void
{
    foreach ($files as $file) {
        if (! file_exists($file)) {
            echo "File $file does not exist.\n";

            continue;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            echo "Could not read file: $file\n";

            continue;
        }

        if (! mb_check_encoding($content, 'UTF-8')) {
            echo "\n❌ Invalid UTF-8 found in: $file\n";

            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                if (! mb_check_encoding($line, 'UTF-8')) {
                    $lineNumber = $lineNum + 1;
                    echo "  📍 Line $lineNumber: ".bin2hex($line)."\n";
                    echo '    Text: '.mb_convert_encoding($line, 'UTF-8', 'UTF-8')."\n";
                }
            }
        }
    }
}

echo "🔍 Searching for invalid UTF-8 content...\n\n";

// Check main application directories
findInvalidUtf8InDirectory('./app');
findInvalidUtf8InDirectory('./config');
findInvalidUtf8InDirectory('./tests');
findInvalidUtf8InDirectory('./bootstrap');

// Check for common files that might have issues
$commonFiles = [
    './composer.json',
    './package.json',
    './.env',
    './.env.example',
    './README.md',
];

foreach ($commonFiles as $file) {
    if (file_exists($file)) {
        findInvalidUtf8InSpecificFiles([$file]);
    }
}

echo "\n🔍 Search complete!\n";
