<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use SpitsOnline\Options\OptionsServiceProvider;

it('runs the migration with the app migrations, without publishing', function () {
    expect(Schema::hasTable('options'))->toBeTrue()
        ->and(Schema::getColumnListing('options'))->toEqualCanonicalizing(['id', 'key', 'value']);
});

it('publishes the migration under its original name', function () {
    $paths = OptionsServiceProvider::pathsToPublish(OptionsServiceProvider::class, 'options-migrations');

    expect($paths)->toHaveCount(1)
        ->and(scandir((string) array_key_first($paths)))->toContain('2017_03_03_100000_create_options_table.php');
});

it('merges a partial config override with the defaults', function () {
    config()->set('options', ['cache' => ['store' => 'redis']]);

    (new OptionsServiceProvider(app()))->register();

    expect(config('options'))->toBe([
        'cache' => [
            'enabled' => true,
            'store' => 'redis',
            'ttl' => null,
        ],
        'migrations' => true,
    ]);
});

it('records the migration under the name appstract/laravel-options used', function () {
    // An app that already ran the appstract migration has this row, so
    // `php artisan migrate` skips ours instead of creating the table twice.
    expect(DB::table('migrations')->pluck('migration'))
        ->toContain('2017_03_03_100000_create_options_table');
});
