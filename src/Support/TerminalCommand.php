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
        return $this->context('sail');
    }

    public function context(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function run(string $command, bool $tty = true): ProcessResult
    {
        $command = match ($this->context) {
            'sail' => "./vendor/bin/sail {$command}",
            default => $command,
        };

        return Process::tty($tty)
            ->run($command, $tty ? function (string $type, string $output): void {
                echo $output;
            } : null)
            ->throw();
    }

    public function git(): self
    {
        return $this->context('local');
    }

    public function initialize(): string
    {
        return $this->run('git init', false)->output();
    }

    public function commit(string $message, string $semantic = 'feat'): string
    {
        // First check if there are changes to commit (without TTY to avoid output)
        $hasChanges = $this->run('git status --porcelain', false);

        if (mb_trim($hasChanges->output()) === '') {
            return 'No changes to commit';
        }

        // Commit with the message and capture output
        $result = $this->run("git add . && git commit -m \"{$semantic}: {$message}\"", false);

        // Format the output (first two lines combined)
        $output = $result->output();
        $lines = explode("\n", $output);

        // Create a new ProcessResult with the formatted output
        if (count($lines) >= 2) {
            return mb_trim($lines[0]).' - '.mb_trim($lines[1]);
        }

        return $output;
    }
}
