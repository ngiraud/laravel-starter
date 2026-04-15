<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;

class FilamentInstaller
{
    public function install(Runner $runner): void
    {
        $runner->run('php artisan filament:install --panels --force --no-interaction');
    }
}
