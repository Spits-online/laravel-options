<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use SpitsOnline\Options\Facades\Option;
use SpitsOnline\Options\Models\Option as OptionModel;
use SpitsOnline\Options\Options;

beforeEach(function () {
    Option::set(['a' => 1, 'b' => 2]);
    nextRequest();
});

it('loads every option with one query, however often they are read', function () {
    $queries = countQueries(function () {
        foreach (range(1, 5) as $i) {
            option('a');
            option('b');
            option('missing', 'default');
            option_exists('a');
        }
    });

    expect($queries)->toBe(1);
});

it('serves later requests from the cache without querying', function () {
    option('a');
    nextRequest();

    expect(countQueries(fn () => option('a')))->toBe(0);
});

it('sees its own writes in the same request', function () {
    option('a');
    option(['a' => 10]);

    expect(option('a'))->toBe(10);
});

it('clears the cache when an option is set', function () {
    option('a');
    option(['a' => 10]);
    nextRequest();

    expect(option('a'))->toBe(10);
});

it('clears the cache when an option is removed', function () {
    option('a');
    option()->remove('a');
    nextRequest();

    expect(option_exists('a'))->toBeFalse();
});

it('clears the cache when the model is saved or deleted', function () {
    option('a');
    OptionModel::query()->where('key', 'a')->firstOrFail()->update(['value' => 'from the model']);
    nextRequest();

    expect(option('a'))->toBe('from the model');

    OptionModel::query()->where('key', 'a')->firstOrFail()->delete();
    nextRequest();

    expect(option_exists('a'))->toBeFalse();
});

it('keeps serving the cache after a direct database write until flushed', function () {
    option('a');
    DB::table('options')->where('key', 'a')->update(['value' => '99']);
    nextRequest();

    expect(option('a'))->toBe(1);

    Option::flush();

    expect(option('a'))->toBe(99);
});

it('starts every request or job with freshly loaded options', function () {
    $first = app(Options::class);
    nextRequest();

    expect(app(Options::class))->not->toBe($first);
});

it('does not cache values read inside a transaction', function () {
    DB::beginTransaction();
    option(['a' => 'uncommitted']);
    expect(option('a'))->toBe('uncommitted');
    DB::rollBack();

    nextRequest();

    expect(option('a'))->toBe(1)
        ->and(Cache::get(Options::CACHE_KEY))->toBe(['a' => '1', 'b' => '2']);
});

it('clears the cache again once a transaction commits', function () {
    DB::transaction(function () {
        option(['a' => 'committed']);

        // Another request caches the old value before this one commits.
        Cache::forever(Options::CACHE_KEY, ['a' => '1', 'b' => '2']);
    });

    nextRequest();

    expect(option('a'))->toBe('committed');
});

it('queries once per request when the cache is disabled', function () {
    config()->set('options.cache.enabled', false);
    nextRequest();

    expect(countQueries(fn () => [option('a'), option('b')]))->toBe(1);

    nextRequest();

    expect(countQueries(fn () => option('a')))->toBe(1)
        ->and(Cache::has(Options::CACHE_KEY))->toBeFalse();
});

it('accepts the cache setting as a string from the env', function () {
    config()->set('options.cache.enabled', 'false');
    nextRequest();
    option('a');

    expect(Cache::has(Options::CACHE_KEY))->toBeFalse();
});

it('uses the configured cache store', function () {
    config()->set('cache.stores.options', ['driver' => 'array']);
    config()->set('options.cache.store', 'options');
    nextRequest();

    option('a');

    expect(Cache::store('options')->has(Options::CACHE_KEY))->toBeTrue()
        ->and(Cache::store('array')->has(Options::CACHE_KEY))->toBeFalse();
});

it('expires the cache after the configured ttl', function () {
    config()->set('options.cache.ttl', '60');
    nextRequest();
    option('a');

    $this->travel(61)->seconds();
    nextRequest();

    expect(countQueries(fn () => option('a')))->toBe(1);
});

it('resolves the facade to the store of the current request', function () {
    Option::get('a');
    nextRequest();

    expect(Option::getFacadeRoot())->toBe(app(Options::class));
});
