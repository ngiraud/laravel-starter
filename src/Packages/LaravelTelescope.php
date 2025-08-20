<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\ProcessRunner;
use Illuminate\Support\Str;

final class LaravelTelescope extends ComposerPackage
{
    public string $name = 'Laravel Telescope';

    public string $require = 'laravel/telescope';

    public bool $isDevRequirement = false;

    public bool $installByDefault = true;

    /**
     * Install Laravel Telescope package
     *
     * Cleans up existing telescope migrations and runs telescope:install command.
     */
    public function install(): void
    {
        // Clean up existing telescope migrations
        foreach ($this->files->allFiles(database_path('migrations')) as $file) {
            if (Str::contains($file->getFilename(), 'telescope')) {
                $this->files->delete($file->getPathname());
            }
        }

        // Install telescope
        ProcessRunner::sail()->run('php artisan telescope:install');
    }
}
