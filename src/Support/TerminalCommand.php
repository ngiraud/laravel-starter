<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Support;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

final class TerminalCommand
{
    private string $context = 'local';

    public function sail(): self
    {
        $this->context = 'sail';

        return $this;
    }

    public function run(string $command): ProcessResult
    {
        $command = match ($this->context) {
            'sail' => "./vendor/bin/sail {$command}",
            default => $command,
        };

        return Process::tty()
            ->run($command, function (string $type, string $output): void {
                echo $output;
            })
            ->throw();
    }
}
