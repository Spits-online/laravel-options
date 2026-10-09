<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Console;

use Illuminate\Console\Command;
use SpitsOnline\Options\Options;

final class GetOptionCommand extends Command
{
    protected $signature = 'option:get {key : The option key}';

    protected $description = 'Show the value of an option as JSON';

    public function handle(Options $options): int
    {
        $key = $this->argument('key');

        if (! is_string($key)) {
            return self::INVALID;
        }

        if (! $options->exists($key)) {
            $this->components->error("Option [{$key}] does not exist.");

            return self::FAILURE;
        }

        $this->line((string) json_encode($options->get($key), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION));

        return self::SUCCESS;
    }
}
