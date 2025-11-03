<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Listeners;

use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Contracts\SmartNotifierInterface;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SmartSchedulerManager;

/**
 * Event listener intercepting scheduled task lifecycle events.
 *
 * This listener:
 * - Tracks task execution start and completion
 * - Prevents overlapping task executions
 * - Triggers notifications on task failures
 */
class ScheduleRunListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected SmartSchedulerManager $manager,
        protected SmartNotifierInterface $notifier
    ) {}

    /**
     * Handle scheduled task starting event.
     */
    public function handleTaskStarting(ScheduledTaskStarting $event): void
    {
        $run = $this->manager->handleTaskStarting($event);

        // If task was ignored due to overlap, prevent execution
        if ($run && $run->isIgnored()) {
            // Mark task to skip execution (Laravel's internal mechanism)
            $event->task->skip(fn () => true);
        }
    }

    /**
     * Handle scheduled task finished event.
     */
    public function handleTaskFinished(ScheduledTaskFinished $event): void
    {
        $run = $this->manager->handleTaskFinished($event);

        // Send notification if task failed
        if ($run && $run->isFailed()) {
            $this->notifier->sendFailureNotification($run);
        }
    }

    /**
     * Register the listeners for the subscriber.
     *
     * @return array<string, string>
     */
    public function subscribe(): array
    {
        return [
            ScheduledTaskStarting::class => 'handleTaskStarting',
            ScheduledTaskFinished::class => 'handleTaskFinished',
        ];
    }
}
