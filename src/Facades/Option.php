<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Facades;

use Illuminate\Support\Facades\Facade;
use SpitsOnline\Options\Options;

/**
 * @method static mixed get(string $key, mixed $default = null)
 * @method static bool exists(string $key)
 * @method static array<string, mixed> all()
 * @method static void set(string|array<array-key, mixed> $key, mixed $value = null)
 * @method static bool remove(string ...$keys)
 * @method static string string(string $key, ?string $default = null)
 * @method static int integer(string $key, ?int $default = null)
 * @method static float float(string $key, ?float $default = null)
 * @method static bool boolean(string $key, ?bool $default = null)
 * @method static array<array-key, mixed> array(string $key, ?array<array-key, mixed> $default = null)
 * @method static void flush()
 *
 * @see Options
 */
class Option extends Facade
{
    /**
     * The store is scoped to a request or job, so resolve it every call
     * instead of holding on to the first one under Octane or a queue worker.
     *
     * @var bool
     */
    protected static $cached = false;

    protected static function getFacadeAccessor(): string
    {
        return Options::class;
    }
}
