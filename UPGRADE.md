# Upgrade guide

## From appstract/laravel-options to 1.0

This package replaces `appstract/laravel-options` 8.x. It uses the same `options` table, stores values as the same JSON and ships the same migration under the same name, so your data stays as it is and `php artisan migrate` won't run the migration again.

### Checklist

- [ ] Swap the packages:
  ```bash
  composer remove appstract/laravel-options
  composer require spits-online/laravel-options
  ```
- [ ] Replace the namespace `Appstract\Options` in your app (see below)
- [ ] Check `@option` for options that hold HTML (see below)
- [ ] With more than one server: make sure your default cache store is shared, or set `OPTIONS_CACHE_STORE` (see the README's [Caching](README.md#caching) section)
- [ ] Run `php artisan migrate`; it should say *Nothing to migrate*

`option()`, `option_exists()`, the `Option` facade alias, `@option`, `@optionExists` and `php artisan option:set` work as before.

### The namespace changed

**Why:** it's a different package.

| Before | After |
|---|---|
| `Appstract\Options\OptionFacade` | `SpitsOnline\Options\Facades\Option` |
| `Appstract\Options\Option` (the model) | `SpitsOnline\Options\Models\Option` |
| `Appstract\Options\OptionsServiceProvider` | `SpitsOnline\Options\OptionsServiceProvider` |

The global `Option` alias points to the new facade, so `use Option;` keeps working.

### `@option` escapes its output

**Why:** an option an admin can edit, such as a site title, could inject scripts into every page.

Before, `@option('footer_html')` printed the raw value. Now it's escaped like `{{ }}`. For an option that holds HTML you trust:

Before:

```blade
@option('footer_html')
```

After:

```blade
{!! option('footer_html') !!}
```

### `option()` without arguments returns the options store

**Why:** the store loads and caches every option; the old Eloquent model ran a query per call.

`option()->remove('key')` works as before. If you called Eloquent methods on it, such as `option()->where(...)`, use the model instead: `SpitsOnline\Options\Models\Option::query()->where(...)`.

### Writes outside the package need a cache flush

**Why:** options are cached across requests now.

Writes through `option()`, the facade, the `Option` model or the commands clear the cache for you. If you change the `options` table any other way, such as `DB::table('options')`, raw SQL or a database import, run `php artisan option:clear-cache` or call `Option::flush()` afterwards.
