<?php

declare(strict_types=1);

namespace Tests\Support\Helpers;

interface Clock
{
    public function nowIso(): string;
}

final class SystemClock implements Clock
{
    public function nowIso(): string
    {
        return date('c');
    }
}

final class FrozenClock implements Clock
{
    public function __construct(private readonly string $iso)
    {
    }

    public function nowIso(): string
    {
        return $this->iso;
    }
}