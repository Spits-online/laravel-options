<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use SpitsOnline\Options\Tests\TestCase;

uses(TestCase::class)->in('Feature');

/**
 * Start a new request or queued job: scoped instances, like the loaded
 * options, are forgotten, but the cache stays.
 */
function nextRequest(): void
{
    app()->forgetScopedInstances();
}

/**
 * The number of queries the callback runs.
 */
function countQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();

    $callback();

    $count = count(DB::getQueryLog());

    DB::disableQueryLog();

    return $count;
}
