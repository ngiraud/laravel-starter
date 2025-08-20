<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use BerryValley\LaravelStarter\Facades\TerminalCommand;
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
final class LaravelStarterCommand extends Command
{
    use InteractsWithDockerComposeServices;

    public $signature = 'starter:install';

    public $description = 'Prepare everything after a fresh Laravel installation';

    /**
     * @var array<int|string>
     */
    private array $dockerServices;

    /**
     * @var array<int, string>
     */
    private array $defaultDockerServices = ['mysql', 'redis', 'minio'];

    private Composer $composer;

    private Filesystem $files;

    private string $selectedLocale = 'fr';

    private PackagesCollection $composerPackages;

    public function handle(Filesystem $files): int
    {
        $this->files = $files;
        $this->composer = app('composer');

        if (! $this->composer->hasPackage('laravel/sail')) {
            $this->components->error('Please install Laravel Sail first.');

            return self::FAILURE;
        }

        /** @var array<int, string> $options */
        $options = [
            ...$this->defaultDockerServices,
            ...Arr::reject($this->services, fn ($service): bool => in_array($service, $this->defaultDockerServices)),
        ];

        $this->dockerServices = multiselect(
            label: 'Which services would you like to install?',
            options: $options,
            default: $this->defaultDockerServices,
        );

        /** @var array<int, string> $packages */
        $packages = config()->array('starter.packages', []);

        $this->composerPackages = PackagesCollection::from($packages);

        if (in_array('minio', $this->dockerServices)) {
            $this->composerPackages->addPackages(FlysystemAwsS3::class);
        }

        $this->editEnvironmentFiles();

        if (! $this->installSail()) {
            return self::FAILURE;
        }

        $this->installComposerPackages();

        $this->newLine(2);
        $this->copyFiles();
        $this->modifyConsoleFile();
        $this->modifyComposerFile();

        $this->newLine(2);
        $this->components->info('Packages have been installed.');

        $this->newLine(2);
        $this->installFrontendDependencies();
        $this->migrateDatabase();

        $this->newLine(2);
        $this->components->success('Installation completed! Now start the local server and enjoy!');
        $this->output->writeln('<fg=gray>➜</> <options=bold>./vendor/bin/sail composer dev</>');

        return self::SUCCESS;
    }

