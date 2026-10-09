<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use SpitsOnline\Options\Exceptions\InvalidOptionValue;
use SpitsOnline\Options\Options;

/**
 * One row of the `options` table. Apps normally go through `option()` or the
 * `Option` facade; this model is for querying the table directly.
 *
 * @property int $id
 * @property string $key
 * @property mixed $value
 */
class Option extends Model
{
    public $timestamps = false;

    protected $table = 'options';

    protected $fillable = [
        'key',
        'value',
    ];

    protected static function booted(): void
    {
        // Saving through the model must not leave the cache behind.
        static::saved(fn () => app(Options::class)->flush());
        static::deleted(fn () => app(Options::class)->flush());
    }

    /**
     * Encode a value the way it is stored in the `value` column.
     *
     * @internal
     *
     * @throws InvalidOptionValue
     */
    public static function encode(string $key, mixed $value): string
    {
        try {
            return json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw InvalidOptionValue::unencodable($key, $e);
        }
    }

    /**
     * Decode a stored `value` column.
     *
     * @internal
     *
     * @throws InvalidOptionValue
     */
    public static function decode(string $key, string $json): mixed
    {
        try {
            return json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw InvalidOptionValue::undecodable($key, $e);
        }
    }

    /**
     * Stored as JSON. Unlike Eloquent's `json` cast, null is stored as the
     * JSON `null`, because the column is not nullable.
     *
     * @return Attribute<mixed, mixed>
     */
    protected function value(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): mixed => is_string($value) ? self::decode($this->keyName(), $value) : $value,
            set: fn (mixed $value): string => self::encode($this->keyName(), $value),
        );
    }

    /**
     * The key for exception messages; the value may be set before the key.
     */
    private function keyName(): string
    {
        $key = $this->attributes['key'] ?? null;

        return is_scalar($key) ? (string) $key : '';
    }
}
