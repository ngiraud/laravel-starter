<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Actions\PublishFilesAction;
use BerryValley\LaravelStarter\Actions\UpdateComposerScriptsAction;
use BerryValley\LaravelStarter\Actions\UpdateEnvironmentAction;
use BerryValley\LaravelStarter\Support\Git;
use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Laravel\Sail\Console\Concerns\InteractsWithDockerComposeServices;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'starter:install')]
class InstallCommand extends Command
{
    use InteractsWithDockerComposeServices;

    public $signature = 'starter:install';

    public $description = 'Set up a fresh Laravel application';

    /**
     * @var array<int, string>
     */
    protected array $defaultDockerServices = ['pgsql', 'redis'];

    public function handle(
        Git $git,
        Filesystem $files,
        UpdateEnvironmentAction $updateEnv,
        PublishFilesAction $publishFiles,
        UpdateComposerScriptsAction $updateScripts,
    ): int {
        /** @var Composer $composer */
        $composer = app('composer');

        // ─── Collect preferences ──────────────────────────────────────────

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

        $useSail = confirm('Use Laravel Sail?', default: false);

        $dockerServices = [];
        if ($useSail) {
            $options = [
                ...$this->defaultDockerServices,
                ...array_filter($this->services, fn (string $s): bool => ! in_array($s, $this->defaultDockerServices)),
            ];

            /** @var array<int, string> $dockerServices */
            $dockerServices = multiselect(
                label: 'Which Sail services would you like to use?',
                options: $options,
                default: $this->defaultDockerServices,
            );
        }

        /** @var array<string, array{label: string, require: string, dev: bool, default: bool, version?: string, installer?: class-string}> $allPackages */
        $allPackages = config()->array('starter.packages', []);

        $defaults = collect($allPackages)
            ->filter(fn (array $p): bool => $p['default'] ?? false)
            ->keys()
            ->all();

        /** @var array<int, string> $selectedKeys */
        $selectedKeys = multiselect(
            label: 'Which packages would you like to install?',
            options: collect($allPackages)->mapWithKeys(fn (array $p, string $k): array => [$k => $p['label']])->all(),
            default: $defaults,
            scroll: 10,
        );

        $database = Str::of(base_path())->basename()->snake()->toString();

        // ─── Git init + initial commit ────────────────────────────────────

        $git->init();
        $publishFiles->updateGitignore();
        $git->commit('Initial commit');

        // ─── Environment files ────────────────────────────────────────────

        $this->components->info('Updating environment files');
        $updateEnv->handle(base_path('.env'), $appName, $locale, $database, $dockerServices);
        $updateEnv->handle(base_path('.env.example'), $appName, $locale, $database, $dockerServices);
        $git->commit('Update environment files');

        // ─── Sail (optional) ──────────────────────────────────────────────

        if ($useSail) {
            $this->installSail($git, $dockerServices);
        }

        $runner = $useSail ? Runner::forSail() : Runner::local();

        // ─── Packages ─────────────────────────────────────────────────────

        foreach ($selectedKeys as $key) {
            $package = $allPackages[$key];

            if ($composer->hasPackage($package['require'])) {
                $this->components->warn("{$package['label']} is already installed. Skipping.");

                continue;
            }

            $this->newLine();
            $this->components->info("Installing {$package['label']}");

            $dev = ($package['dev'] ?? false) ? ' --dev' : '';
            $version = isset($package['version']) ? " \"{$package['version']}\"" : '';
            $runner->run("composer require {$package['require']}{$version}{$dev}");

            if (isset($package['installer'])) {
                app($package['installer'])->install($runner);
            }

            $git->commit("Install {$package['label']}");
        }

        // ─── Flysystem S3 adapter (implicit when Minio or RustFS is used) ─

        if (array_intersect(['minio', 'rustfs'], $dockerServices) !== [] && ! $composer->hasPackage('league/flysystem-aws-s3-v3')) {
            $this->newLine();
            $this->components->info('Installing Flysystem S3 adapter (required for Minio/RustFS)');
            $runner->run('composer require league/flysystem-aws-s3-v3');
            $git->commit('Install Flysystem S3 adapter');
        }

        // ─── Publish files ────────────────────────────────────────────────

        $this->newLine();
        $this->components->info('Publishing files');
        $publishFiles->publishConfigFiles();
        $publishFiles->publishWebLocalFile();
        $publishFiles->publishGithubActions($dockerServices);
        $publishFiles->publishLanguageFiles($locale);
        $publishFiles->updateConsoleFile();
        $updateScripts->handle();

        if (confirm('Publish AI guidelines? (.ai/guidelines)', default: true)) {
            $publishFiles->publishAiGuidelines();
        }

        $publishFiles->publishMakeActionCommand();

        $git->commit('Publish stub files and update composer.json scripts');

        // ─── Frontend dependencies ────────────────────────────────────────

        if (! $files->exists(base_path('node_modules'))) {
            $this->newLine();
            $this->components->info('Installing npm dependencies');
            $runner->run('npm install');
        }

        // ─── Migrations ───────────────────────────────────────────────────

        $this->newLine();
        $this->components->info('Running migrations');
        $runner->run('php artisan migrate:fresh');

        // ─── Rector + Pint ────────────────────────────────────────────────

        $applied = [];

        if ($composer->hasPackage('driftingly/rector-laravel')) {
            $this->newLine();
            $this->components->info('Applying Rector rules');
            $runner->run('composer refactor');
            $applied[] = 'Rector';
        }

        if ($composer->hasPackage('laravel/pint')) {
            $this->newLine();
            $this->components->info('Applying Pint rules');
            $runner->run('composer lint');
            $applied[] = 'Pint';
        }

        if ($applied !== []) {
            $git->commit('Apply '.implode(' and ', $applied).' rules', 'chore');
        }

        // ─── Self-remove ──────────────────────────────────────────────────

        if (confirm('Remove this starter package? (make:action has been published to your project)', default: true)) {
            $runner->run('composer remove ngiraud/laravel-starter --dev');
            $git->commit('Remove laravel-starter package', 'chore');
        }

        // ─── Done ─────────────────────────────────────────────────────────

        $this->displayCompletion($useSail);

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $dockerServices
     */
    private function installSail(Git $git, array $dockerServices): void
    {
        $this->components->info('Installing Sail');

        if (! file_exists($this->composePath())) {
            $this->call('sail:install', ['--with' => implode(',', $dockerServices)]);
            $git->commit('Install Sail');
        }

        $this->newLine();
        $this->line('  Open a new terminal and start your containers:');
        $this->line('  <fg=gray>➜</> <options=bold>./vendor/bin/sail up</>');

        pause('Press ENTER once the containers are running.');
    }

    private function displayCompletion(bool $useSail): void
    {
        $binary = $useSail ? './vendor/bin/sail ' : '';

        $this->newLine(2);
        $this->components->success('Installation complete!');
        $this->newLine();
        $this->line('  Start the development server:');
        $this->line("  <fg=gray>➜</> <options=bold>{$binary}composer dev</>");
        $this->newLine();
        $this->line('  Push to your repository:');
        $this->line('  <fg=gray>➜</> <options=bold>git remote add origin git@github.com:your-org/your-project.git</>');
        $this->line('  <fg=gray>➜</> <options=bold>git push -u origin main</>');
        $this->newLine();
    }
}
