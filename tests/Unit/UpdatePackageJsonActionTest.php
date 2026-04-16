<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Actions\UpdatePackageJsonAction;

beforeEach(function (): void {
    $this->packageJsonPath = base_path('package.json');

    file_put_contents($this->packageJsonPath, json_encode([
        'scripts' => [
            'build' => 'vite build',
            'dev' => 'vite',
            'format' => 'prettier --write resources/',
            'format:check' => 'prettier --check resources/',
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
});

afterEach(function (): void {
    @unlink($this->packageJsonPath);
});

it('adds lint scripts and removes legacy ones', function (): void {
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

it('is idempotent', function (): void {
    $action = new UpdatePackageJsonAction;
    $action->handle();

    $after1 = file_get_contents($this->packageJsonPath);

    $action->handle();

    $after2 = file_get_contents($this->packageJsonPath);

    expect($after1)->toBe($after2);
});
