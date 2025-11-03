<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Contracts;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

/**
 * Contract for notification channels.
 *
 * Implementations should handle sending notifications about failed task executions.
 */
interface SmartNotifierInterface
{
    /**
     * Send notification about a failed task execution.
     */
    public function sendFailureNotification(ScheduleRun $run): void;
}
