<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (!method_exists($notification, 'toTelegram')) {
            return;
        }

        $payload = $notification->toTelegram($notifiable);
        $route = $notifiable->routeNotificationFor('telegram', $notification);

        $botToken = is_array($route) ? ($route['bot_token'] ?? null) : null;
        $chatId = is_array($route) ? ($route['chat_id'] ?? null) : null;

        if (!$botToken || !$chatId) {
            return;
        }

        $endpoint = sprintf('https://api.telegram.org/bot%s/sendMessage', $botToken);

        try {
            Http::post($endpoint, [
                'chat_id' => $chatId,
                'text' => $payload['text'] ?? '',
                'parse_mode' => $payload['parse_mode'] ?? 'Markdown',
            ]);
        } catch (Throwable $exception) {
            Log::warning('SmartScheduler failed to send Telegram notification.', [
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
