<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Facades;

use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \BerryValley\LaravelStarter\Support\TerminalCommand sail()
 * @method static ProcessResult run()
 *
 * @see \BerryValley\LaravelStarter\Support\TerminalCommand
 */
final class TerminalCommand extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BerryValley\LaravelStarter\Support\TerminalCommand::class;
    }
}
