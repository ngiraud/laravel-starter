<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Support\Git;
use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Laravel\Prompts\Support\Logger;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\info;
use function Laravel\Prompts\task;
use function Laravel\Prompts\warning;

#[AsCommand(name: 'starter:finalize')]
class FinalizeCommand extends Command
{
    public $signature = 'starter:finalize';

    public $description = 'Apply code quality tools (Rector, Pint) and commit';

    public function handle(Git $git): int
    {
        /** @var Composer $composer */
        $composer = app('composer');
        $runner = Runner::detect();

        $hasRector = $composer->hasPackage('driftingly/rector-laravel');
        $hasPint = $composer->hasPackage('laravel/pint');

        if (! $hasRector && ! $hasPint) {
            warning('No code quality tools installed (Rector, Pint).');

            return self::SUCCESS;
        }

        task('Applying code quality tools', function (Logger $logger) use ($runner, $git): bool {
            $runner->run('composer lint', $logger);
            $git->commit('Apply code quality rules', 'chore');

            return true;
        });
        info('✓ Code quality tools applied');

        if (file_exists(base_path('boost.json'))) {
            task('Reconfiguring Boost', function (Logger $logger) use ($runner, $git): bool {
                $runner->run('php artisan boost:install', $logger);
                $git->commit('Reconfigure Boost', 'chore');

                return true;
            });
            info('✓ Boost reconfigured');
        }

        return self::SUCCESS;
    }
}
