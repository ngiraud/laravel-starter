<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Facades;

use Illuminate\Process\ProcessResult;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \BerryValley\LaravelStarter\Support\ProcessRunner sail()
 * @method static \BerryValley\LaravelStarter\Support\ProcessRunner git()
 * @method static \BerryValley\LaravelStarter\Support\ProcessRunner withContext(string $context)
 * @method static ProcessResult run(string $command, bool $tty = true)
 * @method static ProcessResult runSilently(string $command)
 * @method static string initialize()
 * @method static string commit(string $message, string $semantic = 'feat')
 *
 * @see \BerryValley\LaravelStarter\Support\ProcessRunner
 */
class ProcessRunner extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BerryValley\LaravelStarter\Support\ProcessRunner::class;
    }
}
