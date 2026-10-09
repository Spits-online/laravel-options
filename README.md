<!--
  Keep this README in sync with the code. Every change to the public API, config,
  routes, channels, exceptions or the fake updates this file in the same commit:
  every feature has a working example here, and nothing is shown that doesn't exist.
-->

<div align="left">
  <a href="https://github.com/Spits-online/laravel-options">
    <picture>
      <source media="(prefers-color-scheme: dark)" srcset="https://raw.githubusercontent.com/Spits-online/laravel-options/main/art/banner-dark.png">
      <img alt="Laravel Options by Spits" src="https://raw.githubusercontent.com/Spits-online/laravel-options/main/art/banner-light.png">
    </picture>
  </a>

<h1>Cached database options for Laravel</h1>

[![Latest Version on Packagist](https://img.shields.io/packagist/v/spits-online/laravel-options.svg?style=flat-square)](https://packagist.org/packages/spits-online/laravel-options)
[![Tests](https://img.shields.io/github/actions/workflow/status/Spits-online/laravel-options/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/Spits-online/laravel-options/actions/workflows/run-tests.yml)
[![PHPStan](https://img.shields.io/github/actions/workflow/status/Spits-online/laravel-options/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/Spits-online/laravel-options/actions/workflows/phpstan.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/spits-online/laravel-options.svg?style=flat-square)](https://packagist.org/packages/spits-online/laravel-options)

</div>

Keep app-wide settings, like a site name, a maintenance banner or a feature
flag, in one `options` table and read them anywhere with `option()`. Every
option is loaded with a single query the first time you read one, then served
from memory and the cache, so calling `option()` twenty times in a request
costs one query at most.

```php
use SpitsOnline\Options\Facades\Option;

option(['site_name' => 'Spits', 'max_uploads' => 5]);

option('site_name'); // 'Spits'
option('footer_text', 'Made with care'); // 'Made with care'
Option::integer('max_uploads'); // 5
```

A drop-in replacement for
[appstract/laravel-options](https://github.com/appstract/laravel-options): same
table, same helpers and directives, and your existing options keep working.
See [Switching from appstract/laravel-options](#switching-from-appstractlaravel-options).

## Requirements

- PHP 8.3 or higher
- Laravel 12 or 13

## Installation

```bash
composer require spits-online/laravel-options
php artisan migrate
```

That's it: the migration runs with your app's own, without publishing, and
options are cached in your default cache store from the start. See
[Caching](#caching) to change the store.

## Usage

### Reading an option

```php
option('site_name');
```

Pass a default for when the option doesn't exist. A closure only runs when
it's needed:

```php
option('site_name', 'My app');
option('site_name', fn () => config('app.name'));
```

An option that exists with the value `null` returns `null`, not the default.

### Reading an option with a guaranteed type

The typed getters return the value as that type, or throw
`InvalidOptionType` when the stored value is something else. Like Laravel's
`Config::string()`, they're the safe choice when the value goes into typed
code.

```php
use SpitsOnline\Options\Facades\Option;

Option::string('site_name');
Option::integer('max_uploads', 10);
Option::float('vat_rate');
Option::boolean('maintenance_banner', false);
Option::array('allowed_countries', []);
```

### Setting options

Pass an array of `key => value` pairs. Several options are written in one
query.

```php
option(['site_name' => 'Spits']);

option([
    'max_uploads' => 5,
    'allowed_countries' => ['NL', 'BE'],
    'maintenance_banner' => false,
]);
```

Or through the facade:

```php
Option::set('site_name', 'Spits');
```

Values are stored as JSON, so you read back what JSON gives you: strings,
numbers, booleans, `null` and arrays keep their type, and objects come back as
arrays.

### Checking and removing options

```php
option_exists('site_name'); // true

option()->remove('site_name'); // true
Option::remove('max_uploads', 'allowed_countries'); // true
```

`remove()` returns whether any of the options existed.

### Reading all options

```php
Option::all(); // ['site_name' => 'Spits', 'max_uploads' => 5, ...]
```

### Using options in Blade

`@option` prints an option, escaped like `{{ }}`. Use `{!! !!}` only for HTML
you trust.

```blade
<title>@option('site_name', 'My app')</title>

{!! option('footer_html') !!}

@optionExists('maintenance_banner')
    <div class="banner">We're doing maintenance tonight.</div>
@endif

@optionExists('support_email')
    <a href="mailto:@option('support_email')">Contact us</a>
@else
    <a href="/contact">Contact us</a>
@endoptionExists
```

### Managing options from the console

`option:set` stores the value as a string:

```bash
php artisan option:set site_name Spits
```

Pass `--json` to store a number, boolean or array:

```bash
php artisan option:set max_uploads 5 --json
php artisan option:set allowed_countries '["NL","BE"]' --json
```

Read, list and remove options:

```bash
php artisan option:get max_uploads
php artisan option:list
php artisan option:remove max_uploads allowed_countries
```

### Querying the table directly

The `Option` model is there for anything the helpers don't cover. Saving or
deleting through it clears the cache too.

```php
use SpitsOnline\Options\Models\Option;

Option::query()->where('key', 'like', 'mail_%')->get();
```

## Caching

Caching is on by default; there's nothing to set up. The first read in a
request loads every option at once. After that:

- **Within a request or queued job**, options come from memory, so any number
  of reads costs no extra queries. Each new request or job, including under
  Octane and in long-running queue workers, loads them fresh.
- **Across requests**, options come from the cache, so a request often runs no
  options query at all. Writes through `option()`, the facade, the model or the
  commands clear the cache, and inside a database transaction they clear it
  again after the commit, so no request keeps reading the old values.

These optional env keys change the defaults:

```env
OPTIONS_CACHE_STORE=redis
OPTIONS_CACHE_TTL=3600
OPTIONS_CACHE=false
```

- `OPTIONS_CACHE_STORE` is a store from `config/cache.php`. Leave it out to use
  your default store.
- `OPTIONS_CACHE_TTL` is in seconds. Leave it out to keep the options cached
  until the next write.
- `OPTIONS_CACHE=false` turns the cache off. Options are then still loaded
  once per request and kept in memory.

There's no config file to publish. To change a default, create
`config/options.php` with **only the keys you change**. It's merged over the
[package defaults](config/options.php) key by key:

```php
<?php

return [
    'cache' => [
        'store' => 'redis',
    ],
];
```

Two things to know:

- **Running more than one server?** Use a shared store such as `redis`,
  `memcached` or `database`, not `file`. Otherwise a write on one server leaves
  the others with old values. With the `database` store (Laravel's default)
  the cache lookup is itself a query, but still only one per request.
- **Changed the `options` table another way**, such as in SQL, a
  `DB::table('options')` query or a seeder? Clear the cache:

```bash
php artisan option:clear-cache
```

```php
Option::flush();
```

## Error handling

Every exception extends `SpitsOnline\Options\Exceptions\OptionsException`, so
one `catch` covers them all.

| Exception | Thrown when |
|---|---|
| `InvalidOptionType` | A typed getter, such as `Option::integer()`, finds another type. |
| `InvalidOptionValue` | A value can't be stored as JSON (such as `NAN`), or a stored value isn't valid JSON. |

```php
use SpitsOnline\Options\Exceptions\InvalidOptionType;
use SpitsOnline\Options\Facades\Option;

try {
    $limit = Option::integer('max_uploads');
} catch (InvalidOptionType $e) {
    report($e);

    $limit = 10;
}
```

A corrupt row only breaks reads of that one option, never the others.

## Testing your app

Options live in your database, so there is nothing to fake. Use
`RefreshDatabase` and set the options a test needs. The cache is skipped
inside a database transaction, so tests never see values from another test.

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the maintenance banner', function () {
    option(['maintenance_banner' => true]);

    $this->get('/')->assertSee('We\'re doing maintenance tonight.');
});
```

## Switching from appstract/laravel-options

This package uses the same table, the same JSON values and the same migration
name, so your data stays as it is and `php artisan migrate` won't run the
migration again.

```bash
composer remove appstract/laravel-options
composer require spits-online/laravel-options
```

Then search your app for `Appstract\Options` and replace it. Read
[UPGRADE](UPGRADE.md) for the details, including the one change in behaviour:
`@option` now escapes its output.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed
recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security vulnerabilities

Please review [our security policy](../../security/policy) on how to report
security vulnerabilities.

## Credits

- [SpitsOnline](https://spits.online)
- [All Contributors](../../contributors)

This package started from [appstract/laravel-options](https://github.com/appstract/laravel-options)
by Gijs Jorissen and [Appstract](https://appstract.nl). Its idea, its API and its migration are
theirs; thank you for building it and sharing it under the MIT license.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more
information.
