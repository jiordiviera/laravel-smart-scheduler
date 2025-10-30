<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support\SmartSchedulerNotifier;
use RuntimeException;
use Throwable;

class SmartScheduleRunCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'smart-schedule:run {--force : Run even if another execution is currently marked as running}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the Laravel scheduler with SmartScheduler tracking and notifications.';

    public function handle(): int
    {
        $wrappedCommand = $this->wrappedCommand();

        if (!config('smart-scheduler.enabled', true)) {
            $this->info('Smart Scheduler disabled. Falling back to native schedule:run.');

            return Artisan::call($wrappedCommand);
        }

        $activeRun = $this->getActiveRun($wrappedCommand);

        if ($activeRun && !$this->option('force')) {
            if ($this->isRunStuck($activeRun)) {
                $this->markRunAsFailed($activeRun, 'Run exceeded stuck_after_minutes threshold and was marked as failed.');
                $activeRun->refresh();
                $this->notifyFailure($activeRun, new RuntimeException('Scheduler run considered stuck.'));
            } else {
                $this->createSkippedRun($activeRun);
                $this->warn('Previous scheduler run is still in progress. Skipping.');

                return self::SUCCESS;
            }
        }

        $run = SmartSchedulerRun::create([
            'command' => $wrappedCommand,
            'status' => SmartSchedulerRun::STATUS_RUNNING,
            'started_at' => Carbon::now(),
        ]);

        try {
            $exitCode = Artisan::call($wrappedCommand);

            if ($exitCode !== Command::SUCCESS) {
                throw new RuntimeException("schedule:run exited with status code {$exitCode}");
            }

            $this->markRunAsFinished($run, SmartSchedulerRun::STATUS_SUCCESS);
            $this->info('Smart scheduler run completed successfully.');

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $this->markRunAsFinished($run, SmartSchedulerRun::STATUS_FAILED, $exception->getMessage());
            $run->refresh();
            $this->notifyFailure($run, $exception);
            $this->error('Smart scheduler detected a failure: '.$exception->getMessage());

            return Command::FAILURE;
        } finally {
            if (isset($run) && $run->exists && $run->status === SmartSchedulerRun::STATUS_RUNNING) {
                $this->markRunAsFinished($run, SmartSchedulerRun::STATUS_SUCCESS);
            }
        }
    }

    protected function wrappedCommand(): string
    {
        return (string) config('smart-scheduler.wrapped_command', 'schedule:run');
    }

    protected function getActiveRun(string $wrappedCommand): ?SmartSchedulerRun
    {
        if (!config('smart-scheduler.prevent_overlaps', true)) {
            return null;
        }

        return SmartSchedulerRun::query()
            ->where('command', $wrappedCommand)
            ->where('status', SmartSchedulerRun::STATUS_RUNNING)
            ->latest('started_at')
            ->first();
    }

    protected function isRunStuck(SmartSchedulerRun $run): bool
    {
        $thresholdMinutes = (int) config('smart-scheduler.stuck_after_minutes', 15);

        if (!$thresholdMinutes || !$run->started_at) {
            return false;
        }

        return $run->started_at->lte(Carbon::now()->subMinutes($thresholdMinutes));
    }

    protected function markRunAsFinished(SmartSchedulerRun $run, string $status, ?string $errorMessage = null): void
    {
        $endedAt = Carbon::now();
        $startedAt = $run->started_at ? Carbon::parse($run->started_at) : null;

        $duration = $startedAt ? $startedAt->diffInMilliseconds($endedAt) : null;

        $run->forceFill([
            'status' => $status,
            'ended_at' => $endedAt,
            'duration_ms' => $duration,
            'error_message' => $errorMessage,
        ])->save();
    }

    protected function markRunAsFailed(SmartSchedulerRun $run, string $message): void
    {
        $run->forceFill([
            'status' => SmartSchedulerRun::STATUS_FAILED,
            'ended_at' => Carbon::now(),
            'error_message' => $message,
        ])->save();
    }

    protected function createSkippedRun(SmartSchedulerRun $activeRun): void
    {
        SmartSchedulerRun::create([
            'command' => $this->wrappedCommand(),
            'status' => SmartSchedulerRun::STATUS_SKIPPED,
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
            'duration_ms' => 0,
            'error_message' => 'Skipped because a previous run is still marked as running (ID: '.$activeRun->id.').',
        ]);
    }

    protected function notifyFailure(SmartSchedulerRun $run, ?Throwable $exception = null): void
    {
        app(SmartSchedulerNotifier::class)->notifyFailure($run, $exception);
    }
}
