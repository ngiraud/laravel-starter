<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Support;

use Illuminate\Support\Facades\Process;

class Runner
{
    public function __construct(private readonly bool $useSail) {}

    /**
     * Auto-detect whether Sail is configured by checking for a compose file.
     */
    public static function detect(): self
    {
        $composePaths = ['compose.yaml', 'compose.yml', 'docker-compose.yaml', 'docker-compose.yml'];

        return new self(
            collect($composePaths)->contains(fn (string $path): bool => file_exists(base_path($path)))
        );
    }

    public static function forSail(): self
    {
        return new self(true);
    }

    public static function local(): self
    {
        return new self(false);
    }

    public function run(string $command): void
    {
        $full = $this->useSail ? "./vendor/bin/sail {$command}" : $command;

        Process::tty(! app()->environment('testing'))
            ->run($full, fn (string $type, string $output): int => print ($output))
            ->throw();
    }

    public function usesSail(): bool
    {
        return $this->useSail;
    }
}
