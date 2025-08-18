<?php

declare(strict_types=1);

namespace BerryValley\LaravelStarter\Packages;

final class FlysystemAwsS3 extends ComposerPackage
{
    public string $name = 'Flysystem AWS S3';

    public string $require = 'league/flysystem-aws-s3-v3';

    public string $version = '^3.0';

    public array $extraArguments = ['--with-all-dependencies'];

    public bool $isDevRequirement = false;

    public bool $installByDefault = true;

    public function install(): void {}
}
