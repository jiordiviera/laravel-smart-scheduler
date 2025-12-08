# Laravel Smart Scheduler

[![Latest Version on Packagist](https://img.shields.io/packagist/v/jiordiviera/laravel-smart-scheduler.svg?style=flat-square)](https://packagist.org/packages/jiordiviera/laravel-smart-scheduler)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/jiordiviera/laravel-smart-scheduler/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/jiordiviera/laravel-smart-scheduler/actions?query=workflow%3Atests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/jiordiviera/laravel-smart-scheduler/pint.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/jiordiviera/laravel-smart-scheduler/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/jiordiviera/laravel-smart-scheduler.svg?style=flat-square)](https://packagist.org/packages/jiordiviera/laravel-smart-scheduler)
[![License](https://img.shields.io/packagist/l/jiordiviera/laravel-smart-scheduler.svg?style=flat-square)](https://packagist.org/packages/jiordiviera/laravel-smart-scheduler)

A Laravel package for intelligent scheduled task management with observability, reliability features, and automatic stuck task detection.

## Features

- **Task Execution Tracking**: Record every scheduled task execution with status, duration, and output
- **Overlap Prevention**: Automatically prevent concurrent executions of the same task
- **Stuck Task Detection**: Automatically detect and handle tasks that are stuck (server crash, process killed)
- **Failure Notifications**: Email notifications for failed and stuck tasks (extensible to other channels)
- **Task History**: Maintain execution history with server information
- **Purge Command**: Clean up old execution records

## Requirements

- PHP 8.2+
- Laravel 11.x or 12.x

## Installation

Install via Composer:

```bash
composer require jiordiviera/laravel-smart-scheduler
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --tag=smart-scheduler-config
php artisan vendor:publish --tag=smart-scheduler-migrations
```

Run migrations:

```bash
php artisan migrate
```

## Configuration

The package automatically registers a service provider. Configure settings in `config/smart-scheduler.php`:

```php
return [
    // Days to retain execution records
    'purge_days' => 7,

    // Minutes before a task is considered stuck
    'stuck_timeout_minutes' => 60,

    'notifications' => [
        'email' => [
            'recipients' => ['admin@example.com'],
        ],
        'notify_on_stuck' => true,
    ],
];
```

Or use environment variables:

```env
SMART_SCHEDULER_PURGE_DAYS=7
SMART_SCHEDULER_STUCK_TIMEOUT=60
SMART_SCHEDULER_EMAIL_RECIPIENTS=admin@example.com,ops@example.com
SMART_SCHEDULER_NOTIFY_STUCK=true
```

## Usage

The package automatically tracks all scheduled tasks defined in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('backup:run')->daily();
Schedule::command('reports:generate')->hourly();
```

### Viewing Execution History

Query the `smart_schedule_runs` table:

```php
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

// Get recent executions
$runs = ScheduleRun::latest('started_at')->limit(10)->get();

// Get failed executions
$failed = ScheduleRun::where('status', ScheduleRun::STATUS_FAILED)->get();

// Get stuck executions
$stuck = ScheduleRun::where('status', ScheduleRun::STATUS_STUCK)->get();
```

### Purging Old Records

Remove old successful and ignored records:

```bash
# Purge records older than configured days
php artisan smart-scheduler:purge

# Purge with custom retention period
php artisan smart-scheduler:purge --days=30

# Dry run to preview what would be deleted
php artisan smart-scheduler:purge --dry-run
```

### Detecting Stuck Tasks

Manually detect and mark stuck tasks:

```bash
# Detect stuck tasks (default: 60 minutes timeout)
php artisan smart-scheduler:detect-stuck

# Custom timeout
php artisan smart-scheduler:detect-stuck --timeout=30

# Dry run (preview only)
php artisan smart-scheduler:detect-stuck --dry-run

# Without sending notifications
php artisan smart-scheduler:detect-stuck --no-notify
```

> **Note**: Stuck tasks are also automatically detected when a new execution of the same task starts.

### Recommended Scheduler Setup

Add these commands to your `routes/console.php` for automatic maintenance:

```php
use Illuminate\Support\Facades\Schedule;

// Purge old records weekly
Schedule::command('smart-scheduler:purge')->weekly();

// Detect stuck tasks every 30 minutes
Schedule::command('smart-scheduler:detect-stuck')->everyThirtyMinutes();
```

## How It Works

The package uses Laravel's event system to intercept scheduled task lifecycle:

```
┌─────────────────────────────────────────────────────────────────┐
│                    Task Execution Flow                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ScheduledTaskStarting                                          │
│         │                                                       │
│         ▼                                                       │
│  ┌──────────────┐     ┌──────────────┐                         │
│  │ Check stuck  │────▶│ Mark as stuck│ (if > timeout)          │
│  │ tasks        │     │ + notify     │                         │
│  └──────────────┘     └──────────────┘                         │
│         │                                                       │
│         ▼                                                       │
│  ┌──────────────┐     ┌──────────────┐                         │
│  │ Check overlap│────▶│ STATUS_IGNORED│ (if running)           │
│  └──────────────┘     └──────────────┘                         │
│         │                                                       │
│         ▼                                                       │
│  ┌──────────────┐                                              │
│  │STATUS_STARTING│                                              │
│  └──────────────┘                                              │
│         │                                                       │
│         ▼                                                       │
│  ScheduledTaskFinished                                          │
│         │                                                       │
│         ▼                                                       │
│  ┌──────────────┐     ┌──────────────┐                         │
│  │ Exit code = 0│────▶│STATUS_SUCCESS │                         │
│  └──────────────┘     └──────────────┘                         │
│         │                                                       │
│         ▼                                                       │
│  ┌──────────────┐     ┌──────────────┐                         │
│  │ Exit code ≠ 0│────▶│STATUS_FAILED  │ + notify               │
│  └──────────────┘     └──────────────┘                         │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### Task Statuses

| Status | Description |
|--------|-------------|
| `starting` | Task is currently running |
| `success` | Task completed successfully (exit code 0) |
| `failed` | Task failed (exit code ≠ 0) |
| `ignored` | Task skipped due to overlap |
| `stuck` | Task was running too long and marked as stuck |

## Extending Notifications

Implement `SmartNotifierInterface` to add custom notification channels:

```php
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Contracts\SmartNotifierInterface;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

class SlackNotifier implements SmartNotifierInterface
{
    public function sendFailureNotification(ScheduleRun $run): void
    {
        // Send to Slack
    }

    public function sendStuckNotification(ScheduleRun $run): void
    {
        // Send to Slack
    }
}
```

Register in a service provider:

```php
$this->app->singleton(SmartNotifierInterface::class, SlackNotifier::class);
```

## Testing

```bash
composer test
```

## Code Style

```bash
# Check
composer pint

# Fix
composer pint:fix
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Credits

- [Jiordi Viera](https://github.com/jiordiviera)
- [All Contributors](../../contributors)
