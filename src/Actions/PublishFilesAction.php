<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

use BerryValley\LaravelStarter\Exceptions\StarterInstallationException;
use Exception;
use Illuminate\Filesystem\Filesystem;

final readonly class PublishFilesAction
{
    private const string STUB_PATH = __DIR__.'/../../stubs';

    public function __construct(
        private Filesystem $files
    ) {}

    /**
     * Publish configuration files (Pint, AppServiceProvider, User model, TestCase)
     */
    public function publishConfigFiles(): void
    {
        $this->publishFile('pint.json.stub', base_path('pint.json'));
        $this->publishFile('AppServiceProvider.php.stub', app_path('Providers/AppServiceProvider.php'));
        $this->publishFile('User.php.stub', app_path('Models/User.php'));
        $this->publishFile('TestCase.php.stub', base_path('tests/TestCase.php'));
    }

    /**
     * Publish web-local.php file and update web.php to include it
     */
    public function publishWebLocalFile(): bool
    {
        $this->publishFile('web-local.php.stub', base_path('routes/web-local.php'));

        return $this->updateWebRoutes();
    }

    /**
     * Publish language files for the specified locale
     */
    public function publishLanguageFiles(string $locale): void
    {
        if ($locale === 'en') {
            return;
        }

        $languagePath = self::STUB_PATH."/lang/{$locale}";
        $languageFile = self::STUB_PATH."/lang/{$locale}.json";

        if ($this->files->exists($languagePath)) {
            $this->files->copyDirectory($languagePath, lang_path($locale));
        }

        if ($this->files->exists($languageFile)) {
            $this->files->copy($languageFile, lang_path("{$locale}.json"));
        }
    }

    /**
     * @param  array<int, string>  $dockerServices
     */
    public function publishGithubActions(array $dockerServices): void
    {
        $this->files->deleteDirectory(base_path('.github'));
        $this->files->copyDirectory(self::STUB_PATH.'/.github', base_path('.github'));

        $this->configureGithubWorkflows($dockerServices);
    }

    /**
     * Update console.php to properly organize use statements
     */
    public function updateConsoleFile(): bool
    {
        $path = base_path('routes/console.php');
        $console = file_get_contents($path);

        if ($console === false) {
            throw StarterInstallationException::fileOperationFailed('read', $path);
        }

        if (! str_contains($console, 'Schedule::')) {
            return false;
        }

        $console = str_replace('use Illuminate\Support\Facades\Schedule;', '', $console);
        $console = str_replace(
            'use Illuminate\Support\Facades\Artisan;',
            "use Illuminate\Support\Facades\Artisan;\nuse Illuminate\Support\Facades\Schedule;",
            $console
        );

        if (file_put_contents($path, $console) === false) {
            throw StarterInstallationException::fileOperationFailed('write', $path);
        }

        return true;
    }

    private function publishFile(string $stub, string $destination): void
    {
        $sourcePath = self::STUB_PATH.'/'.$stub;

        if (! $this->files->exists($sourcePath)) {
            throw StarterInstallationException::fileOperationFailed('find stub', $sourcePath);
        }

        try {
            $this->files->copy($sourcePath, $destination);
        } catch (Exception) {
            throw StarterInstallationException::fileOperationFailed('copy', $destination);
        }
    }

    private function updateWebRoutes(): bool
    {
        $path = base_path('routes/web.php');
        $web = file_get_contents($path);

        if ($web === false) {
            throw StarterInstallationException::fileOperationFailed('read', $path);
        }

        if (str_contains($web, "require __DIR__.'/web-local.php';")) {
            return false;
        }

        $web .= <<<'EOT'
        
        if (app()->isLocal()) {
            require __DIR__.'/web-local.php';
        }
        EOT;

        if (file_put_contents($path, $web) === false) {
            throw StarterInstallationException::fileOperationFailed('write', $path);
        }

        return true;
    }

    /**
     * @param  array<int, string>  $dockerServices
     */
    private function configureGithubWorkflows(array $dockerServices): void
    {
        if (in_array('mysql', $dockerServices)) {
            $this->files->copy(
                base_path('.github/workflows/tests-mysql.yml'),
                base_path('.github/workflows/tests.yml')
            );
        }

        if (in_array('pgsql', $dockerServices)) {
            $this->files->copy(
                base_path('.github/workflows/tests-pgsql.yml'),
                base_path('.github/workflows/tests.yml')
            );
        }

        $this->files->delete(base_path('.github/workflows/tests-mysql.yml'));
        $this->files->delete(base_path('.github/workflows/tests-pgsql.yml'));
    }
}
