<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\TerminalCommand;
use Illuminate\Support\Str;

final class LaravelTelescope extends ComposerPackage
{
    public string $name = 'Laravel Telescope';

    public string $require = 'laravel/telescope';

    public bool $isDevRequirement = false;

    public bool $installByDefault = true;

    public function install(): void
    {
        foreach ($this->files->allFiles(database_path('migrations')) as $file) {
            if (Str::contains($file->getFilename(), 'telescope')) {
                $this->files->delete($file);
            }
        }

        TerminalCommand::sail()->run('php artisan telescope:install');
    }
}
