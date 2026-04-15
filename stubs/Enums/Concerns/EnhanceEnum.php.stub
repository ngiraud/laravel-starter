<?php

declare(strict_types=1);

namespace App\Enums\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait EnhanceEnum
{
    abstract public function label(): string;

    public static function collect(): Collection
    {
        return Collection::wrap(self::cases());
    }

    public static function options(): Collection
    {
        return self::collect()
            ->map(fn (self $enum): array => $enum->toArray())
            ->values();
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => Str::ucfirst($this->label()),
        ];
    }
}
