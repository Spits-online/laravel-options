<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use SpitsOnline\Options\Facades\Option;
use SpitsOnline\Options\OptionsServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [OptionsServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        // Laravel registers it from composer.json `extra.laravel.aliases`.
        return ['Option' => Option::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        // The package loads its own migration, as it does in an app.
        $this->artisan('migrate')->run();
    }
}
