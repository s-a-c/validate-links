<?php

// app/Services/PluginManager.php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\ExtensionInterface;
use Illuminate\Support\Collection;

final readonly class PluginManager
{
    /** @var Collection<string, mixed> */
    private Collection $plugins;

    public function __construct()
    {
        $this->plugins = new Collection();
    }

    public function register(ExtensionInterface $plugin): void
    {
        $this->plugins->put($plugin->getName(), $plugin);
        $plugin->register();
    }

    public function boot(): void
    {
        $this->plugins->each(function (ExtensionInterface $plugin): void {
            $plugin->boot();
        });
    }

    public function getPlugin(string $name): mixed
    {
        return $this->plugins->get($name);
    }
}
