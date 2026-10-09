<?php

declare(strict_types=1);

use SpitsOnline\Options\Models\Option;

it('casts the value to and from JSON', function () {
    Option::query()->create(['key' => 'tags', 'value' => ['a', 'b']]);

    $this->assertDatabaseHas('options', ['key' => 'tags', 'value' => '["a","b"]']);

    expect(Option::query()->where('key', 'tags')->firstOrFail()->value)->toBe(['a', 'b']);
});

it('stores null as JSON null', function () {
    Option::query()->create(['key' => 'nothing', 'value' => null]);

    $this->assertDatabaseHas('options', ['key' => 'nothing', 'value' => 'null']);
});

it('is the same data the store reads', function () {
    Option::query()->create(['key' => 'name', 'value' => 'Spits']);

    expect(option('name'))->toBe('Spits');
});
