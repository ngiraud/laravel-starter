<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\TerminalCommand;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Composer;

abstract class ComposerPackage
{
    use InteractsWithIO;

    abstract public string $name {get; }

    abstract public string $require {get; }

    abstract public bool $isDevRequirement {get; }

    abstract public bool $installByDefault {get; }

    public string $version = 'default';

    public array $extraArguments = [];

    protected Filesystem $files;

    protected Composer $composer;

    public function __construct()
    {
        $this->files = app('files');
        $this->composer = app('composer');
    }

    abstract public function install(): void;

    final public function run(): void
    {
        if ($this->composer->hasPackage($this->require)) {
            $this->components->warn("{$this->name} is already installed. Moving to the next package.");

            return;
        }

        $this->output->newLine(2);

        $this->requirePackage();

        $this->install();
    }

    protected function requirePackage(): void
    {
        $arguments = collect()
            ->when(
                $this->version !== 'default',
                fn (Collection $arguments) => $arguments->push(sprintf('"%s"', $this->version)),
            )
            ->when(
                $this->isDevRequirement,
                fn (Collection $arguments) => $arguments->push('--dev'),
            )
            ->merge($this->extraArguments);

        $this->components->info(sprintf('Installing %s', $this->name));

        TerminalCommand::sail()->run("composer require {$this->require} {$arguments->implode(' ')}");
    }
}
