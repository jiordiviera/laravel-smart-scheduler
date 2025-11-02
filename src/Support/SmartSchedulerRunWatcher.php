<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Throwable;

class SmartSchedulerRunWatcher
{
    protected ?SmartSchedulerRun $currentRun = null;

    protected ?array $failure = null;

    public function setCurrentRun(SmartSchedulerRun $run): void
    {
        $this->currentRun = $run;
        $this->failure = null;
    }

    public function clear(): void
    {
        $this->currentRun = null;
        $this->failure = null;
    }

    public function recordFailure(Throwable $exception): void
    {
        $this->failure = [
            'message' => $exception->getMessage(),
            'exception' => $exception,
        ];
    }

    public function consumeFailure(): ?array
    {
        $failure = $this->failure;
        $this->failure = null;

        return $failure;
    }

    public function currentRun(): ?SmartSchedulerRun
    {
        return $this->currentRun;
    }
}
