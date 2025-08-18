<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Support;

use BerryValley\LaravelStarter\Packages\ComposerPackage;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

final class PackagesCollection extends Collection
{
    /**
     * Create a new packages collection instance from the given items
     *
     * @template TMakeKey of array-key
     * @template TMakeValue
     *
     * @param  Arrayable<TMakeKey, TMakeValue>|iterable<TMakeKey, TMakeValue>  $items
     * @return static<TMakeKey, TMakeValue>
     */
    public static function from(array|Arrayable $items = []): self
    {
        return (new self)->addPackages($items);
    }

    public function addPackages(array|string $classes): self
    {
        $files = collect($classes)
            ->filter(fn (string $class): bool => class_exists($class))
            ->map(fn (string $class): object => new $class)
            ->filter(fn ($a): bool => is_a($a, ComposerPackage::class))
            ->values();

        return $this->merge($files);
    }

    public function installedByDefault(): self
    {
        return $this->where('installByDefault', true)->values();
    }

    public function shouldInstall(array $composerRequires): self
    {
        $this->items = $this
            ->filter(fn (ComposerPackage $package): bool => in_array($package->require, $composerRequires))
            ->values()
            ->all();

        return $this;
    }
}
