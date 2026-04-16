<?php

declare(strict_types=1);

use BerryValley\LaravelStarter\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

afterEach(function (): void {
    Mockery::close();
});
