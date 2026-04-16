<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Support\Git;
use Illuminate\Support\Composer;
use Illuminate\Support\Facades\Process;

pest()->group('feature', 'commands');

beforeEach(function (): void {
    $this->git = Mockery::mock(Git::class);
    $this->app->instance(Git::class, $this->git);

    $this->composer = Mockery::mock(Composer::class);
    $this->app->bind('composer', fn (): Composer => $this->composer);
});

it('fails with an unknown package key', function (): void {
    $this->artisan('starter:remove', ['package' => 'unknown-package'])
        ->expectsOutputToContain('Unknown package [unknown-package]')
        ->assertFailed();
});

it('warns and succeeds when the package is not installed', function (): void {
    $this->composer->allows('hasPackage')->with('laravel/telescope')->andReturn(false);

    $this->artisan('starter:remove', ['package' => 'telescope'])
        ->expectsOutputToContain('Laravel Telescope is not installed')
        ->assertSuccessful();
});

it('removes the package and commits', function (): void {
    $this->composer->allows('hasPackage')->with('laravel/telescope')->andReturn(true);
    $this->git->expects('commit')->once()->with('Remove Laravel Telescope');

    Process::fake();

    $this->artisan('starter:remove', ['package' => 'telescope'])
        ->expectsOutputToContain('Removing Laravel Telescope')
        ->expectsOutputToContain('Laravel Telescope removed')
        ->assertSuccessful();

    Process::assertRan(fn ($process): bool => str_contains($process->command, 'composer remove laravel/telescope'));
});

it('removes a dev package with the --dev flag', function (): void {
    $this->composer->allows('hasPackage')->with('brianium/paratest')->andReturn(true);
    $this->git->allows('commit');

    Process::fake();

    $this->artisan('starter:remove', ['package' => 'paratest'])->assertSuccessful();

    Process::assertRan(fn ($process): bool => str_contains($process->command, '--dev'));
});

it('warns about console routes when the package modifies console and has no uninstall method', function (): void {
    // Horizon has modifies_console: true and HorizonInstaller does have an uninstall method,
    // so we use 'backup' which also has modifies_console: true and no custom installer uninstall.
    $this->composer->allows('hasPackage')->with('spatie/laravel-backup')->andReturn(true);
    $this->git->allows('commit');

    Process::fake();

    $this->artisan('starter:remove', ['package' => 'backup'])
        ->expectsOutputToContain('routes/console.php')
        ->assertSuccessful();
});
