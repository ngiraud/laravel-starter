<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

class UpdatePackageJsonAction
{
    private const array SCRIPTS = [
        'lint' => 'eslint . --fix && prettier --write resources/',
        'test:lint' => 'eslint . && prettier --check resources/',
    ];

    private const array LEGACY_SCRIPTS = ['format', 'format:check', 'lint:check'];

    public function handle(): void
    {
        $path = base_path('package.json');
        /** @var array<string, mixed> $packageJson */
        $packageJson = json_decode((string) file_get_contents($path), true);

        /** @var array<string, string> $scripts */
        $scripts = $packageJson['scripts'] ?? [];

        if ($this->isAlreadyApplied($scripts)) {
            return;
        }

        foreach (self::LEGACY_SCRIPTS as $legacy) {
            unset($scripts[$legacy]);
        }

        foreach (self::SCRIPTS as $name => $command) {
            $scripts[$name] = $command;
        }

        $packageJson['scripts'] = $scripts;

        file_put_contents($path, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * @param  array<string, string>  $scripts
     */
    private function isAlreadyApplied(array $scripts): bool
    {
        foreach (self::SCRIPTS as $name => $command) {
            if (($scripts[$name] ?? null) !== $command) {
                return false;
            }
        }

        return true;
    }
}
