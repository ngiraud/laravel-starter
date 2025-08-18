<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use BerryValley\LaravelStarter\Facades\TerminalCommand;
use Exception;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Composer;

abstract class ComposerPackage
{
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
            throw new Exception("{$this->name} is already installed.\nMoving to the next package.");
        }

        $this->requirePackage();

        $this->install();
    }

    protected function requirePackage(): void
    {
        $arguments = collect();

        if ($this->version !== 'default') {
            $arguments->push(sprintf('"%s"', $this->version));
        }

        if ($this->isDevRequirement) {
            $arguments->push('--dev');
        }

        $arguments->merge($this->extraArguments);

        TerminalCommand::sail()->run("composer require {$this->require} {$arguments->implode(' ')}");
    }
}
