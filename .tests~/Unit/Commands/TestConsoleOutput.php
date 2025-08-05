<?php

declare(strict_types=1);

namespace Tests\Unit\Commands;

/**
 * Test-compatible output class that has the newLine() method.
 */
class TestConsoleOutput extends \Symfony\Component\Console\Output\BufferedOutput
{
    public function newLine(int $count = 1): void
    {
        $this->write(str_repeat(PHP_EOL, $count));
    }
}