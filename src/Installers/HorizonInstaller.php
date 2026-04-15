<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;

class HorizonInstaller
{
    public function install(Runner $runner): void
    {
        $runner->run('php artisan horizon:install');

        $path = base_path('routes/console.php');
        $console = file_get_contents($path);
        $console .= "\n\nSchedule::command('horizon:snapshot')->everyFiveMinutes();";
        file_put_contents($path, $console);
    }
}
