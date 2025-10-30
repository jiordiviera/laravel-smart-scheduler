<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Exceptions;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Throwable;

class SchedulerRunExecutionException extends SchedulerRunException
{
    public function __construct(string $message, ?SmartSchedulerRun $run = null, int $exitCode = 1, ?Throwable $previous = null)
    {
        parent::__construct($message, $run, $exitCode, $previous);
    }
}
