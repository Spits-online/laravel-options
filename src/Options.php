<?php

declare(strict_types=1);

namespace SpitsOnline\Options;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Connection;
use SpitsOnline\Options\Exceptions\InvalidOptionType;
use SpitsOnline\Options\Exceptions\InvalidOptionValue;
use SpitsOnline\Options\Models\Option;

/**
 * The options of the app. The first read loads every option with one query
 * (or none, from the cache); every later read in the same request or queued
 * job is served from memory.
 */
class Options
{
    public const string CACHE_KEY = 'spits-online.options';

    /**
     * Every option as its stored JSON, keyed by option key, or null until the
     * first read. Decoding happens per key, so one corrupt row only breaks
     * reads of that key.
     *
     * @var array<string, string>|null
     */
    private ?array $values = null;

    public function __construct(
        private readonly ?Cache $cache = null,
        private readonly ?int $ttl = null,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->load();

        if (! array_key_exists($key, $values)) {
            return value($default);
        }

        return Option::decode($key, $values[$key]);
    }

    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->load());
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $options = [];

        foreach ($this->load() as $key => $json) {
            $options[$key] = Option::decode($key, $json);
        }

        return $options;
    }

    /**
     * Store one option, or several at once as `[key => value]`, in one query.
     *
     * @param  string|array<array-key, mixed>  $key
     *
     * @throws InvalidOptionValue
     */
    public function set(string|array $key, mixed $value = null): void
    {
        $options = is_array($key) ? $key : [$key => $value];

        if ($options === []) {
            return;
        }

        $rows = [];

        foreach ($options as $optionKey => $optionValue) {
            $optionKey = (string) $optionKey;

            $rows[] = ['key' => $optionKey, 'value' => Option::encode($optionKey, $optionValue)];
        }

        Option::query()->upsert($rows, ['key'], ['value']);

        $this->flush();
    }

    /**
     * Remove one or more options. Returns whether any of them existed.
     */
    public function remove(string ...$keys): bool
    {
        if ($keys === []) {
            return false;
        }

        $deleted = Option::query()->whereIn('key', $keys)->delete();

        $this->flush();

        return $deleted > 0;
    }

    /**
     * @throws InvalidOptionType
     */
    public function string(string $key, ?string $default = null): string
    {
        $value = $this->get($key, $default);

        if (! is_string($value)) {
            throw InvalidOptionType::expected($key, 'string', $value);
        }

        return $value;
    }

    /**
     * @throws InvalidOptionType
     */
    public function integer(string $key, ?int $default = null): int
    {
        $value = $this->get($key, $default);

        if (! is_int($value)) {
            throw InvalidOptionType::expected($key, 'int', $value);
        }

        return $value;
    }

    /**
     * An int is accepted too, because JSON doesn't tell `2` and `2.0` apart
     * in values that were stored by hand.
     *
     * @throws InvalidOptionType
     */
    public function float(string $key, ?float $default = null): float
    {
        $value = $this->get($key, $default);

        if (! is_float($value) && ! is_int($value)) {
            throw InvalidOptionType::expected($key, 'float', $value);
        }

        return (float) $value;
    }

    /**
     * @throws InvalidOptionType
     */
    public function boolean(string $key, ?bool $default = null): bool
    {
        $value = $this->get($key, $default);

        if (! is_bool($value)) {
            throw InvalidOptionType::expected($key, 'bool', $value);
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>|null  $default
     * @return array<array-key, mixed>
     *
     * @throws InvalidOptionType
     */
    public function array(string $key, ?array $default = null): array
    {
        $value = $this->get($key, $default);

        if (! is_array($value)) {
            throw InvalidOptionType::expected($key, 'array', $value);
        }

        return $value;
    }

    /**
     * Forget the loaded options and clear the cache, so the next read comes
     * from the database. Writes through this package or the Option model do
     * this for you; call it after changing the table any other way.
     */
    public function flush(): void
    {
        $this->values = null;

        if ($this->cache === null) {
            return;
        }

        $this->cache->forget(self::CACHE_KEY);

        // Another request may cache the old values before this transaction
        // commits, so clear it again once the new values are visible.
        if ($this->connection()->transactionLevel() > 0) {
            $this->connection()->afterCommit(fn () => $this->cache->forget(self::CACHE_KEY));
        }
    }

    /**
     * @return array<string, string>
     */
    private function load(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        // Inside a transaction the database may hold values that are rolled
        // back later; those must never reach the shared cache.
        if ($this->cache === null || $this->connection()->transactionLevel() > 0) {
            return $this->values = $this->query();
        }

        /** @var array<string, string> $values */
        $values = $this->ttl === null
            ? $this->cache->rememberForever(self::CACHE_KEY, fn () => $this->query())
            : $this->cache->remember(self::CACHE_KEY, $this->ttl, fn () => $this->query());

        return $this->values = $values;
    }

    /**
     * @return array<string, string>
     */
    private function query(): array
    {
        /** @var array<string, string> */
        return Option::query()->toBase()->pluck('value', 'key')->all();
    }

    private function connection(): Connection
    {
        return (new Option)->getConnection();
    }
}
