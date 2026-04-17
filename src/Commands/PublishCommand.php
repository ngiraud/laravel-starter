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
    public $signature = 'starter:publish {--docker-services= : Comma-separated list of active Docker services}';

    public $description = 'Publish configuration stubs and update project structure';

    public function handle(PublishFilesAction $publishFiles, UpdateComposerScriptsAction $updateScripts, UpdatePackageJsonAction $updatePackageJson, Git $git): int
    {
        $locale = config()->string('app.locale', 'en');
        $dockerServices = $this->resolveDockerServices();

        $this->components->info('Publishing stub files');
        $publishFiles->publishConfigFiles();
        $publishFiles->publishWebLocalFile();
        $publishFiles->publishGithubActions($dockerServices);
        $publishFiles->publishLanguageFiles($locale);
        $publishFiles->updateConsoleFile();
        $publishFiles->updateGitignore();

        $this->components->info('Updating composer.json scripts');
        $updateScripts->handle();
        $updatePackageJson->handle();

        if (confirm('Publish AI guidelines? (.ai/guidelines)', default: true)) {
            $publishFiles->publishAiGuidelines(['conventions.md', 'testing.md']);
        }

        if (confirm('Publish Action design pattern? (Action, Fakeable + make:action command)', default: true)) {
            $publishFiles->publishActionPattern();
            $publishFiles->publishAiGuidelines('actions.md');
        }

        if (confirm('Publish EnhanceEnum trait? (app/Enums/Concerns/EnhanceEnum.php)', default: true)) {
            $publishFiles->publishEnhanceEnum();
            $publishFiles->publishAiGuidelines('enums.md');
        }

        $git->commit('Publish stub files and update composer.json scripts');

        $this->components->success('Files published.');

        return self::SUCCESS;
    }

    /**
     * Resolve Docker services from the --docker-services option or by detecting from environment.
     *
     * @return array<int, string>
     */
    private function resolveDockerServices(): array
    {
        /** @var string|null $option */
        $option = $this->option('docker-services');

        if ($option !== null) {
            return array_values(array_filter(explode(',', $option)));
        }

        return $this->detectDockerServices();
    }

    /**
     * Detect active Docker services from environment variables set by sail:install.
     *
     * @return array<int, string>
     */
    private function detectDockerServices(): array
    {
        $services = [];

        $dbConnection = config()->string('database.default', 'sqlite');
        if (in_array($dbConnection, ['mysql', 'pgsql', 'mariadb'])) {
            $services[] = $dbConnection;
        }

        if (config('database.redis.default.host') !== '127.0.0.1') {
            $services[] = 'redis';
        }

        return $services;
    }
}
