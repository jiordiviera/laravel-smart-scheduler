<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Exceptions;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;

class SchedulerRunSkippedException extends SchedulerRunException
{
    public static function becauseOverlap(SmartSchedulerRun $referenceRun): self
    {
        return new self(
            'Scheduler run skipped because another run is still marked as running.',
            $referenceRun
        );
    }
}
