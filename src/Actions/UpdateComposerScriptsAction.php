<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

use Illuminate\Support\Collection;
use Illuminate\Support\Composer;

final readonly class UpdateComposerScriptsAction
{
    private Composer $composer;

    public function __construct(
    ) {
        $this->composer = app('composer');
    }

    /**
     * Update composer.json with development and testing scripts
     *
     * Adds scripts for parallel development (dev), SSR builds (dev:ssr),
     * linting, testing, and static analysis based on installed packages.
     */
    public function handle(): void
    {
        $this->composer->modify(function (array $composer) {
            $commands = $this->buildCommands();
            $composer['scripts'] = $this->buildScripts($commands);

            return $composer;
        });
    }

    /**
     * @return Collection<int, array{color: string, command: string, name: string}>
     */
    private function buildCommands(): Collection
    {
        return collect([
            ['color' => '#93c5fd', 'command' => 'php artisan pail --timeout=0', 'name' => 'logs'],
            ['color' => '#fdba74', 'command' => 'npm run dev', 'name' => 'vite'],
            ['color' => '#fdba74', 'command' => 'php artisan inertia:start-ssr', 'name' => 'ssr'],
            ['color' => '#93c5fd', 'command' => $this->getQueueCommand(), 'name' => 'queue'],
        ]);
    }

    /**
     * Get the appropriate queue command based on installed packages
     *
     * Returns Horizon command if available, otherwise falls back to basic queue listener.
     */
    private function getQueueCommand(): string
    {
        return match ($this->composer->hasPackage('laravel/horizon')) {
            true => 'php artisan horizon',
            false => 'php artisan queue:listen database --tries=1 --queue=default',
        };
    }

    /**
     * @param  Collection<int, array{color: string, command: string, name: string}>  $commands
     * @return array<string, array<int, string>>
     */
    private function buildScripts(Collection $commands): array
    {
        $devCommands = $commands->where('name', '!=', 'ssr');
        $ssrCommands = $commands->where('name', '!=', 'vite');

        $scripts = [
            'dev' => [
                'Composer\\Config::disableProcessTimeout',
                $this->buildConcurrentlyCommand($devCommands),
            ],
            'dev:ssr' => [
                'npm run build:ssr',
                'Composer\\Config::disableProcessTimeout',
                $this->buildConcurrentlyCommand($ssrCommands),
            ],
            'lint' => ['pint --parallel'],
            'test' => [
                '@php artisan config:clear --ansi',
                sprintf(
                    '@php artisan test %s--compact --coverage --min=90',
                    $this->composer->hasPackage('brianium/paratest') ? '--parallel ' : ''
                ),
            ],
            'test:lint' => [
                'pint --parallel --test',
                'npm run lint',
                'npm run format:check',
            ],
        ];

        if ($this->composer->hasPackage('rector/rector')) {
            $scripts['refactor'] = ['rector'];
            $scripts['test:refactor'] = ['rector --dry-run'];
        }

        if ($this->composer->hasPackage('larastan/larastan')) {
            $scripts['test:types'] = ['phpstan'];
        }

        return $scripts;
    }

    /**
     * @param  Collection<int, array{color: string, command: string, name: string}>  $commands
     */
    private function buildConcurrentlyCommand(Collection $commands): string
    {
        return sprintf(
            'npx concurrently -c "%s" %s --names=%s --kill-others',
            $commands->pluck('color')->implode(','),
            $commands->map(fn (array $command): string =>
                /** @var array{color: string, command: string, name: string} $command */
                "\"{$command['command']}\"")->implode(' '),
            $commands->pluck('name')->implode(',')
        );
    }
}
