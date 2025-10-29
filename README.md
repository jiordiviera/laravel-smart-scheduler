# Laravel Smart Scheduler

> Smarter, reliable, and observable task scheduling for Laravel.

---

## 📘 Overview

**Laravel Smart Scheduler** enhances Laravel’s built-in scheduler by adding real-time visibility, failure detection, and smart execution handling.
No more silent cron failures — every scheduled task is tracked, logged, and optionally reported via notifications.

This package was created as part of the **#100DaysOfCode Challenge (Day 37)** by [Jiordi Viera](https://jiordiviera.me).

---

## ✨ Features

| Feature                  | Description                                                      |
| ------------------------ | ---------------------------------------------------------------- |
| **Job Tracking**         | Logs every scheduled task execution (success, fail, skipped).    |
| **Failure Detection**    | Detects and flags failed or stuck jobs.                          |
| **Duplicate Prevention** | Avoids overlapping runs when a previous task is still executing. |
| **Notifications**        | Sends alerts via Mail, Slack, or Telegram on anomalies.          |
| **Smart Retry Logic**    | Optional retry system for failed jobs.                           |
| **Reports**              | Generates summary reports (daily, weekly, etc.).                 |
| **Dashboard (optional)** | Livewire or Filament-based dashboard for monitoring tasks.       |

---

## ⚙️ Installation

### Step 1: Add repository (for local dev)

```json
{
  "repositories": [
    { "type": "path", "url": "packages/laravel-smart-scheduler" }
  ]
}
```

### Step 2: Install the package

```bash
composer require jiordiviera/laravel-smart-scheduler:* --dev
```

### Step 3: Publish config and migrations

```bash
php artisan vendor:publish --provider="JiordiViera\\SmartScheduler\\SmartSchedulerServiceProvider" --tag=config
php artisan vendor:publish --provider="JiordiViera\\SmartScheduler\\SmartSchedulerServiceProvider" --tag=migrations
php artisan migrate
```

---

## 🧠 Usage

Replace your usual cron entry:

```bash
* * * * * php /path/to/artisan smart-schedule:run >> /dev/null 2>&1
```

Then run manually to verify:

```bash
php artisan smart-schedule:run
```

Each run is logged in the `smart_scheduler_runs` table with:

* Command name
* Status (`running`, `success`, `failed`, `skipped`)
* Execution timestamps
* Duration
* Error message (if any)

---

## 🧩 Configuration

`config/smart-scheduler.php`:

```php
return [
    'prevent_overlaps' => true,
    'stuck_after_minutes' => 15,

    'notifications' => [
        'enabled' => true,
        'channels' => ['mail'], // mail, slack, telegram
        'mail' => [
            'to' => env('SMART_SCHEDULER_MAIL_TO', 'admin@example.com'),
        ],
        'slack' => [
            'webhook_url' => env('SMART_SCHEDULER_SLACK_WEBHOOK'),
        ],
        'telegram' => [
            'bot_token' => env('SMART_SCHEDULER_TELEGRAM_TOKEN'),
            'chat_id'   => env('SMART_SCHEDULER_TELEGRAM_CHAT_ID'),
        ],
    ],
];
```

---

## 🧾 Example Output

```bash
$ php artisan smart-schedule:run
[✔] Schedule executed successfully.
[✓] Logged run #42 (duration: 125ms)
```

Database entry example:

| id | command      | status  | started_at          | ended_at            | duration_ms | error_message |
| -- | ------------ | ------- | ------------------- | ------------------- | ----------- | ------------- |
| 42 | schedule:run | success | 2025-10-29 09:10:00 | 2025-10-29 09:10:01 | 125         | NULL          |

---

## 🧪 Testing (Pest + Testbench)

Run tests with [PestPHP](https://pestphp.com):

```bash
composer test
```

Example test:

```php
it('tracks a run when smart-schedule:run executes', function () {
    Artisan::call('smart-schedule:run', ['--once' => true]);
    expect(\JiordiViera\SmartScheduler\Models\SmartSchedulerRun::count())->toBe(1);
});
```

---

## 🧭 Roadmap

* [x] Package scaffold & base command
* [x] Job tracking + overlap prevention
* [ ] Failure notifications (Mail, Slack, Telegram)
* [ ] Smart retry logic
* [ ] Filament dashboard
* [ ] Packagist release + docs

---

## 🧱 Requirements

* **PHP:** 8.2+
* **Laravel:** 10.x or 11.x
* **Database:** MySQL, PostgreSQL, SQLite, etc.

---

## 🧑‍💻 Author

**Jiordi Viera**
Fullstack Developer – Laravel / Next.js

* Website: [jiordiviera.me](https://jiordiviera.me)
* Blog: [blog.jiordiviera.me](https://blog.jiordiviera.me)
* GitHub: [@jiordiviera](https://github.com/jiordiviera)

---

## 📄 License

This project is open-sourced under the [MIT license](LICENSE).

---

## 🧭 Vision

> “Smart automation starts with visibility.”
> — Jiordi Viera
