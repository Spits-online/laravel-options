<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Exceptions;

final class InvalidOptionType extends OptionsException
{
    public static function expected(string $key, string $type, mixed $value): self
    {
        $actual = get_debug_type($value);

        return new self("Option `{$key}` must be of type {$type}, {$actual} given.");
    }
}
