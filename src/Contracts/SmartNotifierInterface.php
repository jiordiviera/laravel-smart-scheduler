<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Contracts;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

/**
 * Contract for notification channels.
 *
 * Implementations should handle sending notifications about failed and stuck task executions.
 */
interface SmartNotifierInterface
{
    /**
     * Send notification about a failed task execution.
     */
    public function sendFailureNotification(ScheduleRun $run): void;

    /**
     * Send notification about a stuck task execution.
     */
    public function sendStuckNotification(ScheduleRun $run): void;
}
