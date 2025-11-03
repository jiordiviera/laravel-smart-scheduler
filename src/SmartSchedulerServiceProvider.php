<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler;

use Illuminate\Support\ServiceProvider;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/smart-scheduler.php' => config_path('smart-scheduler.php'),
            ], 'smart-scheduler-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'smart-scheduler-migrations');

            // $this->commands([
            //     // Register your commands here
            // ]);
        }
    }
}
