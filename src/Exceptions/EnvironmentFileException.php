<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Exceptions;

use Exception;

class EnvironmentFileException extends Exception
{
    /**
     * Create exception for file read failure
     */
    public static function unableToRead(string $path): self
    {
        return new self("Unable to read {$path} file");
    }

    /**
     * Create exception for file write failure
     */
    public static function unableToWrite(string $path): self
    {
        return new self("Unable to write {$path} file");
    }
}
