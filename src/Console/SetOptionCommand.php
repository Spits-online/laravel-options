<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Console;

use Illuminate\Console\Command;
use SpitsOnline\Options\Options;

final class SetOptionCommand extends Command
{
    protected $signature = 'option:set
                            {key : The option key}
                            {value : The option value}
                            {--json : Store the value as JSON, e.g. 5, true or \'["a","b"]\'}';

    protected $description = 'Set an option';

    public function handle(Options $options): int
    {
        $key = $this->argument('key');
        $value = $this->argument('value');

        if (! is_string($key) || ! is_string($value)) {
            return self::INVALID;
        }

        if ($this->option('json')) {
            try {
                $value = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                $this->components->error("The value is not valid JSON: {$e->getMessage()}.");

                return self::FAILURE;
            }
        }

        $options->set($key, $value);

        $this->components->info("Option [{$key}] set.");

        return self::SUCCESS;
    }
}
