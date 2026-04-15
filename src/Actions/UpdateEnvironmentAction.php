<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

use Illuminate\Support\Str;

class UpdateEnvironmentAction
{
    /**
     * @param  array<int, string>  $dockerServices
     */
    public function handle(string $path, string $appName, string $locale, string $database, array $dockerServices = []): void
    {
        $content = (string) file_get_contents($path);

        $content = $this->applyBaseConfiguration($content, $appName, $locale, $database);

        if (in_array('redis', $dockerServices)) {
            $content = $this->configureRedis($content);
        }

        if (in_array('minio', $dockerServices) || in_array('rustfs', $dockerServices)) {
            $content = $this->configureS3($content);
        }

        file_put_contents($path, $content);
    }

    private function applyBaseConfiguration(string $content, string $appName, string $locale, string $database): string
    {
        $fakerLocale = $locale.'_'.Str::upper($locale);
        $appNameValue = str_contains($appName, ' ') ? "\"{$appName}\"" : $appName;

        return str_replace(
            ['APP_NAME=Laravel', 'APP_LOCALE=en', 'APP_FAKER_LOCALE=en_US', 'SESSION_DRIVER=database'],
            ["APP_NAME={$appNameValue}", "APP_LOCALE={$locale}", "APP_FAKER_LOCALE={$fakerLocale}", 'SESSION_DRIVER=cookie'],
            preg_replace('/^DB_DATABASE=.*/m', "DB_DATABASE={$database}", $content) ?? $content,
        );
    }

    private function configureRedis(string $content): string
    {
        return str_replace(
            ['SESSION_DRIVER=cookie', 'QUEUE_CONNECTION=database', 'CACHE_STORE=database'],
            ['SESSION_DRIVER=redis', 'QUEUE_CONNECTION=redis', 'CACHE_STORE=redis'],
            $content,
        );
    }

    private function configureS3(string $content): string
    {
        $content = str_replace('FILESYSTEM_DISK=local', 'FILESYSTEM_DISK=s3', $content);

        $content = str_replace('AWS_USE_PATH_STYLE_ENDPOINT=false', implode("\n", [
            'AWS_USE_PATH_STYLE_ENDPOINT=true',
            'AWS_ENDPOINT=http://localhost:9000',
        ]), $content);

        $content = preg_replace('/AWS_ACCESS_KEY_ID=.*/', 'AWS_ACCESS_KEY_ID=sail', $content) ?? $content;
        $content = preg_replace('/AWS_SECRET_ACCESS_KEY=.*/', 'AWS_SECRET_ACCESS_KEY=password', $content) ?? $content;

        return preg_replace('/AWS_BUCKET=.*/', 'AWS_BUCKET=local', $content) ?? $content;
    }
}
