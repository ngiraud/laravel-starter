<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Actions\PublishFilesAction;
use BerryValley\LaravelStarter\Facades\ProcessRunner;

class Boost extends ComposerPackage
{
    public string $name = 'Laravel Boost';

    public string $require = 'laravel/boost';

    public bool $isDevRequirement = true;

    public bool $installByDefault = true;

    public function install(): void
    {
        app(PublishFilesAction::class)->publishBoostGuidelines();

        ProcessRunner::sail()->run('php artisan boost:install');
    }
}
