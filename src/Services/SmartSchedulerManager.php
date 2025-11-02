<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Exceptions\SchedulerRunExecutionException;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Exceptions\SchedulerRunSkippedException;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Exceptions\SchedulerRunStuckException;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support\SmartSchedulerNotifier;
use Throwable;

class SmartSchedulerManager
{
    public function __construct(
        protected SmartSchedulerNotifier $notifier
    ) {
    }

    public function execute(bool $force = false): SchedulerRunOutcome
    {
        $wrappedCommand = $this->wrappedCommand();

        if (!config('smart-scheduler.enabled', true)) {
            $message = 'Smart Scheduler disabled. Executing '.$wrappedCommand.' directly.';
            $exitCode = Artisan::call($wrappedCommand);

            return SchedulerRunOutcome::native($exitCode, $message);
        }

        if (!$this->ensureRunTableExists()) {
            $message = 'Smart Scheduler migrations are missing. Run `php artisan vendor:publish --tag=migrations` '
                .'and `php artisan migrate` before invoking smart-schedule:run.';

            return SchedulerRunOutcome::native(Command::FAILURE, $message);
        }

        $activeRun = $this->getActiveRun($wrappedCommand);

        if ($activeRun && !$force) {
            if ($this->isRunStuck($activeRun)) {
                return $this->handleStuckRun($activeRun);
            }

            return $this->handleSkippedRun($wrappedCommand, $activeRun);
        }

        $run = $this->createRunRecord($wrappedCommand);

        try {
            $exitCode = Artisan::call($wrappedCommand);

            if ($exitCode !== Command::SUCCESS) {
                throw new SchedulerRunExecutionException("{$wrappedCommand} exited with status code {$exitCode}", $run, $exitCode);
            }

            $completedRun = $this->markRunAsFinished($run, SmartSchedulerRun::STATUS_SUCCESS);

            return SchedulerRunOutcome::success($completedRun);
        } catch (Throwable $exception) {
            $failedRun = $this->markRunAsFinished($run, SmartSchedulerRun::STATUS_FAILED, $exception->getMessage());
            $this->notifier->notifyFailure($failedRun, $exception);

            return SchedulerRunOutcome::failure($failedRun, $exception->getMessage(), $exception);
        } finally {
            if (isset($run) && $run->exists && $run->status === SmartSchedulerRun::STATUS_RUNNING) {
                $this->markRunAsFinished($run, SmartSchedulerRun::STATUS_SUCCESS);
            }
        }
    }

    protected function ensureRunTableExists(): bool
    {
        $model = new SmartSchedulerRun();
        $connection = $model->getConnectionName() ?: config('database.default');

        return Schema::connection($connection)->hasTable($model->getTable());
    }

    protected function handleStuckRun(SmartSchedulerRun $activeRun): SchedulerRunOutcome
    {
        $thresholdMinutes = (int) config('smart-scheduler.stuck_after_minutes', 15);
        $message = "Previous scheduler run was considered stuck after {$thresholdMinutes} minute(s).";

        $run = $this->markRunAsFinished(
            $activeRun,
            SmartSchedulerRun::STATUS_FAILED,
            $message
        );

        $exception = SchedulerRunStuckException::fromRun($run, $thresholdMinutes);

        Log::warning('Smart Scheduler detected a stuck run.', [
            'run_id' => $run->id,
            'threshold_minutes' => $thresholdMinutes,
        ]);

        $this->notifier->notifyFailure($run, $exception);

        return SchedulerRunOutcome::stuck($run, $message);
    }

    protected function handleSkippedRun(string $wrappedCommand, SmartSchedulerRun $activeRun): SchedulerRunOutcome
    {
        $exception = SchedulerRunSkippedException::becauseOverlap($activeRun);
        $message = $exception->getMessage();
        $skippedRun = SmartSchedulerRun::create([
            'command' => $wrappedCommand,
            'status' => SmartSchedulerRun::STATUS_SKIPPED,
            'started_at' => Carbon::now(),
            'ended_at' => Carbon::now(),
            'duration_ms' => 0,
            'error_message' => 'Skipped because a previous run is still marked as running (ID: '.$activeRun->id.').',
        ]);

        Log::info('Smart Scheduler skipped a run because a previous execution is still running.', [
            'active_run_id' => $activeRun->id,
            'skipped_run_id' => $skippedRun->id,
        ]);

        return SchedulerRunOutcome::skipped($skippedRun, $message);
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

        if ($thresholdMinutes <= 0 || !$run->started_at) {
            return false;
        }

        return $run->started_at->lte(Carbon::now()->subMinutes($thresholdMinutes));
    }

    protected function createRunRecord(string $wrappedCommand): SmartSchedulerRun
    {
        return SmartSchedulerRun::create([
            'command' => $wrappedCommand,
            'status' => SmartSchedulerRun::STATUS_RUNNING,
            'started_at' => Carbon::now(),
        ]);
    }

    protected function markRunAsFinished(SmartSchedulerRun $run, string $status, ?string $errorMessage = null): SmartSchedulerRun
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

        return $run->fresh();
    }
}
