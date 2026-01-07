<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Packages;

return [

    /**
     * The packages proposed to installation
     */
    'packages' => [
        Packages\LaravelTelescope::class,
        Packages\LaravelHorizon::class,
        Packages\Paratest::class,
        Packages\Larastan::class,
        Packages\Rector::class,
        Packages\LaravelBackup::class,
        Packages\Filament::class,
        Packages\LaravelNightwatch::class,
        Packages\Boost::class,
    ],

];
