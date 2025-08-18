<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\TerminalCommand;
use Exception;

final class LaravelBackup extends ComposerPackage
{
    public string $name = 'Laravel Spatie Backups';

    public string $require = 'spatie/laravel-backup';

    public bool $isDevRequirement = false;

    public bool $installByDefault = true;

    public function install(): void
    {
        TerminalCommand::sail()->run('php artisan vendor:publish --provider="Spatie\\Backup\\BackupServiceProvider" --tag=backup-config');
        $this->files->copyDirectory(__DIR__.'/../../stubs/lang/vendor/backup', lang_path('vendor/backup'));

        $this->modifyConfigFile();
        $this->modifyConsoleFile();
    }

    private function modifyConfigFile(): void
    {
        $path = base_path('config/backup.php');

        if ((($config = file_get_contents($path))) === false) {
            throw new Exception("Unable to read {$path} file");
        }

        $config = str_replace(
            "'name' => env('APP_NAME', 'laravel-backup')",
            "'name' => str_replace(['http://', 'https://'], '', env('APP_URL'))",
            $config,
        );

        $config = str_replace(
            "\Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => ['mail'],",
            "\Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification::class => [],",
            $config,
        );

        $config = str_replace(
            "\Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => ['mail'],",
            "\Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification::class => [],",
            $config,
        );

        $config = str_replace(
            "\Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => ['mail'],",
            "\Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification::class => [],",
            $config,
        );

        file_put_contents($path, $config);
    }

    private function modifyConsoleFile(): void
    {
        $path = base_path('routes/console.php');

        $console = file_get_contents($path);

        $console .= "\n\nSchedule::command('backup:clean')->at('01:00');";
        $console .= "\nSchedule::command('backup:run')->at('01:30');";

        file_put_contents($path, $console);
    }
}
