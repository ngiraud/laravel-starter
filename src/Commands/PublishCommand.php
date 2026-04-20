<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Actions\PublishFilesAction;
use BerryValley\LaravelStarter\Actions\UpdateComposerScriptsAction;
use BerryValley\LaravelStarter\Actions\UpdatePackageJsonAction;
use BerryValley\LaravelStarter\Support\Git;
use Illuminate\Console\Command;
use Laravel\Prompts\Support\Logger;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\task;

#[AsCommand(name: 'starter:publish')]
class PublishCommand extends Command
{
    public $signature = 'starter:publish {--docker-services= : Comma-separated list of active Docker services}';

    public $description = 'Publish configuration stubs and update project structure';

    public function handle(PublishFilesAction $publishFiles, UpdateComposerScriptsAction $updateScripts, UpdatePackageJsonAction $updatePackageJson, Git $git): int
    {
        $locale = config()->string('app.locale', 'en');
        $dockerServices = $this->resolveDockerServices();

        task('Publishing stub files', function (Logger $logger) use ($publishFiles, $dockerServices, $locale): bool {
            $publishFiles->publishConfigFiles();
            $publishFiles->publishWebLocalFile();
            $publishFiles->publishGithubActions($dockerServices);
            $publishFiles->publishLanguageFiles($locale);
            $publishFiles->updateConsoleFile();
            $publishFiles->updateGitignore();

            return true;
        });
        info('✓ Stub files published');

        task('Updating composer.json scripts', function (Logger $logger) use ($updateScripts, $updatePackageJson): bool {
            $updateScripts->handle();
            $updatePackageJson->handle();

            return true;
        });
        info('✓ composer.json scripts updated');

        if (confirm('Publish AI guidelines? (.ai/guidelines)', default: true)) {
            task('Publishing AI guidelines', function (Logger $logger) use ($publishFiles): bool {
                $publishFiles->publishAiGuidelines(['conventions.md', 'testing.md']);

                return true;
            });
            info('✓ AI guidelines published');
        }

        if (confirm('Publish Action design pattern? (Action, Fakeable + make:action command)', default: true)) {
            task('Publishing Action pattern', function (Logger $logger) use ($publishFiles): bool {
                $publishFiles->publishActionPattern();
                $publishFiles->publishAiGuidelines('actions.md');

                return true;
            });
            info('✓ Action pattern published');
        }

        if (confirm('Publish EnhanceEnum trait? (app/Enums/Concerns/EnhanceEnum.php)', default: true)) {
            task('Publishing EnhanceEnum trait', function (Logger $logger) use ($publishFiles): bool {
                $publishFiles->publishEnhanceEnum();
                $publishFiles->publishAiGuidelines('enums.md');

                return true;
            });
            info('✓ EnhanceEnum trait published');
        }

        task('Committing changes', function (Logger $logger) use ($git): bool {
            $git->commit('Publish stub files and update composer.json scripts');

            return true;
        });
        info('✓ Changes committed');

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
