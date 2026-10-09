<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use SpitsOnline\Options\Options;

it('sets an option as a string', function () {
    $this->artisan('option:set', ['key' => 'count', 'value' => '5'])
        ->expectsOutputToContain('Option [count] set.')
        ->assertSuccessful();

    expect(option('count'))->toBe('5');
});

it('sets an option as JSON', function (string $json, mixed $expected) {
    $this->artisan('option:set', ['key' => 'value', 'value' => $json, '--json' => true])->assertSuccessful();

    expect(option('value'))->toBe($expected);
})->with([
    ['5', 5],
    ['true', true],
    ['["a","b"]', ['a', 'b']],
    ['"text"', 'text'],
]);

it('refuses invalid JSON', function () {
    $this->artisan('option:set', ['key' => 'value', 'value' => '{nope', '--json' => true])
        ->expectsOutputToContain('The value is not valid JSON')
        ->assertFailed();

    expect(option_exists('value'))->toBeFalse();
});

it('shows an option as JSON', function () {
    option(['tags' => ['a' => 'b/c']]);

    $this->artisan('option:get', ['key' => 'tags'])
        ->expectsOutput(json_encode(['a' => 'b/c'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
        ->assertSuccessful();
});

it('fails to show a missing option', function () {
    $this->artisan('option:get', ['key' => 'missing'])
        ->expectsOutputToContain('Option [missing] does not exist.')
        ->assertFailed();
});

it('removes options', function () {
    option(['a' => 1, 'b' => 2, 'c' => 3]);

    $this->artisan('option:remove', ['keys' => ['a', 'b']])
        ->expectsOutputToContain('Options removed.')
        ->assertSuccessful();

    expect(option()->all())->toBe(['c' => 3]);
});

it('warns when removing options that do not exist', function () {
    $this->artisan('option:remove', ['keys' => ['missing']])
        ->expectsOutputToContain('None of these options existed.')
        ->assertSuccessful();
});

it('lists options', function () {
    option(['b' => [1, 2], 'a' => 'text']);

    $this->artisan('option:list')
        ->expectsTable(['Key', 'Value'], [['a', '"text"'], ['b', '[1,2]']])
        ->assertSuccessful();
});

it('lists no options', function () {
    $this->artisan('option:list')
        ->expectsOutputToContain('There are no options yet.')
        ->assertSuccessful();
});

it('clears the cache', function () {
    option('anything');

    expect(Cache::has(Options::CACHE_KEY))->toBeTrue();

    $this->artisan('option:clear-cache')
        ->expectsOutputToContain('Options cache cleared.')
        ->assertSuccessful();

    expect(Cache::has(Options::CACHE_KEY))->toBeFalse();
});
