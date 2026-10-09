<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

beforeEach(fn () => option([
    'title' => 'Spits & co',
    'html' => '<strong>Bold</strong>',
]));

it('prints an option', function () {
    expect(Blade::render("@option('title')"))->toBe('Spits &amp; co');
});

it('escapes the option, like {{ }}', function () {
    option(['title' => '<script>alert(1)</script>']);

    expect(Blade::render("@option('title')"))->toBe('&lt;script&gt;alert(1)&lt;/script&gt;');
});

it('prints a default for a missing option', function () {
    expect(Blade::render("@option('missing', 'Default value')"))->toBe('Default value');
});

it('prints trusted HTML unescaped with {!! !!}', function () {
    expect(Blade::render("{!! option('html') !!}"))->toBe('<strong>Bold</strong>');
});

it('checks whether an option exists', function (string $template, string $expected) {
    expect(trim(Blade::render($template)))->toBe($expected);
})->with([
    'exists, closed with @endif' => ["@optionExists('title') yes @endif", 'yes'],
    'missing, closed with @endif' => ["@optionExists('missing') yes @endif", ''],
    'with @else' => ["@optionExists('missing') yes @else no @endoptionExists", 'no'],
    'unless' => ["@unlessoptionExists('missing') no @endoptionExists", 'no'],
]);

it('refuses to print an array', function () {
    option(['tags' => ['a', 'b']]);

    Blade::render("@option('tags')");
})->throws(ViewException::class, 'must be of type string, array given');
