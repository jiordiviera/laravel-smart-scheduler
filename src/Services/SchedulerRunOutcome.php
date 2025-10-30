<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Services;

use Illuminate\Console\Command;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Throwable;

class SchedulerRunOutcome
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILURE = 'failure';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_STUCK = 'stuck';
    public const STATUS_NATIVE = 'native';

    private function __construct(
        protected string $status,
        protected int $exitCode,
        protected ?SmartSchedulerRun $run = null,
        protected ?string $message = null,
        protected ?Throwable $exception = null
    ) {
    }

    public static function success(SmartSchedulerRun $run): self
    {
        return new self(self::STATUS_SUCCESS, Command::SUCCESS, $run);
    }

    public static function failure(SmartSchedulerRun $run, ?string $message = null, ?Throwable $exception = null): self
    {
        return new self(self::STATUS_FAILURE, Command::FAILURE, $run, $message, $exception);
    }

    public static function skipped(SmartSchedulerRun $run, string $message): self
    {
        return new self(self::STATUS_SKIPPED, Command::SUCCESS, $run, $message);
    }

    public static function stuck(SmartSchedulerRun $run, string $message): self
    {
        return new self(self::STATUS_STUCK, Command::FAILURE, $run, $message);
    }

    public static function native(int $exitCode, string $message = null): self
    {
        return new self(self::STATUS_NATIVE, $exitCode, null, $message);
    }

    public function status(): string
    {
        return $this->status;
    }

    public function exitCode(): int
    {
        return $this->exitCode;
    }

    public function run(): ?SmartSchedulerRun
    {
        return $this->run;
    }

    public function message(): ?string
    {
        return $this->message;
    }

    public function exception(): ?Throwable
    {
        return $this->exception;
    }

    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isFailure(): bool
    {
        return $this->status === self::STATUS_FAILURE || $this->status === self::STATUS_STUCK;
    }

    public function isSkipped(): bool
    {
        return $this->status === self::STATUS_SKIPPED;
    }
}
