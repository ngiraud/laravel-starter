<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Filesystem\Filesystem;

class RectorInstaller extends Installer
{
    public function __construct(private readonly Filesystem $files) {}

    public function install(Runner $runner): void
    {
        $this->files->copy($this->stubsPath('rector.php.stub'), base_path('rector.php'));
    }
}
