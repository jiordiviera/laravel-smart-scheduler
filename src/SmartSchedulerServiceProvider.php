<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler;

use Illuminate\Support\ServiceProvider;

/**
 * Class SmartSchedulerServiceProvider
 *
 * Service provider for the Laravel Smart Scheduler package.
 */
class SmartSchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/smart-scheduler.php', 'smart-scheduler');
    }

    /**
     * Perform post-registration booting of services.
     */
    public function boot(): void
    {
        // Only publish and register console-specific functionality when running in the console
        if ($this->app->runningInConsole()) {
            // Load package migrations so host apps can migrate the runs table
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/smart-scheduler.php' => config_path('smart-scheduler.php'),
            ], 'config');

            // Register package commands (command classes are provided in the package)
            $this->commands([
                Commands\SmartScheduleRunCommand::class,
            ]);
        }
    }
}
