# Laravel Smart Scheduler

A Laravel package for intelligent scheduled task management with observability and reliability features.

## Features

- **Task Execution Tracking**: Record every scheduled task execution with status, duration, and output
- **Overlap Prevention**: Automatically prevent concurrent executions of the same task
- **Failure Notifications**: Email notifications for failed tasks (extensible to other channels)
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

The package automatically registers a service provider. Configure notification settings in `config/smart-scheduler.php`:

```php
return [
    'purge_days' => 7, // Days to retain execution records

    'notifications' => [
        'email' => [
            'recipients' => ['admin@example.com'],
        ],
    ],
];
```

Set environment variables:

```env
SMART_SCHEDULER_PURGE_DAYS=7
SMART_SCHEDULER_EMAIL_RECIPIENTS=admin@example.com,ops@example.com
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

## How It Works

The package uses Laravel's event system to intercept scheduled task lifecycle:

1. When a task starts (`ScheduledTaskStarting`), it checks for overlapping executions
2. If overlap detected, the new execution is ignored
3. If no overlap, a new record is created with `starting` status
4. When a task finishes (`ScheduledTaskFinished`), the record is updated with:
   - Final status (`success` or `failed`)
   - Duration
   - Output (if any)
   - Exception message (if failed)
5. If a task fails, email notifications are sent asynchronously

## Testing

Run the test suite:

```bash
composer test
```

Check code style:

```bash
composer pint
```

## License

MIT