    private function editEnvironmentFiles(): void
    {
        $this->components->info('Editing .env and .env.example files');

        $projectName = Str::of(base_path())->basename();

        $database = $projectName->snake()->value();

        $defaultAppName = $projectName->pascal()->value();

        $appName = text(
            label: 'What is the name of your application?',
            default: $defaultAppName,
        );

        $appName = Str::of($appName)
            ->trim()
            ->wrap('"')
            ->value();

        $this->selectedLocale = (string) select(
            label: 'Which locale do you want to use?',
            options: ['fr', 'en'],
            default: 'fr',
        );

        $fakerLocale = sprintf('%s_%s', $this->selectedLocale, Str::upper($this->selectedLocale));

        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if ((($environment = file_get_contents($envPath))) === false) {
            throw new Exception('Unable to read .env file');
        }

        if ((($environmentExample = file_get_contents($envExamplePath))) === false) {
            throw new Exception('Unable to read .env.example file');
        }

        $environment = str_replace('APP_NAME=Laravel', "APP_NAME={$appName}", $environment);
        $environmentExample = str_replace('APP_NAME=Laravel', "APP_NAME={$appName}", $environmentExample);

        $environment = str_replace('APP_LOCALE=en', "APP_LOCALE={$this->selectedLocale}", $environment);
        $environmentExample = str_replace('APP_LOCALE=en', "APP_LOCALE={$this->selectedLocale}", $environmentExample);

        $environment = str_replace('APP_FAKER_LOCALE=en_US', "APP_FAKER_LOCALE={$fakerLocale}", $environment);
        $environmentExample = str_replace('APP_FAKER_LOCALE=en_US', "APP_FAKER_LOCALE={$fakerLocale}", $environmentExample);

        $environment = str_replace('SESSION_DRIVER=database', 'SESSION_DRIVER=cookie', $environment);
        $environmentExample = str_replace('SESSION_DRIVER=database', 'SESSION_DRIVER=cookie', $environmentExample);

        $environment = str_replace('MAIL_MAILER=log', 'MAIL_MAILER=smtp', $environment);
        $environment = str_replace('MAIL_HOST=127.0.0.1', 'MAIL_HOST=host.docker.internal', $environment);
        $environment = str_replace('MAIL_USERNAME=null', 'MAIL_USERNAME="${APP_NAME}"', $environment);
        $environment = str_replace('MAIL_FROM_ADDRESS="hello@example.com"', 'MAIL_FROM_ADDRESS="support@'.$database.'.local"', $environment);

        if (in_array('redis', $this->dockerServices)) {
            $environment = str_replace('SESSION_DRIVER=cookie', 'SESSION_DRIVER=redis', $environment);
            $environmentExample = str_replace('SESSION_DRIVER=cookie', 'SESSION_DRIVER=redis', $environmentExample);

            $environment = str_replace('QUEUE_CONNECTION=database', 'QUEUE_CONNECTION=redis', $environment);
            $environmentExample = str_replace('QUEUE_CONNECTION=database', 'QUEUE_CONNECTION=redis', $environmentExample);

            $environment = str_replace('CACHE_STORE=database', 'CACHE_STORE=redis', $environment);
            $environmentExample = str_replace('CACHE_STORE=database', 'CACHE_STORE=redis', $environmentExample);
        }

        if (in_array('minio', $this->dockerServices)) {
            $environment = str_replace('FILESYSTEM_DISK=local', 'FILESYSTEM_DISK=s3', $environment);
            $environmentExample = str_replace('FILESYSTEM_DISK=local', 'FILESYSTEM_DISK=s3', $environmentExample);

            $environment = str_replace('AWS_USE_PATH_STYLE_ENDPOINT=false', implode("\n", [
                'AWS_USE_PATH_STYLE_ENDPOINT=true',
                'AWS_ENDPOINT=http://localhost:9000',
                "AWS_URL=http://minio.{$projectName}.orb.local:9000/public",
            ]), $environment);

            $environment = preg_replace('/AWS_ACCESS_KEY_ID=.*/', 'AWS_ACCESS_KEY_ID=sail', (string) $environment);
            $environment = preg_replace('/AWS_SECRET_ACCESS_KEY=.*/', 'AWS_SECRET_ACCESS_KEY=password', (string) $environment);
            $environment = preg_replace('/AWS_BUCKET=.*/', 'AWS_BUCKET=local', (string) $environment);
        }

        file_put_contents($envPath, $environment);
        file_put_contents($envExamplePath, $environmentExample);
    }

    private function installSail(): bool
    {
        $this->components->info('Installing Sail');

        if (! file_exists(base_path('docker-compose.yml'))) {
            $this->call('sail:install', [
                '--with' => implode(',', $this->dockerServices),
            ]);
        }

        $this->newLine();
        $this->components->info('Open a new terminal and run the up command to start the containers.');
        $this->output->writeln('<fg=gray>➜</> <options=bold>./vendor/bin/sail up</>');
        $this->components->info('Then come back here to continue the installation.');

        return pause('Press ENTER to continue.');
    }

    private function installComposerPackages(): void
    {
        $this->newLine();

        /** @var array<string, string> $options */
        $options = $this->composerPackages->pluck('name', 'require');

        /** @var array<int, string> $default */
        $default = $this->composerPackages->installedByDefault()->pluck('require');

        /** @var array<int, string> $selected */
        $selected = multiselect(
            label: 'Which composer dependencies would you like to install?',
            options: $options,
            default: $default,
            scroll: 10
        );

        $this->composerPackages
            ->shouldInstall($selected)
            ->each(function (ComposerPackage $package): void {
                if ($this->composer->hasPackage($package->require)) {
                    $this->components->warn("{$package->name} is already installed. Moving to the next package.");

                    return;
                }

                $this->newLine(2);

                $this->components->info(sprintf('Installing %s', $package->name));

                $package->run();
            });
    }

    private function copyFiles(): void
    {
        $this->components->info('Publishing pint.json config file');
        $this->files->copy(__DIR__.'/../../stubs/pint/pint.json.stub', base_path('pint.json'));

        $this->components->info('Publishing AppServiceProvider class');
        $this->files->copy(__DIR__.'/../../stubs/providers/AppServiceProvider.php.stub', app_path('Providers/AppServiceProvider.php'));

        if ($this->selectedLocale !== 'en') {
            $this->components->info("Publishing language files for: {$this->selectedLocale}");

            if ($this->files->exists($path = __DIR__."/../../stubs/lang/{$this->selectedLocale}")) {
                $this->files->copyDirectory($path, lang_path($this->selectedLocale));
            }

            if ($this->files->exists($path = __DIR__."/../../stubs/lang/{$this->selectedLocale}.json")) {
                $this->files->copy($path, lang_path("{$this->selectedLocale}.json"));
            }
        }
    }

