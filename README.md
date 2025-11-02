# Laravel Smart Scheduler

Laravel Smart Scheduler wraps Laravel’s native `schedule:run` command with guard rails, logging, and proactive notifications so that cron jobs are observable and reliable.

---

## Highlights

- **Track every run** – Each execution is stored with status, timings, duration, and error message.
- **Prevent overlaps** – Blocks concurrent runs unless `--force` is provided.
- **Detect stuck jobs** – Marks long running executions as failed once they exceed `stuck_after_minutes`.
- **Alert on incidents** – Notify by Mail, Slack webhook, or Telegram when runs fail or get stuck.
- **Pluggable infrastructure** – Store logs on the default database or point to a dedicated connection.

---

## Requirements

- PHP 8.2 or newer
- Laravel 10.x or 11.x
- Any database supported by Laravel (SQLite/MySQL/PostgreSQL/etc.)

---

## Installation

1. Require the package (add a local path repository if you use it inside a monorepo):

   ```bash
   composer require jiordiviera/laravel-smart-scheduler
   ```

2. Publish configuration and migrations, then migrate:

   ```bash
   php artisan vendor:publish --tag=smart-scheduler-config
   php artisan vendor:publish --tag=smart-scheduler-migrations
   php artisan migrate
   ```

   > ⛏️ The migrations create the `smart_scheduler_runs` table used by the wrapper. If you skip this step, `smart-schedule:run` exits with a clear error so you know to migrate first.

---

## Configuration

Key options in `config/smart-scheduler.php`:

- `enabled`: disable the wrapper and fall back to the native scheduler when set to `false`.
- `connection`: optional database connection name used to persist runs (defaults to the app connection). Set `SMART_SCHEDULER_CONNECTION` to isolate logs.
- `prevent_overlaps`: toggle automatic overlap detection.
- `stuck_after_minutes`: minutes before a running job is considered stuck and marked as failed.
- `notifications`: configure channels and credentials.
  - `channels`: array of enabled channels (`mail`, `slack`, `telegram`).
  - `mail.to`: email recipient.
  - `slack.webhook_url`: Slack incoming webhook.
  - `telegram.bot_token` / `telegram.chat_id`: Telegram Bot API credentials.

Example `.env` additions:

```env
SMART_SCHEDULER_CONNECTION=scheduler_logs
SMART_SCHEDULER_NOTIFICATIONS_ENABLED=true
SMART_SCHEDULER_NOTIFICATIONS_CHANNELS=mail,slack
SMART_SCHEDULER_MAIL_TO=ops@example.com
SMART_SCHEDULER_SLACK_WEBHOOK=https://hooks.slack.com/services/xxx/yyy/zzz
```

If you specify `SMART_SCHEDULER_CONNECTION`, make sure the connection exists in `config/database.php` and run the published migration against that database.

---

## Usage

1. Update your cron to call the smart scheduler wrapper:

   ```bash
   * * * * * php /path/to/artisan smart-schedule:run >> /dev/null 2>&1
   ```

2. Run it manually to confirm everything is wired up:

   ```bash
   php artisan smart-schedule:run
   ```

During each execution a row is inserted in `smart_scheduler_runs` with the command name, status (`running`, `success`, `failed`, `skipped`), start/end timestamps, duration in milliseconds, and any captured error message.

When notifications are enabled, failures and stuck runs trigger the appropriate channels automatically.

---

## Testing

The package ships with Pest + Orchestra Testbench. Run the full suite with:

```bash
composer test
```

This command covers the scheduler manager, notification flows, and the optional custom connection option.

---

## Roadmap

- ✅ Job tracking, overlap prevention, stuck detection
- ✅ Channel-based notifications (mail, Slack, Telegram)
- ⏳ Smart retry logic
- ⏳ Reporting & analytics (`smart-schedule:report`)
- 🕓 Dashboard (Livewire/Filament)

---

## Author

**Jiordi Viera** – Fullstack Developer (Laravel / Next.js)  
[Website](https://jiordiviera.me) · [Blog](https://blog.jiordiviera.me) · [GitHub](https://github.com/jiordiviera)

---

## License

Released under the [MIT license](LICENSE).
