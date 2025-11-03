<?php

use Illuminate\Support\Facades\Mail;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Mail\TaskFailedMail;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\EmailNotifier;

beforeEach(function () {
    Mail::fake();
});

it('sends email notification for failed task', function () {
    config(['smart-scheduler.notifications.email.recipients' => ['admin@example.com']]);

    $run = ScheduleRun::create([
        'task_identifier' => 'failed-task',
        'status' => ScheduleRun::STATUS_FAILED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'exception' => 'Test error message',
        'server_name' => 'localhost',
    ]);

    $notifier = app(EmailNotifier::class);
    $notifier->sendFailureNotification($run);

    Mail::assertQueued(TaskFailedMail::class, function ($mail) use ($run) {
        return $mail->run->id === $run->id;
    });
});

it('does not send email when no recipients configured', function () {
    config(['smart-scheduler.notifications.email.recipients' => []]);

    $run = ScheduleRun::create([
        'task_identifier' => 'failed-task',
        'status' => ScheduleRun::STATUS_FAILED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'exception' => 'Test error',
        'server_name' => 'localhost',
    ]);

    $notifier = app(EmailNotifier::class);
    $notifier->sendFailureNotification($run);

    Mail::assertNothingQueued();
});

it('sends email to multiple recipients', function () {
    config(['smart-scheduler.notifications.email.recipients' => [
        'admin@example.com',
        'ops@example.com',
    ]]);

    $run = ScheduleRun::create([
        'task_identifier' => 'failed-task',
        'status' => ScheduleRun::STATUS_FAILED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'exception' => 'Test error',
        'server_name' => 'localhost',
    ]);

    $notifier = app(EmailNotifier::class);
    $notifier->sendFailureNotification($run);

    Mail::assertQueued(TaskFailedMail::class);
});

it('creates mailable with correct subject', function () {
    $run = ScheduleRun::create([
        'task_identifier' => 'backup:run',
        'status' => ScheduleRun::STATUS_FAILED,
        'started_at' => now()->subMinute(),
        'finished_at' => now(),
        'exception' => 'Backup failed',
        'server_name' => 'localhost',
    ]);

    $mailable = new TaskFailedMail($run);
    $envelope = $mailable->envelope();

    expect($envelope->subject)->toBe('Scheduled Task Failed: backup:run');
});
