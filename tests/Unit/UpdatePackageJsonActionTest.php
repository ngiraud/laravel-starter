<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Actions\UpdatePackageJsonAction;

pest()->group('unit', 'actions');

function packageJsonWith(array $devDependencies = []): void
{
    $path = base_path('package.json');
    file_put_contents($path, json_encode([
        'scripts' => [
            'build' => 'vite build',
            'dev' => 'vite',
            'format' => 'prettier --write resources/',
            'format:check' => 'prettier --check resources/',
        ],
        'devDependencies' => $devDependencies,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
}

beforeEach(function (): void {
    $this->packageJsonPath = base_path('package.json');
    packageJsonWith(['eslint' => '^9.0.0', 'prettier' => '^3.0.0']);
});

afterEach(function (): void {
    @unlink($this->packageJsonPath);
});

it('adds lint scripts and removes legacy ones when eslint and prettier are installed', function (): void {
    (new UpdatePackageJsonAction)->handle();

    /** @var array{scripts: array<string, string>} $result */
    $result = json_decode((string) file_get_contents($this->packageJsonPath), true);

    expect($result['scripts'])
        ->toHaveKey('lint', 'eslint . --fix && prettier --write resources/')
        ->toHaveKey('test:lint', 'eslint . && prettier --check resources/')
        ->toHaveKey('build')
        ->toHaveKey('dev')
        ->not->toHaveKey('format')
        ->not->toHaveKey('format:check');
});

it('only adds eslint commands when only eslint is installed', function (): void {
    packageJsonWith(['eslint' => '^9.0.0']);

    (new UpdatePackageJsonAction)->handle();

    /** @var array{scripts: array<string, string>} $result */
    $result = json_decode((string) file_get_contents($this->packageJsonPath), true);

    expect($result['scripts'])
        ->toHaveKey('lint', 'eslint . --fix')
        ->toHaveKey('test:lint', 'eslint .');
});

it('only adds prettier commands when only prettier is installed', function (): void {
    packageJsonWith(['prettier' => '^3.0.0']);

    (new UpdatePackageJsonAction)->handle();

    /** @var array{scripts: array<string, string>} $result */
    $result = json_decode((string) file_get_contents($this->packageJsonPath), true);

    expect($result['scripts'])
        ->toHaveKey('lint', 'prettier --write resources/')
        ->toHaveKey('test:lint', 'prettier --check resources/');
});

it('does not add lint scripts when neither eslint nor prettier is installed', function (): void {
    packageJsonWith();

    (new UpdatePackageJsonAction)->handle();

    /** @var array{scripts: array<string, string>} $result */
    $result = json_decode((string) file_get_contents($this->packageJsonPath), true);

    expect($result['scripts'])
        ->not->toHaveKey('lint')
        ->not->toHaveKey('test:lint')
        ->not->toHaveKey('format')
        ->not->toHaveKey('format:check');
});

it('is idempotent', function (): void {
    $action = new UpdatePackageJsonAction;
    $action->handle();

    $after1 = file_get_contents($this->packageJsonPath);

    $action->handle();

    $after2 = file_get_contents($this->packageJsonPath);

    expect($after1)->toBe($after2);
});
