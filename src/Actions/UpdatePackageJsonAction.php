<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

class UpdatePackageJsonAction
{
    public function handle(): void
    {
        $path = base_path('package.json');
        /** @var array<string, mixed> $packageJson */
        $packageJson = json_decode((string) file_get_contents($path), true);

        /** @var array<string, string> $scripts */
        $scripts = $packageJson['scripts'] ?? [];

        // Remove legacy script names
        unset($scripts['format'], $scripts['format:check'], $scripts['lint:check']);

        $scripts['lint'] = 'eslint . --fix && prettier --write resources/';
        $scripts['test:lint'] = 'eslint . && prettier --check resources/';

        $packageJson['scripts'] = $scripts;

        file_put_contents($path, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }
}
