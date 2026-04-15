<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

use Illuminate\Filesystem\Filesystem;

class PublishFilesAction
{
    private const string STUBS_PATH = __DIR__.'/../../stubs';

    public function __construct(private readonly Filesystem $files) {}

    public function publishConfigFiles(): void
    {
        $this->copy('pint.json.stub', base_path('pint.json'));
        $this->copy('AppServiceProvider.php.stub', app_path('Providers/AppServiceProvider.php'));
        $this->copy('User.php.stub', app_path('Models/User.php'));
        $this->copy('TestCase.php.stub', base_path('tests/TestCase.php'));
    }

    public function publishWebLocalFile(): void
    {
        $this->copy('web-local.php.stub', base_path('routes/web-local.php'));
        $this->appendWebRoutes();
    }

    public function publishLanguageFiles(string $locale): void
    {
        if ($locale === 'en') {
            return;
        }

        $directory = self::STUBS_PATH."/lang/{$locale}";
        $jsonFile = self::STUBS_PATH."/lang/{$locale}.json";

        if ($this->files->exists($directory)) {
            $this->files->copyDirectory($directory, lang_path($locale));
        }

        if ($this->files->exists($jsonFile)) {
            $this->files->copy($jsonFile, lang_path("{$locale}.json"));
        }
    }

    /**
     * Copy GitHub Actions workflows, keeping only those relevant to the project.
     * - tests.yml:     always, adapted to the selected database service
     * - lint.yml:      always
     * - phpstan.yml:   only if Larastan is installed
     * - rector.yml:    only if Rector is installed
     *
     * @param  array<int, string>  $dockerServices
     */
    public function publishGithubActions(array $dockerServices): void
    {
        /** @var \Illuminate\Support\Composer $composer */
        $composer = app('composer');

        $this->files->deleteDirectory(base_path('.github'));
        $this->files->copyDirectory(self::STUBS_PATH.'/.github', base_path('.github'));

        $this->selectCiWorkflow($dockerServices);

        if (! $composer->hasPackage('larastan/larastan')) {
            $this->files->delete(base_path('.github/workflows/phpstan.yml'));
        }

        if (! $composer->hasPackage('driftingly/rector-laravel')) {
            $this->files->delete(base_path('.github/workflows/rector.yml'));
        }
    }

    public function publishAiGuidelines(): void
    {
        $this->files->copyDirectory(self::STUBS_PATH.'/.ai/guidelines', base_path('.ai/guidelines'));
    }

    /**
     * Publish MakeActionCommand to the project so it survives package removal.
     * Also publishes the action stub it depends on.
     */
    public function publishMakeActionCommand(): void
    {
        $this->files->ensureDirectoryExists(app_path('Console/Commands'));
        $this->files->ensureDirectoryExists(base_path('stubs/commands'));

        $this->files->copy(
            self::STUBS_PATH.'/commands/MakeActionCommand.php.stub',
            app_path('Console/Commands/MakeActionCommand.php'),
        );

        $this->files->copy(
            self::STUBS_PATH.'/commands/action.stub',
            base_path('stubs/commands/action.stub'),
        );
    }

    /**
     * Append .claude/ to .gitignore so Boost-generated AI workspace files are not committed.
     */
    public function updateGitignore(): void
    {
        $path = base_path('.gitignore');
        $content = (string) file_get_contents($path);

        foreach (['/.claude'] as $entry) {
            if (! str_contains($content, $entry)) {
                $content .= "\n{$entry}";
            }
        }

        file_put_contents($path, $content);
    }

    public function updateConsoleFile(): void
    {
        $path = base_path('routes/console.php');
        $console = (string) file_get_contents($path);

        if (! str_contains($console, 'Schedule::')) {
            return;
        }

        $console = str_replace('use Illuminate\Support\Facades\Schedule;', '', $console);
        $console = str_replace(
            'use Illuminate\Support\Facades\Artisan;',
            "use Illuminate\Support\Facades\Artisan;\nuse Illuminate\Support\Facades\Schedule;",
            $console,
        );

        file_put_contents($path, $console);
    }

    private function copy(string $stub, string $destination): void
    {
        $this->files->copy(self::STUBS_PATH."/{$stub}", $destination);
    }

    private function appendWebRoutes(): void
    {
        $path = base_path('routes/web.php');
        $web = (string) file_get_contents($path);

        if (str_contains($web, "require __DIR__.'/web-local.php';")) {
            return;
        }

        $web .= <<<'PHP'


if (app()->isLocal()) {
    require __DIR__.'/web-local.php';
}
PHP;

        file_put_contents($path, $web);
    }

    /**
     * @param  array<int, string>  $dockerServices
     */
    private function selectCiWorkflow(array $dockerServices): void
    {
        if (in_array('pgsql', $dockerServices)) {
            $this->files->copy(
                base_path('.github/workflows/tests-pgsql.yml'),
                base_path('.github/workflows/tests.yml'),
            );
        } elseif (in_array('mysql', $dockerServices)) {
            $this->files->copy(
                base_path('.github/workflows/tests-mysql.yml'),
                base_path('.github/workflows/tests.yml'),
            );
        }

        $this->files->delete(base_path('.github/workflows/tests-mysql.yml'));
        $this->files->delete(base_path('.github/workflows/tests-pgsql.yml'));
    }
}
