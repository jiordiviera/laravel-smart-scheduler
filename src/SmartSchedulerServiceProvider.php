<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler;

use Illuminate\Support\ServiceProvider;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels\MailNotificationChannel;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels\SlackWebhookNotificationChannel;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels\TelegramWebhookNotificationChannel;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support\SmartSchedulerNotifier;

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

        $this->app->singleton(SmartSchedulerNotifier::class, function ($app) {
            return new SmartSchedulerNotifier([
                $app->make(MailNotificationChannel::class),
                $app->make(SlackWebhookNotificationChannel::class),
                $app->make(TelegramWebhookNotificationChannel::class),
            ]);
        });
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

            $this->publishAssets();

            // Register package commands (command classes are provided in the package)
            $this->commands([
                Commands\SmartScheduleRunCommand::class,
            ]);
        }
    }

    protected function publishAssets(): void
    {
        $configPath = __DIR__.'/../config/smart-scheduler.php';
        $this->publishes([
            $configPath => config_path('smart-scheduler.php'),
        ], 'config');
        $this->publishes([
            $configPath => config_path('smart-scheduler.php'),
        ], 'smart-scheduler-config');

        $migrationSource = __DIR__.'/../database/migrations/2025_10_29_000000_create_smart_scheduler_runs_table.php';
        $timestamp = date('Y_m_d_His');
        $targetMigration = database_path("migrations/{$timestamp}_create_smart_scheduler_runs_table.php");

        $this->publishes([
            $migrationSource => $targetMigration,
        ], 'migrations');

        $this->publishes([
            $migrationSource => $targetMigration,
        ], 'smart-scheduler-migrations');
    }
}
