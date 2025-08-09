<?php

namespace Packages\ToxicityFilter\Providers;

use Illuminate\Support\ServiceProvider;
use Packages\ToxicityFilter\Commands\TestToxicityCommand;
use Packages\ToxicityFilter\Contracts\ToxicityFilterInterface;
use Packages\ToxicityFilter\Services\ToxicityFilterService;

class ToxicityFilterServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(
            __DIR__ . '/../config/toxicity-filter.php', 'toxicity-filter'
        );

        // Register the main service
        $this->app->singleton('toxicity-filter', function ($app) {
            return new ToxicityFilterService(config('toxicity-filter'));
        });

        // Register the interface binding
        $this->app->bind(ToxicityFilterInterface::class, function ($app) {
            return $app->make('toxicity-filter');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/toxicity-filter.php' => config_path('toxicity-filter.php'),
        ], 'toxicity-filter-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../database/migrations/' => database_path('migrations'),
        ], 'toxicity-filter-migrations');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                TestToxicityCommand::class,
            ]);
        }
    }



    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            'toxicity-filter',
            ToxicityFilterInterface::class,
        ];
    }
}
