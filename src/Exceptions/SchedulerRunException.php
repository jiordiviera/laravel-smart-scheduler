<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Exceptions;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use RuntimeException;

class SchedulerRunException extends RuntimeException
{
    public function __construct(
        string $message,
        protected ?SmartSchedulerRun $run = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function run(): ?SmartSchedulerRun
    {
        return $this->run;
    }
}
