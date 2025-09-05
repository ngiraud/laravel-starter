<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter;

use BerryValley\LaravelStarter\Commands\LaravelStarterCommand;
use BerryValley\LaravelStarter\Commands\MakeActionCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelStarterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-starter')
            ->hasConfigFile()
            ->hasCommand(LaravelStarterCommand::class)
            ->hasCommand(MakeActionCommand::class);
    }
}
