<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Tests;

use BerryValley\LaravelStarter\LaravelStarterServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [LaravelStarterServiceProvider::class];
    }
}
