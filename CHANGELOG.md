# Changelog

All notable changes to `laravel-options` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2026-10-09

First release. A drop-in replacement for `appstract/laravel-options` 8.x, using the same table, values and migration. See [UPGRADE.md](UPGRADE.md) to switch.

### Added
- Every option is loaded with one query on the first read and kept in memory for the rest of the request or queued job.
- Options are cached across requests in your cache store, and the cache is cleared on every write, including after a database transaction commits. Configure it with `OPTIONS_CACHE`, `OPTIONS_CACHE_STORE` and `OPTIONS_CACHE_TTL`.
- Typed getters: `Option::string()`, `integer()`, `float()`, `boolean()` and `array()`.
- `Option::all()`, and `Option::remove()` taking several keys.
- `Option::flush()` and `php artisan option:clear-cache` for when the table changes outside the package.
- `php artisan option:get`, `option:list` and `option:remove`, plus `option:set --json` to store numbers, booleans and arrays.
- `@optionExists` supports `@else`, `@endoptionExists` and `@unlessoptionExists`.
- An `OptionsException` base class for every exception.

### Changed
- `@option` escapes its output like `{{ }}`. Use `{!! option('key') !!}` for trusted HTML.
- The migration runs with the app's own `php artisan migrate`; publishing it is optional.

### Fixed
- Options can be `null`. Before, storing `null` failed on the non-nullable column.
- `@option` on an array value no longer prints `Array`; it throws an error, like `{{ }}` does.

[Unreleased]: https://github.com/Spits-online/laravel-options/compare/V1.0.0...HEAD
[1.0.0]: https://github.com/Spits-online/laravel-options/releases/tag/V1.0.0
