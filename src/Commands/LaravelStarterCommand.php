<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Actions\PublishFilesAction;
use BerryValley\LaravelStarter\Actions\UpdateComposerScriptsAction;
use BerryValley\LaravelStarter\Actions\UpdateEnvironmentAction;
use BerryValley\LaravelStarter\Exceptions\StarterInstallationException;
use BerryValley\LaravelStarter\Facades\ProcessRunner;
use BerryValley\LaravelStarter\Packages\ComposerPackage;
use BerryValley\LaravelStarter\Packages\FlysystemAwsS3;
use BerryValley\LaravelStarter\Support\PackagesCollection;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Composer;
use Illuminate\Support\Str;
use Laravel\Sail\Console\Concerns\InteractsWithDockerComposeServices;
use Symfony\Component\Console\Attribute\AsCommand;

use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'starter:install')]
class LaravelStarterCommand extends Command
{
    use InteractsWithDockerComposeServices;

    public $signature = 'starter:install';

    public $description = 'Prepare everything after a fresh Laravel installation';

    protected UpdateEnvironmentAction $updateEnvironmentAction;

    protected PublishFilesAction $publishFilesAction;

    protected UpdateComposerScriptsAction $updateComposerScriptsAction;

    /**
     * @var array<int, string>
     */
    protected array $dockerServices;

    /**
     * @var array<string, string>
     */
    protected array $selectedPackages;

    /**
     * @var array<int, string>
     */
    protected array $defaultDockerServices = ['pgsql', 'redis', 'rustfs'];

    protected Composer $composer;

    protected Filesystem $files;

    protected string $selectedLocale = 'fr';

