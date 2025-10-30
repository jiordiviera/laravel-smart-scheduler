<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Support;

use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Throwable;

class NotificationMessageBuilder
{
    /**
     * @return array<int, string>
     */
    public function buildLines(SmartSchedulerRun $run, ?Throwable $exception = null): array
    {
        $lines = [
            '*Smart Scheduler Alert*',
            '`'.$run->command.'` failed.',
            'Application: '.config('app.name', 'Laravel'),
            'Environment: '.config('app.env', 'production'),
            'Host: '.($this->detectHostname() ?: 'unknown'),
            'Started at: '.optional($run->started_at)->toDateTimeString() ?: 'N/A',
            'Duration: '.($run->duration_ms ? $run->duration_ms.'ms' : 'unknown'),
            'Run ID: `'.$run->id.'`',
            'Error: '.($run->error_message ?: 'No error message was recorded.'),
        ];

        if ($exception) {
            $lines[] = 'Exception: `'.get_class($exception).'` — '.$exception->getMessage();
        }

        return $lines;
    }

    public function buildPlainText(SmartSchedulerRun $run, ?Throwable $exception = null): string
    {
        return implode("\n", $this->buildLines($run, $exception));
    }

    protected function detectHostname(): ?string
    {
        return gethostname() ?: php_uname('n');
    }
}
