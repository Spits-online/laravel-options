<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Exceptions;

final class InvalidOptionValue extends OptionsException
{
    public static function unencodable(string $key, \JsonException $previous): self
    {
        return new self("The value for option `{$key}` can't be stored as JSON: {$previous->getMessage()}.", previous: $previous);
    }

    public static function undecodable(string $key, \JsonException $previous): self
    {
        return new self("The stored value of option `{$key}` is not valid JSON: {$previous->getMessage()}. Store it again with option().", previous: $previous);
    }
}
