<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Support;

use Closure;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

class ProcessRunner
{
    protected const string CONTEXT_SAIL = 'sail';

    protected const string CONTEXT_LOCAL = 'local';

    protected string $context = self::CONTEXT_LOCAL;

    /**
     * Switch to Sail context for running commands inside Docker containers
     */
    public function sail(): self
    {
        return $this->withContext(self::CONTEXT_SAIL);
    }

    /**
     * Switch to Git context (same as local for consistency)
     */
    public function git(): self
    {
        return $this->withContext(self::CONTEXT_LOCAL);
    }

    /**
     * Set the execution context
     */
    public function withContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Execute a command in the current context
     */
    public function run(string $command, bool $tty = true): ProcessResult
    {
        $command = $this->buildCommand($command);

        return Process::tty($tty)
            ->run($command, $tty ? $this->getOutputCallback() : null)
            ->throw();
    }

    /**
     * Execute a command silently (no TTY output)
     */
    public function runSilently(string $command): ProcessResult
    {
        return $this->run($command, false);
    }

    /**
     * Initialize a Git repository
     */
    public function initialize(): string
    {
        return $this->runSilently('git init')->output();
    }

    /**
     * Create a Git commit with the given message and semantic prefix
     */
    public function commit(string $message, string $semantic = 'feat'): string
    {
        if (! $this->hasChangesToCommit()) {
            return 'No changes to commit';
        }

        $commitMessage = "{$semantic}: {$message}";
        $result = $this->runSilently("git add . && git commit -m \"{$commitMessage}\"");

        return $this->formatCommitOutput($result->output());
    }

    /**
     * Get the output callback for TTY processes
     */
    protected function getOutputCallback(): Closure
    {
        return function (string $type, string $output): void {
            echo $output;
        };
    }

    /**
     * Check if there are changes to commit
     */
    protected function hasChangesToCommit(): bool
    {
        $statusResult = $this->runSilently('git status --porcelain');

        return mb_trim($statusResult->output()) !== '';
    }

    /**
     * Build the command based on the current context
     */
    protected function buildCommand(string $command): string
    {
        return match ($this->context) {
            self::CONTEXT_SAIL => "./vendor/bin/sail {$command}",
            default => $command,
        };
    }

    /**
     * Format Git commit output to show commit hash and summary
     */
    protected function formatCommitOutput(string $output): string
    {
        $lines = explode("\n", $output);

        if (count($lines) >= 2) {
            return mb_trim($lines[0]).' - '.mb_trim($lines[1]);
        }

        return $output;
    }
}
