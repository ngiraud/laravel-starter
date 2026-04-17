<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

class UpdatePackageJsonAction
{
    private const array LEGACY_SCRIPTS = ['format', 'format:check', 'lint:check'];

    public function handle(): void
    {
        $path = base_path('package.json');
        /** @var array<string, mixed> $packageJson */
        $packageJson = json_decode((string) file_get_contents($path), true);

        /** @var array<string, string> $scripts */
        $scripts = $packageJson['scripts'] ?? [];
        $toAdd = $this->buildScripts($packageJson);

        $legacyToRemove = array_filter(
            self::LEGACY_SCRIPTS,
            fn (string $k): bool => array_key_exists($k, $scripts),
        );

        if ($legacyToRemove === [] && $this->isAlreadyApplied($scripts, $toAdd)) {
            return;
        }

        foreach (self::LEGACY_SCRIPTS as $legacy) {
            unset($scripts[$legacy]);
        }

        foreach ($toAdd as $name => $command) {
            $scripts[$name] = $command;
        }

        $packageJson['scripts'] = $scripts;

        file_put_contents($path, json_encode($packageJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
    }

    /**
     * @param  array<string, mixed>  $packageJson
     * @return array<string, string>
     */
    private function buildScripts(array $packageJson): array
    {
        /** @var array<string, string> $devDependencies */
        $devDependencies = $packageJson['devDependencies'] ?? [];
        /** @var array<string, string> $dependencies */
        $dependencies = $packageJson['dependencies'] ?? [];
        $allDeps = array_merge($devDependencies, $dependencies);

        $hasEslint = array_key_exists('eslint', $allDeps);
        $hasPrettier = array_key_exists('prettier', $allDeps);

        /** @var array<int, string> $lintParts */
        $lintParts = array_values(array_filter([
            $hasEslint ? 'eslint . --fix' : null,
            $hasPrettier ? 'prettier --write resources/' : null,
        ]));

        /** @var array<int, string> $testLintParts */
        $testLintParts = array_values(array_filter([
            $hasEslint ? 'eslint .' : null,
            $hasPrettier ? 'prettier --check resources/' : null,
        ]));

        $scripts = [];

        if ($lintParts !== []) {
            $scripts['lint'] = implode(' && ', $lintParts);
        }

        if ($testLintParts !== []) {
            $scripts['test:lint'] = implode(' && ', $testLintParts);
        }

        return $scripts;
    }

    /**
     * @param  array<string, string>  $scripts
     * @param  array<string, string>  $toAdd
     */
    private function isAlreadyApplied(array $scripts, array $toAdd): bool
    {
        return array_all($toAdd, fn ($command, $name): bool => ($scripts[$name] ?? null) === $command);
    }
}
