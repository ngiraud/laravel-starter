<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class TelescopeInstaller extends Installer
{
    public function __construct(private readonly Filesystem $files) {}

    public function install(Runner $runner): void
    {
        foreach ($this->files->allFiles(database_path('migrations')) as $file) {
            if (Str::contains($file->getFilename(), 'telescope')) {
                $this->files->delete($file->getPathname());
            }
        }

        $runner->run('php artisan telescope:install');
    }
}