    /**
     * Execute the console command to install Laravel starter kit
     *
     * This method orchestrates the complete installation process including:
     * - Validating prerequisites (Laravel Sail)
     * - Collecting user preferences
     * - Initializing Git repository
     * - Configuring environment files
     * - Installing Sail and packages
     * - Publishing configuration files
     * - Setting up development environment
     */
    public function handle(Filesystem $files): int
    {
        try {
            $this->files = $files;
            $this->composer = app('composer');

            $this->updateEnvironmentAction = app(UpdateEnvironmentAction::class);
            $this->publishFilesAction = app(PublishFilesAction::class, ['files' => $this->files]);
            $this->updateComposerScriptsAction = app(UpdateComposerScriptsAction::class);

            if (! $this->composer->hasPackage('laravel/sail')) {
                throw StarterInstallationException::sailNotInstalled();
            }

            $preferences = $this->collectUserPreferences();
            $this->dockerServices = $preferences['dockerServices'];
            $this->selectedPackages = $preferences['selectedPackages'];

            $this->selectedLocale = $preferences['locale'];

            $this->initializeGit();
            $this->updateEnvironmentFiles($preferences);

            if (! $this->installSail($this->dockerServices)) {
                return self::FAILURE;
            }

            $this->installComposerPackages();
            $this->publishFiles();
            $this->installFrontendDependencies();
            $this->migrateDatabase();
            $this->applyFinalOptimizations();

            $this->displayCompletionMessage();

            return self::SUCCESS;
        } catch (StarterInstallationException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Collect user preferences through interactive prompts
     *
     * Prompts the user to select:
     * - Docker services (MySQL, Redis, Minio, etc.)
     * - Application name and locale
     * - Composer packages to install
     *
     * @return array{dockerServices: array<int, string>, selectedPackages: array<string, string>, appName: string, locale: string, database: string}
     */
    protected function collectUserPreferences(): array
    {
        /** @var array<int, string> $options */
        $options = [
            ...$this->defaultDockerServices,
            ...Arr::reject($this->services, fn ($service): bool => in_array($service, $this->defaultDockerServices)),
        ];

        /** @var array<int, string> $dockerServices */
        $dockerServices = multiselect(
            label: 'Which services would you like to install?',
            options: $options,
            default: $this->defaultDockerServices,
        );

        $projectName = Str::of(base_path())->basename();
        $database = $projectName->snake()->value();
        $defaultAppName = $projectName->pascal()->value();

        $appName = text(
            label: 'What is the name of your application?',
            default: $defaultAppName,
        );

        $appName = Str::of($appName)->trim()->wrap('"')->value();

        $locale = (string) select(
            label: 'Which locale do you want to use?',
            options: ['fr', 'en'],
            default: 'fr',
        );

        /** @var array<int, string> $packages */
        $packages = config()->array('starter.packages', []);
        $composerPackages = PackagesCollection::from($packages);

        /** @var array<string, string> $packageOptions */
        $packageOptions = $composerPackages->pluck('name', 'require');

        /** @var array<int, string> $defaultPackages */
        $defaultPackages = $composerPackages->installedByDefault()->pluck('require');

        /** @var array<string, string> $selectedPackages */
        $selectedPackages = multiselect(
            label: 'Which composer dependencies would you like to install?',
            options: $packageOptions,
            default: $defaultPackages,
            scroll: 10
        );

        return [
            'dockerServices' => $dockerServices,
            'selectedPackages' => $selectedPackages,
            'appName' => $appName,
            'locale' => $locale,
            'database' => $database,
        ];
    }

    /**
     * Initialize Git repository if it doesn't exist and create initial commit
     */
    protected function initializeGit(): void
    {
        if (! $this->files->exists(base_path('.git'))) {
            $this->components->info(ProcessRunner::git()->initialize());
        }

        $this->commit('Initial commit');
    }

    /**
     * Update .env and .env.example files with user preferences
     *
     * Configures application settings, database, Redis, Minio, and other services
     * based on the selected Docker services and user preferences.
     *
     * @param  array{dockerServices: array<int, string>, selectedPackages: array<string, string>, appName: string, locale: string, database: string}  $preferences
     */
    protected function updateEnvironmentFiles(array $preferences): void
    {
        $this->components->info('Updating environment files');

        $this->updateEnvironmentAction->handle(base_path('.env'), $preferences);
        $this->updateEnvironmentAction->handle(base_path('.env.example'), $preferences);

        $this->commit('Update .env and .env.example files');
    }

    /**
     * Install Laravel Sail with selected Docker services
     *
     * Runs the sail:install command with the selected services and prompts
     * the user to start the containers before continuing.
     *
     * @param  array<int, string>  $dockerServices
     */
    protected function installSail(array $dockerServices): bool
    {
        $this->components->info('Installing Sail');

        if (! file_exists(base_path('docker-compose.yml'))) {
            $this->call('sail:install', [
                '--with' => implode(',', $dockerServices),
            ]);
        }

        $this->newLine();
        $this->components->info('Open a new terminal and run the up command to start the containers.');
        $this->output->writeln('<fg=gray>➜</> <options=bold>./vendor/bin/sail up</>');
        $this->components->info('Then come back here to continue the installation.');

        return pause('Press ENTER to continue.');
    }

    /**
     * Install selected Composer packages
     *
     * Installs the packages selected by the user, including any additional
     * packages required by the selected Docker services (like AWS S3 for Minio).
     * Each package installation is committed separately.
     */
    protected function installComposerPackages(): void
    {
        /** @var array<int, string> $packages */
        $packages = config()->array('starter.packages', []);
        $composerPackages = PackagesCollection::from($packages);

        if (in_array('minio', $this->dockerServices)) {
            $composerPackages->addPackages(FlysystemAwsS3::class);
        }

        $composerPackages->shouldInstall($this->selectedPackages)->each(function (ComposerPackage $package): void {
            if ($this->composer->hasPackage($package->require)) {
                $this->components->warn("{$package->name} is already installed. Skipping.");

                return;
            }

            $this->newLine(2);
            $this->components->info("Installing {$package->name}");

            try {
                $package->run();
                $this->commit("Installing {$package->name}");
            } catch (Exception $e) {
                throw StarterInstallationException::packageInstallationFailed($package->name, $e->getMessage());
            }
        });
    }

    /**
     * Publish configuration files and update project structure
     *
     * Publishes various stub files including Pint configuration, AppServiceProvider,
     * User model, TestCase, GitHub Actions workflows, language files, and updates
     * console.php and composer.json with development scripts.
     */
    protected function publishFiles(): void
    {
        $this->newLine();

        $this->components->info('Publishing configuration files');
        $this->publishFilesAction->publishConfigFiles();

        $this->components->info('Publishing web-local.php file');
        $this->publishFilesAction->publishWebLocalFile();

        $this->components->info('Publishing Github Actions');
        $this->publishFilesAction->publishGithubActions($this->dockerServices);

        if ($this->selectedLocale !== 'en') {
            $this->components->info("Publishing language files for: {$this->selectedLocale}");
            $this->publishFilesAction->publishLanguageFiles($this->selectedLocale);
        }

        $this->commit('Publishing stub files');

        $this->components->info('Updating console.php file');

        if ($this->publishFilesAction->updateConsoleFile()) {
            $this->commit('Modify console.php file');
        }

        $this->components->info('Modifying composer.json');

        $this->updateComposerScriptsAction->handle();
        $this->commit('Modify composer.json file');
    }

    /**
     * Install frontend dependencies using npm
     *
     * Skips installation if node_modules already exists.
     */
    protected function installFrontendDependencies(): void
    {
        if ($this->files->exists(base_path('node_modules'))) {
            return;
        }

        $this->newLine();
        $this->components->info('Installing frontend dependencies');
        ProcessRunner::sail()->run('npm install');
        $this->commit('Installing frontend dependencies');
    }

    /**
     * Run database migrations to set up the database schema
     */
    protected function migrateDatabase(): void
    {
        $this->components->info('Migrating database');
        ProcessRunner::sail()->run('php artisan migrate:fresh');
    }

    /**
     * Apply final code optimizations using Rector and Pint
     *
     * Runs Rector for code refactoring and Pint for code formatting
     * if these packages are installed, then commits the changes.
     */
    protected function applyFinalOptimizations(): void
    {
        $hasRector = $this->composer->hasPackage('rector/rector');
        $hasPint = $this->composer->hasPackage('laravel/pint');

        if ($hasRector) {
            $this->newLine();
            $this->components->info('Applying Rector rules');
            ProcessRunner::sail()->run('composer refactor');
        }

        if ($hasPint) {
            $this->newLine();
            $this->components->info('Applying Pint rules');
            ProcessRunner::sail()->run('composer lint');
        }

        if ($hasRector || $hasPint) {
            $message = collect([
                $hasRector ? 'Rector' : null,
                $hasPint ? 'Pint' : null,
            ])->filter()->implode(' and ');

            $this->commit("Applying {$message} rules", 'chore');
        }
    }

    /**
     * Create a Git commit with the specified message and semantic prefix
     *
     * @param  string  $message  The commit message
     * @param  string  $semantic  The semantic prefix (feat, fix, chore, etc.)
     */
    protected function commit(string $message, string $semantic = 'feat'): void
    {
        $this->newLine();
        $this->output->note(ProcessRunner::git()->commit($message, $semantic));
    }

    /**
     * Display completion message with instructions for next steps
     *
     * Shows success message and provides commands for starting development
     * server and setting up Git repository.
     */
    protected function displayCompletionMessage(): void
    {
        $this->newLine(2);
        $this->components->success('Installation completed!');
        $this->newLine();
        $this->components->info('Run the dev command to start the development server.');
        $this->output->writeln('<fg=gray>➜</> <options=bold>./vendor/bin/sail composer dev</>');
        $this->newLine();
        $this->components->info('Review and push your code to your repository.');
        $this->output->writeln('<fg=gray>➜</> <options=bold>git remote add origin git@github.com:your-username/your-project.git</>');
        $this->output->writeln('<fg=gray>➜</> <options=bold>git branch -M main</>');
        $this->output->writeln('<fg=gray>➜</> <options=bold>git push -u origin main</>');
        $this->newLine();
    }
}
