<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

use Exception;

class LaravelNightwatch extends ComposerPackage
{
    public string $name = 'Laravel NightWatch';

    public string $require = 'laravel/nightwatch';

    public bool $isDevRequirement = false;

    public bool $installByDefault = false;

    public function install(): void
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if ((($environment = file_get_contents($envPath))) === false) {
            throw new Exception('Unable to read .env file');
        }

        if ((($environmentExample = file_get_contents($envExamplePath))) === false) {
            throw new Exception('Unable to read .env.example file');
        }

        $environment .= "\nNIGHTWATCH_TOKEN=your-api-key\n";
        $environmentExample .= "\nNIGHTWATCH_TOKEN=your-api-key\n";

        file_put_contents($envPath, $environment);
        file_put_contents($envExamplePath, $environmentExample);
    }
}
