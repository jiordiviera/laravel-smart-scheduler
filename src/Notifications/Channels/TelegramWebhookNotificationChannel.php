<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support\NotificationMessageBuilder;
use Throwable;

class TelegramWebhookNotificationChannel implements NotificationChannel
{
    public function __construct(
        protected NotificationMessageBuilder $messageBuilder
    ) {
    }

    public function key(): string
    {
        return 'telegram';
    }

    public function send(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        $config = config('smart-scheduler.notifications.telegram', []);

        $botToken = Arr::get($config, 'bot_token');
        $chatId = Arr::get($config, 'chat_id');

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);

        Http::post($endpoint, [
            'chat_id' => $chatId,
            'text' => $this->messageBuilder->buildPlainText($run, $exception),
            'parse_mode' => 'Markdown',
        ]);
    }
}
