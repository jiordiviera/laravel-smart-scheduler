<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services;

use Carbon\Carbon;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

/**
 * Service managing scheduled task execution tracking and overlap prevention.
 *
 * This service handles the core business logic for:
 * - Checking for overlapping task executions
 * - Creating execution records
 * - Updating execution status and metrics
 */
class SmartSchedulerManager
{
    /**
     * Handle task starting event.
     *
     * Checks for overlapping executions and either blocks or records the new execution.
     *
     * @return ScheduleRun|null Returns null if task is ignored due to overlap
     */
    public function handleTaskStarting(ScheduledTaskStarting $event): ?ScheduleRun
    {
        $taskIdentifier = $this->getTaskIdentifier($event);

        // Check for existing running task
        $existingRun = ScheduleRun::query()
            ->where('task_identifier', $taskIdentifier)
            ->where('status', ScheduleRun::STATUS_STARTING)
            ->first();

        if ($existingRun) {
            // Record ignored execution
            return ScheduleRun::create([
                'task_identifier' => $taskIdentifier,
                'status' => ScheduleRun::STATUS_IGNORED,
                'started_at' => Carbon::now(),
                'finished_at' => Carbon::now(),
                'duration' => 0,
                'output' => null,
                'exception' => 'Task skipped due to overlapping execution',
                'server_name' => $this->getServerName(),
            ]);
        }

        // Create new starting execution
        return ScheduleRun::create([
            'task_identifier' => $taskIdentifier,
            'status' => ScheduleRun::STATUS_STARTING,
            'started_at' => Carbon::now(),
            'server_name' => $this->getServerName(),
        ]);
    }

    /**
     * Handle task finished event.
     *
     * Updates the execution record with completion status, duration, and output.
     */
    public function handleTaskFinished(ScheduledTaskFinished $event): ?ScheduleRun
    {
        $taskIdentifier = $this->getTaskIdentifier($event);

        // Find the starting record
        $run = ScheduleRun::query()
            ->where('task_identifier', $taskIdentifier)
            ->where('status', ScheduleRun::STATUS_STARTING)
            ->orderBy('started_at', 'desc')
            ->first();

        if (! $run) {
            return null;
        }

        $finishedAt = Carbon::now();
        $duration = $finishedAt->diffInSeconds($run->started_at, true);

        // Determine status based on task exit code
        $status = $event->task->exitCode === 0
            ? ScheduleRun::STATUS_SUCCESS
            : ScheduleRun::STATUS_FAILED;

        // Update the run
        $run->update([
            'status' => $status,
            'finished_at' => $finishedAt,
            'duration' => $duration,
            'output' => $this->captureOutput($event),
            'exception' => $status === ScheduleRun::STATUS_FAILED
                ? $this->captureException($event)
                : null,
        ]);

        return $run->fresh();
    }

    /**
     * Get task identifier from event.
     *
     * @param  ScheduledTaskStarting|ScheduledTaskFinished  $event
     */
    protected function getTaskIdentifier($event): string
    {
        // Use command description or generate hash from task definition
        $task = $event->task;

        if (method_exists($task, 'description') && $task->description) {
            return $task->description;
        }

        // Fallback to task command or expression
        return md5($task->command ?? $task->expression ?? 'unknown');
    }

    /**
     * Get server name.
     */
    protected function getServerName(): string
    {
        return gethostname() ?: 'unknown';
    }

    /**
     * Capture output from task execution.
     */
    protected function captureOutput(ScheduledTaskFinished $event): ?string
    {
        if (! isset($event->task->output) || empty($event->task->output)) {
            return null;
        }

        // Limit output to 5000 characters for database storage
        $output = $event->task->output;

        return mb_substr($output, 0, 5000);
    }

    /**
     * Capture exception information from failed task.
     */
    protected function captureException(ScheduledTaskFinished $event): ?string
    {
        // Try to get exception information if available
        if (isset($event->task->exception)) {
            return (string) $event->task->exception;
        }

        return 'Task exited with non-zero status code: '.$event->task->exitCode;
    }
}
