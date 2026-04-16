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
    $this->artisan('starter:add', ['package' => 'unknown-package'])
        ->expectsOutputToContain('Unknown package [unknown-package]')
        ->assertFailed();
});

it('warns and succeeds when the package is already installed', function (): void {
    $this->composer->allows('hasPackage')->with('laravel/telescope')->andReturn(true);

    $this->artisan('starter:add', ['package' => 'telescope'])
        ->expectsOutputToContain('Laravel Telescope is already installed')
        ->assertSuccessful();
});

it('installs the package and commits', function (): void {
    $this->composer->allows('hasPackage')->with('laravel/telescope')->andReturn(false);
    $this->git->expects('commit')->once()->with('Install Laravel Telescope');

    Process::fake();

    $this->artisan('starter:add', ['package' => 'telescope'])
        ->expectsOutputToContain('Installing Laravel Telescope')
        ->expectsOutputToContain('Laravel Telescope installed')
        ->assertSuccessful();

    Process::assertRan(fn ($process): bool => str_contains($process->command, 'composer require laravel/telescope'));
});

it('installs a dev package with the --dev flag', function (): void {
    $this->composer->allows('hasPackage')->with('brianium/paratest')->andReturn(false);
    $this->git->allows('commit');

    Process::fake();

    $this->artisan('starter:add', ['package' => 'paratest'])->assertSuccessful();

    Process::assertRan(fn ($process): bool => str_contains($process->command, '--dev'));
});

it('installs a package with a version constraint', function (): void {
    $this->composer->allows('hasPackage')->with('larastan/larastan')->andReturn(false);
    $this->git->allows('commit');

    Process::fake();

    $this->artisan('starter:add', ['package' => 'larastan'])->assertSuccessful();

    Process::assertRan(fn ($process): bool => str_contains($process->command, '"^3.0"'));
});
