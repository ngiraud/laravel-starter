<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\ProcessRunner;

final class LaravelHorizon extends ComposerPackage
{
    public string $name = 'Laravel Horizon';

    public string $require = 'laravel/horizon';

    public bool $isDevRequirement = false;

    public bool $installByDefault = true;

    /**
     * Install Laravel Horizon package
     * 
     * Runs horizon:install command and updates console.php with Horizon schedule.
     */
    public function install(): void
    {
        ProcessRunner::sail()->run('php artisan horizon:install');
        $this->modifyConsoleFile();
    }

    /**
     * Add Horizon snapshot command to console schedule
     */
    private function modifyConsoleFile(): void
    {
        $path = base_path('routes/console.php');

        $console = file_get_contents($path);

        $console .= "\n\nSchedule::command('horizon:snapshot')->everyFiveMinutes();";

        file_put_contents($path, $console);
    }
}
