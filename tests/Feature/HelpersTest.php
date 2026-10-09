<?php

declare(strict_types=1);

use SpitsOnline\Options\Options;

it('returns the options store without arguments', function () {
    expect(option())->toBeInstanceOf(Options::class);
});

it('sets options with an array and returns null', function () {
    expect(option(['foo' => 'bar']))->toBeNull()
        ->and(option('foo'))->toBe('bar');
});

it('gets an option with a default', function () {
    expect(option('foo', 'baz'))->toBe('baz');

    option(['foo' => 'bar']);

    expect(option('foo', 'baz'))->toBe('bar');
});

it('checks whether an option exists', function () {
    expect(option_exists('foo'))->toBeFalse();

    option(['foo' => 'bar']);

    expect(option_exists('foo'))->toBeTrue();
});

it('removes an option through the store', function () {
    option(['foo' => 'bar']);

    expect(option()->remove('foo'))->toBeTrue();
    $this->assertDatabaseMissing('options', ['key' => 'foo']);
});

it('keeps the Option alias working', function () {
    option(['foo' => 'bar']);

    expect(Option::exists('foo'))->toBeTrue();
});
