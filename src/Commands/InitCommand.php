<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Actions\PublishFilesAction;
use BerryValley\LaravelStarter\Actions\UpdateEnvironmentAction;
use BerryValley\LaravelStarter\Support\Git;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'starter:init')]
class InitCommand extends Command
{
    public $signature = 'starter:init';

    public $description = 'Initialise git and configure environment files';

    public function handle(Git $git, PublishFilesAction $publishFiles, UpdateEnvironmentAction $updateEnv): int
    {
        $appName = text(
            label: 'Application name',
            default: Str::of(base_path())->basename()->pascal()->toString(),
            required: true,
        );

        $locale = (string) select(
            label: 'Locale',
            options: ['fr', 'en'],
            default: 'fr',
        );

        $database = Str::of(base_path())->basename()->snake()->toString();

        $git->init();
        $publishFiles->updateGitignore();
        $git->commit('Initial commit');

        $this->components->info('Updating environment files');
        $updateEnv->handle(base_path('.env'), $appName, $locale, $database);
        $updateEnv->handle(base_path('.env.example'), $appName, $locale, $database);
        $git->commit('Update environment files');

        $this->components->success('Done. Run starter:sail or starter:add to continue.');

        return self::SUCCESS;
    }
}
