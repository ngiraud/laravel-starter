<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Exceptions;

use Exception;

class StarterInstallationException extends Exception
{
    /**
     * Create exception for missing Laravel Sail dependency
     */
    public static function sailNotInstalled(): self
    {
        return new self('Laravel Sail is not installed. Please install it first with: composer require laravel/sail --dev');
    }

    /**
     * Create exception for Git initialization failure
     */
    public static function gitNotInitialized(): self
    {
        return new self('Git repository is not initialized. Please run: git init');
    }

    /**
     * Create exception for package installation failure
     */
    public static function packageInstallationFailed(string $packageName, string $error): self
    {
        return new self("Failed to install package {$packageName}: {$error}");
    }

    /**
     * Create exception for file operation failure
     */
    public static function fileOperationFailed(string $operation, string $file): self
    {
        return new self("Failed to {$operation} file: {$file}");
    }
}
