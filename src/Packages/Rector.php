<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

final class Rector extends ComposerPackage
{
    public string $name = 'Rector';

    public string $require = 'rector/rector';

    public bool $isDevRequirement = true;

    public bool $installByDefault = true;

    public function install(): void
    {
        $this->files->copy(__DIR__.'/../../stubs/rector.php.stub', base_path('rector.php'));
    }
}
