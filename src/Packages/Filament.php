<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\ProcessRunner;

final class Filament extends ComposerPackage
{
    public string $name = 'Filament';

    public string $require = 'filament/filament';

    public string $version = '^4.0';

    public bool $isDevRequirement = false;

    public bool $installByDefault = false;

    /**
     * Install Filament admin panel package
     * 
     * Runs filament:install command with panels and default settings.
     */
    public function install(): void
    {
        ProcessRunner::sail()->run('php artisan filament:install --panels --force --no-interaction');
    }
}
