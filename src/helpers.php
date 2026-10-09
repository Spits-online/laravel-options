<?php

declare(strict_types=1);

use SpitsOnline\Options\Options;

if (! function_exists('option')) {
    /**
     * Get an option, set options by passing `[key => value]`, or get the
     * options store when called without arguments.
     *
     * @param  string|array<array-key, mixed>|null  $key
     * @return ($key is null ? Options : ($key is array ? null : mixed))
     */
    function option(string|array|null $key = null, mixed $default = null): mixed
    {
        $options = app(Options::class);

        if ($key === null) {
            return $options;
        }

        if (is_array($key)) {
            $options->set($key);

            return null;
        }

        return $options->get($key, $default);
    }
}

if (! function_exists('option_exists')) {
    function option_exists(string $key): bool
    {
        return app(Options::class)->exists($key);
    }
}
