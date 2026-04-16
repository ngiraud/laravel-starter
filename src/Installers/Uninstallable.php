<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;

interface Uninstallable
{
    public function uninstall(Runner $runner): void;
}
