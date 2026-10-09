<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | All options are loaded with one query the first time one is read, and
    | kept in memory for the rest of the request or queued job. With the cache
    | enabled they are also stored in the cache, so later requests don't query
    | the database at all. Writes through this package or the Option model
    | clear it. Running more than one server? Use a shared store (redis,
    | database, memcached), not `file` or `array`.
    |
    | `store` is a store from config/cache.php (null is your default store).
    | `ttl` is in seconds; null keeps the options until the next write.
    |
    */

    'cache' => [
        'enabled' => env('OPTIONS_CACHE', true),
        'store' => env('OPTIONS_CACHE_STORE'),
        'ttl' => env('OPTIONS_CACHE_TTL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Migrations
    |--------------------------------------------------------------------------
    |
    | The `options` table migration runs with your app's own `php artisan
    | migrate`. Turn this off if your app already creates the table in a
    | migration of its own.
    |
    */

    'migrations' => true,

];
