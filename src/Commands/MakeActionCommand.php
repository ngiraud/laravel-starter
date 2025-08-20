<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

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
            ->pascal()
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

        return app_path(str_replace('\\', '/', $name).'.php');
    }

    /**
     * Get the console command arguments.
     *
     * @return array<int, array<int, int|string>>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the action already exists'],
        ];
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
