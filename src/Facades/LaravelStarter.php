<?php

namespace BerryValley\LaravelStarter\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \BerryValley\LaravelStarter\LaravelStarter
 */
class LaravelStarter extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \BerryValley\LaravelStarter\LaravelStarter::class;
    }
}
