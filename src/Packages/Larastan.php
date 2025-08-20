<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

final class Larastan extends ComposerPackage
{
    public string $name = 'Larastan';

    public string $require = 'larastan/larastan';

    public string $version = '^3.0';

    public bool $isDevRequirement = true;

    public bool $installByDefault = true;

    public function install(): void
    {
        $this->files->copy(__DIR__.'/../../stubs/phpstan.neon.stub', base_path('phpstan.neon'));
    }
}
