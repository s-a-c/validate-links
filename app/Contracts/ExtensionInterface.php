<?php

// app/Contracts/ExtensionInterface.php

declare(strict_types=1);

namespace App\Contracts;

interface ExtensionInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function register(): void;

    public function boot(): void;
}
