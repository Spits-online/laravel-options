<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Console;

use Illuminate\Console\Command;
use SpitsOnline\Options\Options;

final class ListOptionsCommand extends Command
{
    protected $signature = 'option:list';

    protected $description = 'List all options and their values as JSON';

    public function handle(Options $options): int
    {
        $all = $options->all();

        if ($all === []) {
            $this->components->info('There are no options yet.');

            return self::SUCCESS;
        }

        ksort($all);

        $this->table(['Key', 'Value'], array_map(
            fn (string $key, mixed $value): array => [$key, (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)],
            array_keys($all),
            $all,
        ));

        return self::SUCCESS;
    }
}
