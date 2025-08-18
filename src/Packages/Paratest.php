<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

final class Paratest extends ComposerPackage
{
    public string $name = 'Paratest';

    public string $require = 'brianium/paratest';

    public bool $isDevRequirement = true;

    public bool $installByDefault = true;

    public function install(): void {}
}
