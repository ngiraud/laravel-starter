<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Actions\UpdateComposerScriptsAction;
use Illuminate\Support\Composer;

pest()->group('unit', 'actions');

/**
 * Build and capture the generated scripts for a given set of installed packages.
 *
 * @param  array<int, string>  $installed
 * @param  array<string, string>  $npmDeps
 * @return array<string, mixed>
 */
function scriptsFor(array $installed = [], array $npmDeps = []): array
{
    $captured = [];

    $mock = Mockery::mock(Composer::class);
    $mock->allows('hasPackage')->andReturnUsing(fn (string $p): bool => in_array($p, $installed));
    $mock->allows('modify')->andReturnUsing(function (callable $callback) use (&$captured): int {
        $result = $callback(['scripts' => []]);
        $captured = $result['scripts'] ?? [];

        return 0;
    });

    app()->bind('composer', fn (): Composer => $mock);

    $packageJsonPath = base_path('package.json');
    if ($npmDeps !== []) {
        file_put_contents($packageJsonPath, json_encode(['devDependencies' => $npmDeps], JSON_PRETTY_PRINT)."\n");
    } else {
        @unlink($packageJsonPath);
    }

    app(UpdateComposerScriptsAction::class)->handle();

    @unlink($packageJsonPath);

    return $captured;
}

it('builds the expected default scripts without optional packages', function (): void {
    $scripts = scriptsFor();

    expect($scripts)
        ->toHaveKeys(['dev', 'lint', 'test:lint', 'test', 'test:all'])
        ->not->toHaveKey('test:types');
});

it('does not include test:types in test:all without larastan', function (): void {
    $scripts = scriptsFor();

    expect($scripts['test:all'])->not->toContain('@test:types');
});

it('adds rector to lint and test:lint when rector is installed', function (): void {
    $scripts = scriptsFor(['driftingly/rector-laravel']);

    expect($scripts['lint'])
        ->toContain('rector')
        ->toContain('pint --parallel');

    expect($scripts['test:lint'])->toContain('rector --dry-run');
});

it('does not include rector in lint when rector is not installed', function (): void {
    $scripts = scriptsFor();

    expect($scripts['lint'])->toBe(['pint --parallel']);
});

it('includes npm run lint when eslint is installed', function (): void {
    $scripts = scriptsFor([], ['eslint' => '^9.0.0']);

    expect($scripts['lint'])->toContain('npm run lint');
    expect($scripts['test:lint'])->toContain('npm run test:lint');
});

it('includes npm run lint when prettier is installed', function (): void {
    $scripts = scriptsFor([], ['prettier' => '^3.0.0']);

    expect($scripts['lint'])->toContain('npm run lint');
    expect($scripts['test:lint'])->toContain('npm run test:lint');
});

it('does not include npm run lint when neither eslint nor prettier is installed', function (): void {
    $scripts = scriptsFor();

    expect($scripts['lint'])->not->toContain('npm run lint');
    expect($scripts['test:lint'])->not->toContain('npm run test:lint');
});

it('adds test:types script and includes it in test:all when larastan is installed', function (): void {
    $scripts = scriptsFor(['larastan/larastan']);

    expect($scripts)
        ->toHaveKey('test:types', ['phpstan']);

    expect($scripts['test:all'])->toContain('@test:types');
});

it('uses horizon queue command when horizon is installed', function (): void {
    $scripts = scriptsFor(['laravel/horizon']);

    expect($scripts['dev'][1])->toContain('php artisan horizon');
});

it('uses queue:listen when horizon is not installed', function (): void {
    $scripts = scriptsFor();

    expect($scripts['dev'][1])->toContain('queue:listen');
});

it('includes all three concurrently commands in dev', function (): void {
    $scripts = scriptsFor();

    expect($scripts['dev'][1])
        ->toContain('php artisan pail')
        ->toContain('npm run dev')
        ->toContain('--names=logs,vite,queue');
});
