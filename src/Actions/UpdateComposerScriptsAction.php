<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Composer;

readonly class UpdateComposerScriptsAction
{
    protected Composer $composer;

    public function __construct()
    {
        $this->composer = app('composer');
    }

    public function handle(): void
    {
        $this->composer->modify(function (array $composer): array {
            $keep = array_flip(['dev', 'test', 'test:lint', 'test:types', 'test:all', 'lint']);

            /** @var array<string, string|array<int, string>> $scripts */
            $scripts = $composer['scripts'] ?? [];

            $composer['scripts'] = array_diff_key($scripts, $keep) + $this->buildScripts();

            return $composer;
        });
    }

    /**
     * @return Collection<int, array{color: string, command: string, name: string}>
     */
    protected function buildCommands(): Collection
    {
        return collect([
            ['color' => '#93c5fd', 'command' => 'php artisan pail --timeout=0', 'name' => 'logs'],
            ['color' => '#fdba74', 'command' => 'npm run dev', 'name' => 'vite'],
            ['color' => '#93c5fd', 'command' => $this->getQueueCommand(), 'name' => 'queue'],
        ]);
    }

    protected function getQueueCommand(): string
    {
        return $this->composer->hasPackage('laravel/horizon')
            ? 'php artisan horizon'
            : 'php artisan queue:listen database --tries=1 --queue=default';
    }

    /**
     * @return array<string, array<int, string>>
     */
    protected function buildScripts(): array
    {
        $devCommands = $this->buildCommands();
        $hasRector = $this->composer->hasPackage('driftingly/rector-laravel');
        $hasLarastan = $this->composer->hasPackage('larastan/larastan');
        $hasParatest = $this->composer->hasPackage('brianium/paratest');
        $hasNpmLint = $this->hasNpmPackage('eslint') || $this->hasNpmPackage('prettier');

        /** @var array<int, string> $lint */
        $lint = [];
        if ($hasRector) {
            $lint[] = 'rector';
        }
        $lint[] = 'pint --parallel';
        if ($hasNpmLint) {
            $lint[] = 'npm run lint';
        }

        /** @var array<int, string> $testLint */
        $testLint = ['pint --parallel --test'];
        if ($hasRector) {
            $testLint[] = 'rector --dry-run';
        }

        if ($hasNpmLint) {
            $testLint[] = 'npm run test:lint';
        }

        /** @var array<int, string> $testAll */
        $testAll = ['@test:lint'];
        if ($hasLarastan) {
            $testAll[] = '@test:types';
        }

        $testAll[] = '@test';

        $scripts = [
            'dev' => [
                'Composer\\Config::disableProcessTimeout',
                $this->buildConcurrentlyCommand($devCommands),
            ],
            'lint' => $lint,
            'test:lint' => $testLint,
            'test' => [
                '@php artisan config:clear --ansi',
                sprintf('@php artisan test%s --parallel --compact', $hasParatest ? ' --parallel' : ''),
            ],
            'test:all' => $testAll,
        ];

        if ($hasLarastan) {
            $scripts['test:types'] = ['phpstan'];
        }

        return $scripts;
    }

    /**
     * @param  Collection<int, array{color: string, command: string, name: string}>  $commands
     */
    protected function buildConcurrentlyCommand(Collection $commands): string
    {
        return sprintf(
            'npx concurrently -c "%s" %s --names=%s --kill-others',
            $commands->pluck('color')->implode(','),
            $commands->map(fn (array $command): string => sprintf('"%s"', $command['command']))->implode(' '),
            $commands->pluck('name')->implode(','),
        );
    }

    private function hasNpmPackage(string $package): bool
    {
        $path = base_path('package.json');

        if (! file_exists($path)) {
            return false;
        }

        /** @var array<string, mixed> $packageJson */
        $packageJson = json_decode((string) file_get_contents($path), true);
        $allDeps = array_merge(
            $packageJson['devDependencies'] ?? [],
            $packageJson['dependencies'] ?? [],
        );

        return array_key_exists($package, $allDeps);
    }
}
