<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

final class MakeActionCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:action';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new action class';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Action';

    /**
     * Get the name input.
     */
    protected function getNameInput(): string
    {
        /** @var string $name */
        $name = $this->argument('name');

        return Str::of(mb_trim($name))
            ->replaceEnd('.php', '')
            ->replaceEnd('Action', '')
            ->append('Action')
            ->toString();
    }

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/commands/action.stub');
    }

    /**
     * Get the default namespace for the class.
     */
    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace.'\Actions';
    }

    /**
     * Get the destination class path.
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        // Use the app_path helper to get the correct path
        return app_path(str_replace('\\', '/', $name).'.php');
    }

    /**
     * Resolve the fully qualified path to the stub.
     */
    private function resolveStubPath(string $stub): string
    {
        $basePath = $this->laravel->basePath(mb_trim($stub, '/'));

        return file_exists($basePath)
            ? $basePath
            : __DIR__.'/../../'.$stub;
    }
}
