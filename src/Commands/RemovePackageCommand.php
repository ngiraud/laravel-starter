<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Installers\Installer;
use BerryValley\LaravelStarter\Installers\Uninstallable;
use BerryValley\LaravelStarter\Support\Git;
use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'starter:remove')]
class RemovePackageCommand extends Command
{
    public $signature = 'starter:remove {package : The package key from config/starter.php}';

    public $description = 'Remove an installed package and commit the changes';

    public function handle(Git $git): int
    {
        /** @var Composer $composer */
        $composer = app('composer');
        /** @var array<string, array{label: string, require: string, dev: bool, default: bool, version?: string, installer?: class-string<Installer>, modifies_console?: bool}> $packages */
        $packages = config()->array('starter.packages', []);

        /** @var string $key */
        $key = $this->argument('package');

        if (! isset($packages[$key])) {
            $this->components->error("Unknown package [{$key}]. Available: ".implode(', ', array_keys($packages)));

            return self::FAILURE;
        }

        $package = $packages[$key];

        if (! $composer->hasPackage($package['require'])) {
            $this->components->warn("{$package['label']} is not installed.");

            return self::SUCCESS;
        }

        $runner = Runner::detect();

        $this->components->info("Removing {$package['label']}");

        if (isset($package['installer'])) {
            /** @var Installer $installer */
            $installer = app($package['installer']);

            if ($installer instanceof Uninstallable) {
                $installer->uninstall($runner);
            } elseif ($package['modifies_console'] ?? false) {
                $this->components->warn('This package added scheduled tasks to routes/console.php. Please remove them manually.');
            }
        } elseif ($package['modifies_console'] ?? false) {
            $this->components->warn('This package added scheduled tasks to routes/console.php. Please remove them manually.');
        }

        $dev = $package['dev'] ? ' --dev' : '';
        $runner->run("composer remove {$package['require']}{$dev}");

        $git->commit("Remove {$package['label']}");
        $this->components->success("{$package['label']} removed.");

        return self::SUCCESS;
    }
}
