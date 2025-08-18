<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\TerminalCommand;

final class LaravelHorizon extends ComposerPackage
{
    public string $name = 'Laravel Horizon';

    public string $require = 'laravel/horizon';

    public bool $isDevRequirement = false;

    public bool $installByDefault = true;

    public function install(): void
    {
        TerminalCommand::sail()->run('php artisan horizon:install');

        $this->modifyConsoleFile();
    }

    private function modifyConsoleFile(): void
    {
        $path = base_path('routes/console.php');

        $console = file_get_contents($path);

        $console .= "\n\nSchedule::command('horizon:snapshot')->everyFiveMinutes();";

        file_put_contents($path, $console);
    }
}
