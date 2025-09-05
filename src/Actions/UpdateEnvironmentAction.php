<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

use BerryValley\LaravelStarter\Exceptions\EnvironmentFileException;
use Illuminate\Support\Str;

class UpdateEnvironmentAction
{
    /**
     * @var array{dockerServices: array<int, string>, selectedPackages: array<int, string>, appName: string, locale: string, database: string}
     */
    protected array $preferences;

    /**
     * Update environment file with user preferences and Docker service configurations
     *
     * Applies base configuration (app name, locale, mail settings) and configures
     * Redis and Minio services based on selected Docker services.
     *
     * @param  string  $path  Path to the environment file (.env or .env.example)
     * @param  array{dockerServices: array<int, string>, selectedPackages: array<int, string>, appName: string, locale: string, database: string}  $preferences
     */
    public function handle(string $path, array $preferences): void
    {
        $this->preferences = $preferences;

        $environment = $this->readEnvironmentFile($path);

        $environment = $this->applyBaseConfiguration($environment);

        $environment = $this->configureRedis($environment);
        $environment = $this->configureMinio($environment);

        $this->writeEnvironmentFile($path, $environment);
    }

    /**
     * Read environment file content and validate it exists
     */
    protected function readEnvironmentFile(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new EnvironmentFileException("Unable to read {$path} file");
        }

        return $content;
    }

    /**
     * Write content to environment file and validate success
     */
    protected function writeEnvironmentFile(string $path, string $content): void
    {
        if (file_put_contents($path, $content) === false) {
            throw new EnvironmentFileException("Unable to write {$path} file");
        }
    }

    /**
     * Apply base application configuration to environment content
     *
     * Updates app name, locale, faker locale, session driver, and mail settings.
     */
    protected function applyBaseConfiguration(string $content): string
    {
        $fakerLocale = sprintf('%s_%s', $this->preferences['locale'], Str::upper($this->preferences['locale']));

        $replacements = [
            'APP_NAME=Laravel' => "APP_NAME={$this->preferences['appName']}",
            'APP_LOCALE=en' => "APP_LOCALE={$this->preferences['locale']}",
            'APP_FAKER_LOCALE=en_US' => "APP_FAKER_LOCALE={$fakerLocale}",
            'SESSION_DRIVER=database' => 'SESSION_DRIVER=cookie',
            'MAIL_MAILER=log' => 'MAIL_MAILER=smtp',
            'MAIL_HOST=127.0.0.1' => 'MAIL_HOST=host.docker.internal',
            'MAIL_USERNAME=null' => 'MAIL_USERNAME="${APP_NAME}"',
            'MAIL_FROM_ADDRESS="hello@example.com"' => 'MAIL_FROM_ADDRESS="support@'.$this->preferences['database'].'.local"',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Configure Redis settings in environment content if Redis service is selected
     *
     * Updates session driver, queue connection, and cache store to use Redis.
     */
    protected function configureRedis(string $content): string
    {
        if (! in_array('redis', $this->preferences['dockerServices'])) {
            return $content;
        }

        $replacements = [
            'SESSION_DRIVER=cookie' => 'SESSION_DRIVER=redis',
            'QUEUE_CONNECTION=database' => 'QUEUE_CONNECTION=redis',
            'CACHE_STORE=database' => 'CACHE_STORE=redis',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Configure Minio (AWS S3 compatible) settings if Minio service is selected
     *
     * Updates filesystem disk to S3, sets AWS credentials for local development,
     * and configures S3 endpoints for Minio.
     */
    protected function configureMinio(string $content): string
    {
        if (! in_array('minio', $this->preferences['dockerServices'])) {
            return $content;
        }

        $content = str_replace('FILESYSTEM_DISK=local', 'FILESYSTEM_DISK=s3', $content);

        $projectName = basename(getcwd());

        $content = str_replace('AWS_USE_PATH_STYLE_ENDPOINT=false', implode("\n", [
            'AWS_USE_PATH_STYLE_ENDPOINT=true',
            'AWS_ENDPOINT=http://localhost:9000',
            "AWS_URL=http://minio.{$projectName}.orb.local:9000/public",
        ]), $content);

        $content = preg_replace('/AWS_ACCESS_KEY_ID=.*/', 'AWS_ACCESS_KEY_ID=sail', $content) ?? $content;
        $content = preg_replace('/AWS_SECRET_ACCESS_KEY=.*/', 'AWS_SECRET_ACCESS_KEY=password', $content) ?? $content;

        return preg_replace('/AWS_BUCKET=.*/', 'AWS_BUCKET=local', $content) ?? $content;
    }
}
