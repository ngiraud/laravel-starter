<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Packages;

return [

    'packages' => [
        Packages\LaravelTelescope::class,
        Packages\LaravelHorizon::class,
        Packages\Paratest::class,
        Packages\Larastan::class,
        Packages\Rector::class,
        Packages\LaravelBackup::class,
    ],

];
