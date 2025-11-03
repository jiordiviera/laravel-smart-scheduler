<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Contracts\SmartNotifierInterface;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Mail\TaskFailedMail;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

/**
 * Email notification handler for failed task executions.
 *
 * Sends asynchronous email notifications to configured recipients.
 */
class EmailNotifier implements ShouldQueue, SmartNotifierInterface
{
    /**
     * Send failure notification via email.
     */
    public function sendFailureNotification(ScheduleRun $run): void
    {
        $recipients = config('smart-scheduler.notifications.email.recipients', []);

        if (empty($recipients)) {
            return;
        }

        Mail::to($recipients)->queue(new TaskFailedMail($run));
    }
}
