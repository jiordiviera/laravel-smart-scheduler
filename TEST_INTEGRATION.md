# Testing Laravel Smart Scheduler in a Real Application

## Method 1: Local Path Repository (Development)

### Step 1: Create Test Laravel App

```bash
cd ~/projects  # or wherever you want to create test app
composer create-project laravel/laravel scheduler-test
cd scheduler-test
```

### Step 2: Configure Composer to Use Local Package

Edit `composer.json` in your test app and add:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/home/jiordiviera/packages/laravel-smart-scheduler",
            "options": {
                "symlink": true
            }
        }
    ],
    "require": {
        "jiordiviera/laravel-smart-scheduler": "@dev"
    }
}
```

### Step 3: Install the Package

```bash
composer require jiordiviera/laravel-smart-scheduler:@dev
```

This will create a symlink to your local package, so any changes you make will be reflected immediately.

### Step 4: Publish Configuration and Run Migrations

```bash
php artisan vendor:publish --tag=smart-scheduler-config
php artisan vendor:publish --tag=smart-scheduler-migrations
php artisan migrate
```

### Step 5: Configure Environment

Edit `.env`:

```env
SMART_SCHEDULER_PURGE_DAYS=7
SMART_SCHEDULER_EMAIL_RECIPIENTS=test@example.com

# Configure mail for testing
MAIL_MAILER=log
```

### Step 6: Create Test Scheduled Tasks

**For Laravel 11+**, edit `routes/console.php`:

```php
<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('inspire')->everyMinute();

Schedule::call(function () {
    logger('Task executed successfully at ' . now());
})->everyMinute()->name('test-success-task');

Schedule::call(function () {
    throw new \Exception('Intentional failure for testing');
})->everyMinute()->name('test-failing-task');
```

**For Laravel 10**, edit `app/Console/Kernel.php`:

```php
<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('inspire')->everyMinute();

        $schedule->call(function () {
            logger('Task executed successfully at ' . now());
        })->everyMinute()->name('test-success-task');

        $schedule->call(function () {
            throw new \Exception('Intentional failure for testing');
        })->everyMinute()->name('test-failing-task');
    }
}
```

### Step 7: Test Scheduler Execution

```bash
# Run scheduler once
php artisan schedule:run

# Or run continuously (every minute)
php artisan schedule:work
```

### Step 8: Verify Tracking

```bash
# Check database records
php artisan tinker
```

In tinker:

```php
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

// See all executions
ScheduleRun::all();

// See failed tasks
ScheduleRun::where('status', 'failed')->get();

// See successful tasks
ScheduleRun::where('status', 'success')->get();

// Check latest execution
ScheduleRun::latest('started_at')->first();
```

### Step 9: Test Purge Command

```bash
# Dry run to see what would be deleted
php artisan smart-scheduler:purge --dry-run

# Actually purge old records
php artisan smart-scheduler:purge --days=1
```

### Step 10: Test Overlap Prevention

Create a long-running task:

```php
Schedule::call(function () {
    logger('Long task started');
    sleep(90); // Sleep for 90 seconds
    logger('Long task finished');
})->everyMinute()->name('long-running-task');
```

Run scheduler twice within the same minute:

```bash
php artisan schedule:run &
php artisan schedule:run
```

Check that second execution was ignored:

```php
ScheduleRun::where('task_identifier', 'long-running-task')
    ->where('status', 'ignored')
    ->exists(); // Should return true
```

---

## Method 2: Via GitHub Repository

If you've pushed to GitHub, you can test via VCS repository:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/jiordiviera/laravel-smart-scheduler"
        }
    ],
    "require": {
        "jiordiviera/laravel-smart-scheduler": "dev-main"
    }
}
```

---

## Method 3: Via Packagist (Production)

Once published to Packagist:

```bash
composer require jiordiviera/laravel-smart-scheduler
```

---

## Troubleshooting

### Package Not Found

If you get "Package not found", ensure:
1. The path in `repositories.url` is absolute and correct
2. The package name matches exactly
3. You're using `@dev` for local development

### Migrations Not Running

If migrations don't run automatically:

```bash
php artisan vendor:publish --tag=smart-scheduler-migrations
php artisan migrate
```

### Email Notifications Not Sending

Check:
1. Queue is configured: `QUEUE_CONNECTION=database` or `sync`
2. Queue worker is running: `php artisan queue:work`
3. Recipients are configured in `.env`

### No Executions Being Tracked

Verify:
1. Package is installed: `composer show jiordiviera/laravel-smart-scheduler`
2. Service provider is registered (auto-discovery should handle this)
3. Migrations have run: `php artisan migrate:status`
4. Events are being fired: Check Laravel scheduler is actually running

---

## Expected Results

After running `php artisan schedule:run`, you should see:

1. **Database records** in `smart_schedule_runs` table
2. **Email notifications** in `storage/logs/laravel.log` (if using `MAIL_MAILER=log`)
3. **Queue jobs** for email notifications
4. **Overlap prevention** working for concurrent executions
5. **Purge command** removing old records

---

## Quick Verification Script

Create `tests/verify-package.php` in test app:

```php
<?php

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

// Run this after executing scheduler
$total = ScheduleRun::count();
$success = ScheduleRun::where('status', 'success')->count();
$failed = ScheduleRun::where('status', 'failed')->count();
$ignored = ScheduleRun::where('status', 'ignored')->count();

echo "Total executions: {$total}\n";
echo "Successful: {$success}\n";
echo "Failed: {$failed}\n";
echo "Ignored (overlapping): {$ignored}\n";
```

Run with: `php tests/verify-package.php`
