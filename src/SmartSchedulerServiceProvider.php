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
     *
     * @return void
     */
    public function register(): void
    {
        // Merge package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/smart-scheduler.php', 'smart-scheduler');
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Only publish and register console-specific functionality when running in the console
        if ($this->app->runningInConsole()) {
            // Publish configuration
            $this->publishes([
                __DIR__ . '/../config/smart-scheduler.php' => config_path('smart-scheduler.php'),
            ], 'config');

            // Register package commands if they exist (guard with class_exists to avoid hard dependency)
            $possibleCommands = [
                '\\Jiordiviera\\SmartScheduler\\LaravelSmartScheduler\\Commands\\SmartSchedulerCommand',
            ];

            $commandsToRegister = array_filter($possibleCommands, function ($class) {
                return class_exists($class);
            });

            if (! empty($commandsToRegister)) {
                $this->commands($commandsToRegister);
            }
        }
    }
}
