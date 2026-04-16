<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Support\Facades\Process;

pest()->group('unit', 'support');

it('detects sail when compose.yaml exists', function (): void {
    $path = base_path('compose.yaml');
    file_put_contents($path, 'services: {}');

    expect(Runner::detect()->usesSail())->toBeTrue();

    unlink($path);
});

it('detects sail when docker-compose.yml exists', function (): void {
    $path = base_path('docker-compose.yml');
    file_put_contents($path, 'services: {}');

    expect(Runner::detect()->usesSail())->toBeTrue();

    unlink($path);
});

it('detects local runner when no compose file exists', function (): void {
    foreach (['compose.yaml', 'compose.yml', 'docker-compose.yaml', 'docker-compose.yml'] as $file) {
        @unlink(base_path($file));
    }

    expect(Runner::detect()->usesSail())->toBeFalse();
});

it('prefixes the command with sail when useSail is true', function (): void {
    Process::fake();

    Runner::forSail()->run('composer install');

    Process::assertRan(fn ($process): bool => $process->command === './vendor/bin/sail composer install');
});

it('runs the command directly when useSail is false', function (): void {
    Process::fake();

    Runner::local()->run('composer install');

    Process::assertRan(fn ($process): bool => $process->command === 'composer install');
});
