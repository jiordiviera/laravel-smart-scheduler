<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels;

use Illuminate\Support\Facades\Notification;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\SchedulerRunFailedNotification;
use Throwable;

class MailNotificationChannel implements NotificationChannel
{
    public function key(): string
    {
        return 'mail';
    }

    public function send(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        $address = config('smart-scheduler.notifications.mail.to');

        if (empty($address)) {
            return;
        }

        Notification::route('mail', $address)->notify(
            new SchedulerRunFailedNotification($run, $exception, ['mail'])
        );
    }
}
