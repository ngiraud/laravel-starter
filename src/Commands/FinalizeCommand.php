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

        $hasRector = $composer->hasPackage('driftingly/rector-laravel');
        $hasPint = $composer->hasPackage('laravel/pint');

        if (! $hasRector && ! $hasPint) {
            $this->components->warn('No code quality tools installed (Rector, Pint).');

            return self::SUCCESS;
        }

        $this->components->info('Applying code quality tools');
        $runner->run('composer lint');

        $git->commit('Apply code quality rules', 'chore');
        $this->components->success('Done.');

        return self::SUCCESS;
    }
}
