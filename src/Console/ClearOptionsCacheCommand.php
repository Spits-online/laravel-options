<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Console;

use Illuminate\Console\Command;
use SpitsOnline\Options\Options;

final class ClearOptionsCacheCommand extends Command
{
    protected $signature = 'option:clear-cache';

    protected $description = 'Clear the options cache, after changing the options table by hand';

    public function handle(Options $options): int
    {
        $options->flush();

        $this->components->info('Options cache cleared.');

        return self::SUCCESS;
    }
}
