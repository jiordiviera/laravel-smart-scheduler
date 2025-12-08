<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Contracts\SmartNotifierInterface;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

/**
 * Artisan command to detect and mark stuck scheduled tasks.
 *
 * A task is considered stuck if it has been in "starting" status
 * for longer than the configured timeout period.
 */
class DetectStuckTasksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smart-scheduler:detect-stuck
                            {--timeout= : Minutes to consider a task stuck (overrides config)}
                            {--dry-run : Display stuck tasks without marking them}
                            {--no-notify : Skip sending notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Detect and mark tasks that are stuck in starting status';

    /**
     * Execute the console command.
     */
    public function handle(SmartNotifierInterface $notifier): int
    {
        $timeoutMinutes = $this->option('timeout') ?? config('smart-scheduler.stuck_timeout_minutes', 60);
        $dryRun = $this->option('dry-run');
        $noNotify = $this->option('no-notify');

        $cutoffTime = Carbon::now()->subMinutes($timeoutMinutes);

        $this->info("Detecting tasks stuck for more than {$timeoutMinutes} minutes (started before {$cutoffTime->toDateTimeString()})...");

        $stuckTasks = ScheduleRun::query()
            ->where('status', ScheduleRun::STATUS_STARTING)
            ->where('started_at', '<', $cutoffTime)
            ->get();

        if ($stuckTasks->isEmpty()) {
            $this->info('No stuck tasks detected.');

            return self::SUCCESS;
        }

        $this->warn("Found {$stuckTasks->count()} stuck task(s):");

        $this->table(
            ['ID', 'Task Identifier', 'Started At', 'Server', 'Duration'],
            $stuckTasks->map(fn (ScheduleRun $run) => [
                $run->id,
                $run->task_identifier,
                $run->started_at->toDateTimeString(),
                $run->server_name,
                $run->started_at->diffForHumans(now(), true),
            ])
        );

        if ($dryRun) {
            $this->warn('DRY RUN: No changes made.');

            return self::SUCCESS;
        }

        $shouldNotify = ! $noNotify && config('smart-scheduler.notifications.notify_on_stuck', true);

        foreach ($stuckTasks as $run) {
            $run->update([
                'status' => ScheduleRun::STATUS_STUCK,
                'finished_at' => Carbon::now(),
                'exception' => "Task marked as stuck after {$timeoutMinutes} minutes without completion",
            ]);

            if ($shouldNotify) {
                $notifier->sendStuckNotification($run);
            }
        }

        $this->info("Marked {$stuckTasks->count()} task(s) as stuck.");

        if ($shouldNotify) {
            $this->info('Notifications sent.');
        }

        return self::SUCCESS;
    }
}
