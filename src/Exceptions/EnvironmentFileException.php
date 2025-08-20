<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Exceptions;

use Exception;

final class EnvironmentFileException extends Exception
{
    public static function unableToRead(string $path): self
    {
        return new self("Unable to read {$path} file");
    }

    public static function unableToWrite(string $path): self
    {
        return new self("Unable to write {$path} file");
    }
}
