<?php

namespace BerryValley\LaravelStarter;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use BerryValley\LaravelStarter\Commands\LaravelStarterCommand;

class LaravelStarterServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-starter')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_starter_table')
            ->hasCommand(LaravelStarterCommand::class);
    }
}
