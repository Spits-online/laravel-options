<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Console;

use Illuminate\Console\Command;
use SpitsOnline\Options\Options;

final class RemoveOptionCommand extends Command
{
    protected $signature = 'option:remove {keys* : One or more option keys}';

    protected $description = 'Remove one or more options';

    public function handle(Options $options): int
    {
        /** @var list<string> $keys */
        $keys = $this->argument('keys');

        if (! $options->remove(...$keys)) {
            $this->components->warn('None of these options existed.');

            return self::SUCCESS;
        }

        $this->components->info('Options removed.');

        return self::SUCCESS;
    }
}
