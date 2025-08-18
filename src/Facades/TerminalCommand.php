<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Facades;

use BerryValley\LaravelStarter\Support\CommandExecutor;
use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CommandExecutor sail()
 * @method static ProcessResult run()
 *
 * @see CommandExecutor
 */
final class TerminalCommand extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CommandExecutor::class;
    }
}
