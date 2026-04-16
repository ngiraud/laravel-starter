<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Installers;

use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Filesystem\Filesystem;

class LaravelBackupInstaller extends Installer
{
    public function __construct(private readonly Filesystem $files) {}

    public function install(Runner $runner): void
    {
        $runner->run('php artisan vendor:publish --provider="Spatie\\Backup\\BackupServiceProvider" --tag=backup-config');

        $this->files->copyDirectory($this->stubsPath('lang/vendor/backup'), lang_path('vendor/backup'));

        $this->configureBackup();
        $this->scheduleBackup();
    }

    private function configureBackup(): void
    {
        $path = base_path('config/backup.php');
        $config = (string) file_get_contents($path);

        $config = str_replace(
            "'name' => env('APP_NAME', 'laravel-backup')",
            "'name' => str_replace(['http://', 'https://'], '', env('APP_URL'))",
            $config,
        );

        $silencedNotifications = [
            'BackupWasSuccessfulNotification',
            'HealthyBackupWasFoundNotification',
            'CleanupWasSuccessfulNotification',
        ];

        foreach ($silencedNotifications as $notification) {
            $config = str_replace(
                "\\Spatie\\Backup\\Notifications\\Notifications\\{$notification}::class => ['mail'],",
                "\\Spatie\\Backup\\Notifications\\Notifications\\{$notification}::class => [],",
                $config,
            );
        }

        file_put_contents($path, $config);
    }

    private function scheduleBackup(): void
    {
        $path = base_path('routes/console.php');
        $console = (string) file_get_contents($path);

        $console .= "\n\nSchedule::command('backup:clean')->at('01:00');";
        $console .= "\nSchedule::command('backup:run')->at('01:30');";

        file_put_contents($path, $console);
    }
}
