<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\SmartSchedulerRun;
use Throwable;

class SchedulerRunFailedNotification extends Notification
{
    use Queueable;

    /**
     * @param  array<string>  $channels
     */
    public function __construct(
        protected SmartSchedulerRun $run,
        protected ?Throwable $exception,
        protected array $channels
    ) {
    }

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $startedAt = $this->run->started_at ? Carbon::parse($this->run->started_at)->toDateTimeString() : 'N/A';
        $duration = $this->run->duration_ms ? $this->run->duration_ms.'ms' : 'unknown';

        $message = (new MailMessage)
            ->subject('Smart Scheduler: schedule:run failure detected')
            ->greeting('Hello operations team,')
            ->line('The Laravel scheduler run managed by Smart Scheduler has failed.')
            ->line("Command: {$this->run->command}")
            ->line("Started at: {$startedAt}")
            ->line("Duration: {$duration}")
            ->line('Error: '.($this->run->error_message ?: 'No message recorded.'))
            ->line('Run ID: '.$this->run->id);

        if ($this->exception) {
            $message->line('Exception class: '.get_class($this->exception));
        }

        return $message->salutation('— Smart Scheduler');
    }

}
