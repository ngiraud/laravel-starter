<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Installers;

return [

    /*
     * The packages proposed during starter:install.
     *
     * Each entry is keyed by a short slug used with starter:add / starter:remove.
     *
     * Keys:
     *   - label           : Display name shown in prompts
     *   - require         : Composer package name
     *   - dev             : Whether to install as --dev
     *   - default         : Pre-selected in the multiselect prompt
     *   - version         : Optional version constraint
     *   - installer       : Optional class to run post-install / pre-remove steps
     *   - modifies_console: Whether the installer adds entries to routes/console.php (removal warning)
     */
    'packages' => [

        'telescope' => [
            'label' => 'Laravel Telescope',
            'require' => 'laravel/telescope',
            'dev' => false,
            'default' => true,
            'installer' => Installers\TelescopeInstaller::class,
        ],

        'paratest' => [
            'label' => 'Paratest',
            'require' => 'brianium/paratest',
            'dev' => true,
            'default' => true,
        ],

        'larastan' => [
            'label' => 'Larastan',
            'require' => 'larastan/larastan',
            'dev' => true,
            'default' => true,
            'version' => '^3.0',
            'installer' => Installers\LarastanInstaller::class,
        ],

        'rector' => [
            'label' => 'Rector (Laravel)',
            'require' => 'driftingly/rector-laravel',
            'dev' => true,
            'default' => true,
            'installer' => Installers\RectorInstaller::class,
        ],

        'horizon' => [
            'label' => 'Laravel Horizon',
            'require' => 'laravel/horizon',
            'dev' => false,
            'default' => false,
            'installer' => Installers\HorizonInstaller::class,
            'modifies_console' => true,
        ],

        'backup' => [
            'label' => 'Laravel Backup',
            'require' => 'spatie/laravel-backup',
            'dev' => false,
            'default' => false,
            'installer' => Installers\LaravelBackupInstaller::class,
            'modifies_console' => true,
        ],

        'filament' => [
            'label' => 'Filament',
            'require' => 'filament/filament',
            'dev' => false,
            'default' => false,
            'version' => '^5.0',
            'installer' => Installers\FilamentInstaller::class,
        ],

        'nightwatch' => [
            'label' => 'Laravel Nightwatch',
            'require' => 'laravel/nightwatch',
            'dev' => false,
            'default' => false,
        ],

    ],

];