    private function modifyConsoleFile(): void
    {
        $path = base_path('routes/console.php');

        if ((($console = file_get_contents($path))) === false) {
            throw new Exception("Unable to read {$path} file");
        }

        if (! str_contains($console, 'Schedule::')) {
            return;
        }

        $this->components->info("Updating {$path}...");

        $console = str_replace('use Illuminate\Support\Facades\Schedule;', '', $console);

        $console = str_replace('use Illuminate\Support\Facades\Artisan;', implode("\n", [
            'use Illuminate\Support\Facades\Artisan;',
            'use Illuminate\Support\Facades\Schedule;',
        ]), $console);

        file_put_contents($path, $console);
    }

    private function modifyComposerFile(): void
    {
        $this->components->info('Modifying composer.json');

        $this->composer->modify(function (array $composer) {
            $commands = collect([
                ['color' => '#93c5fd', 'command' => 'php artisan pail --timeout=0', 'name' => 'logs'],
                ['color' => '#fdba74', 'command' => 'npm run dev', 'name' => 'vite'],
                ['color' => '#fdba74', 'command' => 'php artisan inertia:start-ssr', 'name' => 'ssr'],
            ]);

            $queueCommand = match ($this->composer->hasPackage('laravel/horizon')) {
                true => 'php artisan horizon',
                false => 'php artisan queue:listen database --tries=1 --queue=default',
            };

            $commands[] = ['color' => '#93c5fd', 'command' => $queueCommand, 'name' => 'queue'];

            $devCommands = $commands->where('name', '!=', 'ssr');
            $ssrCommands = $commands->where('name', '!=', 'vite');

            /** @var array<string,string|array<int,string>> $scripts */
            $scripts = $composer['scripts'] ?? [];

            $scripts['dev'] = [
                'Composer\\Config::disableProcessTimeout',
                sprintf(
                    'npx concurrently -c \"%s\" %s --names=%s --kill-others',
                    $devCommands->pluck('color')->implode(','),
                    $devCommands->map(fn (array $command): string => "\"{$command['command']}\"")->implode(' '),
                    $devCommands->pluck('name')->implode(',')
                ),
            ];

            $scripts['dev:ssr'] = [
                'npm run build:ssr',
                'Composer\\Config::disableProcessTimeout',
                sprintf(
                    'npx concurrently -c \"%s\" %s --names=%s --kill-others',
                    $ssrCommands->pluck('color')->implode(','),
                    $ssrCommands->map(fn (array $command): string => "\"{$command['command']}\"")->implode(' '),
                    $ssrCommands->pluck('name')->implode(',')
                ),
            ];

            $scripts['lint'] = [
                'pint --parallel',
            ];

            if ($hasRectorDependency = $this->composer->hasPackage('rector/rector')) {
                $scripts['refactor'] = [
                    'rector',
                ];
            }

            $scripts['test'] = [
                '@php artisan config:clear --ansi',
                sprintf('@php artisan test %s--compact --coverage --min=90', $this->composer->hasPackage('brianium/paratest') ? '--parallel' : ''),
            ];

            $scripts['test:lint'] = [
                'pint --parallel --test',
                'npm run lint',
                'npm run format:check',
            ];

            if ($this->composer->hasPackage('larastan/larastan')) {
                $scripts['test:types'] = [
                    'phpstan',
                ];
            }

            if ($hasRectorDependency) {
                $scripts['test:refactor'] = [
                    'rector --dry-run',
                ];
            }

            $composer['scripts'] = $scripts;

            return $composer;
        });
    }

    private function installFrontendDependencies(): void
    {
        if ($this->files->exists(base_path('node_modules'))) {
            return;
        }

        $this->components->info('Installing frontend dependencies');

        TerminalCommand::sail()->run('npm install');
    }

    private function migrateDatabase(): void
    {
        $this->components->info('Migrate database');

        TerminalCommand::sail()->run('php artisan migrate:fresh');
    }
}
