<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Support\Git;
use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Symfony\Component\Console\Attribute\AsCommand;

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
        $applied = [];

        if ($composer->hasPackage('driftingly/rector-laravel')) {
            $this->components->info('Applying Rector rules');
            $runner->run('composer refactor');
            $applied[] = 'Rector';
        }

        if ($composer->hasPackage('laravel/pint')) {
            $this->components->info('Applying Pint rules');
            $runner->run('composer lint');
            $applied[] = 'Pint';
        }

        if ($applied !== []) {
            $git->commit('Apply '.implode(' and ', $applied).' rules', 'chore');
        }

        $this->components->success('Done.');

        return self::SUCCESS;
    }
}
