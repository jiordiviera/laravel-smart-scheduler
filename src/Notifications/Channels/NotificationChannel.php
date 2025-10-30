<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications\Channels;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Throwable;

interface NotificationChannel
{
    public function key(): string;

    public function send(SmartSchedulerRun $run, ?Throwable $exception = null): void;
}
