<?php

namespace BerryValley\LaravelStarter\Commands;

use Illuminate\Console\Command;

class LaravelStarterCommand extends Command
{
    public $signature = 'starter:install';

    public $description = 'Prepare everything after a fresh Laravel installation';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
