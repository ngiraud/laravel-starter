<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Actions\UpdateEnvironmentAction;

pest()->group('unit', 'actions');

$stub = <<<'ENV'
APP_NAME=Laravel
APP_LOCALE=en
APP_FAKER_LOCALE=en_US
DB_DATABASE=laravel
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
REDIS_HOST=127.0.0.1
FILESYSTEM_DISK=local
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false
ENV;

beforeEach(function () use ($stub): void {
    $this->envPath = base_path('.env.test-tmp');
    file_put_contents($this->envPath, $stub);
    $this->action = new UpdateEnvironmentAction;
});

afterEach(function (): void {
    @unlink($this->envPath);
});

it('sets app name, locale and database', function (): void {
    $this->action->handle($this->envPath, 'MyApp', 'fr', 'my_app');

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('APP_NAME=MyApp')
        ->toContain('APP_LOCALE=fr')
        ->toContain('APP_FAKER_LOCALE=fr_FR')
        ->toContain('DB_DATABASE=my_app')
        ->toContain('SESSION_DRIVER=cookie');
});

it('wraps app name containing spaces in quotes', function (): void {
    $this->action->handle($this->envPath, 'My App', 'en', 'my_app');

    expect(file_get_contents($this->envPath))->toContain('APP_NAME="My App"');
});

it('configures redis when redis is selected as docker service', function (): void {
    $this->action->handle($this->envPath, 'App', 'en', 'app', ['redis']);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('SESSION_DRIVER=redis')
        ->toContain('QUEUE_CONNECTION=redis')
        ->toContain('CACHE_STORE=redis');
});

it('does not configure redis when not in docker services', function (): void {
    $this->action->handle($this->envPath, 'App', 'en', 'app', ['pgsql']);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('SESSION_DRIVER=cookie')
        ->toContain('QUEUE_CONNECTION=database')
        ->toContain('CACHE_STORE=database');
});

it('configures s3 when minio is selected as docker service', function (): void {
    $this->action->handle($this->envPath, 'App', 'en', 'app', ['minio']);

    $content = file_get_contents($this->envPath);

    expect($content)
        ->toContain('FILESYSTEM_DISK=s3')
        ->toContain('AWS_USE_PATH_STYLE_ENDPOINT=true')
        ->toContain('AWS_ENDPOINT=http://localhost:9000')
        ->toContain('AWS_ACCESS_KEY_ID=sail')
        ->toContain('AWS_SECRET_ACCESS_KEY=password')
        ->toContain('AWS_BUCKET=local');
});

it('configures s3 when rustfs is selected as docker service', function (): void {
    $this->action->handle($this->envPath, 'App', 'en', 'app', ['rustfs']);

    expect(file_get_contents($this->envPath))->toContain('FILESYSTEM_DISK=s3');
});
