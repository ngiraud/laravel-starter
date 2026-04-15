<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Actions\PublishFilesAction;
use BerryValley\LaravelStarter\Actions\UpdateComposerScriptsAction;
use BerryValley\LaravelStarter\Actions\UpdatePackageJsonAction;
use BerryValley\LaravelStarter\Support\Git;
use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'starter:publish')]
class PublishCommand extends Command
{
    public $signature = 'starter:publish';

    public $description = 'Publish configuration stubs and update project structure';

    public function handle(PublishFilesAction $publishFiles, UpdateComposerScriptsAction $updateScripts, UpdatePackageJsonAction $updatePackageJson, Git $git): int
    {
        $locale = (string) env('APP_LOCALE', 'en');

        $this->components->info('Publishing stub files');
        $publishFiles->publishConfigFiles();
        $publishFiles->publishWebLocalFile();
        $publishFiles->publishGithubActions($this->detectDockerServices());
        $publishFiles->publishLanguageFiles($locale);
        $publishFiles->updateConsoleFile();
        $publishFiles->updateGitignore();

        $this->components->info('Updating composer.json scripts');
        $updateScripts->handle();
        $updatePackageJson->handle();

        if (confirm('Publish AI guidelines? (.ai/guidelines)', default: true)) {
            $publishFiles->publishAiGuidelines();
        }

        if (confirm('Publish make:action command to your project? (allows removing this package)', default: true)) {
            $publishFiles->publishMakeActionCommand();
        }

        $git->commit('Publish stub files and update composer.json scripts');

        $this->components->success('Files published.');

        return self::SUCCESS;
    }

    /**
     * Detect active Docker services from environment variables set by sail:install.
     *
     * @return array<int, string>
     */
    private function detectDockerServices(): array
    {
        $services = [];

        $dbConnection = (string) env('DB_CONNECTION', 'sqlite');
        if (in_array($dbConnection, ['mysql', 'pgsql', 'mariadb'])) {
            $services[] = $dbConnection;
        }

        if (env('REDIS_HOST') !== '127.0.0.1') {
            $services[] = 'redis';
        }

        return $services;
    }
}
