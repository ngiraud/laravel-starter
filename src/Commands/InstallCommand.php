<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Actions\PublishFilesAction;
use BerryValley\LaravelStarter\Actions\UpdateEnvironmentAction;
use BerryValley\LaravelStarter\Support\Git;
use BerryValley\LaravelStarter\Support\Runner;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Laravel\Prompts\Support\Logger;
use Laravel\Sail\Console\Concerns\InteractsWithDockerComposeServices;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\info;
use function Laravel\Prompts\intro;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\outro;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\select;
use function Laravel\Prompts\task;
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

    public function handle(Git $git, Filesystem $files, UpdateEnvironmentAction $updateEnv, PublishFilesAction $publishFiles): int
    {
        intro('Laravel Starter — Installation');

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
            ->filter(fn (array $p): bool => $p['default'])
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

        task('Initializing git', function (Logger $logger) use ($git, $publishFiles): bool {
            $git->init();
            $publishFiles->updateGitignore();
            $git->commit('Initial commit');

            return true;
        });
        info('✓ Git initialized');

        // ─── Environment files ────────────────────────────────────────────

        task('Updating environment files', function (Logger $logger) use ($updateEnv, $git, $appName, $locale, $database, $dockerServices): bool {
            $updateEnv->handle(base_path('.env'), $appName, $locale, $database, $dockerServices);
            $updateEnv->handle(base_path('.env.example'), $appName, $locale, $database, $dockerServices);
            $git->commit('Update environment files');

            return true;
        });
        info('✓ Environment files updated');

        // ─── Sail (optional) ──────────────────────────────────────────────

        if ($useSail) {
            $this->installSail($git, $dockerServices);
        }

        $runner = Runner::detect();

        // ─── Packages ─────────────────────────────────────────────────────

        foreach ($selectedKeys as $key) {
            $this->call('starter:add', ['package' => $key]);
        }

        // ─── Flysystem S3 adapter (implicit when Minio or RustFS is used) ─

        /** @var Composer $composer */
        $composer = app('composer');

        if (array_intersect(['minio', 'rustfs'], $dockerServices) !== [] && ! $composer->hasPackage('league/flysystem-aws-s3-v3')) {
            task('Installing Flysystem S3 adapter', function (Logger $logger) use ($runner, $git): bool {
                $runner->run('composer require league/flysystem-aws-s3-v3', $logger);
                $git->commit('Install Flysystem S3 adapter');

                return true;
            });
            info('✓ Flysystem S3 adapter installed');
        }

        // ─── Publish files ────────────────────────────────────────────────

        $this->call('starter:publish', [
            '--docker-services' => implode(',', $dockerServices),
        ]);

        // ─── Frontend dependencies ────────────────────────────────────────

        if (! $files->exists(base_path('node_modules'))) {
            task('Installing npm dependencies', function (Logger $logger) use ($runner): bool {
                $runner->run('npm install', $logger);

                return true;
            });
            info('✓ npm dependencies installed');
        }

        // ─── Migrations ───────────────────────────────────────────────────

        task('Running migrations', function (Logger $logger) use ($runner): bool {
            $runner->run('php artisan migrate:fresh', $logger);

            return true;
        });
        info('✓ Migrations ran');

        // ─── Rector + Pint ────────────────────────────────────────────────

        $this->call('starter:finalize');

        // ─── Self-remove ──────────────────────────────────────────────────

        if (confirm('Remove this starter package?', default: true)) {
            task('Removing starter package', function (Logger $logger) use ($runner, $git): bool {
                $runner->run('composer remove ngiraud/laravel-starter --dev', $logger);
                $git->commit('Remove laravel-starter package', 'chore');

                return true;
            });
            info('✓ Starter package removed');
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
        $this->requireSailInProject();

        if (! file_exists($this->composePath())) {
            $this->call('sail:install', ['--with' => implode(',', $dockerServices)]);
            $git->commit('Install Sail');
            info('✓ Sail installed');
        }

        $this->newLine();
        $this->line('  Open a new terminal and start your containers:');
        $this->line('  <fg=gray>➜</> <options=bold>./vendor/bin/sail up</>');

        pause('Press ENTER once the containers are running.');
    }

    private function requireSailInProject(): void
    {
        /** @var array<string, array<string, string>> $composerJson */
        $composerJson = json_decode((string) file_get_contents(base_path('composer.json')), true);

        if (isset($composerJson['require']['laravel/sail']) || isset($composerJson['require-dev']['laravel/sail'])) {
            return;
        }

        task('Adding laravel/sail as a dev dependency', function (Logger $logger): bool {
            Runner::local()->run('composer require --dev laravel/sail', $logger);

            return true;
        });
        info('✓ laravel/sail added');
    }

    private function displayCompletion(bool $useSail): void
    {
        $binary = $useSail ? './vendor/bin/sail ' : '';

        $this->newLine();
        outro('Installation complete!');
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
