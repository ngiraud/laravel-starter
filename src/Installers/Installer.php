<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;

abstract class Installer
{
    abstract public function install(Runner $runner): void;

    protected function stubsPath(?string $path = null): string
    {
        $base = __DIR__.'/../../stubs';

        return $path !== null ? $base.'/'.$path : $base;
    }
}
