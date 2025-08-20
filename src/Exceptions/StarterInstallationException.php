<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Exceptions;

use Exception;

final class StarterInstallationException extends Exception
{
    public static function sailNotInstalled(): self
    {
        return new self('Laravel Sail is not installed. Please install it first with: composer require laravel/sail --dev');
    }

    public static function gitNotInitialized(): self
    {
        return new self('Git repository is not initialized. Please run: git init');
    }

    public static function packageInstallationFailed(string $packageName, string $error): self
    {
        return new self("Failed to install package {$packageName}: {$error}");
    }

    public static function fileOperationFailed(string $operation, string $file): self
    {
        return new self("Failed to {$operation} file: {$file}");
    }
}
