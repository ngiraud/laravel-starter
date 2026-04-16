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

it('warns and skips when neither rector nor pint is installed', function (): void {
    $this->composer->allows('hasPackage')->andReturn(false);

    $this->artisan('starter:finalize')
        ->expectsOutputToContain('No code quality tools installed')
        ->assertSuccessful();
});

it('runs composer lint when pint is installed', function (): void {
    $this->composer->allows('hasPackage')->with('driftingly/rector-laravel')->andReturn(false);
    $this->composer->allows('hasPackage')->with('laravel/pint')->andReturn(true);
    $this->git->allows('commit');

    Process::fake();

    $this->artisan('starter:finalize')
        ->expectsOutputToContain('Applying code quality tools')
        ->assertSuccessful();

    Process::assertRan(fn ($process): bool => $process->command === 'composer lint');
});

it('runs composer lint when rector is installed', function (): void {
    $this->composer->allows('hasPackage')->with('driftingly/rector-laravel')->andReturn(true);
    $this->composer->allows('hasPackage')->with('laravel/pint')->andReturn(false);
    $this->git->allows('commit');

    Process::fake();

    $this->artisan('starter:finalize')->assertSuccessful();

    Process::assertRan(fn ($process): bool => $process->command === 'composer lint');
});

it('commits after running code quality tools', function (): void {
    $this->composer->allows('hasPackage')->andReturn(true);
    $this->git->expects('commit')->once()->with('Apply code quality rules', 'chore');

    Process::fake();

    $this->artisan('starter:finalize')->assertSuccessful();
});

it('does not commit when no quality tools are installed', function (): void {
    $this->composer->allows('hasPackage')->andReturn(false);
    $this->git->expects('commit')->never();

    $this->artisan('starter:finalize')->assertSuccessful();
});
