<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Support;

use BerryValley\LaravelStarter\Packages\ComposerPackage;
use Illuminate\Support\Collection;

/** @extends Collection<array-key, ComposerPackage> */
class PackagesCollection extends Collection
{
    /**
     * @param  array<int, string>  $items
     */
    public static function from(array $items): self
    {
        return (new self)->addPackages($items);
    }

    /**
     * @param  array<int, string>|string  $classes
     */
    public function addPackages(array|string $classes): self
    {
        $files = Collection::wrap($classes)
            ->filter(fn (string $class): bool => class_exists($class) && is_a($class, ComposerPackage::class, true))
            ->map(fn (string $class): ComposerPackage => new $class)
            ->values();

        return $this->merge($files);
    }

    public function installedByDefault(): self
    {
        return $this->where('installByDefault', true)->values();
    }

    /**
     * @param  array<int, string>  $composerRequires
     */
    public function shouldInstall(array $composerRequires): self
    {
        $this->items = $this
            ->filter(fn (ComposerPackage $package): bool => in_array($package->require, $composerRequires))
            ->values()
            ->all();

        return $this;
    }
}
