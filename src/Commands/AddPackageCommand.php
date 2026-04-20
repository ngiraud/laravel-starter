<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Installers\Installer;
use BerryValley\LaravelStarter\Support\Git;
use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Laravel\Prompts\Support\Logger;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\info;
use function Laravel\Prompts\select;
use function Laravel\Prompts\task;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'starter:add')]
class AddPackageCommand extends Command
{
    public $signature = 'starter:add {package? : The package key from config/starter.php}';

    public $description = 'Install a package and commit the changes';

    public function handle(Git $git): int
    {
        /** @var Composer $composer */
        $composer = app('composer');
        /** @var array<string, array{label: string, require: string, dev: bool, default: bool, version?: string, installer?: class-string<Installer>}> $packages */
        $packages = config()->array('starter.packages', []);

        $key = $this->resolvePackageKey($packages);

        if (! isset($packages[$key])) {
            $this->components->error("Unknown package [{$key}]. Available: ".implode(', ', array_keys($packages)));

            return self::FAILURE;
        }

        $package = $packages[$key];

        if ($composer->hasPackage($package['require'])) {
            warning("{$package['label']} is already installed.");

            return self::SUCCESS;
        }

        $runner = Runner::detect();

        task("Installing {$package['label']}", function (Logger $logger) use ($package, $runner, $git): bool {
            $dev = $package['dev'] ? ' --dev' : '';
            $version = isset($package['version']) ? " \"{$package['version']}\"" : '';
            $runner->run("composer require {$package['require']}{$version}{$dev}", $logger);

            if (isset($package['installer'])) {
                /** @var Installer $installer */
                $installer = app($package['installer']);
                $installer->install($runner);
            }

            $git->commit("Install {$package['label']}");

            return true;
        });
        info("✓ {$package['label']} installed");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array{label: string, require: string, dev: bool, default: bool, version?: string, installer?: class-string<Installer>}>  $packages
     */
    private function resolvePackageKey(array $packages): string
    {
        $key = $this->argument('package');

        if (is_string($key)) {
            return $key;
        }

        return (string) select(
            label: 'Which package would you like to install?',
            options: collect($packages)->mapWithKeys(fn (array $p, string $k): array => [$k => $p['label']])->all(),
        );
    }
}
