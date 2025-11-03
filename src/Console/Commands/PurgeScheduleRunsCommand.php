<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

/**
 * Artisan command to purge old schedule run records.
 *
 * Removes successful and ignored records older than the configured retention period.
 */
class PurgeScheduleRunsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smart-scheduler:purge
                            {--days= : Number of days to retain (overrides config)}
                            {--dry-run : Display what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge old successful and ignored schedule run records';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days') ?? config('smart-scheduler.purge_days', 7);
        $dryRun = $this->option('dry-run');

        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Purging schedule runs older than {$days} days (before {$cutoffDate->toDateTimeString()})...");

        $query = ScheduleRun::query()
            ->whereIn('status', [ScheduleRun::STATUS_SUCCESS, ScheduleRun::STATUS_IGNORED])
            ->where(function ($q) use ($cutoffDate) {
                $q->where('finished_at', '<', $cutoffDate)
                    ->orWhere(function ($q2) use ($cutoffDate) {
                        $q2->whereNull('finished_at')
                            ->where('created_at', '<', $cutoffDate);
                    });
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No records to purge.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn("DRY RUN: Would delete {$count} record(s).");

            $this->table(
                ['Task Identifier', 'Status', 'Started At', 'Finished At'],
                $query->limit(10)->get()->map(fn ($run) => [
                    $run->task_identifier,
                    $run->status,
                    $run->started_at->toDateTimeString(),
                    $run->finished_at?->toDateTimeString() ?? 'N/A',
                ])
            );

            if ($count > 10) {
                $this->info('... and '.($count - 10).' more record(s).');
            }

            return self::SUCCESS;
        }

        $deleted = $query->delete();

        $this->info("Successfully purged {$deleted} record(s).");

        return self::SUCCESS;
    }
}
