<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Support\Git;
use Illuminate\Support\Facades\Process;

pest()->group('unit', 'support');

it('skips git init when .git directory already exists', function (): void {
    Process::fake();

    $gitDir = base_path('.git');
    mkdir($gitDir, 0755, true);

    app(Git::class)->init();

    Process::assertNothingRan();

    rmdir($gitDir);
});

it('runs git init when .git directory does not exist', function (): void {
    Process::fake();

    $gitDir = base_path('.git');
    if (is_dir($gitDir)) {
        rmdir($gitDir);
    }

    app(Git::class)->init();

    Process::assertRan('git init');
});

it('skips commit when working tree is clean', function (): void {
    Process::fake([
        'git status --porcelain' => Process::result(output: ''),
    ]);

    app(Git::class)->commit('Initial commit');

    Process::assertNotRan(fn ($process): bool => str_contains($process->command, 'git commit'));
});

it('commits staged changes with feat type by default', function (): void {
    Process::fake([
        'git status --porcelain' => Process::result(output: 'M app/Models/User.php'),
        '*git commit*' => Process::result(),
    ]);

    app(Git::class)->commit('Initial commit');

    Process::assertRan(fn ($process): bool => $process->command === 'git add . && git commit -m "feat: Initial commit"');
});

it('commits with a custom type', function (): void {
    Process::fake([
        'git status --porcelain' => Process::result(output: 'M composer.json'),
        '*git commit*' => Process::result(),
    ]);

    app(Git::class)->commit('Remove laravel-starter package', 'chore');

    Process::assertRan(fn ($process): bool => $process->command === 'git add . && git commit -m "chore: Remove laravel-starter package"');
});
