<?php

declare(strict_types=1);

namespace SpitsOnline\Options;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\View\Compilers\BladeCompiler;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use SpitsOnline\Options\Console\ClearOptionsCacheCommand;
use SpitsOnline\Options\Console\GetOptionCommand;
use SpitsOnline\Options\Console\ListOptionsCommand;
use SpitsOnline\Options\Console\RemoveOptionCommand;
use SpitsOnline\Options\Console\SetOptionCommand;

final class OptionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('options')
            ->hasConfigFile()
            ->hasCommands(
                SetOptionCommand::class,
                GetOptionCommand::class,
                RemoveOptionCommand::class,
                ListOptionsCommand::class,
                ClearOptionsCacheCommand::class,
            );
    }

    public function packageRegistered(): void
    {
        $this->mergeConfigRecursively();

        // Scoped, so each request and queued job starts with fresh options
        // under Octane and long-running queue workers.
        $this->app->scoped(Options::class, function (Application $app): Options {
            if (! filter_var(Config::get('options.cache.enabled'), FILTER_VALIDATE_BOOLEAN)) {
                return new Options;
            }

            $store = Config::get('options.cache.store');
            $ttl = Config::get('options.cache.ttl');

            return new Options(
                cache: $app->make(CacheFactory::class)->store(is_string($store) && $store !== '' ? $store : null),
                ttl: is_numeric($ttl) ? (int) $ttl : null,
            );
        });
    }

    public function packageBooted(): void
    {
        $migrations = __DIR__.'/../database/migrations';

        // The file keeps its original name, so apps that already ran the
        // migration from appstract/laravel-options don't run it again.
        $this->publishes([$migrations => database_path('migrations')], 'options-migrations');

        if (Config::boolean('options.migrations')) {
            $this->loadMigrationsFrom($migrations);
        }

        $this->callAfterResolving(BladeCompiler::class, function (BladeCompiler $blade): void {
            // Escaped like {{ }}; use {!! option('key') !!} for trusted HTML.
            $blade->directive('option', fn (string $expression): string => "<?php echo e(option({$expression})); ?>");
            $blade->if('optionExists', fn (string $key): bool => option_exists($key));
        });
    }

    /**
     * Laravel merges a package config one level deep, so an app that sets only
     * `cache.store` would lose `cache.enabled`. Merging the package file
     * underneath again, key by key, lets the app's `config/options.php` state
     * only what differs. The config cache already holds the merged result.
     */
    private function mergeConfigRecursively(): void
    {
        if ($this->app->configurationIsCached()) {
            return;
        }

        Config::set('options', self::merge(Arr::wrap(require __DIR__.'/../config/options.php'), Config::array('options', [])));
    }

    /**
     * Associative arrays merge recursively, lists are replaced wholesale.
     *
     * @param  array<array-key, mixed>  $base
     * @param  array<array-key, mixed>  $override
     * @return array<array-key, mixed>
     */
    private static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            $base[$key] = is_array($value)
                && is_array($base[$key] ?? null)
                && ! array_is_list($value)
                && ! array_is_list($base[$key])
                    ? self::merge($base[$key], $value)
                    : $value;
        }

        return $base;
    }
}
