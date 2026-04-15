<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Filesystem\Filesystem;

class LarastanInstaller
{
    private const string STUBS_PATH = __DIR__.'/../../stubs';

    public function __construct(private readonly Filesystem $files) {}

    public function install(Runner $runner): void
    {
        $this->files->copy(self::STUBS_PATH.'/phpstan.neon.stub', base_path('phpstan.neon'));
    }
}
