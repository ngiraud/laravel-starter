<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Filesystem\Filesystem;

class LarastanInstaller extends Installer
{
    public function __construct(private readonly Filesystem $files) {}

    public function install(Runner $runner): void
    {
        $this->files->copy($this->stubsPath('phpstan.neon.stub'), base_path('phpstan.neon'));
    }
}
