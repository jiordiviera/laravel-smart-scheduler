<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\SchedulerRunFailedNotification;
use Throwable;

class SmartSchedulerNotifier
{
    public function notifyFailure(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        if (!config('smart-scheduler.notifications.enabled')) {
            return;
        }

        $channels = (array) config('smart-scheduler.notifications.channels', []);

        foreach ($channels as $channel) {
            $channel = strtolower(trim($channel));

            try {
                match ($channel) {
                    'mail' => $this->notifyMail($run, $exception),
                    'slack' => $this->notifySlack($run, $exception),
                    'telegram' => $this->notifyTelegram($run, $exception),
                    default => null,
                };
            } catch (Throwable $sendException) {
                Log::warning('SmartScheduler notifier failed to deliver notification.', [
                    'channel' => $channel,
                    'error' => $sendException->getMessage(),
                ]);
            }
        }
    }

    protected function notifyMail(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        $address = config('smart-scheduler.notifications.mail.to');

        if (empty($address)) {
            return;
        }

        Notification::route('mail', $address)->notify(
            new SchedulerRunFailedNotification($run, $exception, ['mail'])
        );
    }

    protected function notifySlack(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        $webhookUrl = config('smart-scheduler.notifications.slack.webhook_url');

        if (empty($webhookUrl)) {
            return;
        }

        Http::post($webhookUrl, [
            'text' => implode("\n", $this->buildAlertLines($run, $exception)),
        ]);
    }

    protected function notifyTelegram(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        $config = config('smart-scheduler.notifications.telegram', []);

        $botToken = Arr::get($config, 'bot_token');
        $chatId = Arr::get($config, 'chat_id');

        if (empty($botToken) || empty($chatId)) {
            return;
        }

        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);

        $text = implode("\n", $this->buildAlertLines($run, $exception));

        Http::post($endpoint, [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    /**
     * @return array<int, string>
     */
    protected function buildAlertLines(SmartSchedulerRun $run, ?Throwable $exception = null): array
    {
        $lines = [
            '*Smart Scheduler Alert*',
            '`'.$run->command.'` failed.',
            'Started at: '.optional($run->started_at)->toDateTimeString() ?: 'N/A',
            'Duration: '.($run->duration_ms ? $run->duration_ms.'ms' : 'unknown'),
            'Run ID: `'.$run->id.'`',
            'Error: '.($run->error_message ?: 'No error message was recorded.'),
        ];

        if ($exception) {
            $lines[] = 'Exception: `'.get_class($exception).'` — '.$exception->getMessage();
        }

        return $lines;
    }
}
