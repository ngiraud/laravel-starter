<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Support;

use Illuminate\Support\Facades\Process;

class Git
{
    public function init(): void
    {
        if (file_exists(base_path('.git'))) {
            return;
        }

        Process::run('git init')->throw();
    }

    public function commit(string $message, string $type = 'feat'): void
    {
        $status = Process::run('git status --porcelain')->throw();

        if (mb_trim($status->output()) === '') {
            return;
        }

        Process::run("git add . && git commit -m \"{$type}: {$message}\"")->throw();
    }
}
