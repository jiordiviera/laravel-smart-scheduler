<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support;

use Illuminate\Support\Facades\Log;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels\NotificationChannel;
use Throwable;

class SmartSchedulerNotifier
{
    /**
     * @var array<string, NotificationChannel>
     */
    protected array $channels = [];

    /**
     * @param  iterable<NotificationChannel>  $channels
     */
    public function __construct(iterable $channels)
    {
        foreach ($channels as $channel) {
            $this->channels[$channel->key()] = $channel;
        }
    }

    public function notifyFailure(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        if (!config('smart-scheduler.notifications.enabled')) {
            return;
        }

        $configuredChannels = (array) config('smart-scheduler.notifications.channels', []);

        foreach ($configuredChannels as $channelKey) {
            $channelKey = strtolower(trim((string) $channelKey));

            if (!isset($this->channels[$channelKey])) {
                Log::notice('SmartScheduler notification channel not registered.', [
                    'channel' => $channelKey,
                ]);

                continue;
            }

            $channel = $this->channels[$channelKey];

            try {
                $channel->send($run, $exception);
            } catch (Throwable $sendException) {
                Log::warning('SmartScheduler notifier failed to deliver notification.', [
                    'channel' => $channelKey,
                    'error' => $sendException->getMessage(),
                ]);
            }
        }
    }
}
