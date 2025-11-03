<?php

namespace Jiordiviera\SmartScheduler\LaravelSmartScheduler\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Jiordiviera\SmartScheduler\LaravelSmartScheduler\Models\ScheduleRun;

/**
 * Mailable for scheduled task failure notifications.
 */
class TaskFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public ScheduleRun $run
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Scheduled Task Failed: '.$this->run->task_identifier,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'smart-scheduler::emails.task-failed',
            with: [
                'run' => $this->run,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
