<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

class Rector extends ComposerPackage
{
    public string $name = 'Rector (Custom Laravel Package)';

    public string $require = 'driftingly/rector-laravel';

    public bool $isDevRequirement = true;

    public bool $installByDefault = true;

    public function install(): void
    {
        $this->files->copy(__DIR__.'/../../stubs/rector.php.stub', base_path('rector.php'));
    }
}
