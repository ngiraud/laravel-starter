<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter;

use BerryValley\LaravelStarter\Commands\AddPackageCommand;
use BerryValley\LaravelStarter\Commands\FinalizeCommand;
use BerryValley\LaravelStarter\Commands\InitCommand;
use BerryValley\LaravelStarter\Commands\InstallCommand;
use BerryValley\LaravelStarter\Commands\MakeActionCommand;
use BerryValley\LaravelStarter\Commands\PublishCommand;
use BerryValley\LaravelStarter\Commands\RemovePackageCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelStarterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-starter')
            ->hasConfigFile()
            ->hasCommands([
                InstallCommand::class,
                InitCommand::class,
                AddPackageCommand::class,
                RemovePackageCommand::class,
                PublishCommand::class,
                FinalizeCommand::class,
                MakeActionCommand::class,
            ]);
    }
}
