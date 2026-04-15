<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Support\Git;
use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;

#[AsCommand(name: 'starter:add')]
class AddPackageCommand extends Command
{
    public $signature = 'starter:add {package? : The package key from config/starter.php}';

    public $description = 'Install a package and commit the changes';

    public function handle(Git $git): int
    {
        /** @var Composer $composer */
        $composer = app('composer');
        /** @var array<string, array{label: string, require: string, dev: bool, default: bool, version?: string, installer?: class-string}> $packages */
        $packages = config()->array('starter.packages', []);

        $key = $this->argument('package') ?? select(
            label: 'Which package would you like to install?',
            options: collect($packages)->mapWithKeys(fn (array $p, string $k): array => [$k => $p['label']])->all(),
        );

        if (! isset($packages[$key])) {
            $this->components->error("Unknown package [{$key}]. Available: ".implode(', ', array_keys($packages)));

            return self::FAILURE;
        }

        $package = $packages[$key];

        if ($composer->hasPackage($package['require'])) {
            $this->components->warn("{$package['label']} is already installed.");

            return self::SUCCESS;
        }

        $runner = Runner::detect();

        $this->components->info("Installing {$package['label']}");
        $this->requirePackage($package, $runner);

        if (isset($package['installer'])) {
            app($package['installer'])->install($runner);
        }

        $git->commit("Install {$package['label']}");
        $this->components->success("{$package['label']} installed.");

        return self::SUCCESS;
    }

    /**
     * @param  array{label: string, require: string, dev: bool, default: bool, version?: string, installer?: class-string}  $package
     */
    private function requirePackage(array $package, Runner $runner): void
    {
        $dev = ($package['dev'] ?? false) ? ' --dev' : '';
        $version = isset($package['version']) ? " \"{$package['version']}\"" : '';

        $runner->run("composer require {$package['require']}{$version}{$dev}");
    }
}
