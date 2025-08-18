<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter;

use BerryValley\LaravelStarter\Commands\LaravelStarterCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class LaravelStarterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-starter')
            ->hasConfigFile()
            ->hasCommand(LaravelStarterCommand::class);
    }
}
