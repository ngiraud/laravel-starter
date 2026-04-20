<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Actions;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

class PublishFilesAction
{
    private const string STUBS_PATH = __DIR__.'/../../stubs';

    public function __construct(private readonly Filesystem $files) {}

    public function publishConfigFiles(): void
    {
        $this->copy('pint.json.stub', base_path('pint.json'));
        $this->copy('AppServiceProvider.php.stub', app_path('Providers/AppServiceProvider.php'));
        $this->copy('TestCase.php.stub', base_path('tests/TestCase.php'));
    }

    /**
     * Publish the Action design pattern: abstract Action, Fakeable trait, EnhanceEnum trait,
     * test fixtures, and the make:action command + stub (so the package can be removed).
     */
    public function publishActionPattern(): void
    {
        $this->files->ensureDirectoryExists(app_path('Actions'));
        $this->copy('Actions/Action.php.stub', app_path('Actions/Action.php'));

        $this->files->ensureDirectoryExists(app_path('Support'));
        $this->copy('Support/Fakeable.php.stub', app_path('Support/Fakeable.php'));

        $this->files->ensureDirectoryExists(base_path('tests/Fixtures'));
        $this->copy('tests/Fixtures/FakeAction.php.stub', base_path('tests/Fixtures/FakeAction.php'));

        $this->files->ensureDirectoryExists(base_path('tests/Unit/Support'));
        $this->copy('tests/Unit/Support/FakeableTest.php.stub', base_path('tests/Unit/Support/FakeableTest.php'));

        $this->files->ensureDirectoryExists(app_path('Console/Commands'));
        $this->files->ensureDirectoryExists(base_path('stubs/commands'));
        $this->copy('commands/MakeActionCommand.php.stub', app_path('Console/Commands/MakeActionCommand.php'));
        $this->copy('commands/action.stub', base_path('stubs/commands/action.stub'));
    }

    public function publishEnhanceEnum(): void
    {
        $this->files->ensureDirectoryExists(app_path('Enums/Concerns'));
        $this->copy('Enums/Concerns/EnhanceEnum.php.stub', app_path('Enums/Concerns/EnhanceEnum.php'));
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
        /** @var Composer $composer */
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

    /**
     * @param  string|array<int, string>  $guidelines
     */
    public function publishAiGuidelines(string|array $guidelines): void
    {
        $this->files->ensureDirectoryExists(base_path('.ai/guidelines'));

        foreach ((array) $guidelines as $file) {
            $this->files->copy(self::STUBS_PATH."/.ai/guidelines/{$file}", base_path(".ai/guidelines/{$file}"));
        }
    }

    /**
     * Append Boost/AI agent directories to .gitignore so generated files are not committed.
     * These directories contain machine-specific paths (cwd, config) that differ between users.
     */
    public function updateGitignore(): void
    {
        $path = base_path('.gitignore');
        $content = (string) file_get_contents($path);

        foreach (['/.claude', '/.agents', '/.amp', '/.codex', '/.gemini', '/.junie', '/.kiro', '/.github/skills'] as $entry) {
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
