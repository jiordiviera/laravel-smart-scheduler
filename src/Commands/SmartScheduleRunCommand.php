<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Commands;

use Illuminate\Console\Command;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SchedulerRunOutcome;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services\SmartSchedulerManager;

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

    public function __construct(
        protected SmartSchedulerManager $manager
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $outcome = $this->manager->execute((bool) $this->option('force'));

        $this->reportOutcome($outcome);

        return $outcome->exitCode();
    }

    protected function reportOutcome(SchedulerRunOutcome $outcome): void
    {
        $message = $outcome->message();

        switch ($outcome->status()) {
            case SchedulerRunOutcome::STATUS_NATIVE:
                if ($message) {
                    if ($outcome->exitCode() === Command::SUCCESS) {
                        $this->line($message);
                    } else {
                        $this->error($message);
                    }
                }
                break;

            case SchedulerRunOutcome::STATUS_SUCCESS:
                $this->info($message ?: 'Smart scheduler run completed successfully.');
                break;

            case SchedulerRunOutcome::STATUS_SKIPPED:
                $this->warn($message ?: 'Smart scheduler skipped this run because a previous execution is in progress.');
                break;

            case SchedulerRunOutcome::STATUS_STUCK:
                $this->error($message ?: 'Smart scheduler detected a stuck run and marked it as failed.');
                break;

            case SchedulerRunOutcome::STATUS_FAILURE:
            default:
                $details = $message ?: 'Smart scheduler detected a failure.';
                $this->error($details);
                break;
        }
    }
}
