<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels;

use Illuminate\Support\Facades\Http;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support\NotificationMessageBuilder;
use Throwable;

class SlackWebhookNotificationChannel implements NotificationChannel
{
    public function __construct(
        protected NotificationMessageBuilder $messageBuilder
    ) {
    }

    public function key(): string
    {
        return 'slack';
    }

    public function send(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        $webhookUrl = config('smart-scheduler.notifications.slack.webhook_url');

        if (empty($webhookUrl)) {
            return;
        }

        Http::post($webhookUrl, [
            'text' => $this->messageBuilder->buildPlainText($run, $exception),
        ]);
    }
}
