<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use SpitsOnline\Options\Exceptions\InvalidOptionType;
use SpitsOnline\Options\Exceptions\InvalidOptionValue;
use SpitsOnline\Options\Exceptions\OptionsException;
use SpitsOnline\Options\Facades\Option;
use SpitsOnline\Options\Options;

it('sets and gets an option', function () {
    Option::set('site_name', 'Spits');

    expect(Option::get('site_name'))->toBe('Spits');
});

it('stores values as JSON, compatible with appstract/laravel-options', function () {
    Option::set('foo', 'bar');

    $this->assertDatabaseHas('options', ['key' => 'foo', 'value' => '"bar"']);
});

it('reads rows written by appstract/laravel-options', function () {
    DB::table('options')->insert(['key' => 'legacy', 'value' => '{"a":[1,2]}']);

    expect(Option::get('legacy'))->toBe(['a' => [1, 2]]);
});

it('returns the default for a missing option', function () {
    expect(Option::get('missing'))->toBeNull()
        ->and(Option::get('missing', 'fallback'))->toBe('fallback')
        ->and(Option::get('missing', fn () => 'lazy'))->toBe('lazy');
});

it('keeps the types of the values', function (mixed $value) {
    Option::set('value', $value);
    nextRequest();

    expect(Option::get('value'))->toBe($value);
})->with([
    'string' => 'text',
    'int' => 42,
    'float' => 1.5,
    'float without fraction' => 2.0,
    'bool' => false,
    'list' => [['bar', 'baz']],
    'map' => [['a' => 1, 'b' => ['c' => true]]],
    'unicode' => 'Ünïcødé ✓',
]);

it('stores null, although the column is not nullable', function () {
    Option::set('nothing', null);

    expect(Option::exists('nothing'))->toBeTrue()
        ->and(Option::get('nothing', 'default'))->toBeNull();
});

it('sets several options in one query', function () {
    $queries = countQueries(fn () => Option::set(['a' => 1, 'b' => 2, 'c' => 3]));

    expect($queries)->toBe(1)
        ->and(Option::all())->toBe(['a' => 1, 'b' => 2, 'c' => 3]);
});

it('overwrites an existing option', function () {
    Option::set('foo', 'old');
    Option::set('foo', 'new');

    expect(Option::get('foo'))->toBe('new');
    $this->assertDatabaseCount('options', 1);
});

it('accepts integer keys', function () {
    Option::set([2026 => 'year']);

    expect(Option::get('2026'))->toBe('year');
});

it('ignores setting an empty array', function () {
    expect(countQueries(fn () => Option::set([])))->toBe(0);
});

it('checks whether an option exists', function () {
    expect(Option::exists('foo'))->toBeFalse();

    Option::set('foo', 'bar');

    expect(Option::exists('foo'))->toBeTrue();
});

it('removes options', function () {
    Option::set(['a' => 1, 'b' => 2, 'c' => 3]);

    expect(Option::remove('a'))->toBeTrue()
        ->and(Option::remove('b', 'c'))->toBeTrue()
        ->and(Option::remove('a'))->toBeFalse()
        ->and(Option::remove())->toBeFalse()
        ->and(Option::all())->toBe([]);
});

it('returns all options', function () {
    Option::set(['a' => 1, 'b' => ['x']]);

    expect(Option::all())->toBe(['a' => 1, 'b' => ['x']]);
});

it('throws when a value cannot be stored as JSON', function () {
    Option::set('broken', NAN);
})->throws(InvalidOptionValue::class, 'The value for option `broken` can\'t be stored as JSON');

it('throws only for the corrupt option when a stored value is not valid JSON', function () {
    DB::table('options')->insert([
        ['key' => 'fine', 'value' => '"ok"'],
        ['key' => 'corrupt', 'value' => '{not json'],
    ]);

    expect(Option::get('fine'))->toBe('ok')
        ->and(fn () => Option::get('corrupt'))->toThrow(InvalidOptionValue::class, 'option `corrupt` is not valid JSON');
});

describe('typed getters', function () {
    beforeEach(fn () => Option::set([
        'name' => 'Spits',
        'count' => 5,
        'ratio' => 0.5,
        'whole' => 3,
        'enabled' => true,
        'tags' => ['a', 'b'],
    ]));

    it('returns values of the right type', function () {
        expect(Option::string('name'))->toBe('Spits')
            ->and(Option::integer('count'))->toBe(5)
            ->and(Option::float('ratio'))->toBe(0.5)
            ->and(Option::float('whole'))->toBe(3.0)
            ->and(Option::boolean('enabled'))->toBeTrue()
            ->and(Option::array('tags'))->toBe(['a', 'b']);
    });

    it('returns the default for a missing option', function () {
        expect(Option::string('missing', 'x'))->toBe('x')
            ->and(Option::integer('missing', 1))->toBe(1)
            ->and(Option::float('missing', 1.5))->toBe(1.5)
            ->and(Option::boolean('missing', false))->toBeFalse()
            ->and(Option::array('missing', []))->toBe([]);
    });

    it('throws when the value has another type', function (string $method, string $key, string $message) {
        expect(fn () => Option::{$method}($key))->toThrow(InvalidOptionType::class, $message);
    })->with([
        ['string', 'count', 'Option `count` must be of type string, int given.'],
        ['integer', 'name', 'Option `name` must be of type int, string given.'],
        ['float', 'enabled', 'Option `enabled` must be of type float, bool given.'],
        ['boolean', 'count', 'Option `count` must be of type bool, int given.'],
        ['array', 'name', 'Option `name` must be of type array, string given.'],
        ['string', 'missing', 'Option `missing` must be of type string, null given.'],
    ]);
});

it('lets one catch cover every package exception', function () {
    expect(fn () => Option::integer('missing'))->toThrow(OptionsException::class)
        ->and(fn () => Option::set('broken', INF))->toThrow(OptionsException::class);
});

it('resolves the facade and the class to the same store', function () {
    expect(Option::getFacadeRoot())->toBe(app(Options::class));
});
