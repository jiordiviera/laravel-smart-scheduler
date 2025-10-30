<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Exceptions;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;

class SchedulerRunStuckException extends SchedulerRunException
{
    public static function fromRun(SmartSchedulerRun $run, int $thresholdMinutes): self
    {
        return new self(
            "Scheduler run has been marked as stuck after {$thresholdMinutes} minute(s).",
            $run
        );
    }
}
