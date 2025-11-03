<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Console\Commands\PurgeScheduleRunsCommand;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Contracts\SmartNotifierInterface;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Listeners\ScheduleRunListener;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\EmailNotifier;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SmartSchedulerManager;

class SmartSchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/smart-scheduler.php',
            'smart-scheduler'
        );

        // Register SmartSchedulerManager as singleton
        $this->app->singleton(SmartSchedulerManager::class);

        // Register SmartNotifierInterface implementation
        $this->app->singleton(SmartNotifierInterface::class, EmailNotifier::class);

        // Register ScheduleRunListener
        $this->app->singleton(ScheduleRunListener::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Load migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'smart-scheduler');

        if ($this->app->runningInConsole()) {
            // Publish config
            $this->publishes([
                __DIR__.'/../config/smart-scheduler.php' => config_path('smart-scheduler.php'),
            ], 'smart-scheduler-config');

            // Publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'smart-scheduler-migrations');

            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/smart-scheduler'),
            ], 'smart-scheduler-views');

            // Register commands
            $this->commands([
                PurgeScheduleRunsCommand::class,
            ]);
        }

        // Register event listener
        Event::subscribe(ScheduleRunListener::class);
    }
}
