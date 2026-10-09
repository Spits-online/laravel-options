<?php

declare(strict_types=1);

namespace SpitsOnline\Options\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use SpitsOnline\Options\Tests\TestCase;

final class DisabledMigrationsTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        // Like an app's own config/options.php, set before the app boots.
        $app['config']->set('options.migrations', false);
    }

    #[Test]
    public function it_leaves_the_migration_to_the_app(): void
    {
        $this->assertFalse(Schema::hasTable('options'));
    }
}
